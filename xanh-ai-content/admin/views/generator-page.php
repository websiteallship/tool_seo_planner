<?php
/**
 * Generator Page — 5-step content creation flow.
 *
 * Step 1: Angle selector (8 cards)
 * Step 2: Topic/keyword form
 * Step 3: Generate button + progress
 * Step 4: Preview with inline editing + score
 * Step 5: Save Draft
 *
 * @package Xanh_AI_Content
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check API key.
$has_key = ! empty( Xanh_AI_Security::decrypt_key( get_option( 'xanh_ai_gemini_key', '' ) ) );
$angles  = Xanh_AI_Angles::get_all();
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'XANH AI — Tạo Bài Viết', 'xanh-ai-content' ); ?>
	</h1>

	<?php if ( ! $has_key ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'Chưa cấu hình API Key.', 'xanh-ai-content' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=xanh-ai-settings' ) ); ?>">
					<?php esc_html_e( 'Đi đến Cài Đặt →', 'xanh-ai-content' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<!-- ═══ RECOVERY BANNER ═══ -->
	<div id="xanh-ai-recovery-banner" class="notice notice-info" style="display:none; padding: 12px;">
		<p style="margin:0; display:flex; align-items:center; gap:10px;">
			<strong style="font-size: 14px;">Hệ thống phát hiện bạn có nội dung chưa được lưu từ phiên làm việc trước.</strong>
			<button type="button" class="button button-primary" id="xanh-ai-btn-restore"><span class="dashicons dashicons-backup" style="vertical-align: middle;"></span> Khôi Phục Lại</button>
			<button type="button" class="button" id="xanh-ai-btn-discard"><span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> Bỏ Qua</button>
		</p>
	</div>

	<!-- ═══ STEP 1: Angle Selector ═══ -->
	<div id="xanh-ai-step-angle" class="xanh-ai-step">
		<h2><?php esc_html_e( 'Bước 1: Chọn Góc Viết', 'xanh-ai-content' ); ?></h2>
		<div class="xanh-ai-angle-grid">
			<?php foreach ( $angles as $id => $angle ) : ?>
				<div class="xanh-ai-angle-card" data-angle-id="<?php echo esc_attr( $id ); ?>">
					<span class="dashicons <?php echo esc_attr( $angle['icon'] ); ?> xanh-ai-angle-icon"></span>
					<h3><?php echo esc_html( $angle['label'] ); ?></h3>
					<p class="xanh-ai-angle-tone"><?php echo esc_html( $angle['tone'] ); ?></p>
					<p class="xanh-ai-angle-words"><?php printf( esc_html__( 'Min %d từ', 'xanh-ai-content' ), $angle['min_words'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- ═══ STEP 2: Topic/Keyword Form ═══ -->
	<div id="xanh-ai-step-form" class="xanh-ai-step" style="display:none;">
		<h2><?php esc_html_e( 'Bước 2: Nhập Thông Tin', 'xanh-ai-content' ); ?></h2>

		<div id="xanh-ai-selected-angle" class="xanh-ai-selected-angle"></div>

		<table class="form-table">
			<tr>
				<th><label for="xanh-ai-topic"><?php esc_html_e( 'Chủ Đề', 'xanh-ai-content' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" id="xanh-ai-topic" class="large-text" placeholder="VD: Chi Phí Xây Nhà Phố 2026" required>
				</td>
			</tr>
			<tr>
				<th><label for="xanh-ai-keyword"><?php esc_html_e( 'Từ Khóa Chính', 'xanh-ai-content' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" id="xanh-ai-keyword" class="regular-text" placeholder="VD: chi phí xây nhà phố" required>
					<div id="xanh-ai-keyword-suggestions" class="xanh-ai-keyword-suggestions"></div>
				</td>
			</tr>
			<tr>
				<th><label for="xanh-ai-secondary"><?php esc_html_e( 'Từ Khóa Phụ', 'xanh-ai-content' ); ?></label></th>
				<td>
					<input type="text" id="xanh-ai-secondary" class="large-text" placeholder="VD: giá xây nhà, dự toán xây dựng">
					<p class="description"><?php esc_html_e( 'Phân cách bằng dấu phẩy.', 'xanh-ai-content' ); ?></p>
					<div id="xanh-ai-secondary-suggestions" style="margin-top: 10px;"></div>
				</td>
			</tr>
			<tr>
				<th><label for="xanh-ai-length"><?php esc_html_e( 'Độ Dài', 'xanh-ai-content' ); ?></label></th>
				<td>
					<select id="xanh-ai-length">
						<option value="test"><?php esc_html_e( 'Test (200-400 từ)', 'xanh-ai-content' ); ?></option>
						<option value="standard"><?php esc_html_e( 'Tiêu chuẩn (800-1200 từ)', 'xanh-ai-content' ); ?></option>
						<option value="long"><?php esc_html_e( 'Dài (1500-2000 từ)', 'xanh-ai-content' ); ?></option>
						<option value="guide"><?php esc_html_e( 'Hướng dẫn (2000+ từ)', 'xanh-ai-content' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="xanh-ai-notes"><?php esc_html_e( 'Ghi Chú', 'xanh-ai-content' ); ?></label></th>
				<td>
					<textarea id="xanh-ai-notes" class="large-text" rows="3" placeholder="VD: Focus vào Nha Trang, data Q1/2026"></textarea>
				</td>
			</tr>
		</table>

		<!-- Internal Links Checkboxes (populated by JS based on selected angle) -->
		<div id="xanh-ai-internal-links" class="xanh-ai-internal-links" style="margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
			<h4 style="margin: 0 0 10px; font-size: 13px; color: #1d2327;">
				<span class="dashicons dashicons-admin-links" style="vertical-align: middle;"></span> <?php esc_html_e( 'Internal Links', 'xanh-ai-content' ); ?>
				<span class="description" style="font-weight: normal; margin-left: 8px;"><?php esc_html_e( '(tự động chèn khi tạo bài)', 'xanh-ai-content' ); ?></span>
			</h4>
			<div id="xanh-ai-link-checkboxes"></div>
			<p class="description" style="margin-top: 8px; font-style: italic;">
				<?php esc_html_e( 'Bài viết liên quan sẽ được tự động gợi ý theo danh mục.', 'xanh-ai-content' ); ?>
			</p>
		</div>

		<p class="submit">
			<button type="button" id="xanh-ai-btn-back" class="button">
				<?php esc_html_e( '« Chọn Lại Góc Viết', 'xanh-ai-content' ); ?>
			</button>
			<button type="button" id="xanh-ai-btn-preview-prompt" class="button button-primary" <?php disabled( ! $has_key ); ?>>
				<?php esc_html_e( 'Xem Prompt →', 'xanh-ai-content' ); ?>
			</button>
		</p>
	</div>

	<!-- ═══ STEP 2.5: Prompt Preview & Edit ═══ -->
	<div id="xanh-ai-step-prompt" class="xanh-ai-step" style="display:none;">
		<h2><?php esc_html_e( 'Xem & Sửa Prompt', 'xanh-ai-content' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Đây là toàn bộ prompt sẽ gửi cho Gemini. Bạn có thể chỉnh sửa tự do trước khi tạo bài viết.', 'xanh-ai-content' ); ?>
		</p>
		<p>
			<span id="xanh-ai-token-estimate" class="description"></span>
		</p>
		<textarea id="xanh-ai-prompt-editor" class="large-text" rows="25" style="font-family:monospace; font-size:13px; line-height:1.5;"></textarea>

		<p class="submit">
			<button type="button" id="xanh-ai-btn-back-to-form" class="button">
				<?php esc_html_e( '« Sửa Thông Tin', 'xanh-ai-content' ); ?>
			</button>
			<button type="button" id="xanh-ai-btn-generate" class="button button-primary" <?php disabled( ! $has_key ); ?>>
				<?php esc_html_e( 'Tạo Nội Dung', 'xanh-ai-content' ); ?>
			</button>
		</p>

		<!-- Progress -->
		<div id="xanh-ai-progress" class="xanh-ai-progress" style="display:none;">
			<span class="spinner is-active"></span>
			<span id="xanh-ai-progress-text"></span>
		</div>
	</div>

	<!-- ═══ STEP 3: Preview + Edit ═══ -->
	<div id="xanh-ai-step-preview" class="xanh-ai-step" style="display:none;">
		<h2><?php esc_html_e( 'Bước 3: Xem Trước & Chỉnh Sửa', 'xanh-ai-content' ); ?></h2>

		<!-- Score Badge -->
		<div id="xanh-ai-score-badge" class="xanh-ai-score-badge"></div>

		<!-- Token Usage Bar -->
		<div id="xanh-ai-token-usage" class="xanh-ai-token-usage" style="display:none;"></div>

		<!-- Title -->
		<div class="xanh-ai-preview-field">
			<label><?php esc_html_e( 'Tiêu Đề', 'xanh-ai-content' ); ?></label>
			<input type="text" id="xanh-ai-preview-title" class="large-text">
			<span class="xanh-ai-char-count" data-max="60"></span>
		</div>

		<!-- Meta Description -->
		<div class="xanh-ai-preview-field">
			<label><?php esc_html_e( 'Meta Description', 'xanh-ai-content' ); ?></label>
			<textarea id="xanh-ai-preview-meta" class="large-text" rows="2"></textarea>
			<span class="xanh-ai-char-count" data-max="160"></span>
		</div>

		<!-- Score Breakdown -->
		<div id="xanh-ai-score-details" class="xanh-ai-score-details"></div>

		<!-- Content Preview -->
		<div class="xanh-ai-preview-field">
			<label><?php esc_html_e( 'Nội Dung', 'xanh-ai-content' ); ?></label>
			<div id="xanh-ai-preview-content" class="xanh-ai-preview-content"></div>
		</div>

		<!-- Featured Image -->
		<div class="xanh-ai-preview-field">
			<label><?php esc_html_e( 'Ảnh Đại Diện', 'xanh-ai-content' ); ?></label>
			<div id="xanh-ai-preview-image" class="xanh-ai-preview-image">
				<p class="description"><?php esc_html_e( 'Ảnh sẽ được tạo tự động khi lưu draft.', 'xanh-ai-content' ); ?></p>
			</div>
			<button type="button" id="xanh-ai-btn-gen-image" class="button">
				<span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Tạo Ảnh Ngay', 'xanh-ai-content' ); ?>
			</button>
		</div>

		<!-- Tags -->
		<div class="xanh-ai-preview-field">
			<label><?php esc_html_e( 'Tags', 'xanh-ai-content' ); ?></label>
			<input type="text" id="xanh-ai-preview-tags" class="large-text">
			<p class="description"><?php esc_html_e( 'Phân cách bằng dấu phẩy.', 'xanh-ai-content' ); ?></p>
		</div>

		<!-- FAQ -->
		<div id="xanh-ai-preview-faq" class="xanh-ai-preview-faq"></div>

		<!-- Actions -->
		<p class="submit">
			<button type="button" id="xanh-ai-btn-back-form" class="button">
				<?php esc_html_e( '« Quay Lại Form', 'xanh-ai-content' ); ?>
			</button>
			<button type="button" id="xanh-ai-btn-save-draft" class="button button-primary">
				<?php esc_html_e( 'Lưu Draft', 'xanh-ai-content' ); ?>
			</button>
		</p>

		<!-- Save Result -->
		<div id="xanh-ai-save-result" class="xanh-ai-save-result" style="display:none;"></div>
	</div>

	<!-- Hidden data store -->
	<input type="hidden" id="xanh-ai-data-angle-id" value="">
	<input type="hidden" id="xanh-ai-data-image-prompt" value="">
	<input type="hidden" id="xanh-ai-data-faq" value="[]">
	<input type="hidden" id="xanh-ai-data-score" value="{}">
	<input type="hidden" id="xanh-ai-data-tokens" value="0">
	<input type="hidden" id="xanh-ai-data-excerpt" value="">
	<input type="hidden" id="xanh-ai-data-slug" value="">
</div>
