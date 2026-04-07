<?php
/**
 * Gemini API client — text + image generation.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
	exit;
}

class Xanh_AI_API
{

	/**
	 * Gemini API base URL.
	 */
	private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

	/**
	 * Timeout for text generation (seconds).
	 */
	private const TEXT_TIMEOUT = 120;

	/**
	 * Timeout for image generation (seconds).
	 */
	private const IMAGE_TIMEOUT = 120;

	/**
	 * Max retries for JSON parse failures.
	 */
	private const MAX_RETRIES = 2;

	/**
	 * Delay between retries (seconds).
	 */
	private const RETRY_DELAY = 2;

	/**
	 * Get decrypted API key.
	 *
	 * @return string|WP_Error
	 */
	private function get_api_key()
	{
		$encrypted = get_option('xanh_ai_gemini_key', '');
		$key = Xanh_AI_Security::decrypt_key($encrypted);

		if (empty($key)) {
			return new WP_Error(
				'no_api_key',
				__('Vui lòng cấu hình Gemini API Key trong Cài Đặt.', 'xanh-ai-content')
			);
		}

		return $key;
	}

	/*--------------------------------------------------------------
	 * Text Generation
	 *------------------------------------------------------------*/

	/**
	 * Generate text content using Gemini API.
	 *
	 * Includes automatic retry for JSON parse failures (max 2 retries).
	 *
	 * @param string $prompt Full prompt (system + user).
	 * @param array  $config Optional generation config overrides.
	 * @return array|WP_Error Parsed JSON response with '_tokens' key.
	 */
	public function generate_text(string $prompt, array $config = [])
	{
		$api_key = $this->get_api_key();
		if (is_wp_error($api_key)) {
			return $api_key;
		}

		$model = get_option('xanh_ai_text_model', 'gemini-2.5-flash');
		$url = self::BASE_URL . $model . ':generateContent';

		// Determine mime type — controls whether we parse JSON or return raw text.
		$mime_type = $config['responseMimeType'] ?? 'application/json';

		$gen_config = [
			'maxOutputTokens' => 16384,
			'responseMimeType' => $mime_type,
		];

		// Thinking models (gemini-2.5-*) don't support temperature or responseSchema.
		// They require thinkingConfig instead.
		if (str_starts_with($model, 'gemini-2.5')) {
			$gen_config['thinkingConfig'] = ['thinkingBudget' => 8192];
		} else {
			$gen_config['temperature'] = (float) get_option('xanh_ai_temperature', 0.7);
			// Non-thinking models support responseSchema for structured output.
			if ('application/json' === $mime_type && !isset($config['responseSchema'])) {
				$gen_config['responseSchema'] = self::get_content_schema();
			}
		}

		// Allow caller overrides.
		$gen_config = wp_parse_args($config, $gen_config);

		$body = [
			'contents' => [
				[
					'role' => 'user',
					'parts' => [['text' => $prompt]],
				],
			],
			'generationConfig' => $gen_config,
		];

		/**
		 * Fires before making a text generation API call.
		 *
		 * @param string $prompt The prompt being sent.
		 * @param array  $body   The full request body.
		 */
		do_action('xanh_ai_before_generate', $prompt, $body);

		$response = wp_remote_post($url, [
			'headers' => [
				'Content-Type' => 'application/json',
				'x-goog-api-key' => $api_key,
			],
			'body' => wp_json_encode($body),
			'timeout' => self::TEXT_TIMEOUT,
		]);

		if (is_wp_error($response)) {
			Xanh_AI_Security::log('Text API Error: ' . $response->get_error_message());
			return new WP_Error(
				'api_error',
				__('Lỗi kết nối API. Vui lòng thử lại.', 'xanh-ai-content')
			);
		}

		$code = wp_remote_retrieve_response_code($response);

		if (429 === $code) {
			Xanh_AI_Security::log('Text API rate limited by Google.');
			return new WP_Error(
				'api_rate_limited',
				__('Đã vượt giới hạn API (Free tier: 20 requests/ngày). Đợi đến 07:00 sáng mai hoặc nâng cấp lên Pay-as-you-go tại aistudio.google.com.', 'xanh-ai-content')
			);
		}

		if (200 !== $code) {
			$error_body = wp_remote_retrieve_body($response);
			Xanh_AI_Security::log("Text API HTTP {$code}: " . mb_substr($error_body, 0, 500));
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__('Gemini API trả về lỗi HTTP %d. Vui lòng thử lại.', 'xanh-ai-content'),
					$code
				)
			);
		}

		$data = json_decode(wp_remote_retrieve_body($response), true);

		// Check for blocked content / safety filters.
		$finish_reason = $data['candidates'][0]['finishReason'] ?? '';
		if ('SAFETY' === $finish_reason) {
			Xanh_AI_Security::log('Text API blocked by safety filter.');
			return new WP_Error(
				'safety_blocked',
				__('Nội dung bị chặn bởi bộ lọc an toàn của Google. Vui lòng thử chủ đề khác.', 'xanh-ai-content')
			);
		}

		// Check for truncated output due to token limit.
		if ('MAX_TOKENS' === $finish_reason) {
			$output_tokens = $data['usageMetadata']['candidatesTokenCount'] ?? 0;
			Xanh_AI_Security::log('Text API truncated by MAX_TOKENS. Output tokens: ' . $output_tokens);
			return new WP_Error(
				'max_tokens',
				__('Nội dung quá dài, AI tạo không hết. Vui lòng chọn độ dài ngắn hơn hoặc thử lại.', 'xanh-ai-content')
			);
		}

		// Extract text from candidates.
		$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
		if (empty($text)) {
			Xanh_AI_Security::log('Text API returned empty content. finishReason=' . $finish_reason);
			return new WP_Error(
				'empty_response',
				__('AI không trả về nội dung. Vui lòng thử lại.', 'xanh-ai-content')
			);
		}

		// If mime type is NOT JSON, return raw text (strip markdown fences if AI added them).
		if ('application/json' !== $mime_type) {
			// Remove ```html ... ``` or ``` ... ``` wrappers.
			$text = preg_replace('/^\s*```(?:html|htm)?\s*\n?/i', '', $text);
			$text = preg_replace('/\n?\s*```\s*$/i', '', $text);
			$text = trim($text);

			return [
				'content' => $text,
				'_tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
				'_prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
				'_output_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
			];
		}

		// Strip markdown code fences if present (```json ... ```).
		$text = self::strip_json_fences($text);

		// Parse JSON response.
		$parsed = json_decode($text, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$output_tokens = $data['usageMetadata']['candidatesTokenCount'] ?? 0;
			Xanh_AI_Security::log(
				'JSON parse error: ' . json_last_error_msg()
				. ' | finishReason=' . $finish_reason
				. ' | outputTokens=' . $output_tokens
				. ' | Raw (500 chars): ' . mb_substr($text, 0, 500)
			);

			// Attempt to salvage: try to extract JSON from the text.
			$parsed = self::attempt_json_extract($text);
			if (null === $parsed) {
				// Return parse_error — caller (generate_text_with_retry) will handle retry.
				return new WP_Error(
					'parse_error',
					__('Không thể parse response từ AI. Vui lòng thử lại.', 'xanh-ai-content')
				);
			}
		}

		if (!is_array($parsed)) {
			$parsed = ['content' => $parsed];
		}

		// Attach token usage metadata.
		$parsed['_tokens']         = $data['usageMetadata']['totalTokenCount'] ?? 0;
		$parsed['_prompt_tokens']  = $data['usageMetadata']['promptTokenCount'] ?? 0;
		$parsed['_output_tokens']  = $data['usageMetadata']['candidatesTokenCount'] ?? 0;

		// Token metadata attached — logging handled at AJAX layer via Xanh_AI_History.

		return $parsed;
	}

	/**
	 * Generate text with automatic retry for JSON parse failures.
	 *
	 * Wraps generate_text() with retry logic. Only retries on 'parse_error',
	 * not on safety blocks, rate limits, or HTTP errors.
	 *
	 * @param string $prompt Full prompt (system + user).
	 * @param array  $config Optional generation config overrides.
	 * @return array|WP_Error Parsed JSON response with '_tokens' key.
	 */
	public function generate_text_with_retry(string $prompt, array $config = [])
	{
		$last_error = null;

		for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
			if ($attempt > 0) {
				Xanh_AI_Security::log('Retry attempt ' . $attempt . '/' . self::MAX_RETRIES . ' for JSON parse error.');
				sleep(self::RETRY_DELAY * $attempt); // Exponential backoff: 2s, 4s.
			}

			$result = $this->generate_text($prompt, $config);

			// If success or non-retryable error, return immediately.
			if (!is_wp_error($result)) {
				if ($attempt > 0) {
					Xanh_AI_Security::log('Retry succeeded on attempt ' . ($attempt + 1) . '.');
				}
				return $result;
			}

			// Only retry on parse_error. All other errors are final.
			if ('parse_error' !== $result->get_error_code()) {
				return $result;
			}

			$last_error = $result;
		}

		Xanh_AI_Security::log('All ' . (self::MAX_RETRIES + 1) . ' attempts failed with parse_error.');
		return $last_error;
	}

	/**
	 * Strip markdown code fences from JSON response.
	 *
	 * Gemini sometimes wraps JSON in ```json ... ``` even with responseMimeType set.
	 *
	 * @param string $text Raw response text.
	 * @return string Cleaned text.
	 */
	private static function strip_json_fences(string $text): string
	{
		$text = trim($text);

		// Remove ```json at start and ``` at end.
		if (preg_match('/^```(?:json)?\s*\n?(.*?)\n?\s*```$/s', $text, $matches)) {
			return trim($matches[1]);
		}

		return $text;
	}

	/**
	 * Attempt to extract valid JSON from malformed text.
	 *
	 * Tries to find JSON object boundaries and parse.
	 *
	 * @param string $text Raw text containing JSON somewhere.
	 * @return array|null Parsed array or null on failure.
	 */
	private static function attempt_json_extract(string $text): ?array
	{
		// Find the first { and last }.
		$start = strpos($text, '{');
		$end = strrpos($text, '}');

		if (false === $start || false === $end || $end <= $start) {
			// Possibly truncated JSON — try appending closing braces.
			if (false !== $start) {
				$partial = substr($text, $start);
				$repaired = self::repair_truncated_json($partial);
				if (null !== $repaired) {
					Xanh_AI_Security::log('JSON salvaged by truncation repair.');
					return $repaired;
				}
			}
			return null;
		}

		$json = substr($text, $start, $end - $start + 1);
		$parsed = json_decode($json, true);

		if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
			Xanh_AI_Security::log('JSON salvaged by extraction.');
			return $parsed;
		}

		// Clean trailing commas and retry.
		$cleaned = self::fix_trailing_commas($json);
		if ($cleaned !== $json) {
			$parsed = json_decode($cleaned, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
				Xanh_AI_Security::log('JSON salvaged by trailing comma fix.');
				return $parsed;
			}
		}

		return null;
	}

	/**
	 * Attempt to repair truncated JSON by closing open braces/brackets.
	 *
	 * @param string $partial Partial JSON string starting with {.
	 * @return array|null Parsed array or null on failure.
	 */
	private static function repair_truncated_json(string $partial): ?array
	{
		// Count open vs close braces/brackets.
		$open_braces = substr_count($partial, '{') - substr_count($partial, '}');
		$open_brackets = substr_count($partial, '[') - substr_count($partial, ']');

		if ($open_braces <= 0 && $open_brackets <= 0) {
			return null;
		}

		// Strip possible incomplete value at the end (e.g., truncated string).
		// Look for last complete key-value pair.
		$repaired = rtrim($partial);

		// Remove trailing incomplete string (unmatched quote).
		$quote_count = substr_count($repaired, '"') - substr_count($repaired, '\\"');
		if ($quote_count % 2 !== 0) {
			// Find the last unescaped quote and truncate after the previous complete value.
			$last_quote = strrpos($repaired, '"');
			if (false !== $last_quote) {
				$repaired = substr($repaired, 0, $last_quote);
				// Clean up trailing partial key or comma.
				$repaired = preg_replace('/,\s*["\w]*$/s', '', $repaired);
			}
		}

		// Remove trailing comma.
		$repaired = preg_replace('/,\s*$/s', '', $repaired);

		// Close open brackets and braces.
		$repaired .= str_repeat(']', max(0, $open_brackets));
		$repaired .= str_repeat('}', max(0, $open_braces));

		$parsed = json_decode($repaired, true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
			return $parsed;
		}

		return null;
	}

	/**
	 * Fix trailing commas before } or ] in JSON.
	 *
	 * @param string $json JSON string.
	 * @return string Fixed JSON string.
	 */
	private static function fix_trailing_commas(string $json): string
	{
		return preg_replace('/,\s*([}\]])/s', '$1', $json);
	}

	/**
	 * Get JSON schema for structured content generation.
	 *
	 * This tells Gemini exactly what JSON structure to return.
	 *
	 * @return array Schema definition.
	 */
	private static function get_content_schema(): array
	{
		return [
			'type' => 'OBJECT',
			'properties' => [
				'title' => ['type' => 'STRING', 'description' => 'Tiêu đề bài viết, tối đa 60 ký tự'],
				'slug' => ['type' => 'STRING', 'description' => 'URL slug không dấu, phân cách bằng gạch ngang'],
				'meta_description' => ['type' => 'STRING', 'description' => 'Mô tả meta tối đa 160 ký tự, có CTA'],
				'excerpt' => ['type' => 'STRING', 'description' => 'Tóm tắt 2-3 câu'],
				'content_html' => ['type' => 'STRING', 'description' => 'Nội dung HTML đầy đủ với h2, h3, p, ul, ol, table, blockquote, strong, a'],
				'tags' => [
					'type' => 'ARRAY',
					'items' => ['type' => 'STRING'],
				],
				'faq' => [
					'type' => 'ARRAY',
					'items' => [
						'type' => 'OBJECT',
						'properties' => [
							'question' => ['type' => 'STRING'],
							'answer' => ['type' => 'STRING'],
						],
						'required' => ['question', 'answer'],
					],
				],
				'image_prompt' => ['type' => 'STRING', 'description' => 'Prompt tiếng Anh mô tả ảnh đại diện, phong cách editorial photography'],
				'section_images' => [
					'type' => 'ARRAY',
					'description' => 'Image prompt cho mỗi H2 section chính',
					'items' => [
						'type' => 'OBJECT',
						'properties' => [
							'after_h2' => ['type' => 'STRING', 'description' => 'Text chính xác của thẻ H2 tương ứng'],
							'prompt' => ['type' => 'STRING', 'description' => 'Prompt tiếng Anh mô tả ảnh editorial photography phù hợp nội dung section'],
						],
						'required' => ['after_h2', 'prompt'],
					],
				],
			],
			'required' => ['title', 'slug', 'meta_description', 'excerpt', 'content_html', 'tags', 'faq', 'image_prompt', 'section_images'],
		];
	}

	/*--------------------------------------------------------------
	 * Image Generation
	 *------------------------------------------------------------*/

	/**
	 * Generate an image using Gemini Imagen API and upload to Media Library.
	 *
	 * @param string $prompt Image generation prompt.
	 * @param array  $seo    Optional SEO context { keyword, title, section_title }.
	 * @return int|WP_Error Attachment ID on success.
	 */
	public function generate_image(string $prompt, array $seo = [])
	{
		$api_key = $this->get_api_key();
		if (is_wp_error($api_key)) {
			return $api_key;
		}

		$model = get_option('xanh_ai_image_model', 'gemini-3.1-flash-image-preview');
		$url = self::BASE_URL . $model . ':generateContent';

		// Append Vietnamese context suffix to ensure every image matches brand identity.
		$vn_suffix = ', set in Vietnam, Vietnamese people, Nha Trang coastal city, bright airy natural sunlight, fresh clean luminous tone, warm luxury, editorial photography';
		if (stripos($prompt, 'Vietnam') === false) {
			$prompt .= $vn_suffix;
		}

		$body = [
			'contents' => [
				['parts' => [['text' => $prompt]]],
			],
			'generationConfig' => [
				'responseModalities' => ['Image'],
				'imageConfig' => [
					'aspectRatio' => get_option('xanh_ai_image_aspect', '16:9'),
					'imageSize' => get_option('xanh_ai_image_size', '2K'),
				],
			],
		];

		/**
		 * Fires before making an image generation API call.
		 *
		 * @param string $prompt The image prompt.
		 */
		do_action('xanh_ai_before_image', $prompt);

		// Extend PHP execution time for long image generation.
		if (function_exists('set_time_limit')) {
			@set_time_limit(180);
		}

		$response = wp_remote_post($url, [
			'headers' => [
				'Content-Type' => 'application/json',
				'x-goog-api-key' => $api_key,
			],
			'body' => wp_json_encode($body),
			'timeout' => self::IMAGE_TIMEOUT,
		]);

		if (is_wp_error($response)) {
			Xanh_AI_Security::log('Image API Error: ' . $response->get_error_message());
			return new WP_Error(
				'api_error',
				__('Lỗi kết nối API hình ảnh: ', 'xanh-ai-content') . $response->get_error_message()
			);
		}

		$code = wp_remote_retrieve_response_code($response);
		if (200 !== $code) {
			$body_text = wp_remote_retrieve_body($response);
			Xanh_AI_Security::log("Image API HTTP error: {$code} | Body: " . mb_substr($body_text, 0, 500));

			// Parse error message from API response.
			$error_data = json_decode($body_text, true);
			$api_msg = $error_data['error']['message'] ?? '';
			$user_msg = __('Không thể tạo hình ảnh.', 'xanh-ai-content');
			if (429 === $code) {
				$user_msg = __('API quá tải. Vui lòng đợi 1 phút rồi thử lại.', 'xanh-ai-content');
			} elseif (!empty($api_msg)) {
				$user_msg .= ' (' . mb_substr($api_msg, 0, 100) . ')';
			}
			return new WP_Error('api_error', $user_msg);
		}

		$data = json_decode(wp_remote_retrieve_body($response), true);
		$image_b64 = $data['candidates'][0]['content']['parts'][0]['inlineData']['data'] ?? '';
		$mime = $data['candidates'][0]['content']['parts'][0]['inlineData']['mimeType'] ?? 'image/png';

		if (empty($image_b64)) {
			Xanh_AI_Security::log('Image API returned no image data.');
			return new WP_Error(
				'no_image',
				__('API không trả về hình ảnh. Vui lòng thử prompt khác.', 'xanh-ai-content')
			);
		}

		// Validate mime type and estimated size.
		$image_data = base64_decode($image_b64);
		if (!Xanh_AI_Security::validate_image_upload($mime, strlen($image_data))) {
			return new WP_Error(
				'invalid_image',
				__('Hình ảnh không hợp lệ (sai định dạng hoặc quá lớn).', 'xanh-ai-content')
			);
		}

		$attachment_id = $this->upload_base64_image($image_data, $mime, $seo);

		if (!is_wp_error($attachment_id)) {
			// Usage logging handled at AJAX layer via Xanh_AI_History.

			/**
			 * Fires after successfully uploading an AI-generated image.
			 *
			 * @param int $attachment_id The attachment ID.
			 */
			do_action('xanh_ai_after_image', $attachment_id);
		}

		return $attachment_id;
	}

	/*--------------------------------------------------------------
	 * Upload base64 image to Media Library
	 *------------------------------------------------------------*/

	/**
	 * Upload decoded image binary to WordPress Media Library with SEO metadata.
	 *
	 * @param string $image_data Raw image binary data.
	 * @param string $mime       MIME type.
	 * @param array  $seo        SEO context { keyword, title, section_title }.
	 * @return int|WP_Error Attachment ID on success.
	 */
	private function upload_base64_image(string $image_data, string $mime, array $seo = [])
	{
		$ext_map = [
			'image/png' => 'png',
			'image/jpeg' => 'jpg',
			'image/webp' => 'webp',
		];
		$ext = $ext_map[$mime] ?? 'png';

		// Build SEO-friendly filename from keyword.
		$seo_keyword      = sanitize_text_field( $seo['keyword'] ?? '' );
		$seo_title         = sanitize_text_field( $seo['title'] ?? '' );
		$seo_section_title = sanitize_text_field( $seo['section_title'] ?? '' );
		$short_id          = substr( wp_generate_uuid4(), 0, 8 );
		$site_name         = get_bloginfo( 'name' ); // e.g. "Xanh Design & Build"

		if ( ! empty( $seo_keyword ) ) {
			// Filename: keyword-sitename-shortid.ext
			$fname = sanitize_title( $seo_keyword . ' ' . $site_name ) . '-' . $short_id . '.' . $ext;
		} else {
			$fname = 'xanh-ai-' . $short_id . '.' . $ext;
		}

		$upload = wp_upload_bits($fname, null, $image_data);
		if (!empty($upload['error'])) {
			Xanh_AI_Security::log('Upload error: ' . $upload['error']);
			return new WP_Error(
				'upload_error',
				__('Không thể lưu hình ảnh. Kiểm tra quyền thư mục uploads.', 'xanh-ai-content')
			);
		}

		// Brand suffix for Alt & Title.
		$brand_suffix = ! empty( $site_name ) ? ' | ' . $site_name : '';

		// Build SEO meta values for all 4 WordPress image fields.
		$img_alt     = $seo_keyword . $brand_suffix; // Mô tả SEO (Alt text)
		$img_title   = $seo_keyword . $brand_suffix; // Tiêu đề (Title)
		$img_caption = '';                            // Chú thích (Caption)
		$img_desc    = '';                            // Miêu tả (Description)

		if ( ! empty( $seo_section_title ) ) {
			// Section image: Alt = "keyword - section title | Site Name"
			$img_alt     = $seo_keyword . ' - ' . $seo_section_title . $brand_suffix;
			// Section image: Title = "section title | keyword | Site Name"
			$img_title   = $seo_section_title . ' | ' . $seo_keyword . $brand_suffix;
			$img_caption = $seo_section_title;
			$img_desc    = sprintf( 'Hình ảnh minh họa %s trong bài viết %s', $seo_section_title, $seo_title );
		} elseif ( ! empty( $seo_title ) ) {
			// Featured image: Alt = "keyword | Site Name", Title = "post title | Site Name"
			$img_alt     = $seo_keyword . $brand_suffix;
			$img_title   = $seo_title . $brand_suffix;
			$img_caption = $seo_keyword;
			$img_desc    = sprintf( 'Ảnh đại diện bài viết: %s', $seo_title );
		}

		$attachment_id = wp_insert_attachment([
			'post_mime_type' => $mime,
			'post_title'     => $img_title ?: sanitize_file_name( $fname ),
			'post_excerpt'   => $img_caption, // WordPress Caption field
			'post_content'   => $img_desc,    // WordPress Description field
			'post_status'    => 'inherit',
		], $upload['file']);

		if (is_wp_error($attachment_id)) {
			return $attachment_id;
		}

		// Set Alt text (Mô tả SEO) — stored as post meta.
		if ( ! empty( $img_alt ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $img_alt );
		}

		// Generate attachment metadata (thumbnails, etc.).
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$meta = wp_generate_attachment_metadata($attachment_id, $upload['file']);
		wp_update_attachment_metadata($attachment_id, $meta);

		return $attachment_id;
	}

	/*--------------------------------------------------------------
	 * Image Prompt Builder
	 *------------------------------------------------------------*/

	/**
	 * Build an image generation prompt based on title and angle.
	 *
	 * @param string $title    Post title.
	 * @param string $angle_id Content angle ID.
	 * @return string The image prompt.
	 */
	public function build_image_prompt(string $title, string $angle_id = ''): string
	{
		// Default style for all angles.
		$style = 'professional interior design';

		// Angle-specific styles (will be enhanced in Sprint 2 with Angles class).
		$angle_styles = [
			'service' => 'professional architectural services',
			'material' => 'construction materials closeup',
			'local-seo' => 'modern Vietnamese home exterior',
			'knowledge' => 'interior design concept',
			'experience' => 'home renovation process',
			'trend' => 'contemporary design trend',
			'diary' => 'construction site documentary',
			'case-study' => 'completed luxury home showcase',
			'qa_problem_solving' => 'problem solution FAQ infographic clean editorial',
		];

		if (!empty($angle_id) && isset($angle_styles[$angle_id])) {
			$style = $angle_styles[$angle_id];
		}

		$prompt = sprintf(
			'Professional %s photograph, editorial style, warm ambient lighting, '
			. 'cream and emerald green color palette, natural warm tones, '
			. 'topic: %s, high quality architectural photography, '
			. 'no text overlay, no watermark, clean composition, luxury feel',
			$style,
			$title
		);

		/**
		 * Filter the image generation prompt.
		 *
		 * @param string $prompt   The image prompt.
		 * @param string $title    Post title.
		 * @param string $angle_id Content angle ID.
		 */
		return apply_filters('xanh_ai_image_prompt', $prompt, $title, $angle_id);
	}

	/*--------------------------------------------------------------
	 * Test Connection
	 *------------------------------------------------------------*/

	/**
	 * Test the API connection by sending a minimal prompt.
	 *
	 * @param string $plaintext_key Optional plaintext key (from form, before save).
	 * @return array|WP_Error Array with model info on success.
	 */
	public function test_connection(string $plaintext_key = '')
	{
		// Use the provided key if given, otherwise fall back to saved key.
		if (!empty($plaintext_key)) {
			$api_key = $plaintext_key;
		} else {
			$api_key = $this->get_api_key();
			if (is_wp_error($api_key)) {
				return $api_key;
			}
		}

		$model = get_option('xanh_ai_text_model', 'gemini-2.5-flash');
		$url = self::BASE_URL . $model . ':generateContent';

		// Send a minimal test prompt.
		$gen_config = [
			'maxOutputTokens' => 20,
			'responseMimeType' => 'application/json',
		];

		// Thinking models don't support temperature.
		if (str_starts_with($model, 'gemini-2.5')) {
			$gen_config['thinkingConfig'] = ['thinkingBudget' => 0];
		} else {
			$gen_config['temperature'] = 0.0;
		}

		$body = [
			'contents' => [
				[
					'role' => 'user',
					'parts' => [['text' => 'Respond with only: {"status":"ok"}']],
				],
			],
			'generationConfig' => $gen_config,
		];

		$response = wp_remote_post($url, [
			'headers' => [
				'Content-Type' => 'application/json',
				'x-goog-api-key' => $api_key,
			],
			'body' => wp_json_encode($body),
			'timeout' => 15,
		]);

		if (is_wp_error($response)) {
			return new WP_Error(
				'connection_failed',
				__('Không thể kết nối đến Gemini API. Kiểm tra internet và API key.', 'xanh-ai-content')
			);
		}

		$code = wp_remote_retrieve_response_code($response);

		if (401 === $code || 403 === $code) {
			return new WP_Error(
				'auth_failed',
				__('API Key không hợp lệ hoặc đã hết hạn. Vui lòng kiểm tra lại.', 'xanh-ai-content')
			);
		}

		if (200 !== $code) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__('API trả về mã lỗi %d.', 'xanh-ai-content'),
					$code
				)
			);
		}

		return [
			'status' => 'ok',
			'model' => $model,
		];
	}

	/*--------------------------------------------------------------
	 * Keyword Suggestion
	 *------------------------------------------------------------*/

	/**
	 * Suggest LSI keywords using Gemini API (lightweight call).
	 *
	 * @param string $topic          The content topic.
	 * @param string $angle_label    Human-readable angle name.
	 * @param string $primary_keyword Primary keyword already chosen.
	 * @return array|WP_Error Array of keyword strings on success.
	 */
	public function suggest_keywords(string $topic, string $angle_label, string $primary_keyword)
	{
		$api_key = $this->get_api_key();
		if (is_wp_error($api_key)) {
			return $api_key;
		}

		$model = get_option('xanh_ai_text_model', 'gemini-2.5-flash');
		$url = self::BASE_URL . $model . ':generateContent';

		$prompt = sprintf(
			'Cho chủ đề "%s" trong lĩnh vực thiết kế & thi công nội thất tại Nha Trang, '
			. 'gợi ý 8-12 từ khóa LSI (Latent Semantic Indexing) liên quan.' . "\n\n"
			. 'Yêu cầu:' . "\n"
			. '- Từ khóa tiếng Việt, có search intent rõ ràng' . "\n"
			. '- Bao gồm: long-tail keywords, câu hỏi người dùng hay tìm, từ đồng nghĩa' . "\n"
			. '- Phù hợp với góc viết: %s' . "\n"
			. '- Từ khóa chính đang dùng: "%s"' . "\n"
			. '- KHÔNG trùng với từ khóa chính' . "\n\n"
			. 'CHỈ trả về JSON array of strings, không giải thích:' . "\n"
			. '["keyword1", "keyword2", ...]',
			$topic,
			$angle_label,
			$primary_keyword
		);

		$gen_config = [
			'maxOutputTokens' => 2048,
			'responseMimeType' => 'application/json',
		];

		// Thinking models (gemini-2.5-*) don't support responseSchema;
		// disable thinking entirely for this fast utility call.
		if (str_starts_with($model, 'gemini-2.5')) {
			$gen_config['thinkingConfig'] = ['thinkingBudget' => 0];
		} else {
			$gen_config['temperature'] = 0.5;
		}

		$body = [
			'contents' => [
				[
					'role' => 'user',
					'parts' => [['text' => $prompt]],
				],
			],
			'generationConfig' => $gen_config,
		];

		$response = wp_remote_post($url, [
			'headers' => [
				'Content-Type' => 'application/json',
				'x-goog-api-key' => $api_key,
			],
			'body' => wp_json_encode($body),
			'timeout' => 30,
		]);

		if (is_wp_error($response)) {
			return new WP_Error(
				'api_error',
				__('Không thể gợi ý từ khóa. Vui lòng thử lại.', 'xanh-ai-content')
			);
		}

		$code = wp_remote_retrieve_response_code($response);

		if (429 === $code) {
			return new WP_Error(
				'api_rate_limited',
				__('API quá tải. Vui lòng đợi 1 phút rồi thử lại.', 'xanh-ai-content')
			);
		}

		if (200 !== $code) {
			$error_body = wp_remote_retrieve_body($response);
			Xanh_AI_Security::log("suggest_keywords HTTP {$code}: " . mb_substr($error_body, 0, 500));
			$error_data = json_decode($error_body, true);
			$api_msg = $error_data['error']['message'] ?? mb_substr($error_body, 0, 200);
			return new WP_Error(
				'api_error',
				sprintf(__('Gemini API lỗi HTTP %d khi gợi ý từ khóa.', 'xanh-ai-content'), $code)
			);
		}

		$raw_body = wp_remote_retrieve_body($response);
		$data = json_decode($raw_body, true);

		// Check if Gemini returned an error in the response body.
		if (!is_array($data)) {
			return new WP_Error('api_error', __('Gemini API trả về lỗi. Vui lòng thử lại.', 'xanh-ai-content'));
		}

		// Check for blocked / safety / truncation.
		$finish_reason = $data['candidates'][0]['finishReason'] ?? '';
		if ('SAFETY' === $finish_reason) {
			return new WP_Error('safety_blocked', __('Nội dung bị chặn bởi bộ lọc an toàn.', 'xanh-ai-content'));
		}

		$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

		if (empty($text)) {
			return new WP_Error('empty_response', __('AI không trả về từ khóa gợi ý.', 'xanh-ai-content'));
		}

		// Try parsing raw text first.
		$keywords = json_decode($text, true);

		// If raw parse fails, manually strip markdown code fences.
		if (!is_array($keywords)) {
			// Remove opening ```json or ``` and closing ```
			$stripped = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
			$stripped = preg_replace('/\s*```\s*$/', '', $stripped);
			$keywords = json_decode(trim($stripped), true);
		}

		// Fallback: use strip_json_fences utility.
		if (!is_array($keywords)) {
			$cleaned = self::strip_json_fences($text);
			$keywords = json_decode($cleaned, true);
		}

		// Last resort: regex extract JSON array.
		if (!is_array($keywords)) {
			if (preg_match('/\[[\s\S]*\]/u', $text, $matches)) {
				$keywords = json_decode($matches[0], true);
			}
		}

		if (!is_array($keywords)) {
			Xanh_AI_Security::log('suggest_keywords parse_error. finishReason=' . $finish_reason . '. Raw(200ch): ' . mb_substr($text, 0, 200));
			return new WP_Error('parse_error', __('Không thể đọc từ khóa gợi ý từ AI.', 'xanh-ai-content'));
		}

		// If result is associative (OBJECT wrap), try to extract keywords array.
		if (isset($keywords['keywords']) && is_array($keywords['keywords'])) {
			$keywords = $keywords['keywords'];
		}

		// Sanitize and filter.
		$keywords = array_values(array_filter(array_map('sanitize_text_field', $keywords)));

		// Log keyword suggestion usage via History.
		// We store the output array as JSON in the error_message column purely for UI retrieval.
		Xanh_AI_History::log( [
			'type'          => 'suggest',
			'model'         => $model,
			'input_tokens'  => $data['usageMetadata']['promptTokenCount'] ?? 0,
			'output_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
			'tokens_used'   => ( $data['usageMetadata']['promptTokenCount'] ?? 0 ) + ( $data['usageMetadata']['candidatesTokenCount'] ?? 0 ),
			'status'        => 'success',
			'topic'         => mb_substr( $topic, 0, 255 ),
			'keywords'      => $primary_keyword,
			'angle'         => mb_substr( $angle_label, 0, 50 ),
			'error_message' => wp_json_encode( array_slice( $keywords, 0, 12 ) ),
		] );

		return array_slice($keywords, 0, 12);
	}
}
