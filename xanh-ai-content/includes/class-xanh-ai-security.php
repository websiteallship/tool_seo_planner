<?php
/**
 * Security utilities — encryption, rate limiting, validation.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_Security {

	/**
	 * Cipher method for API key encryption.
	 */
	private const CIPHER = 'AES-256-CBC';

	/**
	 * Rate limit cooldown in seconds.
	 */
	private const RATE_LIMIT_SECONDS = 30;

	/*--------------------------------------------------------------
	 * API Key Encryption
	 *------------------------------------------------------------*/

	/**
	 * Encrypt a plaintext API key using AES-256-CBC.
	 *
	 * @param string $plaintext The API key to encrypt.
	 * @return string Base64-encoded encrypted string (IV + ciphertext).
	 */
	public static function encrypt_key( string $plaintext ): string {
		if ( empty( $plaintext ) ) {
			return '';
		}

		$key    = hash( 'sha256', wp_salt( 'auth' ), true );
		$iv     = openssl_random_pseudo_bytes( 16 );
		$cipher = openssl_encrypt( $plaintext, self::CIPHER, $key, 0, $iv );

		if ( false === $cipher ) {
			return '';
		}

		return base64_encode( $iv . $cipher );
	}

	/**
	 * Decrypt an encrypted API key.
	 *
	 * @param string $encrypted Base64-encoded encrypted string.
	 * @return string Decrypted plaintext key.
	 */
	public static function decrypt_key( string $encrypted ): string {
		if ( empty( $encrypted ) ) {
			return '';
		}

		// If the stored value already looks like a plaintext API key, return it directly.
		if ( str_starts_with( $encrypted, 'AIza' ) ) {
			return $encrypted;
		}

		$key  = hash( 'sha256', wp_salt( 'auth' ), true );
		$data = base64_decode( $encrypted );

		if ( false === $data || strlen( $data ) < 17 ) {
			return '';
		}

		$iv     = substr( $data, 0, 16 );
		$cipher = substr( $data, 16 );

		$result = openssl_decrypt( $cipher, self::CIPHER, $key, 0, $iv );

		// Validate decryption result looks like a Google API key.
		if ( false === $result || ! str_starts_with( $result, 'AIza' ) ) {
			return '';
		}

		return $result;
	}

	/**
	 * Mask an API key for display (e.g., "AIza...x4Qm").
	 *
	 * @param string $key The decrypted key.
	 * @return string Masked key.
	 */
	public static function mask_key( string $key ): string {
		if ( strlen( $key ) <= 8 ) {
			return str_repeat( '•', strlen( $key ) );
		}

		return substr( $key, 0, 4 ) . '...' . substr( $key, -4 );
	}

	/*--------------------------------------------------------------
	 * Rate Limiting
	 *------------------------------------------------------------*/

	/**
	 * Check if current user is rate limited.
	 *
	 * @return bool True if request is allowed, false if rate limited.
	 */
	public static function check_rate_limit(): bool {
		$user_id = get_current_user_id();
		$key     = 'xanh_ai_rate_' . $user_id;

		if ( get_transient( $key ) ) {
			return false;
		}

		set_transient( $key, true, self::RATE_LIMIT_SECONDS );
		return true;
	}

	/*--------------------------------------------------------------
	 * AJAX Validation
	 *------------------------------------------------------------*/

	/**
	 * Validate an AJAX request: nonce + capability check.
	 * Sends JSON error response and returns false on failure.
	 *
	 * @param string $nonce_action Nonce action name.
	 * @param string $capability  Required user capability.
	 * @return bool True if valid.
	 */
	public static function validate_ajax_request( string $nonce_action, string $capability = 'edit_posts' ): bool {
		if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
			wp_send_json_error( [
				'message' => __( 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.', 'xanh-ai-content' ),
			], 403 );
			return false;
		}

		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error( [
				'message' => __( 'Bạn không có quyền thực hiện thao tác này.', 'xanh-ai-content' ),
			], 403 );
			return false;
		}

		return true;
	}

	/*--------------------------------------------------------------
	 * Input Sanitization
	 *------------------------------------------------------------*/

	/**
	 * Sanitize common generator inputs.
	 *
	 * @param array $raw Raw POST data.
	 * @return array Sanitized data.
	 */
	public static function sanitize_generator_input( array $raw ): array {
		// Sanitize custom_links array if present.
		$custom_links = [];
		if ( ! empty( $raw['custom_links'] ) && is_array( $raw['custom_links'] ) ) {
			$custom_links = array_map( 'sanitize_text_field', $raw['custom_links'] );
			$custom_links = array_values( array_filter( $custom_links ) );
		}

		return [
			'topic'        => sanitize_text_field( wp_unslash( $raw['topic'] ?? '' ) ),
			'keyword'      => sanitize_text_field( wp_unslash( $raw['keyword'] ?? '' ) ),
			'secondary'    => sanitize_text_field( wp_unslash( $raw['secondary'] ?? '' ) ),
			'angle'        => sanitize_text_field( wp_unslash( $raw['angle'] ?? '' ) ),
			'length'       => sanitize_text_field( wp_unslash( $raw['length'] ?? 'standard' ) ),
			'notes'        => sanitize_textarea_field( wp_unslash( $raw['notes'] ?? '' ) ),
			'custom_links' => $custom_links,
		];
	}

	/*--------------------------------------------------------------
	 * Image Upload Validation
	 *------------------------------------------------------------*/

	/**
	 * Validate image mime type and size.
	 *
	 * @param string $mime MIME type.
	 * @param int    $size File size in bytes.
	 * @return bool True if valid.
	 */
	public static function validate_image_upload( string $mime, int $size ): bool {
		$allowed_mimes = [ 'image/png', 'image/jpeg', 'image/webp' ];

		if ( ! in_array( $mime, $allowed_mimes, true ) ) {
			return false;
		}

		// Max 5MB.
		if ( $size > 5 * MB_IN_BYTES ) {
			return false;
		}

		return true;
	}

	/*--------------------------------------------------------------
	 * Error Logging (debug only)
	 *------------------------------------------------------------*/

	/**
	 * Log an error message if WP_DEBUG is enabled.
	 * Never logs sensitive data like API keys.
	 *
	 * @param string $message Error message.
	 */
	public static function log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[XANH AI] ' . $message );
		}
	}
}
