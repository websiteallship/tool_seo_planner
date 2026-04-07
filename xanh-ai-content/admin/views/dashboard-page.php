<?php
/**
 * Admin view: Token Usage Dashboard — Premium Analytics.
 *
 * @package Xanh_AI_Content
 * @since   1.2.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*--------------------------------------------------------------
 * Date Range Logic — Presets
 *------------------------------------------------------------*/
$now       = new DateTime( current_time( 'Y-m-d' ) );
$preset    = sanitize_text_field( $_GET['preset'] ?? 'this_month' );
$custom_from = sanitize_text_field( $_GET['from'] ?? '' );
$custom_to   = sanitize_text_field( $_GET['to'] ?? '' );

// Calculate start/end based on preset.
switch ( $preset ) {
	case 'today':
		$start_date = $now->format( 'Y-m-d' );
		$end_date   = $now->format( 'Y-m-d' );
		$range_label = 'Hôm nay';
		break;

	case 'yesterday':
		$yesterday  = ( clone $now )->modify( '-1 day' );
		$start_date = $yesterday->format( 'Y-m-d' );
		$end_date   = $yesterday->format( 'Y-m-d' );
		$range_label = 'Hôm qua';
		break;

	case 'last_7':
		$start_date = ( clone $now )->modify( '-6 days' )->format( 'Y-m-d' );
		$end_date   = $now->format( 'Y-m-d' );
		$range_label = '7 ngày qua';
		break;

	case 'this_week':
		// Monday = start of week.
		$dow        = (int) $now->format( 'N' ); // 1=Mon, 7=Sun
		$start_date = ( clone $now )->modify( '-' . ( $dow - 1 ) . ' days' )->format( 'Y-m-d' );
		$end_date   = $now->format( 'Y-m-d' );
		$range_label = 'Tuần này';
		break;

	case 'this_month':
		$start_date = $now->format( 'Y-m-01' );
		$end_date   = $now->format( 'Y-m-d' );
		$range_label = 'Tháng này';
		break;

	case 'last_month':
		$last_month = ( clone $now )->modify( 'first day of last month' );
		$start_date = $last_month->format( 'Y-m-01' );
		$end_date   = $last_month->format( 'Y-m-t' );
		$range_label = 'Tháng trước';
		break;

	case 'this_year':
		$start_date = $now->format( 'Y-01-01' );
		$end_date   = $now->format( 'Y-m-d' );
		$range_label = 'Năm nay';
		break;

	case 'custom':
		$start_date  = $custom_from ?: $now->format( 'Y-m-01' );
		$end_date    = $custom_to ?: $now->format( 'Y-m-d' );
		$range_label = $start_date . ' → ' . $end_date;
		break;

	default:
		$start_date  = $now->format( 'Y-m-01' );
		$end_date    = $now->format( 'Y-m-d' );
		$range_label = 'Tháng này';
		$preset      = 'this_month';
		break;
}

/*--------------------------------------------------------------
 * Fetch Data — Now unified from xanh_ai_history table
 *------------------------------------------------------------*/
$range_data   = Xanh_AI_History::get_dashboard_range( $start_date, $end_date );
$models       = $range_data['models'] ?? [];
$cost_data    = Xanh_AI_Pricing::estimate_cost( $models );
$stats_data   = Xanh_AI_History::get_dashboard_summary( 6 );
$content_stats = $range_data['content_stats'] ?? [];

// Aggregate totals.
$total_api_calls = $range_data['total_api_calls'] ?? 0;
$total_input     = 0;
$total_output    = 0;
$total_images    = 0;

foreach ( $models as $m ) {
	$total_input  += $m['text_input'] ?? 0;
	$total_output += $m['text_output'] ?? 0;
	$total_images += $m['image_calls'] ?? 0;
}

$grand_total_tokens = $total_input + $total_output;
$avg_per_request    = $total_api_calls > 0 ? round( $grand_total_tokens / $total_api_calls ) : 0;

// Daily data for chart.
$daily_data   = $range_data['daily'] ?? [];
$chart_labels = [];
$chart_input  = [];
$chart_output = [];
$chart_images = [];

// Build chart data — iterate each day in range.
$cursor = new DateTime( $start_date );
$end_dt = new DateTime( $end_date );
while ( $cursor <= $end_dt ) {
	$key = $cursor->format( 'Y-m-d' );
	$chart_labels[] = $cursor->format( 'd/m' );
	$chart_input[]  = $daily_data[ $key ]['text_input'] ?? 0;
	$chart_output[] = $daily_data[ $key ]['text_output'] ?? 0;
	$chart_images[] = $daily_data[ $key ]['image_calls'] ?? 0;
	$cursor->modify( '+1 day' );
}

