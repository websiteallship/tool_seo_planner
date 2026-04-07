<?php
/**
 * Plugin Name:       XANH AI Content Generator
 * Plugin URI:        https://xanhdesignbuild.com
 * Description:       AI-powered content & image generator for XANH Design & Build. Uses Google Gemini API with Warm Luxury brand voice.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            XANH Design & Build
 * Author URI:        https://xanhdesignbuild.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       xanh-ai-content
 * Domain Path:       /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*--------------------------------------------------------------
 * Constants
 *------------------------------------------------------------*/
define( 'XANH_AI_VERSION', '1.2.0' );
define( 'XANH_AI_FILE', __FILE__ );
define( 'XANH_AI_DIR', plugin_dir_path( __FILE__ ) );
define( 'XANH_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'XANH_AI_BASENAME', plugin_basename( __FILE__ ) );

/*--------------------------------------------------------------
 * Autoloader — load classes from includes/ and admin/
 *------------------------------------------------------------*/
spl_autoload_register( function ( string $class_name ): void {
	// Only autoload our classes.
	if ( strpos( $class_name, 'Xanh_AI_' ) !== 0 ) {
		return;
	}

	// Convert class name to filename: Xanh_AI_Settings → class-xanh-ai-settings.php
	$file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

	// Search in includes/ first, then admin/.
	$paths = [
		XANH_AI_DIR . 'includes/' . $file,
		XANH_AI_DIR . 'admin/' . $file,
	];

	foreach ( $paths as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
} );

/*--------------------------------------------------------------
 * Activation hook
 *------------------------------------------------------------*/
register_activation_hook( __FILE__, function (): void {
	// Set default options if not already set.
	$defaults = [
		'xanh_ai_text_model'    => 'gemini-2.5-flash',
		'xanh_ai_image_model'   => 'gemini-3.1-flash-image-preview',
		'xanh_ai_temperature'   => 0.7,
		'xanh_ai_image_aspect'  => '16:9',
		'xanh_ai_image_size'    => '2K',
		'xanh_ai_auto_image'    => 1,
		'xanh_ai_default_author' => get_current_user_id(),
	];

	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( $key ) ) {
			add_option( $key, $value );
		}
	}

	// Create / update custom tables.
	require_once __DIR__ . '/includes/class-xanh-ai-history.php';
	Xanh_AI_History::create_table();

	// Store plugin version for future migrations.
	update_option( 'xanh_ai_version', XANH_AI_VERSION );

	// Flush rewrite rules.
	flush_rewrite_rules();
} );

/*--------------------------------------------------------------
 * Deactivation hook
 *------------------------------------------------------------*/
register_deactivation_hook( __FILE__, function (): void {
	// Clear any scheduled cron events.
	wp_clear_scheduled_hook( 'xanh_ai_batch_cron' );
	wp_clear_scheduled_hook( 'xanh_ai_schedule_cron' );
	wp_clear_scheduled_hook( 'xanh_ai_updater_cron' );

	// Clear all rate limit transients.
	global $wpdb;
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_xanh_ai_rate_%' OR option_name LIKE '_transient_timeout_xanh_ai_rate_%'"
	);

	flush_rewrite_rules();
} );

/*--------------------------------------------------------------
 * Initialize plugin on plugins_loaded
 *------------------------------------------------------------*/
add_action( 'plugins_loaded', function (): void {
	// Check minimum PHP version.
	if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
		add_action( 'admin_notices', function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'XANH AI Content Generator yêu cầu PHP 7.4 trở lên.', 'xanh-ai-content' )
			);
		} );
		return;
	}

	// Check openssl extension (needed for AES-256 encryption).
	if ( ! extension_loaded( 'openssl' ) ) {
		add_action( 'admin_notices', function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'XANH AI Content Generator yêu cầu PHP OpenSSL extension.', 'xanh-ai-content' )
			);
		} );
		return;
	}

	// Auto-migrate deprecated model IDs (gemini-2.0-flash → gemini-2.5-flash).
	$deprecated_text = [ 'gemini-2.0-flash', 'gemini-2.0-flash-001', 'gemini-1.5-flash', 'gemini-1.5-pro' ];
	$current_model   = get_option( 'xanh_ai_text_model', '' );
	if ( in_array( $current_model, $deprecated_text, true ) ) {
		update_option( 'xanh_ai_text_model', 'gemini-2.5-flash' );
	}

	// Version-based migration: create/update tables when version changes.
	$stored_version = get_option( 'xanh_ai_version', '0' );
	if ( version_compare( $stored_version, XANH_AI_VERSION, '<' ) ) {
		Xanh_AI_History::create_table();
		update_option( 'xanh_ai_version', XANH_AI_VERSION );
	}

	// Boot the plugin.
	Xanh_AI_Content::instance();
} );
