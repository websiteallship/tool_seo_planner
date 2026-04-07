<?php
/**
 * Content Generator — orchestrates prompt → API → score → link → save.
 *
 * Implements PLUGIN_AI_WORKFLOW.md single post generation flow.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_Generator {

	/**
	 * Generate a complete blog post via AI.
	 *
	 * @param array $params {
	 *     @type string $topic      Post topic (required).
	 *     @type string $keyword    Primary keyword (required).
	 *     @type string $secondary  Secondary keywords.
	 *     @type string $angle_id   Content angle ID (required).
	 *     @type string $length     Content length: standard|long|guide.
	 *     @type string $notes      Additional notes.
	 * }
	 * @return array|WP_Error Generated content data with score.
	 */
	public function generate( array $params ) {
		$angle_id = $params['angle_id'] ?? 'knowledge';
		$angle    = Xanh_AI_Angles::get( $angle_id );

		if ( ! $angle ) {
			return new WP_Error( 'invalid_angle', __( 'Góc viết không hợp lệ.', 'xanh-ai-content' ) );
		}

		// Use custom prompt if provided (from prompt preview step), else build.
		if ( ! empty( $params['custom_prompt'] ) ) {
			$full_prompt = $params['custom_prompt'];
		} else {
			$system_prompt = Xanh_AI_Prompts::build_system_prompt( $angle_id );
			$user_prompt   = Xanh_AI_Prompts::build_user_prompt( $params );
			$full_prompt   = $system_prompt . "\n\n" . $user_prompt;
		}

		// Call Gemini API with automatic retry for JSON parse failures.
		$api    = new Xanh_AI_API();
		$result = $api->generate_text_with_retry( $full_prompt );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Extract content from parsed response.
		$content_html = $result['content_html'] ?? '';
		if ( empty( $content_html ) ) {
			return new WP_Error( 'no_content', __( 'AI không trả về nội dung. Vui lòng thử lại.', 'xanh-ai-content' ) );
		}

		// Inject missing internal links (with per-post override if provided).
		$custom_links = $params['custom_links'] ?? [];
		$content_html = Xanh_AI_Linker::inject_links( $content_html, $angle_id, $custom_links );

		// Inject dynamic related post links (same category + keyword scoring).
		$content_html = Xanh_AI_Linker::inject_related_posts(
			$content_html,
			$angle_id,
			$params['keyword'] ?? ''
		);

		// Calculate content score.
		$score_data = Xanh_AI_Score::calculate( [
			'title'            => $result['title'] ?? '',
			'meta_description' => $result['meta_description'] ?? '',
			'content_html'     => $content_html,
			'keyword'          => $params['keyword'] ?? '',
			'angle_id'         => $angle_id,
			'has_image'        => false,
		] );

		// Check for banned words in title too.
		$title_banned = Xanh_AI_Score::find_banned_words( $result['title'] ?? '' );

		return [
			'title'            => $result['title'] ?? '',
			'slug'             => $result['slug'] ?? '',
			'meta_description' => $result['meta_description'] ?? '',
			'excerpt'          => $result['excerpt'] ?? '',
			'content_html'     => $content_html,
			'tags'             => $result['tags'] ?? [],
			'faq'              => $result['faq'] ?? [],
			'image_prompt'     => $result['image_prompt'] ?? '',
			'section_images'   => $result['section_images'] ?? [],
			'score'            => $score_data,
			'tokens'           => $result['_tokens'] ?? 0,
			'prompt_tokens'    => $result['_prompt_tokens'] ?? 0,
			'output_tokens'    => $result['_output_tokens'] ?? 0,
			'angle'            => $angle,
			'title_banned'     => $title_banned,
		];
	}

	/**
	 * Save generated content as a WordPress draft post.
	 *
	 * @param array $data Generated content data from generate().
	 * @param array $params Original generation params.
	 * @return int|WP_Error Post ID on success.
	 */
	public function save_draft( array $data, array $params ) {
		$angle    = $data['angle'] ?? Xanh_AI_Angles::get( $params['angle_id'] ?? 'knowledge' );
		$category = $angle['category'] ?? '';

		// Get or create category.
		$cat_id = 0;
		if ( ! empty( $category ) ) {
			$cat = get_category_by_slug( $category );
			if ( $cat ) {
				$cat_id = $cat->term_id;
			}
		}

		// Default author from settings.
		$author_id = absint( get_option( 'xanh_ai_default_author', get_current_user_id() ) );

		// Build FAQ HTML.
		$faq_html = self::build_faq_html( $data['faq'] ?? [] );

		// Combine content + FAQ.
		$full_content = $data['content_html'];
		if ( ! empty( $faq_html ) ) {
			$full_content .= "\n\n" . $faq_html;
		}

		// Insert post.
		$post_data = [
			'post_title'   => sanitize_text_field( $data['title'] ?? '' ),
			'post_name'    => sanitize_title( $data['slug'] ?? '' ),
			'post_content' => wp_kses_post( $full_content ),
			'post_excerpt' => sanitize_text_field( $data['excerpt'] ?? '' ),
			'post_status'  => 'draft',
			'post_type'    => 'post',
			'post_author'  => $author_id,
		];

		if ( $cat_id ) {
			$post_data['post_category'] = [ $cat_id ];
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Set tags.
		if ( ! empty( $data['tags'] ) ) {
			wp_set_post_tags( $post_id, array_map( 'sanitize_text_field', $data['tags'] ) );
		}

		// Set RankMath SEO meta.
		Xanh_AI_Linker::set_rankmath_meta( $post_id, [
			'title'            => $data['title'] ?? '',
			'meta_description' => $data['meta_description'] ?? '',
			'keyword'          => $params['keyword'] ?? '',
		] );

		// Set AI tracking meta.
		Xanh_AI_Linker::set_ai_meta( $post_id, [
			'angle_id' => $params['angle_id'] ?? '',
			'score'    => $data['score']['score'] ?? 0,
			'tokens'   => $data['tokens'] ?? 0,
			'keyword'  => $params['keyword'] ?? '',
		] );

		// Save image prompt to meta for manual generation later (saves 1 API call).
		if ( ! empty( $data['image_prompt'] ) ) {
			update_post_meta( $post_id, '_xanh_ai_image_prompt', sanitize_text_field( $data['image_prompt'] ) );
		}

		// Generate and save FAQPage JSON-LD schema.
		$faq_schema = self::build_faq_schema( $data['faq'] ?? [], $post_id );
		if ( ! empty( $faq_schema ) ) {
			update_post_meta( $post_id, '_xanh_ai_faq_schema', wp_json_encode( $faq_schema, JSON_UNESCAPED_UNICODE ) );
		}

		/**
		 * Fires after a draft post is saved.
		 *
		 * @param int   $post_id Post ID.
		 * @param array $data    Generation data.
		 * @param array $params  Original parameters.
		 */
		do_action( 'xanh_ai_draft_saved', $post_id, $data, $params );

		return $post_id;
	}

	/**
	 * Generate featured image and attach to post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Generated content data.
	 * @return int|WP_Error Attachment ID or error.
	 */
	public function generate_and_set_image( int $post_id, array $data ) {
		$api = new Xanh_AI_API();

		// Use AI-generated image prompt or build one.
		$image_prompt = $data['image_prompt'] ?? '';
		if ( empty( $image_prompt ) ) {
			$image_prompt = $api->build_image_prompt(
				$data['title'] ?? '',
				$data['angle']['id'] ?? ''
			);
		}

		// SEO context for image metadata.
		$seo = [
			'keyword' => get_post_meta( $post_id, 'rank_math_focus_keyword', true ) ?: ( $data['title'] ?? '' ),
			'title'   => $data['title'] ?? '',
		];

		$attachment_id = $api->generate_image( $image_prompt, $seo );

		if ( is_wp_error( $attachment_id ) ) {
			Xanh_AI_Security::log( 'Image generation failed for post ' . $post_id );
			return $attachment_id;
		}

		// Set as featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	/**
	 * Regenerate a single H2 section of a post.
	 *
	 * @param string $content       Full HTML content.
	 * @param string $section_title H2 title to rewrite.
	 * @param string $notes         User notes.
	 * @param string $angle_id      Content angle.
	 * @return string|WP_Error New section HTML.
	 */
	public function regenerate_section( string $content, string $section_title, string $notes = '', string $angle_id = '' ) {
		$system_prompt = '';
		if ( ! empty( $angle_id ) ) {
			$system_prompt = Xanh_AI_Prompts::build_system_prompt( $angle_id );
		}

		$user_prompt = Xanh_AI_Prompts::build_section_prompt( $content, $section_title, $notes );
		$full_prompt = $system_prompt . "\n\n" . $user_prompt;

		$api    = new Xanh_AI_API();
		$result = $api->generate_text( $full_prompt, [
			'responseMimeType' => 'text/plain',
			'maxOutputTokens'  => 4096,
		] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// For section regeneration, result may be plain text or in 'content' key.
		if ( is_array( $result ) && isset( $result['content'] ) ) {
			return $result['content'];
		}

		return is_string( $result ) ? $result : '';
	}

	/*--------------------------------------------------------------
	 * Helpers
	 *------------------------------------------------------------*/

	/**
	 * Build FAQ HTML using <details>/<summary> elements.
	 *
	 * @param array $faqs Array of { question, answer } objects.
	 * @return string FAQ HTML.
	 */
	private static function build_faq_html( array $faqs ): string {
		if ( empty( $faqs ) ) {
			return '';
		}

		$html = '<div class="xanh-ai-faq">' . "\n";
		$html .= '<h2>Câu Hỏi Thường Gặp</h2>' . "\n";

		foreach ( $faqs as $faq ) {
			if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
				continue;
			}
			$html .= sprintf(
				'<details><summary>%s</summary><p>%s</p></details>' . "\n",
				esc_html( $faq['question'] ),
				wp_kses_post( $faq['answer'] )
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Build FAQPage JSON-LD schema from FAQ array.
	 *
	 * @param array $faqs    Array of { question, answer } objects.
	 * @param int   $post_id Post ID for mainEntity URL.
	 * @return array|null FAQPage schema array or null if empty.
	 */
	private static function build_faq_schema( array $faqs, int $post_id ): ?array {
		if ( empty( $faqs ) ) {
			return null;
		}

		$items = [];
		foreach ( $faqs as $faq ) {
			if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
				continue;
			}
			$items[] = [
				'@type'          => 'Question',
				'name'           => sanitize_text_field( $faq['question'] ),
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $faq['answer'] ),
				],
			];
		}

		if ( empty( $items ) ) {
			return null;
		}

		return [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $items,
		];
	}
}


