<?php
/**
 * History Page — Generation history table with filters.
 *
 * @package Xanh_AI_Content
 * @since   1.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap xanh-ai-wrap">
	<h1 class="xanh-ai-page-title">
		<span class="dashicons dashicons-backup"></span>
		<?php esc_html_e( 'Lịch Sử Tạo Nội Dung', 'xanh-ai-content' ); ?>
	</h1>

	<!-- Stats Bar -->
	<div class="xanh-ai-stats-bar" id="xanh-ai-history-stats">
		<div class="xanh-ai-stat-card">
			<span class="xanh-ai-stat-value" id="stat-total">—</span>
			<span class="xanh-ai-stat-label"><?php esc_html_e( 'Tổng', 'xanh-ai-content' ); ?></span>
		</div>
		<div class="xanh-ai-stat-card">
			<span class="xanh-ai-stat-value" id="stat-success-rate">—</span>
			<span class="xanh-ai-stat-label"><?php esc_html_e( 'Thành công', 'xanh-ai-content' ); ?></span>
		</div>
		<div class="xanh-ai-stat-card">
			<span class="xanh-ai-stat-value" id="stat-avg-score">—</span>
			<span class="xanh-ai-stat-label"><?php esc_html_e( 'Điểm TB', 'xanh-ai-content' ); ?></span>
		</div>
		<div class="xanh-ai-stat-card">
			<span class="xanh-ai-stat-value" id="stat-tokens">—</span>
			<span class="xanh-ai-stat-label"><?php esc_html_e( 'Tokens', 'xanh-ai-content' ); ?></span>
		</div>
		<div class="xanh-ai-stat-card">
			<span class="xanh-ai-stat-value" id="stat-posts">—</span>
			<span class="xanh-ai-stat-label"><?php esc_html_e( 'Bài viết', 'xanh-ai-content' ); ?></span>
		</div>
		<div class="xanh-ai-stat-card">
			<span class="xanh-ai-stat-value" id="stat-images">—</span>
			<span class="xanh-ai-stat-label"><?php esc_html_e( 'Hình ảnh', 'xanh-ai-content' ); ?></span>
		</div>
		<div class="xanh-ai-stat-card">
			<span class="xanh-ai-stat-value" id="stat-suggests">&mdash;</span>
			<span class="xanh-ai-stat-label"><?php esc_html_e( 'Gợi ý', 'xanh-ai-content' ); ?></span>
		</div>
		<div class="xanh-ai-stat-card">
			<span class="xanh-ai-stat-value" id="stat-img-prompts">&mdash;</span>
			<span class="xanh-ai-stat-label"><?php esc_html_e( 'Gợi Ý Ảnh', 'xanh-ai-content' ); ?></span>
		</div>
	</div>

	<!-- Tabs + Filters -->
	<div class="xanh-ai-history-controls">
		<div class="xanh-ai-tabs" id="history-tabs">
			<button class="xanh-ai-tab active" data-type="">
				<?php esc_html_e( 'Tất Cả', 'xanh-ai-content' ); ?>
			</button>
			<button class="xanh-ai-tab" data-type="post">
				<span class="dashicons dashicons-admin-post"></span>
				<?php esc_html_e( 'Bài Viết', 'xanh-ai-content' ); ?>
			</button>
			<button class="xanh-ai-tab" data-type="image">
				<span class="dashicons dashicons-format-image"></span>
				<?php esc_html_e( 'Hình Ảnh', 'xanh-ai-content' ); ?>
			</button>
			<button class="xanh-ai-tab" data-type="suggest">
				<span class="dashicons dashicons-lightbulb"></span>
				<?php esc_html_e( 'Gợi Ý', 'xanh-ai-content' ); ?>
			</button>
			<button class="xanh-ai-tab" data-type="img_prompt">
				<span class="dashicons dashicons-camera"></span>
				<?php esc_html_e( 'Gợi Ý Ảnh', 'xanh-ai-content' ); ?>
			</button>
		</div>

		<div class="xanh-ai-filters">
			<select id="filter-status" class="xanh-ai-filter-select">
				<option value=""><?php esc_html_e( 'Trạng thái', 'xanh-ai-content' ); ?></option>
				<option value="success"><?php esc_html_e( 'Thành công', 'xanh-ai-content' ); ?></option>
				<option value="error"><?php esc_html_e( 'Lỗi', 'xanh-ai-content' ); ?></option>
			</select>

			<select id="filter-angle" class="xanh-ai-filter-select">
				<option value=""><?php esc_html_e( 'Góc viết', 'xanh-ai-content' ); ?></option>
				<!-- Populated by JS -->
			</select>

			<input type="date" id="filter-date-from" class="xanh-ai-filter-date"
			       placeholder="<?php esc_attr_e( 'Từ ngày', 'xanh-ai-content' ); ?>">
			<input type="date" id="filter-date-to" class="xanh-ai-filter-date"
			       placeholder="<?php esc_attr_e( 'Đến ngày', 'xanh-ai-content' ); ?>">

			<input type="text" id="filter-search" class="xanh-ai-filter-search"
			       placeholder="<?php esc_attr_e( 'Tìm kiếm...', 'xanh-ai-content' ); ?>">
		</div>
	</div>

	<!-- Table -->
	<div class="xanh-ai-history-table-wrap">
		<table class="xanh-ai-table" id="history-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Ngày', 'xanh-ai-content' ); ?></th>
					<th><?php esc_html_e( 'Loại', 'xanh-ai-content' ); ?></th>
					<th><?php esc_html_e( 'Topic / Prompt', 'xanh-ai-content' ); ?></th>
					<th><?php esc_html_e( 'Angle', 'xanh-ai-content' ); ?></th>
					<th><?php esc_html_e( 'Score', 'xanh-ai-content' ); ?></th>
					<th><?php esc_html_e( 'Tokens', 'xanh-ai-content' ); ?></th>
					<th><?php esc_html_e( 'Trạng thái', 'xanh-ai-content' ); ?></th>
					<th><?php esc_html_e( 'Thao tác', 'xanh-ai-content' ); ?></th>
				</tr>
			</thead>
			<tbody id="history-tbody">
				<tr class="xanh-ai-loading-row">
					<td colspan="8">
						<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>
						<?php esc_html_e( 'Đang tải...', 'xanh-ai-content' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Pagination -->
	<div class="xanh-ai-pagination" id="history-pagination"></div>

	<!-- Suggestion Details Modal -->
	<div id="xanh-ai-suggest-overlay" class="xanh-ai-modal-overlay" style="display:none;">
		<div id="xanh-ai-suggest-modal" class="xanh-ai-modal" style="max-width: 600px;">
			<span class="xanh-ai-modal-close dashicons dashicons-no-alt" id="xanh-ai-suggest-close" style="float:right; cursor:pointer;"></span>
			<h2 style="margin-top:0; border-bottom:1px solid #ddd; padding-bottom:10px;">
				<?php esc_html_e( 'Chi tiết Gợi ý Từ Khóa', 'xanh-ai-content' ); ?>
			</h2>
			<div class="xanh-ai-suggest-grid" style="display:grid; grid-template-columns:120px 1fr; gap:10px; margin-bottom:15px; background:#f9f9f9; padding:15px; border-radius:4px;">
				<strong>Topic:</strong> <span id="modal-suggest-topic"></span>
				<strong>Angle:</strong> <span id="modal-suggest-angle"></span>
				<strong>Keywords:</strong> <span id="modal-suggest-seed"></span>
			</div>
			<h3 style="margin-top:0; margin-bottom:10px;"><?php esc_html_e( 'Từ khóa AI gợi ý:', 'xanh-ai-content' ); ?></h3>
			<ul id="modal-suggest-list" class="xanh-ai-suggest-list" style="columns: 2; margin:0; padding-left:20px; color:#2271b1; font-weight:600;"></ul>
		</div>
	</div>

	<!-- Image Preview Modal -->
	<div id="xanh-ai-image-overlay" class="xanh-ai-modal-overlay" style="display:none;">
		<div id="xanh-ai-image-modal" class="xanh-ai-modal" style="max-width: 800px; text-align:center;">
			<span class="xanh-ai-modal-close dashicons dashicons-no-alt" id="xanh-ai-image-close" style="float:right; cursor:pointer;"></span>
			<h2 style="margin-top:0; border-bottom:1px solid #ddd; padding-bottom:10px; text-align:left;">
				<?php esc_html_e( 'Chi tiết Hình Ảnh', 'xanh-ai-content' ); ?>
			</h2>
			<div class="xanh-ai-image-details" style="display:grid; grid-template-columns:80px 1fr; gap:10px; margin-bottom:15px; background:#f9f9f9; padding:15px; border-radius:4px; text-align:left;">
				<strong>Prompt:</strong> <span id="modal-image-prompt" style="font-family: monospace; white-space: pre-wrap;"></span>
			</div>
			<img id="modal-image-preview" src="" alt="Image Preview" style="max-width:100%; max-height: 60vh; height:auto; border-radius:4px; box-shadow:0 4px 12px rgba(0,0,0,0.1);" />
		</div>
	</div>

	<!-- AI Image Prompt Detail Modal -->
	<div id="xanh-ai-imgprompt-overlay" class="xanh-ai-modal-overlay" style="display:none;">
		<div id="xanh-ai-imgprompt-modal" class="xanh-ai-modal" style="max-width: 640px;">
			<span class="xanh-ai-modal-close dashicons dashicons-no-alt" id="xanh-ai-imgprompt-close" style="float:right; cursor:pointer;"></span>
			<h2 style="margin-top:0; border-bottom:1px solid #ddd; padding-bottom:10px;">
				<span class="dashicons dashicons-camera" style="color:#2271b1; margin-right:6px;"></span>
				<?php esc_html_e( 'Chi tiết Gợi Ý Ảnh AI', 'xanh-ai-content' ); ?>
			</h2>
			<div style="display:grid; grid-template-columns:130px 1fr; gap:10px; margin-bottom:15px; background:#f0f7ff; padding:15px; border-radius:6px;">
				<strong><?php esc_html_e( 'Bài viết:', 'xanh-ai-content' ); ?></strong>
				<span id="modal-imgprompt-post"></span>
				<strong><?php esc_html_e( 'Thời gian:', 'xanh-ai-content' ); ?></strong>
				<span id="modal-imgprompt-date"></span>
				<strong><?php esc_html_e( 'Đoạn văn:', 'xanh-ai-content' ); ?></strong>
				<span id="modal-imgprompt-text" style="font-style:italic; color:#1d2327;"></span>
				<strong><?php esc_html_e( 'Model:', 'xanh-ai-content' ); ?></strong>
				<span id="modal-imgprompt-model" style="font-family:monospace;"></span>
				<strong><?php esc_html_e( 'Input Tokens:', 'xanh-ai-content' ); ?></strong>
				<span id="modal-imgprompt-input"></span>
				<strong><?php esc_html_e( 'Output Tokens:', 'xanh-ai-content' ); ?></strong>
				<span id="modal-imgprompt-output"></span>
			</div>
			<h3 style="margin-top:0; margin-bottom:8px;">
				<span class="dashicons dashicons-format-image" style="color:#7c3aed; margin-right:4px;"></span>
				<?php esc_html_e( 'Prompt ảnh đã tạo:', 'xanh-ai-content' ); ?>
			</h3>
			<div id="modal-imgprompt-result" style="background:#f9f9f9; padding:12px 15px; border-radius:6px; font-family:monospace; font-size:12px; line-height:1.6; white-space:pre-wrap; border:1px solid #dcdcde;">
			</div>
		</div>
	</div>

</div>
