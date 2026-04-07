<?php
/**
 * Meta Box: AI Image Generator — Simplified button to open popup.
 *
 * @package Xanh_AI_Content
 * @since   1.3.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="xanh-ai-img-box" class="xanh-ai-img-box">
	<p class="description" style="margin-bottom:10px;">
		<?php esc_html_e( 'Tạo ảnh minh họa bằng Gemini AI. Bôi đen đoạn văn trước để có ảnh chính xác hơn.', 'xanh-ai-content' ); ?>
	</p>

	<button type="button" id="xanh-ai-img-open-popup" class="button button-primary" style="width:100%;">
		<span class="dashicons dashicons-format-image" style="vertical-align:middle;"></span>
		<?php esc_html_e( 'Tạo Ảnh AI', 'xanh-ai-content' ); ?>
	</button>

	<p class="description" style="margin-top:8px; font-size:11px; color:#646970;">
		<?php esc_html_e( 'Hoặc dùng nút ✨ AI Ảnh trên thanh công cụ.', 'xanh-ai-content' ); ?>
	</p>
</div>
