<?php
/**
 * Main plugin singleton — orchestrates all modules.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_Content {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use instance().
	 */
	private function __construct() {
		$this->load_textdomain();
		$this->init_hooks();
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/*--------------------------------------------------------------
	 * Text domain
	 *------------------------------------------------------------*/
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'xanh-ai-content',
			false,
			dirname( XANH_AI_BASENAME ) . '/languages'
		);
	}

	/*--------------------------------------------------------------
	 * Hook registration
	 *------------------------------------------------------------*/
	private function init_hooks(): void {
		// Admin-only modules.
		if ( is_admin() ) {
			// Admin UI (menu, assets, AJAX).
			$admin = new Xanh_AI_Admin();
			$admin->init();

			// Settings.
			$settings = new Xanh_AI_Settings();
			$settings->init();
		}

		// AJAX handlers (fire on both admin and front for logged-in users).
		add_action( 'wp_ajax_xanh_ai_test_connection',    [ $this, 'ajax_test_connection' ] );
		add_action( 'wp_ajax_xanh_ai_generate_content',   [ $this, 'ajax_generate_content' ] );
		add_action( 'wp_ajax_xanh_ai_regenerate_section', [ $this, 'ajax_regenerate_section' ] );
		add_action( 'wp_ajax_xanh_ai_save_draft',         [ $this, 'ajax_save_draft' ] );
		add_action( 'wp_ajax_xanh_ai_generate_image',     [ $this, 'ajax_generate_image' ] );
		add_action( 'wp_ajax_xanh_ai_generate_image_prompt', [ $this, 'ajax_generate_image_prompt' ] );
		add_action( 'wp_ajax_xanh_ai_preview_prompt',     [ $this, 'ajax_preview_prompt' ] );
		add_action( 'wp_ajax_xanh_ai_suggest_keywords',   [ $this, 'ajax_suggest_keywords' ] );
		add_action( 'wp_ajax_xanh_ai_get_usage_stats',    [ $this, 'ajax_get_usage_stats' ] );
		add_action( 'wp_ajax_xanh_ai_export_usage',       [ $this, 'ajax_export_usage' ] );
		add_action( 'wp_ajax_xanh_ai_reset_usage',        [ $this, 'ajax_reset_usage' ] );
		add_action( 'wp_ajax_xanh_ai_calendar_data',      [ $this, 'ajax_calendar_data' ] );
		add_action( 'wp_ajax_xanh_ai_reschedule_post',    [ $this, 'ajax_reschedule_post' ] );

		// History AJAX.
		add_action( 'wp_ajax_xanh_ai_get_history',        [ $this, 'ajax_get_history' ] );

		// History hooks — log generation events.
		add_action( 'xanh_ai_draft_saved', [ 'Xanh_AI_History', 'on_draft_saved' ], 10, 3 );

		// Update history when a post is permanently deleted.
		add_action( 'before_delete_post', function ( int $post_id ): void {
			Xanh_AI_History::mark_post_deleted( $post_id );
		} );

		// Frontend: Output FAQ Schema JSON-LD for blog posts.
		add_action( 'wp_head', [ $this, 'output_faq_schema' ], 20 );
	}

	/*--------------------------------------------------------------
	 * Frontend: Output FAQ Schema JSON-LD in <head>
	 *------------------------------------------------------------*/

	/**
	 * Output FAQPage JSON-LD for blog posts with FAQ sections.
	 *
	 * 1. Check stored meta from save_draft (fast path).
	 * 2. Fallback: parse <details>/<summary> HTML from post_content.
	 * 3. Cache parsed result for future page loads.
	 */
	public function output_faq_schema(): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}

		$post_id = get_the_ID();
		$decoded = null;

		// 1. Try stored FAQ schema meta (from save_draft).
		$faq_schema = get_post_meta( $post_id, '_xanh_ai_faq_schema', true );
		if ( ! empty( $faq_schema ) ) {
			$decoded = json_decode( $faq_schema, true );
			if ( json_last_error() !== JSON_ERROR_NONE || empty( $decoded['mainEntity'] ) ) {
				$decoded = null;
			}
		}

		// 2. Fallback: parse FAQ from post content <details>/<summary> HTML.
		if ( null === $decoded ) {
			$post    = get_post( $post_id );
			$content = $post ? $post->post_content : '';

			if ( preg_match_all(
				'/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/is',
				$content,
				$matches,
				PREG_SET_ORDER
			) ) {
				$items = [];
				foreach ( $matches as $match ) {
					$question = wp_specialchars_decode( wp_strip_all_tags( trim( $match[1] ) ) );
					$answer   = wp_specialchars_decode( wp_strip_all_tags( trim( $match[2] ) ) );
					if ( ! empty( $question ) && ! empty( $answer ) ) {
						$items[] = [
							'@type'          => 'Question',
							'name'           => $question,
							'acceptedAnswer' => [
								'@type' => 'Answer',
								'text'  => $answer,
							],
						];
					}
				}

				if ( ! empty( $items ) ) {
					$decoded = [
						'@context'   => 'https://schema.org',
						'@type'      => 'FAQPage',
						'mainEntity' => $items,
					];

					// Cache to meta for future page loads.
					update_post_meta( $post_id, '_xanh_ai_faq_schema', wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE ) );
				}
			}
		}

		if ( empty( $decoded ) || empty( $decoded['mainEntity'] ) ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT )
		);
	}

	/*--------------------------------------------------------------
	 * AJAX: Calendar Data — posts by month
	 *------------------------------------------------------------*/

	/**
	 * Return posts for a given month/year, grouped by day.
	 */
	public function ajax_calendar_data(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'edit_posts' ) ) {
			return;
		}

		$year  = absint( $_POST['year'] ?? gmdate( 'Y' ) );
		$month = absint( $_POST['month'] ?? gmdate( 'n' ) );

		// Validate ranges.
		if ( $year < 2020 || $year > 2100 ) {
			$year = (int) gmdate( 'Y' );
		}
		if ( $month < 1 || $month > 12 ) {
			$month = (int) gmdate( 'n' );
		}

		// Category filter (0 = all).
		$cat_id = absint( $_POST['category'] ?? 0 );

		// Status filter ('' = all).
		$status_filter = sanitize_text_field( $_POST['status'] ?? '' );
		$allowed_statuses = [ 'publish', 'draft', 'future', 'pending' ];
		$post_status = in_array( $status_filter, $allowed_statuses, true )
			? [ $status_filter ]
			: [ 'publish', 'draft', 'future', 'pending' ];

		// Date range for the month.
		$start_date = sprintf( '%04d-%02d-01', $year, $month );
		$end_date   = gmdate( 'Y-m-t', strtotime( $start_date ) );

		$args = [
			'post_type'      => 'post',
			'post_status'    => $post_status,
			'posts_per_page' => 100,
			'date_query'     => [
				[
					'after'     => $start_date,
					'before'    => $end_date . ' 23:59:59',
					'inclusive' => true,
				],
			],
			'orderby'        => 'date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		];

		if ( $cat_id > 0 ) {
			$args['category__in'] = [ $cat_id ];
		}

		// Only AI-generated posts? Check for meta key.
		$ai_only = ! empty( $_POST['ai_only'] );
		if ( $ai_only ) {
			$args['meta_query'] = [
				[
					'key'     => '_xanh_ai_generated',
					'compare' => 'EXISTS',
				],
			];
		}

		$posts  = get_posts( $args );
		$result = [];

		foreach ( $posts as $post ) {
			$cats     = wp_get_post_categories( $post->ID, [ 'fields' => 'all' ] );
			$cat_info = ! empty( $cats ) ? [
				'id'   => $cats[0]->term_id,
				'name' => $cats[0]->name,
				'slug' => $cats[0]->slug,
			] : null;

			$result[] = [
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'date'     => $post->post_date,
				'day'      => (int) gmdate( 'j', strtotime( $post->post_date ) ),
				'status'   => $post->post_status,
				'edit_url' => get_edit_post_link( $post->ID, 'raw' ),
				'view_url' => get_permalink( $post->ID ),
				'category' => $cat_info,
				'ai'       => ! empty( get_post_meta( $post->ID, '_xanh_ai_generated', true ) ),
			];
		}

		wp_send_json_success( [
			'year'  => $year,
			'month' => $month,
			'posts' => $result,
		] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Reschedule Post (Drag & Drop)
	 *------------------------------------------------------------*/

	/**
	 * Update post date from calendar drag & drop.
	 */
	public function ajax_reschedule_post(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'edit_posts' ) ) {
			return;
		}

		$post_id  = absint( $_POST['post_id'] ?? 0 );
		$new_date = sanitize_text_field( $_POST['new_date'] ?? '' ); // Format: YYYY-MM-DD

		if ( ! $post_id || ! $new_date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $new_date ) ) {
			wp_send_json_error( [ 'message' => __( 'Dữ liệu không hợp lệ.', 'xanh-ai-content' ) ] );
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( [ 'message' => __( 'Bài viết không tồn tại.', 'xanh-ai-content' ) ] );
			return;
		}

		// Check user can edit this specific post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Bạn không có quyền chỉnh sửa bài viết này.', 'xanh-ai-content' ) ] );
			return;
		}

		// Security: Prevent rescheduling published posts to protect SEO
		if ( 'publish' === $post->post_status ) {
			wp_send_json_error( [ 'message' => __( 'KHÔNG THỂ DỜI NGÀY BÀI VIẾT ĐÃ XUẤT BẢN. Việc này có thể làm thay đổi URL/Permalink và gây lỗi 404 mất backlink/traffic hiện tại. Bạn chỉ có thể kéo thả bản nháp hoặc bài hẹn giờ.', 'xanh-ai-content' ) ] );
			return;
		}

		// Keep original LOCAL time of the post (post_date is in site timezone).
		$local_time    = date( 'H:i:s', strtotime( $post->post_date ) );
		$new_datetime  = $new_date . ' ' . $local_time; // Local datetime.
		$new_gmt       = get_gmt_from_date( $new_datetime );

		// Determine correct post status: if new date is in the future and post is a draft, set to 'future'.
		$status = $post->post_status;
		$now    = current_time( 'timestamp' );
		$target = strtotime( $new_datetime );

		if ( 'draft' === $status && $target > $now ) {
			$status = 'future';
		} elseif ( 'future' === $status && $target <= $now ) {
			$status = 'draft';
		}

		$post_data = [
			'ID'            => $post_id,
			'post_date'     => $new_datetime,
			'post_date_gmt' => $new_gmt,
			'post_status'   => $status,
			'edit_date'     => true, // Force WordPress to accept the provided date.
		];

		// Disable revision creation for this quick update.
		remove_action( 'pre_post_update', 'wp_save_post_revision' );

		$update = wp_update_post( $post_data, true );

		if ( is_wp_error( $update ) ) {
			wp_send_json_error( [ 'message' => $update->get_error_message() ] );
			return;
		}

		wp_send_json_success( [ 'message' => __( 'Đã cập nhật ngày thành công.', 'xanh-ai-content' ) ] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Suggest keywords via AI
	 *------------------------------------------------------------*/
	public function ajax_suggest_keywords(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'edit_posts' ) ) {
			return;
		}

		$angle_id = sanitize_text_field( wp_unslash( $_POST['angle_id'] ?? '' ) );
		$topic    = sanitize_text_field( wp_unslash( $_POST['topic'] ?? '' ) );
		$keyword  = sanitize_text_field( wp_unslash( $_POST['primary_keyword'] ?? '' ) );

		if ( empty( $topic ) ) {
			wp_send_json_error( [
				'message' => __( 'Vui lòng nhập chủ đề trước khi gợi ý từ khóa.', 'xanh-ai-content' ),
			] );
		}

		// Get angle label for better context.
		$angle       = Xanh_AI_Angles::get( $angle_id );
		$angle_label = $angle['label'] ?? 'Kiến Thức';

		$api    = new Xanh_AI_API();
		$result = $api->suggest_keywords( $topic, $angle_label, $keyword );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [
				'message' => $result->get_error_message(),
			] );
		}

		wp_send_json_success( [
			'keywords' => $result,
			'source'   => 'ai',
		] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Test API connection
	 *------------------------------------------------------------*/
	public function ajax_test_connection(): void {
		// Security checks.
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'manage_options' ) ) {
			return; // validate_ajax_request sends the error response.
		}

		// Accept API key from form field (allows test before saving).
		$raw_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );

		$api    = new Xanh_AI_API();
		$result = $api->test_connection( $raw_key );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [
				'message' => $result->get_error_message(),
			] );
		}

		// Save the key automatically upon successful test
		if ( ! empty( $raw_key ) ) {
			update_option( 'xanh_ai_gemini_key', Xanh_AI_Security::encrypt_key( $raw_key ) );
		}

		wp_send_json_success( [
			'message' => __( 'Kết nối API thành công!', 'xanh-ai-content' ),
			'model'   => $result['model'] ?? '',
		] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Preview prompt (0 API calls)
	 *------------------------------------------------------------*/
	public function ajax_preview_prompt(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'edit_posts' ) ) {
			return;
		}

		$params = Xanh_AI_Security::sanitize_generator_input( $_POST );

		if ( empty( $params['topic'] ) || empty( $params['keyword'] ) ) {
			wp_send_json_error( [
				'message' => __( 'Vui lòng nhập chủ đề và từ khóa.', 'xanh-ai-content' ),
			] );
		}

		$angle_id      = $params['angle_id'] ?? 'knowledge';
		$system_prompt = Xanh_AI_Prompts::build_system_prompt( $angle_id );
		$user_prompt   = Xanh_AI_Prompts::build_user_prompt( $params );
		$full_prompt   = $system_prompt . "\n\n" . $user_prompt;

		// Rough token estimate: ~1 token per 4 chars for Vietnamese.
		$token_estimate = (int) ceil( mb_strlen( $full_prompt ) / 4 );

		wp_send_json_success( [
			'full_prompt'    => $full_prompt,
			'token_estimate' => $token_estimate,
		] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Generate content
	 *------------------------------------------------------------*/
	public function ajax_generate_content(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'edit_posts' ) ) {
			return;
		}

		if ( ! Xanh_AI_Security::check_rate_limit() ) {
			wp_send_json_error( [
				'message' => __( 'Vui lòng đợi 30 giây trước khi tạo tiếp.', 'xanh-ai-content' ),
				'code'    => 'rate_limited',
			], 429 );
		}

		$params = Xanh_AI_Security::sanitize_generator_input( $_POST );

		if ( empty( $params['topic'] ) || empty( $params['keyword'] ) ) {
			wp_send_json_error( [
				'message' => __( 'Vui lòng nhập chủ đề và từ khóa.', 'xanh-ai-content' ),
			] );
		}

		// Support custom prompt from the prompt preview step.
		$custom_prompt = wp_unslash( $_POST['custom_prompt'] ?? '' );
		if ( ! empty( $custom_prompt ) ) {
			$params['custom_prompt'] = $custom_prompt;
		}

		$generator = new Xanh_AI_Generator();
		$result    = $generator->generate( $params );

		if ( is_wp_error( $result ) ) {
			// Log failed generation.
			Xanh_AI_History::log( [
				'type'          => 'post',
				'angle'         => $params['angle_id'] ?? null,
				'topic'         => $params['topic'] ?? null,
				'keywords'      => $params['keyword'] ?? null,
				'model'         => get_option( 'xanh_ai_text_model', 'gemini-2.5-flash' ),
				'status'        => 'error',
				'error_message' => $result->get_error_message(),
			] );

			wp_send_json_error( [
				'message' => $result->get_error_message(),
			] );
		}

		// Log successful generation (tokens consumed at this point).
		// Note: Generator returns 'tokens'/'prompt_tokens'/'output_tokens' (no underscore).
		$history_id = Xanh_AI_History::log( [
			'type'          => 'post',
			'angle'         => $params['angle_id'] ?? null,
			'topic'         => $result['title'] ?? $params['topic'] ?? null,
			'keywords'      => $params['keyword'] ?? null,
			'model'         => get_option( 'xanh_ai_text_model', 'gemini-2.5-flash' ),
			'tokens_used'   => $result['tokens'] ?? 0,
			'input_tokens'  => $result['prompt_tokens'] ?? 0,
			'output_tokens' => $result['output_tokens'] ?? 0,
			'score'         => isset( $result['score']['score'] ) ? absint( $result['score']['score'] ) : null,
			'status'        => 'success',
		] );

		// Include history_id so JS can pass it to save_draft.
		$result['history_id'] = $history_id;

		wp_send_json_success( $result );
	}

	/*--------------------------------------------------------------
	 * AJAX: Regenerate a single section
	 *------------------------------------------------------------*/
	public function ajax_regenerate_section(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'edit_posts' ) ) {
			return;
		}

		$content       = wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) );
		$section_title = sanitize_text_field( wp_unslash( $_POST['section_title'] ?? '' ) );
		$notes         = sanitize_text_field( wp_unslash( $_POST['notes'] ?? '' ) );
		$angle_id      = sanitize_text_field( wp_unslash( $_POST['angle_id'] ?? '' ) );

		if ( empty( $content ) || empty( $section_title ) ) {
			wp_send_json_error( [
				'message' => __( 'Thiếu nội dung hoặc tiêu đề section.', 'xanh-ai-content' ),
			] );
		}

		$generator  = new Xanh_AI_Generator();
		$new_section = $generator->regenerate_section( $content, $section_title, $notes, $angle_id );

		if ( is_wp_error( $new_section ) ) {
			wp_send_json_error( [
				'message' => $new_section->get_error_message(),
			] );
		}

		wp_send_json_success( [
			'section_html' => $new_section,
		] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Save draft
	 *------------------------------------------------------------*/
	public function ajax_save_draft(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'edit_posts' ) ) {
			return;
		}

		// Rebuild data from POST.
		$raw_title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );

		// Auto-append site name suffix if not already present.
		$site_name = get_bloginfo( 'name' );
		if ( ! empty( $site_name ) && mb_stripos( $raw_title, $site_name ) === false ) {
			$raw_title .= ' | ' . $site_name;
		}

		$data = [
			'title'            => $raw_title,
			'slug'             => sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) ),
			'meta_description' => sanitize_text_field( wp_unslash( $_POST['meta_description'] ?? '' ) ),
			'excerpt'          => sanitize_text_field( wp_unslash( $_POST['excerpt'] ?? '' ) ),
			'content_html'     => wp_kses_post( wp_unslash( $_POST['content_html'] ?? '' ) ),
			'tags'             => array_map( 'sanitize_text_field', (array) ( $_POST['tags'] ?? [] ) ),
			'faq'              => json_decode( wp_unslash( $_POST['faq'] ?? '[]' ), true ) ?: [],
			'image_prompt'     => sanitize_text_field( wp_unslash( $_POST['image_prompt'] ?? '' ) ),
			'score'            => json_decode( wp_unslash( $_POST['score'] ?? '{}' ), true ) ?: [],
			'tokens'           => absint( $_POST['tokens'] ?? 0 ),
			'angle'            => Xanh_AI_Angles::get( sanitize_text_field( $_POST['angle_id'] ?? 'knowledge' ) ),
			'history_id'       => absint( $_POST['history_id'] ?? 0 ),
		];

		$params = [
			'angle_id' => sanitize_text_field( $_POST['angle_id'] ?? 'knowledge' ),
			'keyword'  => sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) ),
		];

		$generator = new Xanh_AI_Generator();
		$post_id   = $generator->save_draft( $data, $params );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( [
				'message' => $post_id->get_error_message(),
			] );
		}

		// Set featured image if attachment_id was provided from image generation.
		$attachment_id = absint( $_POST['attachment_id'] ?? 0 );
		if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}

		wp_send_json_success( [
			'post_id'  => $post_id,
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
			'message'  => __( 'Đã lưu bài viết nháp thành công!', 'xanh-ai-content' ),
		] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Generate featured image
	 *------------------------------------------------------------*/
	public function ajax_generate_image(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'upload_files' ) ) {
			return;
		}

		$image_prompt = sanitize_text_field( wp_unslash( $_POST['image_prompt'] ?? '' ) );

		if ( empty( $image_prompt ) ) {
			wp_send_json_error( [
				'message' => __( 'Thiếu prompt cho hình ảnh.', 'xanh-ai-content' ),
			] );
		}

		// SEO context for image metadata (alt, title, caption, description, filename).
		$seo = [
			'keyword'       => sanitize_text_field( wp_unslash( $_POST['seo_keyword'] ?? '' ) ),
			'title'         => sanitize_text_field( wp_unslash( $_POST['seo_title'] ?? '' ) ),
			'section_title' => sanitize_text_field( wp_unslash( $_POST['seo_section_title'] ?? '' ) ),
		];

		$api           = new Xanh_AI_API();
		$attachment_id = $api->generate_image( $image_prompt, $seo );

		if ( is_wp_error( $attachment_id ) ) {
			// Log failed image generation.
			Xanh_AI_History::log( [
				'type'          => 'image',
				'topic'         => $image_prompt,
				'model'         => get_option( 'xanh_ai_image_model', 'gemini-3.1-flash-image-preview' ),
				'status'        => 'error',
				'error_message' => $attachment_id->get_error_message(),
			] );

			wp_send_json_error( [
				'message' => $attachment_id->get_error_message(),
			] );
		}

		// Log successful image generation.
		Xanh_AI_History::log( [
			'type'      => 'image',
			'object_id' => $attachment_id,
			'topic'     => $image_prompt,
			'model'     => get_option( 'xanh_ai_image_model', 'gemini-3.1-flash-image-preview' ),
			'status'    => 'success',
		] );

		wp_send_json_success( [
			'attachment_id' => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
		] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Generate image prompt from context (text-only AI call)
	 *------------------------------------------------------------*/
	public function ajax_generate_image_prompt(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'upload_files' ) ) {
			return;
		}

		$selected_text = sanitize_textarea_field( wp_unslash( $_POST['selected_text'] ?? '' ) );
		$full_content  = sanitize_textarea_field( wp_unslash( $_POST['full_content'] ?? '' ) );
		$post_title    = sanitize_text_field( wp_unslash( $_POST['post_title'] ?? '' ) );
		$keyword       = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );
		$post_id       = absint( $_POST['post_id'] ?? 0 );

		if ( empty( $selected_text ) && empty( $post_title ) ) {
			wp_send_json_error( [ 'message' => 'Không có đoạn văn hoặc tiêu đề.' ] );
		}

		// Build system prompt for image prompt generation.
		$system = "You are an expert AI image prompt engineer for editorial photography.\n";
		$system .= "Context: XANH Design & Build — a luxury architecture/interior company in Nha Trang, Vietnam.\n";
		$system .= "Style guidelines: bright airy natural sunlight, warm tones, cream and emerald green palette, " .
			"editorial/architectural photography, Vietnamese modern home, no text overlay, no watermark.\n";
		$system .= "IMPORTANT: Always output ONLY the English image prompt (max 60 words). No explanation, no quotes.";

		$user_prompt = '';
		if ( ! empty( $full_content ) ) {
			$user_prompt .= "Full article context (for reference):\n" . mb_substr( $full_content, 0, 1000 ) . "\n\n";
		}
		if ( ! empty( $post_title ) ) {
			$user_prompt .= "Article title: {$post_title}\n";
		}
		if ( ! empty( $keyword ) ) {
			$user_prompt .= "Focus keyword: {$keyword}\n";
		}
		if ( ! empty( $selected_text ) ) {
			$user_prompt .= "\nSpecific paragraph to illustrate:\n\"" . mb_substr( $selected_text, 0, 500 ) . "\"\n";
		}
		$user_prompt .= "\nWrite a detailed English image prompt to illustrate this specific paragraph, keeping the overall article context and XANH brand aesthetic in mind.";

		// Combine system + user into one prompt (generate_text expects a single prompt string).
		$full_prompt = $system . "\n\n" . $user_prompt;

		$api = new Xanh_AI_API();

		$result = $api->generate_text( $full_prompt, [
			'responseMimeType' => 'text/plain',
			'maxOutputTokens'  => 1024,
		] );

		if ( is_wp_error( $result ) ) {
			// Log failed prompt generation.
			Xanh_AI_History::log( [
				'type'          => 'img_prompt',
				'object_id'     => $post_id ?: null,
				'topic'         => mb_substr( $selected_text ?: $post_title, 0, 200 ),
				'model'         => get_option( 'xanh_ai_text_model', 'gemini-2.5-flash' ),
				'status'        => 'error',
				'error_message' => $result->get_error_message(),
			] );
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$prompt_text = trim( $result['content'] ?? '' );

		// Remove quotes if AI wrapped it.
		$prompt_text = trim( $prompt_text, '"\'' );

		if ( empty( $prompt_text ) ) {
			wp_send_json_error( [ 'message' => 'AI không trả về prompt.' ] );
		}

		// Log successful prompt generation (text token usage).
		Xanh_AI_History::log( [
			'type'          => 'img_prompt',
			'object_id'     => $post_id ?: null,
			'topic'         => mb_substr( $selected_text ?: $post_title, 0, 200 ),
			'keywords'      => $prompt_text,
			'model'         => get_option( 'xanh_ai_text_model', 'gemini-2.5-flash' ),
			'status'        => 'success',
			'input_tokens'  => $result['_prompt_tokens'] ?? 0,
			'output_tokens' => $result['_output_tokens'] ?? 0,
		] );

		wp_send_json_success( [ 'prompt' => $prompt_text ] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Get generation history
	 *------------------------------------------------------------*/
	public function ajax_get_history(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'edit_posts' ) ) {
			return;
		}

		$filters = [
			'type'      => sanitize_text_field( $_POST['type'] ?? '' ),
			'status'    => sanitize_text_field( $_POST['status'] ?? '' ),
			'angle'     => sanitize_text_field( $_POST['angle'] ?? '' ),
			'search'    => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ),
			'date_from' => sanitize_text_field( $_POST['date_from'] ?? '' ),
			'date_to'   => sanitize_text_field( $_POST['date_to'] ?? '' ),
		];

		$page     = absint( $_POST['page'] ?? 1 );
		$per_page = absint( $_POST['per_page'] ?? 20 );

		$list  = Xanh_AI_History::get_list( $filters, $page, $per_page );
		$stats = Xanh_AI_History::get_stats( $filters );

		wp_send_json_success( [
			'items' => $list['items'],
			'total' => $list['total'],
			'pages' => $list['pages'],
			'page'  => $list['page'],
			'stats' => $stats,
		] );
	}
	/*--------------------------------------------------------------
	 * AJAX: Get usage stats for dashboard
	 *------------------------------------------------------------*/
	public function ajax_get_usage_stats(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'manage_options' ) ) {
			return;
		}

		$months = absint( $_POST['months'] ?? 3 );
		$stats  = Xanh_AI_History::get_dashboard_summary( $months );

		wp_send_json_success( [
			'stats' => $stats,
		] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Export usage data as CSV
	 *------------------------------------------------------------*/
	public function ajax_export_usage(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'manage_options' ) ) {
			return;
		}

		// Use current month date range for export.
		$now   = current_time( 'Y-m-d' );
		$start = substr( $now, 0, 8 ) . '01';
		$csv   = Xanh_AI_History::export_csv( $start, $now );

		wp_send_json_success( [
			'csv'   => $csv,
			'month' => gmdate( 'Y_m' ),
		] );
	}

	/*--------------------------------------------------------------
	 * AJAX: Reset usage data for a month
	 *------------------------------------------------------------*/
	public function ajax_reset_usage(): void {
		if ( ! Xanh_AI_Security::validate_ajax_request( 'xanh_ai_ajax', 'manage_options' ) ) {
			return;
		}

		// Delete current month's history data.
		$now   = current_time( 'Y-m-d' );
		$start = substr( $now, 0, 8 ) . '01';
		Xanh_AI_History::delete_range( $start, $now );

		wp_send_json_success( [
			'message' => __( 'Đã xóa dữ liệu thành công.', 'xanh-ai-content' ),
		] );
	}
}
