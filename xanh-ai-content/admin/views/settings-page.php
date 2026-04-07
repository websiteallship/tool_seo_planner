<?php
/**
 * Settings page template.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e('XANH AI — Cài Đặt', 'xanh-ai-content'); ?></h1>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
settings_fields('xanh_ai_settings');
do_settings_sections('xanh-ai-settings');
submit_button(__('Lưu Cài Đặt', 'xanh-ai-content'));
?>
	</form>

	<!-- Plugin Info -->
	<div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #ccd0d4;">
		<p>
			<strong>XANH AI Content Generator</strong> v<?php echo esc_html(XANH_AI_VERSION); ?>
			· <?php esc_html_e('Powered by Allship Solution', 'xanh-ai-content'); ?>
		</p>
		<p class="description">
			<?php esc_html_e('Plugin tự động tạo bài viết blog + hình ảnh cho XANH Design & Build.', 'xanh-ai-content'); ?>
		</p>
	</div>
</div>
