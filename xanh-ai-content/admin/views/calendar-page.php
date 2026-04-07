<?php
/**
 * Content Calendar Page — Monthly overview of AI-generated posts.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap xanh-cal">
	<!-- Header -->
	<div class="xanh-cal-header">
		<div>
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Lịch Nội Dung', 'xanh-ai-content' ); ?>
			</h1>
			<p class="xanh-cal-subtitle">
				<?php esc_html_e( 'Theo dõi và quản lý lịch đăng bài viết.', 'xanh-ai-content' ); ?>
			</p>
		</div>
		<div class="xanh-cal-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=xanh-ai' ) ); ?>" class="button button-primary">
				<span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
				<?php esc_html_e( 'Tạo Bài Mới', 'xanh-ai-content' ); ?>
			</a>
		</div>
	</div>

	<!-- Filter Bar -->
	<div class="xanh-cal-filter">
		<div class="xanh-cal-filter-row">
			<label>
				<?php esc_html_e( 'Danh mục:', 'xanh-ai-content' ); ?>
				<select id="xanh-cal-filter-cat" class="xanh-dash-select">
					<option value="0"><?php esc_html_e( 'Tất cả', 'xanh-ai-content' ); ?></option>
					<?php
					$categories = get_categories( [ 'hide_empty' => false ] );
					foreach ( $categories as $cat ) :
					?>
						<option value="<?php echo esc_attr( $cat->term_id ); ?>">
							<?php echo esc_html( $cat->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<?php esc_html_e( 'Trạng thái:', 'xanh-ai-content' ); ?>
				<select id="xanh-cal-filter-status" class="xanh-dash-select">
					<option value=""><?php esc_html_e( 'Tất cả', 'xanh-ai-content' ); ?></option>
					<option value="publish"><?php esc_html_e( 'Đã đăng', 'xanh-ai-content' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Bản nháp', 'xanh-ai-content' ); ?></option>
					<option value="future"><?php esc_html_e( 'Hẹn giờ', 'xanh-ai-content' ); ?></option>
					<option value="pending"><?php esc_html_e( 'Chờ duyệt', 'xanh-ai-content' ); ?></option>
				</select>
			</label>
			<label>
				<input type="checkbox" id="xanh-cal-filter-ai">
				<?php esc_html_e( 'Chỉ bài AI', 'xanh-ai-content' ); ?>
			</label>
		</div>
	</div>

	<!-- Legend -->
	<div class="xanh-cal-legend">
		<span class="xanh-cal-legend-item">
			<span class="xanh-cal-legend-dot xanh-cal-status--publish"></span>
			<?php esc_html_e( 'Đã đăng', 'xanh-ai-content' ); ?>
		</span>
		<span class="xanh-cal-legend-item">
			<span class="xanh-cal-legend-dot xanh-cal-status--draft"></span>
			<?php esc_html_e( 'Bản nháp', 'xanh-ai-content' ); ?>
		</span>
		<span class="xanh-cal-legend-item">
			<span class="xanh-cal-legend-dot xanh-cal-status--future"></span>
			<?php esc_html_e( 'Hẹn giờ', 'xanh-ai-content' ); ?>
		</span>
		<span class="xanh-cal-legend-item">
			<span class="xanh-cal-legend-dot xanh-cal-status--pending"></span>
			<?php esc_html_e( 'Chờ duyệt', 'xanh-ai-content' ); ?>
		</span>
		<span class="xanh-cal-legend-item">
			<span class="xanh-cal-legend-dot xanh-cal-legend-dot--ai"></span>
			<?php esc_html_e( 'AI Generated', 'xanh-ai-content' ); ?>
		</span>
	</div>

	<!-- Stats summary -->
	<div id="xanh-cal-stats" class="xanh-cal-stats"></div>

	<!-- Month Navigation -->
	<div class="xanh-cal-nav">
		<button type="button" id="xanh-cal-prev" class="button">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
		</button>
		<h2 id="xanh-cal-month-title"></h2>
		<button type="button" id="xanh-cal-next" class="button">
			<span class="dashicons dashicons-arrow-right-alt2"></span>
		</button>
		<button type="button" id="xanh-cal-today" class="button" style="margin-left: 12px;">
			<?php esc_html_e( 'Hôm Nay', 'xanh-ai-content' ); ?>
		</button>
	</div>

	<!-- Calendar Grid -->
	<div class="xanh-cal-grid-wrapper">
		<!-- Day headers -->
		<div class="xanh-cal-day-headers">
			<div class="xanh-cal-day-header"><?php esc_html_e( 'T2', 'xanh-ai-content' ); ?></div>
			<div class="xanh-cal-day-header"><?php esc_html_e( 'T3', 'xanh-ai-content' ); ?></div>
			<div class="xanh-cal-day-header"><?php esc_html_e( 'T4', 'xanh-ai-content' ); ?></div>
			<div class="xanh-cal-day-header"><?php esc_html_e( 'T5', 'xanh-ai-content' ); ?></div>
			<div class="xanh-cal-day-header"><?php esc_html_e( 'T6', 'xanh-ai-content' ); ?></div>
			<div class="xanh-cal-day-header xanh-cal-day-header--weekend"><?php esc_html_e( 'T7', 'xanh-ai-content' ); ?></div>
			<div class="xanh-cal-day-header xanh-cal-day-header--weekend"><?php esc_html_e( 'CN', 'xanh-ai-content' ); ?></div>
		</div>
		<!-- Calendar cells rendered by JS -->
		<div id="xanh-cal-grid" class="xanh-cal-grid"></div>
	</div>
</div>
