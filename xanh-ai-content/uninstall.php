<?php
/**
 * Uninstall — clean up all plugin data.
 *
 * Runs when user deletes the plugin via WP Admin.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access — only WP uninstall should call this.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*--------------------------------------------------------------
 * 1. Remove all plugin options
 *------------------------------------------------------------*/
$options = [
	'xanh_ai_gemini_key',
	'xanh_ai_text_model',
	'xanh_ai_image_model',
	'xanh_ai_image_aspect',
	'xanh_ai_image_size',
	'xanh_ai_auto_image',
	'xanh_ai_default_author',
	'xanh_ai_temperature',
	'xanh_ai_schedule_frequency',
	'xanh_ai_schedule_time',
	'xanh_ai_version',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Clean up dynamic token usage tracking options (xanh_ai_usage_YYYY_MM)
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		'xanh_ai_usage_%'
	)
);

/*--------------------------------------------------------------
 * 2. Drop custom tables
 *------------------------------------------------------------*/
global $wpdb;

$tables = [
	$wpdb->prefix . 'xanh_ai_history',
	$wpdb->prefix . 'xanh_ai_sources',
];

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/*--------------------------------------------------------------
 * 3. Remove post meta
 *------------------------------------------------------------*/
$meta_keys = [
	'_xanh_ai_generated',
	'_xanh_ai_angle',
	'_xanh_ai_score',
	'_xanh_ai_tokens',
	'_xanh_ai_sources',
	'_xanh_ai_schema_type',
	'_xanh_ai_backlinks_injected',
	'_xanh_ai_last_refreshed',
];

foreach ( $meta_keys as $meta_key ) {
	delete_post_meta_by_key( $meta_key );
}

/*--------------------------------------------------------------
 * 4. Clear transients
 *------------------------------------------------------------*/
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%xanh\_ai\_%'"
);
