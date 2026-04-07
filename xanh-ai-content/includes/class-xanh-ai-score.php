<?php
/**
 * Content Score — evaluates generated content quality (0-100).
 *
 * 9 criteria from PLUGIN_AI_SEO.md §6.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_Score {

	/**
	 * Calculate content score (0-100) with detailed checks.
	 *
	 * @param array $data {
	 *     @type string $title            Post title.
	 *     @type string $meta_description Meta description.
	 *     @type string $content_html     HTML content.
	 *     @type string $keyword          Primary keyword.
	 *     @type string $angle_id         Content angle ID.
	 *     @type bool   $has_image        Whether featured image exists.
	 * }
	 * @return array { score: int, level: string, color: string, checks: array }
	 */
	public static function calculate( array $data ): array {
		$score  = 0;
		$checks = [];

		$title       = $data['title'] ?? '';
		$meta        = $data['meta_description'] ?? '';
		$content     = $data['content_html'] ?? '';
		$keyword     = $data['keyword'] ?? '';
		$angle_id    = $data['angle_id'] ?? 'knowledge';
		$has_image   = $data['has_image'] ?? false;

		$angle     = Xanh_AI_Angles::get( $angle_id );
		$min_words = $angle['min_words'] ?? 800;
		$plain     = wp_strip_all_tags( $content );

		// 1. Title check (max 10 points).
		$title_len = mb_strlen( $title );
		if ( $title_len > 10 && $title_len <= 60 ) {
			$title_score = 5;
			if ( ! empty( $keyword ) && mb_stripos( $title, $keyword ) !== false && mb_stripos( $title, $keyword ) <= 10 ) {
				$title_score += 3; // keyword near beginning
			}
			if ( mb_stripos( $title, 'XANH' ) !== false ) {
				$title_score += 2; // brand at end
			}
			$score += $title_score;
			$checks['title'] = [
				'pass'    => $title_score >= 8,
				'score'   => $title_score,
				'max'     => 10,
				'message' => sprintf( 'Title: %d/%d ký tự', $title_len, 60 ),
			];
		} else {
			$checks['title'] = [
				'pass'    => false,
				'score'   => 0,
				'max'     => 10,
				'message' => $title_len > 60
					? sprintf( 'Title quá dài: %d/60 ký tự', $title_len )
					: 'Title quá ngắn',
			];
		}

		// 2. Meta Description (max 10 points).
		$meta_len = mb_strlen( $meta );
		if ( $meta_len > 50 && $meta_len <= 160 ) {
			$meta_score = 5;
			if ( ! empty( $keyword ) && mb_stripos( $meta, $keyword ) !== false ) {
				$meta_score += 3;
			}
			// Check for CTA hint (→, CTA words).
			if ( preg_match( '/→|Xem|Đọc|Khám phá|Đặt lịch/u', $meta ) ) {
				$meta_score += 2;
			}
			$score += $meta_score;
			$checks['meta'] = [
				'pass'    => $meta_score >= 8,
				'score'   => $meta_score,
				'max'     => 10,
				'message' => sprintf( 'Meta: %d/%d ký tự', $meta_len, 160 ),
			];
		} else {
			$checks['meta'] = [
				'pass'    => false,
				'score'   => 0,
				'max'     => 10,
				'message' => $meta_len > 160
					? sprintf( 'Meta quá dài: %d/160 ký tự', $meta_len )
					: 'Meta quá ngắn',
			];
		}

		// 3. Heading Hierarchy (max 10 points).
		$h2_count = preg_match_all( '/<h2\b/i', $content );
		$h3_count = preg_match_all( '/<h3\b/i', $content );
		$h1_count = preg_match_all( '/<h1\b/i', $content );
		$heading_score = 0;
		if ( $h1_count <= 1 ) {
			$heading_score += 4;
		}
		if ( $h2_count >= 2 ) {
			$heading_score += 4;
		}
		if ( $h3_count >= 1 ) {
			$heading_score += 2;
		}
		$score += $heading_score;
		$checks['headings'] = [
			'pass'    => $heading_score >= 8,
			'score'   => $heading_score,
			'max'     => 10,
			'message' => sprintf( 'H2: %d, H3: %d', $h2_count, $h3_count ),
		];

		// 4. Word Count (max 15 points).
		$word_count = self::count_vietnamese_words( $plain );
		if ( $word_count >= $min_words ) {
			$word_score = 15;
		} elseif ( $word_count >= $min_words * 0.7 ) {
			$word_score = 10;
		} elseif ( $word_count >= $min_words * 0.5 ) {
			$word_score = 5;
		} else {
			$word_score = 0;
		}
		$score += $word_score;
		$checks['words'] = [
			'pass'    => $word_score >= 10,
			'score'   => $word_score,
			'max'     => 15,
			'message' => sprintf( 'Số từ: %d/%d', $word_count, $min_words ),
		];

		// 5. Internal Links (max 15 points).
		$has_portfolio = (bool) preg_match( '/href=["\'][^"\']*\/du-an\//i', $content );
		$has_contact   = (bool) preg_match( '/href=["\'][^"\']*\/lien-he\//i', $content );
		$link_count    = preg_match_all( '/<a\s+[^>]*href=["\'][^"\']+["\']/i', $content );
		$link_score    = 0;
		if ( $has_portfolio ) {
			$link_score += 5;
		}
		if ( $has_contact ) {
			$link_score += 5;
		}
		if ( $link_count >= 3 ) {
			$link_score += 5;
		} elseif ( $link_count >= 2 ) {
			$link_score += 3;
		}
		$score += $link_score;
		$checks['links'] = [
			'pass'    => $link_score >= 10,
			'score'   => $link_score,
			'max'     => 15,
			'message' => sprintf( 'Links: %d tìm thấy (Portfolio: %s, Contact: %s)',
				$link_count,
				$has_portfolio ? 'OK' : 'X',
				$has_contact ? 'OK' : 'X'
			),
		];

		// 6. Banned Words (max 15 points).
		$found_banned = self::find_banned_words( $plain );
		if ( empty( $found_banned ) ) {
			$score += 15;
			$checks['banned'] = [
				'pass'    => true,
				'score'   => 15,
				'max'     => 15,
				'message' => 'Không có từ cấm',
			];
		} else {
			$checks['banned'] = [
				'pass'    => false,
				'score'   => 0,
				'max'     => 15,
				'message' => 'Từ cấm: ' . implode( ', ', array_slice( $found_banned, 0, 5 ) ),
			];
		}

		// 7. Featured Image (max 10 points).
		$image_score = $has_image ? 10 : 0;
		$score += $image_score;
		$checks['image'] = [
			'pass'    => $has_image,
			'score'   => $image_score,
			'max'     => 10,
			'message' => $has_image ? 'Có featured image' : 'Chưa có featured image',
		];

		// 8. External Link (max 5 points).
		$has_external = (bool) preg_match( '/<a\s+[^>]*href=["\']https?:\/\/(?!xanhdesignbuild)/i', $content );
		$ext_score = $has_external ? 5 : 0;
		$score += $ext_score;
		$checks['external'] = [
			'pass'    => $has_external,
			'score'   => $ext_score,
			'max'     => 5,
			'message' => $has_external ? 'Có external link' : 'Chưa có external link',
		];

		// 9. Keyword Density (max 10 points).
		if ( ! empty( $keyword ) && $word_count > 0 ) {
			$kw_count   = mb_substr_count( mb_strtolower( $plain ), mb_strtolower( $keyword ) );
			$kw_density = ( $kw_count / max( 1, $word_count ) ) * 100;
			if ( $kw_density >= 0.5 && $kw_density <= 1.5 ) {
				$kw_score = 10;
			} elseif ( $kw_density > 0 && $kw_density < 2.5 ) {
				$kw_score = 5;
			} else {
				$kw_score = 0;
			}
			$score += $kw_score;
			$checks['keyword'] = [
				'pass'    => $kw_score >= 5,
				'score'   => $kw_score,
				'max'     => 10,
				'message' => sprintf( 'Keyword "%s": %d lần (%.1f%%)', $keyword, $kw_count, $kw_density ),
			];
		} else {
			$checks['keyword'] = [
				'pass'    => false,
				'score'   => 0,
				'max'     => 10,
				'message' => 'Chưa có keyword',
			];
		}

		$level = self::get_level( $score );

		return [
			'score'  => min( 100, $score ),
			'level'  => $level['label'],
			'color'  => $level['color'],
			'emoji'  => $level['emoji'],
			'checks' => $checks,
		];
	}

	/**
	 * Get score level info.
	 *
	 * @param int $score Score 0-100.
	 * @return array { label, color, emoji }
	 */
	public static function get_level( int $score ): array {
		if ( $score >= 90 ) {
			return [ 'label' => 'Xuất sắc', 'color' => 'green', 'emoji' => '🟢' ];
		}
		if ( $score >= 70 ) {
			return [ 'label' => 'Tốt', 'color' => 'blue', 'emoji' => '🔵' ];
		}
		if ( $score >= 50 ) {
			return [ 'label' => 'Trung bình', 'color' => 'yellow', 'emoji' => '🟡' ];
		}
		return [ 'label' => 'Yếu', 'color' => 'red', 'emoji' => '🔴' ];
	}

	/**
	 * Find banned words/phrases in content.
	 *
	 * @param string $text Plain text content.
	 * @return string[] Found banned phrases.
	 */
	public static function find_banned_words( string $text ): array {
		$banned = Xanh_AI_Prompts::get_banned_phrases();
		$found  = [];
		$lower  = mb_strtolower( $text );

		foreach ( $banned as $phrase ) {
			if ( mb_stripos( $lower, mb_strtolower( $phrase ) ) !== false ) {
				$found[] = $phrase;
			}
		}

		return $found;
	}

	/**
	 * Count Vietnamese words (handles multi-syllable words).
	 *
	 * @param string $text Plain text.
	 * @return int Approximate word count.
	 */
	private static function count_vietnamese_words( string $text ): int {
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );
		if ( empty( $text ) ) {
			return 0;
		}
		return count( explode( ' ', $text ) );
	}
}
