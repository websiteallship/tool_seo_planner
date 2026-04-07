<?php
/**
 * Generation History — unified per-event log and analytics source.
 *
 * Single source of truth for all AI generation events.
 * Powers both the History page and the Analytics Dashboard.
 *
 * @package Xanh_AI_Content
 * @since   1.1.0
 * @since   1.2.0 Added input_tokens/output_tokens, dashboard query methods.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_History {

	/** Table name (without prefix). */
	private const TABLE = 'xanh_ai_history';

	/** Default items per page. */
	private const PER_PAGE = 20;

	/*--------------------------------------------------------------
	 * Database Setup
	 *------------------------------------------------------------*/

	/**
	 * Create or update the history table via dbDelta.
	 *
	 * Called during plugin activation and version upgrade.
	 */
	public static function create_table(): void {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			type          VARCHAR(10)     NOT NULL DEFAULT 'post',
			object_id     BIGINT UNSIGNED DEFAULT NULL,
			angle         VARCHAR(50)     DEFAULT NULL,
			topic         VARCHAR(255)    DEFAULT NULL,
			keywords      TEXT            DEFAULT NULL,
			model         VARCHAR(100)    DEFAULT NULL,
			tokens_used   INT UNSIGNED    DEFAULT 0,
			input_tokens  INT UNSIGNED    DEFAULT 0,
			output_tokens INT UNSIGNED    DEFAULT 0,
			score         SMALLINT UNSIGNED DEFAULT NULL,
			status        VARCHAR(20)     NOT NULL DEFAULT 'pending',
			error_message TEXT            DEFAULT NULL,
			user_id       BIGINT UNSIGNED DEFAULT NULL,
			created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY   (id),
			KEY idx_created   (created_at),
			KEY idx_type      (type),
			KEY idx_status    (status),
			KEY idx_object_id (object_id),
			KEY idx_model     (model),
			KEY idx_type_date (type, created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Check if the history table exists.
	 *
	 * @return bool
	 */
	public static function table_exists(): bool {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
	}

	/*--------------------------------------------------------------
	 * Write Operations
	 *------------------------------------------------------------*/

	/**
	 * Log a generation event.
	 *
	 * @param array $data {
	 *     @type string $type          'post', 'image', 'suggest', or 'img_prompt'.
	 *     @type int    $object_id     Post ID or Attachment ID.
	 *     @type string $angle         Angle ID.
	 *     @type string $topic         Topic or image prompt.
	 *     @type string $keywords      Keywords used.
	 *     @type string $model         Model identifier.
	 *     @type int    $tokens_used   Total tokens consumed.
	 *     @type int    $input_tokens  Input/prompt tokens.
	 *     @type int    $output_tokens Output/candidate tokens.
	 *     @type int    $score         Content score 0-100.
	 *     @type string $status        'pending', 'success', 'saved', 'error'.
	 *     @type string $error_message Error details.
	 *     @type int    $user_id       WordPress user ID.
	 * }
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public static function log( array $data ): int {
		if ( ! self::table_exists() ) {
			return 0;
		}

		global $wpdb;

		$defaults = [
			'type'          => 'post',
			'object_id'     => null,
			'angle'         => null,
			'topic'         => null,
			'keywords'      => null,
			'model'         => null,
			'tokens_used'   => 0,
			'input_tokens'  => 0,
			'output_tokens' => 0,
			'score'         => null,
			'status'        => 'pending',
			'error_message' => null,
			'user_id'       => get_current_user_id(),
			'created_at'    => current_time( 'mysql' ),
		];

		$row = array_merge( $defaults, $data );

		// Sanitize.
		$row['type']          = in_array( $row['type'], [ 'post', 'image', 'suggest', 'img_prompt' ], true ) ? $row['type'] : 'post';
		$row['status']        = in_array( $row['status'], [ 'pending', 'success', 'error', 'saved', 'post_deleted' ], true ) ? $row['status'] : 'pending';
		$row['topic']         = is_string( $row['topic'] ) ? mb_substr( $row['topic'], 0, 255 ) : null;
		$row['angle']         = is_string( $row['angle'] ) ? mb_substr( $row['angle'], 0, 50 ) : null;
		$row['model']         = is_string( $row['model'] ) ? mb_substr( $row['model'], 0, 100 ) : null;
		$row['tokens_used']   = absint( $row['tokens_used'] );
		$row['input_tokens']  = absint( $row['input_tokens'] );
		$row['output_tokens'] = absint( $row['output_tokens'] );

		// Auto-compute tokens_used if not explicitly provided.
		if ( 0 === $row['tokens_used'] && ( $row['input_tokens'] > 0 || $row['output_tokens'] > 0 ) ) {
			$row['tokens_used'] = $row['input_tokens'] + $row['output_tokens'];
		}
		$row['object_id']     = $row['object_id'] ? absint( $row['object_id'] ) : null;
		$row['score']         = $row['score'] !== null ? absint( $row['score'] ) : null;
		$row['user_id']       = $row['user_id'] ? absint( $row['user_id'] ) : null;

		$inserted = $wpdb->insert(
			$wpdb->prefix . self::TABLE,
			$row,
			[
				'%s', // type
				'%d', // object_id
				'%s', // angle
				'%s', // topic
				'%s', // keywords
				'%s', // model
				'%d', // tokens_used
				'%d', // input_tokens
				'%d', // output_tokens
				'%d', // score
				'%s', // status
				'%s', // error_message
				'%d', // user_id
				'%s', // created_at
			]
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Mark history records as 'post_deleted' when a WP post is trashed/deleted.
	 *
	 * @param int $post_id Post ID being deleted.
	 */
	public static function mark_post_deleted( int $post_id ): void {
		if ( ! self::table_exists() || $post_id <= 0 ) {
			return;
		}

		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . self::TABLE,
			[ 'status' => 'post_deleted' ],
			[
				'object_id' => $post_id,
				'type'      => 'post',
			],
			[ '%s' ],
			[ '%d', '%s' ]
		);
	}

	/**
	 * Link an existing history record to a saved WP post.
	 *
	 * @param int $history_id History record ID.
	 * @param int $post_id    WordPress post ID.
	 */
	public static function link_to_post( int $history_id, int $post_id ): void {
		if ( ! self::table_exists() || $history_id <= 0 || $post_id <= 0 ) {
			return;
		}

		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . self::TABLE,
			[
				'object_id' => $post_id,
				'status'    => 'saved',
			],
			[ 'id' => $history_id ],
			[ '%d', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Callback for 'xanh_ai_draft_saved' — link history to saved post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Generation data.
	 * @param array $params  Original parameters.
	 */
	public static function on_draft_saved( int $post_id, array $data, array $params ): void {
		$history_id = absint( $data['history_id'] ?? 0 );

		if ( $history_id > 0 ) {
			self::link_to_post( $history_id, $post_id );
			return;
		}

		// Fallback: find the most recent unlinked post record for this user.
		if ( ! self::table_exists() ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE type = 'post' AND object_id IS NULL AND user_id = %d ORDER BY created_at DESC LIMIT 1",
				get_current_user_id()
			),
			ARRAY_A
		);

		if ( $row ) {
			self::link_to_post( (int) $row['id'], $post_id );
		}
	}

	/*--------------------------------------------------------------
	 * Read Operations — History Page
	 *------------------------------------------------------------*/

	/**
	 * Get paginated history list with filters.
	 *
	 * @param array $filters {
	 *     @type string $type        'post', 'image', 'suggest', 'img_prompt', or '' for all.
	 *     @type string $status      'success', 'error', or '' for all.
	 *     @type string $angle       Angle ID filter.
	 *     @type string $search      Search topic/keywords.
	 *     @type string $date_from   'Y-m-d' start date.
	 *     @type string $date_to     'Y-m-d' end date.
	 * }
	 * @param int $page     Page number (1-indexed).
	 * @param int $per_page Items per page.
	 * @return array { items: array, total: int, pages: int, page: int }
	 */
	public static function get_list( array $filters = [], int $page = 1, int $per_page = 0 ): array {
		if ( ! self::table_exists() ) {
			return [ 'items' => [], 'total' => 0, 'pages' => 0, 'page' => 1 ];
		}

		global $wpdb;

		$table    = $wpdb->prefix . self::TABLE;
		$where    = [];
		$values   = [];
		$per_page = $per_page > 0 ? $per_page : self::PER_PAGE;
		$page     = max( 1, $page );

		// Type filter.
		if ( ! empty( $filters['type'] ) && in_array( $filters['type'], [ 'post', 'image', 'suggest', 'img_prompt' ], true ) ) {
			$where[]  = 'type = %s';
			$values[] = $filters['type'];
		}

		// Status filter.
		if ( ! empty( $filters['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_text_field( $filters['status'] );
		}

		// Angle filter.
		if ( ! empty( $filters['angle'] ) ) {
			$where[]  = 'angle = %s';
			$values[] = sanitize_text_field( $filters['angle'] );
		}

		// Search.
		if ( ! empty( $filters['search'] ) ) {
			$search   = '%' . $wpdb->esc_like( sanitize_text_field( $filters['search'] ) ) . '%';
			$where[]  = '(topic LIKE %s OR keywords LIKE %s)';
			$values[] = $search;
			$values[] = $search;
		}

		// Date range.
		if ( ! empty( $filters['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$values[] = sanitize_text_field( $filters['date_from'] ) . ' 00:00:00';
		}
		if ( ! empty( $filters['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$values[] = sanitize_text_field( $filters['date_to'] ) . ' 23:59:59';
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// Count total.
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		if ( ! empty( $values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$pages  = (int) ceil( $total / $per_page );
		$offset = ( $page - 1 ) * $per_page;

		// Fetch items.
		$query = "SELECT * FROM {$table} {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$query_values = array_merge( $values, [ $per_page, $offset ] );
		$items = $wpdb->get_results( $wpdb->prepare( $query, $query_values ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Enrich items with edit URLs.
		foreach ( $items as &$item ) {
			$item['edit_url'] = null;
			$item['view_url'] = null;

			if ( ! empty( $item['object_id'] ) ) {
				if ( 'post' === $item['type'] || 'img_prompt' === $item['type'] ) {
					$post = get_post( (int) $item['object_id'] );
					if ( $post ) {
						$item['edit_url']    = get_edit_post_link( $post->ID, 'raw' );
						$item['view_url']    = get_permalink( $post->ID );
						$item['post_title']  = $post->post_title;
					}
				} elseif ( 'image' === $item['type'] ) {
					$item['view_url'] = wp_get_attachment_url( (int) $item['object_id'] );
					$item['thumb_url'] = wp_get_attachment_image_url( (int) $item['object_id'], 'thumbnail' );
				}
			}

			// Angle label.
			if ( ! empty( $item['angle'] ) && class_exists( 'Xanh_AI_Angles' ) ) {
				$angle = Xanh_AI_Angles::get( $item['angle'] );
				$item['angle_label'] = $angle['label'] ?? $item['angle'];
				$item['angle_icon']  = $angle['icon'] ?? '';
			}
		}
		unset( $item );

		return [
			'items' => $items ?: [],
			'total' => $total,
			'pages' => $pages,
			'page'  => $page,
		];
	}

	/**
	 * Get a single history record by ID.
	 *
	 * @param int $id History record ID.
	 * @return array|null Row data or null.
	 */
	public static function get_by_id( int $id ): ?array {
		if ( ! self::table_exists() ) {
			return null;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
			ARRAY_A
		);
	}

	/**
	 * Get aggregated stats for the stats bar (History page).
	 *
	 * @param array $filters Same structure as get_list filters.
	 * @return array
	 */
	public static function get_stats( array $filters = [] ): array {
		if ( ! self::table_exists() ) {
			return self::empty_stats();
		}

		global $wpdb;

		$table  = $wpdb->prefix . self::TABLE;
		$where  = [];
		$values = [];

		if ( ! empty( $filters['type'] ) && in_array( $filters['type'], [ 'post', 'image', 'suggest', 'img_prompt' ], true ) ) {
			$where[]  = 'type = %s';
			$values[] = $filters['type'];
		}
		if ( ! empty( $filters['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$values[] = sanitize_text_field( $filters['date_from'] ) . ' 00:00:00';
		}
		if ( ! empty( $filters['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$values[] = sanitize_text_field( $filters['date_to'] ) . ' 23:59:59';
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$sql = "SELECT
			COUNT(*)                                          AS total,
			SUM(CASE WHEN status = 'success' OR status = 'saved' THEN 1 ELSE 0 END) AS success_count,
			SUM(CASE WHEN status = 'error'   THEN 1 ELSE 0 END) AS error_count,
			ROUND(AVG(CASE WHEN score IS NOT NULL AND score > 0 THEN score END)) AS avg_score,
			SUM(tokens_used)                                  AS total_tokens,
			SUM(input_tokens)                                 AS total_input,
			SUM(output_tokens)                                AS total_output,
			SUM(CASE WHEN type = 'post'    THEN 1 ELSE 0 END)  AS total_posts,
			SUM(CASE WHEN type = 'image'      THEN 1 ELSE 0 END)  AS total_images,
			SUM(CASE WHEN type = 'suggest'    THEN 1 ELSE 0 END)  AS total_suggests,
			SUM(CASE WHEN type = 'img_prompt' THEN 1 ELSE 0 END)  AS total_img_prompts
			FROM {$table} {$where_sql}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$row = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $row || ! $row['total'] ) {
			return self::empty_stats();
		}

		$total   = (int) $row['total'];
		$success = (int) $row['success_count'];

		return [
			'total'          => $total,
			'success_count'  => $success,
			'error_count'    => (int) $row['error_count'],
			'success_rate'   => $total > 0 ? round( ( $success / $total ) * 100 ) : 0,
			'avg_score'      => (int) ( $row['avg_score'] ?? 0 ),
			'total_tokens'   => (int) ( $row['total_tokens'] ?? 0 ),
			'total_input'    => (int) ( $row['total_input'] ?? 0 ),
			'total_output'   => (int) ( $row['total_output'] ?? 0 ),
			'total_posts'    => (int) ( $row['total_posts'] ?? 0 ),
			'total_images'   => (int) ( $row['total_images'] ?? 0 ),
			'total_suggests'    => (int) ( $row['total_suggests'] ?? 0 ),
			'total_img_prompts' => (int) ( $row['total_img_prompts'] ?? 0 ),
		];
	}

	/*--------------------------------------------------------------
	 * Read Operations — Dashboard (Analytics)
	 *------------------------------------------------------------*/

	/**
	 * Get aggregated data for the dashboard, grouped by model and by day.
	 *
	 * Replaces Xanh_AI_Tracker::get_range().
	 *
	 * @param string $start_date 'Y-m-d' format.
	 * @param string $end_date   'Y-m-d' format.
	 * @return array {
	 *     models: { model_id: { text_input, text_output, image_calls, api_calls } },
	 *     total_api_calls: int,
	 *     daily: { 'Y-m-d': { text_input, text_output, image_calls, api_calls } },
	 *     content_stats: { success_rate, avg_score, total_posts, total_images },
	 * }
	 */
	public static function get_dashboard_range( string $start_date, string $end_date ): array {
		$result = [
			'models'          => [],
			'total_api_calls' => 0,
			'daily'           => [],
			'content_stats'   => [
				'success_rate'  => 0,
				'avg_score'     => 0,
				'total_posts'   => 0,
				'total_images'  => 0,
			],
		];

		if ( ! self::table_exists() ) {
			return $result;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		// Per-model aggregation.
		$model_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					model,
					COUNT(*)                                               AS api_calls,
					SUM(input_tokens)                                      AS text_input,
					SUM(output_tokens)                                     AS text_output,
					SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END)       AS image_calls
				FROM {$table}
				WHERE created_at >= %s AND created_at <= %s
				GROUP BY model",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			),
			ARRAY_A
		);

		$total_calls = 0;
		foreach ( $model_rows as $mr ) {
			$mid = $mr['model'] ?: 'unknown';
			$result['models'][ $mid ] = [
				'text_input'  => (int) $mr['text_input'],
				'text_output' => (int) $mr['text_output'],
				'image_calls' => (int) $mr['image_calls'],
				'api_calls'   => (int) $mr['api_calls'],
			];
			$total_calls += (int) $mr['api_calls'];
		}
		$result['total_api_calls'] = $total_calls;

		// Daily aggregation for chart.
		$daily_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE(created_at) AS day,
					SUM(input_tokens)                                  AS text_input,
					SUM(output_tokens)                                 AS text_output,
					SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END)   AS image_calls,
					COUNT(*)                                           AS api_calls
				FROM {$table}
				WHERE created_at >= %s AND created_at <= %s
				GROUP BY DATE(created_at)
				ORDER BY day",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			),
			ARRAY_A
		);

		foreach ( $daily_rows as $dr ) {
			$result['daily'][ $dr['day'] ] = [
				'text_input'  => (int) $dr['text_input'],
				'text_output' => (int) $dr['text_output'],
				'image_calls' => (int) $dr['image_calls'],
				'api_calls'   => (int) $dr['api_calls'],
			];
		}

		// Content performance stats for the date range.
		$perf = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					ROUND(AVG(CASE WHEN score IS NOT NULL AND score > 0 THEN score END)) AS avg_score,
					SUM(CASE WHEN status IN ('success','saved') THEN 1 ELSE 0 END)       AS success_cnt,
					COUNT(*)                                                              AS total_cnt,
					SUM(CASE WHEN type = 'post'  THEN 1 ELSE 0 END)                      AS post_cnt,
					SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END)                      AS img_cnt
				FROM {$table}
				WHERE created_at >= %s AND created_at <= %s
				  AND type IN ('post','image')",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			),
			ARRAY_A
		);

		if ( $perf && (int) $perf['total_cnt'] > 0 ) {
			$result['content_stats'] = [
				'success_rate'  => round( ( (int) $perf['success_cnt'] / (int) $perf['total_cnt'] ) * 100 ),
				'avg_score'     => (int) ( $perf['avg_score'] ?? 0 ),
				'total_posts'   => (int) $perf['post_cnt'],
				'total_images'  => (int) $perf['img_cnt'],
			];
		}

		return $result;
	}

	/**
	 * Get monthly summaries for the dashboard history table.
	 *
	 * Replaces Xanh_AI_Tracker::get_summary().
	 *
	 * @param int $months Number of months to include.
	 * @return array [ 'YYYY_MM' => { models, total_api_calls } ]
	 */
	public static function get_dashboard_summary( int $months = 6 ): array {
		if ( ! self::table_exists() ) {
			return [];
		}

		global $wpdb;
		$table  = $wpdb->prefix . self::TABLE;
		$result = [];

		for ( $i = 0; $i < $months; $i++ ) {
			$ym    = gmdate( 'Y_m', strtotime( "-{$i} months" ) );
			$parts = explode( '_', $ym );
			$start = sprintf( '%s-%s-01 00:00:00', $parts[0], $parts[1] );
			$end   = gmdate( 'Y-m-t 23:59:59', strtotime( $start ) );

			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(*)          AS total_api_calls,
						SUM(input_tokens) AS total_input,
						SUM(output_tokens) AS total_output,
						SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END) AS total_images
					FROM {$table}
					WHERE created_at >= %s AND created_at <= %s",
					$start,
					$end
				),
				ARRAY_A
			);

			// Per-model breakdown for this month.
			$model_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
						model,
						COUNT(*)          AS api_calls,
						SUM(input_tokens) AS text_input,
						SUM(output_tokens) AS text_output,
						SUM(CASE WHEN type = 'image' THEN 1 ELSE 0 END) AS image_calls
					FROM {$table}
					WHERE created_at >= %s AND created_at <= %s
					GROUP BY model",
					$start,
					$end
				),
				ARRAY_A
			);

			$models = [];
			foreach ( $model_rows as $mr ) {
				$mid = $mr['model'] ?: 'unknown';
				$models[ $mid ] = [
					'text_input'  => (int) $mr['text_input'],
					'text_output' => (int) $mr['text_output'],
					'image_calls' => (int) $mr['image_calls'],
					'api_calls'   => (int) $mr['api_calls'],
				];
			}

			$result[ $ym ] = [
				'models'          => $models,
				'total_api_calls' => (int) ( $row['total_api_calls'] ?? 0 ),
			];
		}

		return $result;
	}

	/**
	 * Export history data as CSV string for a date range.
	 *
	 * @param string $start_date 'Y-m-d'.
	 * @param string $end_date   'Y-m-d'.
	 * @return string CSV content.
	 */
	public static function export_csv( string $start_date, string $end_date ): string {
		$range = self::get_dashboard_range( $start_date, $end_date );
		$models = $range['models'] ?? [];
		$cost   = Xanh_AI_Pricing::estimate_cost( $models );

		$lines   = [];
		$lines[] = 'Model,API Calls,Input Tokens,Output Tokens,Image Calls,Cost (USD),Cost (VND)';

		foreach ( $models as $model_id => $usage ) {
			$mc      = $cost['by_model'][ $model_id ] ?? [ 'usd' => 0, 'vnd' => 0 ];
			$lines[] = sprintf(
				'%s,%d,%d,%d,%d,%.4f,%d',
				$model_id,
				$usage['api_calls'] ?? 0,
				$usage['text_input'] ?? 0,
				$usage['text_output'] ?? 0,
				$usage['image_calls'] ?? 0,
				$mc['usd'],
				$mc['vnd']
			);
		}

		// Totals row.
		$lines[] = sprintf(
			'TOTAL,%d,%d,%d,%d,%.4f,%d',
			$range['total_api_calls'],
			array_sum( array_column( $models, 'text_input' ) ),
			array_sum( array_column( $models, 'text_output' ) ),
			array_sum( array_column( $models, 'image_calls' ) ),
			$cost['total_usd'],
			$cost['total_vnd']
		);

		return implode( "\n", $lines );
	}

	/*--------------------------------------------------------------
	 * Delete / Cleanup
	 *------------------------------------------------------------*/

	/**
	 * Delete records older than N months.
	 *
	 * @param int $months Number of months to retain.
	 * @return int Number of rows deleted.
	 */
	public static function delete_old( int $months = 12 ): int {
		if ( ! self::table_exists() || $months < 1 ) {
			return 0;
		}

		global $wpdb;
		$table    = $wpdb->prefix . self::TABLE;
		$cutoff   = gmdate( 'Y-m-d H:i:s', strtotime( "-{$months} months" ) );

		return (int) $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", $cutoff )
		);
	}

	/**
	 * Delete records within a date range.
	 *
	 * Used by Dashboard "Reset" functionality.
	 *
	 * @param string $start_date 'Y-m-d'.
	 * @param string $end_date   'Y-m-d'.
	 * @return int Number of rows deleted.
	 */
	public static function delete_range( string $start_date, string $end_date ): int {
		if ( ! self::table_exists() ) {
			return 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at >= %s AND created_at <= %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);
	}

	/*--------------------------------------------------------------
	 * Helpers
	 *------------------------------------------------------------*/

	/**
	 * Empty stats structure.
	 *
	 * @return array
	 */
	private static function empty_stats(): array {
		return [
			'total'         => 0,
			'success_count' => 0,
			'error_count'   => 0,
			'success_rate'  => 0,
			'avg_score'     => 0,
			'total_tokens'  => 0,
			'total_input'   => 0,
			'total_output'  => 0,
			'total_posts'   => 0,
			'total_images'      => 0,
			'total_img_prompts' => 0,
		];
	}
}