// Base URL for preset links.
$base_url = admin_url( 'admin.php?page=xanh-ai-dashboard' );

// Presets.
$presets = [
	'today'      => 'Hôm nay',
	'yesterday'  => 'Hôm qua',
	'last_7'     => '7 ngày qua',
	'this_week'  => 'Tuần này',
	'this_month' => 'Tháng này',
	'last_month' => 'Tháng trước',
	'this_year'  => 'Năm nay',
	'custom'     => 'Tuỳ chỉnh',
];

?>
<div class="wrap xanh-ai-wrap xanh-dash">
	<div class="xanh-dash-header">
		<div>
			<h1><?php esc_html_e( 'AI Analytics Dashboard', 'xanh-ai-content' ); ?></h1>
			<p class="xanh-dash-subtitle">
				<?php echo esc_html( $range_label ); ?>
				<span style="color:#8c8f94;margin-left:4px;">(<?php echo esc_html( $start_date . '  —  ' . $end_date ); ?>)</span>
			</p>
		</div>
		<div class="xanh-dash-actions">
			<button type="button" id="xanh-dash-export" class="button">
				<span class="dashicons dashicons-download" style="margin-top:3px;margin-right:2px;"></span>
				<?php esc_html_e( 'Export CSV', 'xanh-ai-content' ); ?>
			</button>
			<button type="button" id="xanh-dash-reset" class="button" style="color:#d63638;">
				<span class="dashicons dashicons-trash" style="margin-top:3px;margin-right:2px;"></span>
				<?php esc_html_e( 'Reset', 'xanh-ai-content' ); ?>
			</button>
		</div>
	</div>

	<!-- ═══ DATE RANGE FILTER ═══ -->
	<div class="xanh-dash-filter">
		<div class="xanh-filter-presets">
			<?php foreach ( $presets as $key => $label ) : ?>
				<?php if ( 'custom' === $key ) : ?>
					<button type="button"
						class="xanh-filter-btn<?php echo 'custom' === $preset ? ' active' : ''; ?>"
						id="xanh-filter-custom-toggle"
					>
						<span class="dashicons dashicons-calendar" style="font-size:14px;width:14px;height:14px;margin-right:3px;"></span>
						<?php echo esc_html( $label ); ?>
					</button>
				<?php else : ?>
					<a href="<?php echo esc_url( add_query_arg( 'preset', $key, $base_url ) ); ?>"
					   class="xanh-filter-btn<?php echo $key === $preset ? ' active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<div class="xanh-filter-custom" id="xanh-filter-custom" style="display:<?php echo 'custom' === $preset ? 'flex' : 'none'; ?>;">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="xanh-filter-custom-form">
				<input type="hidden" name="page" value="xanh-ai-dashboard">
				<input type="hidden" name="preset" value="custom">
				<label>Từ <input type="date" name="from" value="<?php echo esc_attr( $start_date ); ?>" max="<?php echo esc_attr( $now->format( 'Y-m-d' ) ); ?>"></label>
				<label>Đến <input type="date" name="to" value="<?php echo esc_attr( $end_date ); ?>" max="<?php echo esc_attr( $now->format( 'Y-m-d' ) ); ?>"></label>
				<button type="submit" class="button button-primary" style="padding:2px 14px;">Áp dụng</button>
			</form>
		</div>
	</div>

	<!-- ═══ STAT CARDS ═══ -->
	<div class="xanh-dash-cards">
		<div class="xanh-card xanh-card--tokens">
			<div class="xanh-card-icon"><span class="dashicons dashicons-editor-code"></span></div>
			<div class="xanh-card-body">
				<span class="xanh-card-label"><?php esc_html_e( 'Tổng Token', 'xanh-ai-content' ); ?></span>
				<span class="xanh-card-value"><?php echo esc_html( number_format_i18n( $grand_total_tokens ) ); ?></span>
				<span class="xanh-card-sub">
					<?php echo esc_html( number_format_i18n( $total_input ) ); ?> in
					+ <?php echo esc_html( number_format_i18n( $total_output ) ); ?> out
				</span>
			</div>
		</div>

		<div class="xanh-card xanh-card--requests">
			<div class="xanh-card-icon"><span class="dashicons dashicons-rest-api"></span></div>
			<div class="xanh-card-body">
				<span class="xanh-card-label"><?php esc_html_e( 'API Requests', 'xanh-ai-content' ); ?></span>
				<span class="xanh-card-value"><?php echo esc_html( number_format_i18n( $total_api_calls ) ); ?></span>
				<span class="xanh-card-sub"><?php esc_html_e( 'Tất cả models', 'xanh-ai-content' ); ?></span>
			</div>
		</div>

		<div class="xanh-card xanh-card--images">
			<div class="xanh-card-icon"><span class="dashicons dashicons-format-image"></span></div>
			<div class="xanh-card-body">
				<span class="xanh-card-label"><?php esc_html_e( 'Hình Ảnh AI', 'xanh-ai-content' ); ?></span>
				<span class="xanh-card-value"><?php echo esc_html( number_format_i18n( $total_images ) ); ?></span>
				<span class="xanh-card-sub"><?php esc_html_e( 'Gemini Image Generation', 'xanh-ai-content' ); ?></span>
			</div>
		</div>

		<div class="xanh-card xanh-card--cost">
			<div class="xanh-card-icon"><span class="dashicons dashicons-chart-area"></span></div>
			<div class="xanh-card-body">
				<span class="xanh-card-label"><?php esc_html_e( 'Chi Phí Ước Tính', 'xanh-ai-content' ); ?></span>
				<span class="xanh-card-value">
					<?php
					if ( $cost_data['total_vnd'] > 0 ) {
						echo esc_html( number_format_i18n( $cost_data['total_vnd'] ) . ' ₫' );
					} else {
						echo '0 ₫ <small style="color:#00a32a">(Free tier)</small>';
					}
					?>
				</span>
				<span class="xanh-card-sub">
					$<?php echo esc_html( number_format( $cost_data['total_usd'], 4 ) ); ?> USD
				</span>
			</div>
		</div>

		<div class="xanh-card xanh-card--avg">
			<div class="xanh-card-icon"><span class="dashicons dashicons-performance"></span></div>
			<div class="xanh-card-body">
				<span class="xanh-card-label"><?php esc_html_e( 'Trung Bình / Request', 'xanh-ai-content' ); ?></span>
				<span class="xanh-card-value"><?php echo esc_html( number_format_i18n( $avg_per_request ) ); ?></span>
				<span class="xanh-card-sub"><?php esc_html_e( 'tokens/request', 'xanh-ai-content' ); ?></span>
			</div>
		</div>
	</div>

	<!-- ═══ DAILY USAGE CHART ═══ -->
	<div class="xanh-dash-section">
		<div class="xanh-dash-section-header">
			<h2>
				<span class="dashicons dashicons-chart-bar" style="margin-right:6px;color:#2271b1;"></span>
				<?php
				printf( esc_html__( 'Token Usage — %s', 'xanh-ai-content' ), esc_html( $range_label ) );
				?>
			</h2>
		</div>
		<div class="xanh-dash-chart-wrap">
			<canvas id="xanh-daily-chart" height="280"></canvas>
		</div>
	</div>

	<!-- ═══ PER-MODEL BREAKDOWN ═══ -->
	<div class="xanh-dash-section">
		<div class="xanh-dash-section-header">
			<h2>
				<span class="dashicons dashicons-database" style="margin-right:6px;color:#8b5cf6;"></span>
				<?php
				printf( esc_html__( 'Chi Tiết theo Model — %s', 'xanh-ai-content' ), esc_html( $range_label ) );
				?>
			</h2>
		</div>
		<div class="xanh-dash-table-wrap">
			<?php if ( empty( $models ) ) : ?>
				<p style="padding:20px;color:#646970;"><?php esc_html_e( 'Chưa có dữ liệu sử dụng trong khoảng thời gian này.', 'xanh-ai-content' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width:25%"><?php esc_html_e( 'Model', 'xanh-ai-content' ); ?></th>
							<th class="xanh-col-right"><?php esc_html_e( 'API Calls', 'xanh-ai-content' ); ?></th>
							<th class="xanh-col-right"><?php esc_html_e( 'Input Tokens', 'xanh-ai-content' ); ?></th>
							<th class="xanh-col-right"><?php esc_html_e( 'Output Tokens', 'xanh-ai-content' ); ?></th>
							<th class="xanh-col-right"><?php esc_html_e( 'Images', 'xanh-ai-content' ); ?></th>
							<th class="xanh-col-right"><?php esc_html_e( 'Cost (USD)', 'xanh-ai-content' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $models as $model_id => $m ) :
							$mc = $cost_data['by_model'][ $model_id ] ?? [ 'usd' => 0 ];
							$model_tokens = ( $m['text_input'] ?? 0 ) + ( $m['text_output'] ?? 0 );
							$pct = $grand_total_tokens > 0 ? round( ( $model_tokens / $grand_total_tokens ) * 100, 1 ) : 0;
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $model_id ); ?></strong>
								<?php if ( $pct > 0 ) : ?>
									<div class="xanh-model-bar">
										<div class="xanh-model-bar-fill" style="width:<?php echo esc_attr( min( $pct, 100 ) ); ?>%"></div>
									</div>
								<?php endif; ?>
							</td>
							<td class="xanh-col-right"><?php echo esc_html( number_format_i18n( $m['api_calls'] ?? 0 ) ); ?></td>
							<td class="xanh-col-right"><?php echo esc_html( number_format_i18n( $m['text_input'] ?? 0 ) ); ?></td>
							<td class="xanh-col-right"><?php echo esc_html( number_format_i18n( $m['text_output'] ?? 0 ) ); ?></td>
							<td class="xanh-col-right"><?php echo esc_html( number_format_i18n( $m['image_calls'] ?? 0 ) ); ?></td>
							<td class="xanh-col-right">$<?php echo esc_html( number_format( $mc['usd'], 4 ) ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<!-- ═══ MONTHLY HISTORY ═══ -->
	<div class="xanh-dash-section">
		<div class="xanh-dash-section-header">
			<h2>
				<span class="dashicons dashicons-calendar-alt" style="margin-right:6px;color:#00a32a;"></span>
				<?php esc_html_e( 'Lịch Sử Sử Dụng (6 Tháng)', 'xanh-ai-content' ); ?>
			</h2>
		</div>
		<div class="xanh-dash-table-wrap">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Tháng', 'xanh-ai-content' ); ?></th>
						<th class="xanh-col-right"><?php esc_html_e( 'API Calls', 'xanh-ai-content' ); ?></th>
						<th class="xanh-col-right"><?php esc_html_e( 'Tokens (In + Out)', 'xanh-ai-content' ); ?></th>
						<th class="xanh-col-right"><?php esc_html_e( 'Hình Ảnh', 'xanh-ai-content' ); ?></th>
						<th class="xanh-col-right"><?php esc_html_e( 'Chi Phí (USD)', 'xanh-ai-content' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$current_ym = gmdate( 'Y_m' );
					foreach ( $stats_data as $ym => $d ) :
						$mc_models  = $d['models'] ?? [];
						$m_calls    = $d['total_api_calls'] ?? 0;
						$m_tokens   = 0;
						$m_images   = 0;
						foreach ( $mc_models as $mid => $md ) {
							$m_tokens += ( $md['text_input'] ?? 0 ) + ( $md['text_output'] ?? 0 );
							$m_images += $md['image_calls'] ?? 0;
						}
						$m_cost = Xanh_AI_Pricing::estimate_cost( $mc_models );
						$is_current = ( $ym === $current_ym );
					?>
					<tr<?php if ( $is_current ) echo ' style="background:#f0f7ff;"'; ?>>
						<td>
							<?php echo esc_html( str_replace( '_', '/', $ym ) ); ?>
							<?php if ( $is_current ) : ?>
								<span style="color:#2271b1;font-size:11px;margin-left:6px;">← hiện tại</span>
							<?php endif; ?>
						</td>
						<td class="xanh-col-right"><?php echo esc_html( number_format_i18n( $m_calls ) ); ?></td>
						<td class="xanh-col-right"><?php echo esc_html( number_format_i18n( $m_tokens ) ); ?></td>
						<td class="xanh-col-right"><?php echo esc_html( number_format_i18n( $m_images ) ); ?></td>
						<td class="xanh-col-right">$<?php echo esc_html( number_format( $m_cost['total_usd'], 4 ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<p class="xanh-dash-footer">
		<?php if ( ! empty( $range_data['last_updated'] ) ) : ?>
			<?php printf( esc_html__( 'Cập nhật lần cuối: %s', 'xanh-ai-content' ), esc_html( $range_data['last_updated'] ) ); ?>
			&nbsp;•&nbsp;
		<?php endif; ?>
		<?php esc_html_e( 'Chi phí ước tính dựa trên bảng giá Gemini API (Pay-as-you-go). Free tier = $0.', 'xanh-ai-content' ); ?>
	</p>
</div>

<!-- Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

<script>
jQuery(function($) {

	/* ── Chart ── */
	var ctx = document.getElementById('xanh-daily-chart');
	if (ctx) {
		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: <?php echo wp_json_encode( $chart_labels ); ?>,
				datasets: [
					{
						label: 'Input Tokens',
						data: <?php echo wp_json_encode( $chart_input ); ?>,
						backgroundColor: 'rgba(34, 113, 177, 0.7)',
						borderRadius: 3,
						barPercentage: 0.6,
					},
					{
						label: 'Output Tokens',
						data: <?php echo wp_json_encode( $chart_output ); ?>,
						backgroundColor: 'rgba(0, 163, 42, 0.7)',
						borderRadius: 3,
						barPercentage: 0.6,
					},
					{
						label: 'Images',
						data: <?php echo wp_json_encode( $chart_images ); ?>,
						backgroundColor: 'rgba(214, 54, 56, 0.7)',
						borderRadius: 3,
						barPercentage: 0.6,
						yAxisID: 'y1',
					}
				]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				plugins: {
					legend: {
						position: 'top',
						labels: { usePointStyle: true, padding: 16, font: { size: 12 } }
					},
					tooltip: {
						backgroundColor: 'rgba(29, 35, 39, 0.9)',
						titleFont: { size: 13 },
						bodyFont: { size: 12 },
						padding: 10,
						cornerRadius: 6,
						callbacks: {
							label: function(ctx) {
								return ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString();
							}
						}
					}
				},
				scales: {
					x: {
						grid: { display: false },
						ticks: { font: { size: 11 }, maxRotation: 45 }
					},
					y: {
						beginAtZero: true,
						grid: { color: 'rgba(0,0,0,0.04)' },
						ticks: {
							font: { size: 11 },
							callback: function(v) { return v >= 1000 ? (v / 1000).toFixed(0) + 'K' : v; }
						},
						title: { display: true, text: 'Tokens', font: { size: 12 } }
					},
					y1: {
						display: true,
						position: 'right',
						beginAtZero: true,
						grid: { drawOnChartArea: false },
						ticks: { font: { size: 11 }, stepSize: 1 },
						title: { display: true, text: 'Images', font: { size: 12 } }
					}
				}
			}
		});
	}

	/* ── Custom Date Toggle ── */
	$('#xanh-filter-custom-toggle').on('click', function() {
		$('#xanh-filter-custom').slideToggle(200);
	});

	/* ── Export CSV ── */
	$('#xanh-dash-export').on('click', function() {
		var $btn = $(this);
		$btn.prop('disabled', true).text('Đang xuất...');

		$.post(ajaxurl, {
			action: 'xanh_ai_export_usage',
			_ajax_nonce: '<?php echo esc_js( wp_create_nonce( 'xanh_ai_ajax' ) ); ?>',
			month: '<?php echo esc_js( gmdate( 'Y_m' ) ); ?>'
		}, function(res) {
			if (res.success && res.data.csv) {
				var blob = new Blob([res.data.csv], {type: 'text/csv'});
				var url = URL.createObjectURL(blob);
				var a = document.createElement('a');
				a.href = url;
				a.download = 'xanh-ai-usage-' + res.data.month + '.csv';
				a.click();
				URL.revokeObjectURL(url);
			}
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="margin-top:3px;margin-right:2px;"></span> Export CSV');
		});
	});

	/* ── Reset Usage ── */
	$('#xanh-dash-reset').on('click', function() {
		if ( ! confirm('Bạn có chắc muốn xóa toàn bộ dữ liệu tháng hiện tại? Hành động này không thể hoàn tác!') ) {
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true);

		$.post(ajaxurl, {
			action: 'xanh_ai_reset_usage',
			_ajax_nonce: '<?php echo esc_js( wp_create_nonce( 'xanh_ai_ajax' ) ); ?>',
			month: '<?php echo esc_js( gmdate( 'Y_m' ) ); ?>'
		}, function(res) {
			if (res.success) {
				location.reload();
			} else {
				alert(res.data?.message || 'Reset thất bại.');
				$btn.prop('disabled', false);
			}
		});
	});

});
</script>
