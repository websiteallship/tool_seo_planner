<?php
/**
 * Settings management — WordPress Settings API integration.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_Settings {

	/**
	 * Settings group name.
	 */
	private const GROUP = 'xanh_ai_settings';

	/**
	 * Option page slug.
	 */
	private const PAGE = 'xanh-ai-settings';

	/**
	 * Default option values.
	 *
	 * @var array<string, mixed>
	 */
	private static array $defaults = [
		'xanh_ai_gemini_key'     => '',
		'xanh_ai_text_model'     => 'gemini-2.5-flash',
		'xanh_ai_image_model'    => 'gemini-3.1-flash-image-preview',
		'xanh_ai_temperature'    => 0.7,
		'xanh_ai_image_aspect'   => '16:9',
		'xanh_ai_image_size'     => '2K',
		'xanh_ai_auto_image'     => 1,
		'xanh_ai_default_author' => 1,
	];

	/**
	 * Initialize settings hooks.
	 */
	public function init(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/*--------------------------------------------------------------
	 * Settings Registration
	 *------------------------------------------------------------*/

	/**
	 * Register all settings fields with WordPress Settings API.
	 */
	public function register_settings(): void {
		// --- Section: API Configuration ---
		add_settings_section(
			'xanh_ai_section_api',
			__( 'Cấu Hình API', 'xanh-ai-content' ),
			function (): void {
				echo '<p>' . esc_html__( 'Kết nối với Google Gemini API để tạo nội dung và hình ảnh.', 'xanh-ai-content' ) . '</p>';
			},
			self::PAGE
		);

		// API Key.
		register_setting( self::GROUP, 'xanh_ai_gemini_key', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_api_key' ],
		] );
		add_settings_field(
			'xanh_ai_gemini_key',
			__( 'Gemini API Key', 'xanh-ai-content' ),
			[ $this, 'render_api_key_field' ],
			self::PAGE,
			'xanh_ai_section_api'
		);

		// Text Model.
		register_setting( self::GROUP, 'xanh_ai_text_model', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_text_model' ],
			'default'           => 'gemini-2.5-flash',
		] );
		add_settings_field(
			'xanh_ai_text_model',
			__( 'Text Model', 'xanh-ai-content' ),
			[ $this, 'render_text_model_field' ],
			self::PAGE,
			'xanh_ai_section_api'
		);

		// Image Model.
		register_setting( self::GROUP, 'xanh_ai_image_model', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_image_model' ],
			'default'           => 'gemini-3.1-flash-image-preview',
		] );
		add_settings_field(
			'xanh_ai_image_model',
			__( 'Image Model', 'xanh-ai-content' ),
			[ $this, 'render_image_model_field' ],
			self::PAGE,
			'xanh_ai_section_api'
		);

		// Temperature.
		register_setting( self::GROUP, 'xanh_ai_temperature', [
			'type'              => 'number',
			'sanitize_callback' => [ $this, 'sanitize_temperature' ],
			'default'           => 0.7,
		] );
		add_settings_field(
			'xanh_ai_temperature',
			__( 'Temperature', 'xanh-ai-content' ),
			[ $this, 'render_temperature_field' ],
			self::PAGE,
			'xanh_ai_section_api'
		);

		// --- Section: Image Settings ---
		add_settings_section(
			'xanh_ai_section_image',
			__( 'Cài Đặt Hình Ảnh', 'xanh-ai-content' ),
			function (): void {
				echo '<p>' . esc_html__( 'Cấu hình tạo hình ảnh AI tự động.', 'xanh-ai-content' ) . '</p>';
			},
			self::PAGE
		);

		// Auto Image.
		register_setting( self::GROUP, 'xanh_ai_auto_image', [
			'type'              => 'boolean',
			'sanitize_callback' => 'absint',
			'default'           => 1,
		] );
		add_settings_field(
			'xanh_ai_auto_image',
			__( 'Tự Động Tạo Ảnh', 'xanh-ai-content' ),
			[ $this, 'render_auto_image_field' ],
			self::PAGE,
			'xanh_ai_section_image'
		);

		// Aspect Ratio.
		register_setting( self::GROUP, 'xanh_ai_image_aspect', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_aspect_ratio' ],
			'default'           => '16:9',
		] );
		add_settings_field(
			'xanh_ai_image_aspect',
			__( 'Tỷ Lệ Ảnh', 'xanh-ai-content' ),
			[ $this, 'render_aspect_ratio_field' ],
			self::PAGE,
			'xanh_ai_section_image'
		);

		// Image Size.
		register_setting( self::GROUP, 'xanh_ai_image_size', [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'sanitize_image_size' ],
			'default'           => '2K',
		] );
		add_settings_field(
			'xanh_ai_image_size',
			__( 'Kích Thước Ảnh', 'xanh-ai-content' ),
			[ $this, 'render_image_size_field' ],
			self::PAGE,
			'xanh_ai_section_image'
		);

		// --- Section: Content Defaults ---
		add_settings_section(
			'xanh_ai_section_content',
			__( 'Mặc Định Nội Dung', 'xanh-ai-content' ),
			function (): void {
				echo '<p>' . esc_html__( 'Cấu hình mặc định cho bài viết được tạo.', 'xanh-ai-content' ) . '</p>';
			},
			self::PAGE
		);

		// Default Author.
		register_setting( self::GROUP, 'xanh_ai_default_author', [
			'type'              => 'integer',
			'sanitize_callback' => [ $this, 'sanitize_author' ],
			'default'           => 1,
		] );
		add_settings_field(
			'xanh_ai_default_author',
			__( 'Tác Giả Mặc Định', 'xanh-ai-content' ),
			[ $this, 'render_author_field' ],
			self::PAGE,
			'xanh_ai_section_content'
		);

		// --- Section: Internal Links ---
		add_settings_section(
			'xanh_ai_section_links',
			__( 'Internal Links', 'xanh-ai-content' ),
			function (): void {
				echo '<p>' . esc_html__( 'Cấu hình link nội bộ được tự động chèn vào bài viết AI. Chia 2 nhóm: Dịch Vụ (ưu tiên sale) và Trang Chính (CTA & brand).', 'xanh-ai-content' ) . '</p>';
			},
			self::PAGE
		);

		register_setting( self::GROUP, 'xanh_ai_link_targets', [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_link_targets' ],
			'default'           => [],
		] );
		add_settings_field(
			'xanh_ai_link_targets',
			__( 'Link Targets', 'xanh-ai-content' ),
			[ $this, 'render_link_targets_field' ],
			self::PAGE,
			'xanh_ai_section_links'
		);

		// Related Posts Max Count.
		register_setting( self::GROUP, 'xanh_ai_related_posts_max', [
			'type'              => 'integer',
			'sanitize_callback' => [ $this, 'sanitize_related_posts_max' ],
			'default'           => 3,
		] );
		add_settings_field(
			'xanh_ai_related_posts_max',
			__( 'Số bài liên quan', 'xanh-ai-content' ),
			[ $this, 'render_related_posts_max_field' ],
			self::PAGE,
			'xanh_ai_section_links'
		);
	}

	/*--------------------------------------------------------------
	 * Field Renderers
	 *------------------------------------------------------------*/

	public function render_api_key_field(): void {
		$encrypted = get_option( 'xanh_ai_gemini_key', '' );
		$decrypted = Xanh_AI_Security::decrypt_key( $encrypted );
		$masked    = Xanh_AI_Security::mask_key( $decrypted );
		$has_key   = ! empty( $decrypted );
		?>
		<div class="xanh-ai-api-key-wrapper">
			<input
				type="password"
				id="xanh_ai_gemini_key"
				name="xanh_ai_gemini_key"
				value=""
				placeholder="<?php echo $has_key ? esc_attr( $masked ) : esc_attr__( 'Nhập Gemini API Key...', 'xanh-ai-content' ); ?>"
				class="regular-text"
				autocomplete="off"
			/>
			<?php if ( $has_key ) : ?>
				<span class="xanh-ai-key-status xanh-ai-key-status--active">
					<?php esc_html_e( 'Đã cấu hình', 'xanh-ai-content' ); ?>
				</span>
			<?php endif; ?>
			<p class="description">
				<strong><?php esc_html_e( 'Hướng dẫn lấy API Key:', 'xanh-ai-content' ); ?></strong><br>
				1. <?php esc_html_e( 'Truy cập', 'xanh-ai-content' ); ?> <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer">Google AI Studio</a>.<br>
				2. <?php esc_html_e( 'Đăng nhập bằng tài khoản Google của bạn.', 'xanh-ai-content' ); ?><br>
				3. <?php esc_html_e( 'Nhấp vào nút "Create API key" ở thanh menu bên trái, sau đó copy mã khóa và dán vào ô trên.', 'xanh-ai-content' ); ?>
			</p>
			<p class="description">
				<em><?php esc_html_e( 'Để trống nếu muốn giữ key hiện tại. Key được mã hóa AES-256 trước khi lưu.', 'xanh-ai-content' ); ?></em>
			</p>
			<button type="button" id="xanh-ai-test-connection" class="button button-secondary" <?php echo $has_key ? '' : 'disabled'; ?>>
				<?php esc_html_e( 'Test Kết Nối', 'xanh-ai-content' ); ?>
			</button>
			<span id="xanh-ai-test-result"></span>
		</div>
		<?php
	}

	public function render_text_model_field(): void {
		$value = get_option( 'xanh_ai_text_model', 'gemini-2.5-flash' );
		$models = self::get_text_models();
		?>
		<select id="xanh_ai_text_model" name="xanh_ai_text_model">
			<?php foreach ( $models as $model_id => $label ) : ?>
				<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $value, $model_id ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Model AI để tạo nội dung text.', 'xanh-ai-content' ); ?></p>
		<?php
	}

	public function render_image_model_field(): void {
		$value = get_option( 'xanh_ai_image_model', 'gemini-3.1-flash-image-preview' );
		$models = self::get_image_models();
		?>
		<select id="xanh_ai_image_model" name="xanh_ai_image_model">
			<?php foreach ( $models as $model_id => $label ) : ?>
				<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $value, $model_id ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Model AI để tạo hình ảnh.', 'xanh-ai-content' ); ?></p>
		<?php
	}

	public function render_temperature_field(): void {
		$value = get_option( 'xanh_ai_temperature', 0.7 );
		?>
		<div class="xanh-ai-range-wrapper">
			<input
				type="range"
				id="xanh_ai_temperature"
				name="xanh_ai_temperature"
				min="0"
				max="1"
				step="0.1"
				value="<?php echo esc_attr( $value ); ?>"
			/>
			<span id="xanh-ai-temp-value" class="xanh-ai-range-value"><?php echo esc_html( $value ); ?></span>
		</div>
		<p class="description">
			<?php esc_html_e( '0.0 = chính xác, chặt chẽ · 1.0 = sáng tạo, ngẫu nhiên · Khuyến nghị: 0.7', 'xanh-ai-content' ); ?>
		</p>
		<?php
	}

	public function render_auto_image_field(): void {
		$value = get_option( 'xanh_ai_auto_image', 1 );
		?>
		<label>
			<input type="checkbox" id="xanh_ai_auto_image" name="xanh_ai_auto_image" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Tự động tạo featured image bằng AI khi tạo bài viết', 'xanh-ai-content' ); ?>
		</label>
		<?php
	}

	public function render_aspect_ratio_field(): void {
		$value   = get_option( 'xanh_ai_image_aspect', '16:9' );
		$options = [
			'1:1'  => '1:1 — Social Media / Thumbnail',
			'16:9' => '16:9 — Featured Image (khuyến nghị)',
			'4:3'  => '4:3 — Standard Landscape',
			'3:4'  => '3:4 — Portrait',
			'9:16' => '9:16 — Story / Vertical',
		];
		?>
		<select id="xanh_ai_image_aspect" name="xanh_ai_image_aspect">
			<?php foreach ( $options as $ratio => $label ) : ?>
				<option value="<?php echo esc_attr( $ratio ); ?>" <?php selected( $value, $ratio ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function render_image_size_field(): void {
		$value   = get_option( 'xanh_ai_image_size', '2K' );
		$options = [
			'1K' => '1K — 1024px',
			'2K' => '2K — 2048px (khuyến nghị)',
			'4K' => '4K — 4096px',
		];
		?>
		<select id="xanh_ai_image_size" name="xanh_ai_image_size">
			<?php foreach ( $options as $size => $label ) : ?>
				<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $value, $size ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function render_author_field(): void {
		$value = get_option( 'xanh_ai_default_author', 1 );
		wp_dropdown_users( [
			'name'     => 'xanh_ai_default_author',
			'id'       => 'xanh_ai_default_author',
			'selected' => $value,
			'role__in' => [ 'administrator', 'editor', 'author' ],
		] );
		?>
		<p class="description"><?php esc_html_e( 'Tác giả mặc định cho bài viết AI tạo.', 'xanh-ai-content' ); ?></p>
		<?php
	}

	/*--------------------------------------------------------------
	 * Sanitizers
	 *------------------------------------------------------------*/

	/**
	 * Sanitize and encrypt API key.
	 * If empty, keep the existing key.
	 */
	public function sanitize_api_key( string $input ): string {
		$input = sanitize_text_field( $input );

		// If empty, user wants to keep existing key.
		if ( empty( $input ) ) {
			return get_option( 'xanh_ai_gemini_key', '' );
		}

		return Xanh_AI_Security::encrypt_key( $input );
	}

	public function sanitize_text_model( string $input ): string {
		$allowed = array_keys( self::get_text_models() );
		if ( in_array( $input, $allowed, true ) ) {
			return $input;
		}
		// Auto-migrate deprecated models.
		$migrations = [
			'gemini-2.0-flash'     => 'gemini-2.5-flash',
			'gemini-2.0-flash-001' => 'gemini-2.5-flash',
			'gemini-1.5-flash'     => 'gemini-2.5-flash',
			'gemini-1.5-pro'       => 'gemini-2.5-pro',
		];
		return $migrations[ $input ] ?? 'gemini-2.5-flash';
	}

	public function sanitize_image_model( string $input ): string {
		$allowed = array_keys( self::get_image_models() );
		return in_array( $input, $allowed, true ) ? $input : 'gemini-3.1-flash-image-preview';
	}

	public function sanitize_temperature( $input ): float {
		$value = floatval( $input );
		return max( 0.0, min( 1.0, $value ) );
	}

	public function sanitize_aspect_ratio( string $input ): string {
		$allowed = [ '1:1', '16:9', '4:3', '3:4', '9:16' ];
		return in_array( $input, $allowed, true ) ? $input : '16:9';
	}

	public function sanitize_image_size( string $input ): string {
		$allowed = [ '1K', '2K', '4K' ];
		return in_array( $input, $allowed, true ) ? $input : '2K';
	}

	public function sanitize_author( $input ): int {
		$user_id = absint( $input );
		$user    = get_userdata( $user_id );
		return $user ? $user_id : get_current_user_id();
	}

	/**
	 * Sanitize Related Posts max count.
	 */
	public function sanitize_related_posts_max( $input ): int {
		$value = absint( $input );
		return max( 1, min( 10, $value ) );
	}

	/**
	 * Render Related Posts max count field.
	 */
	public function render_related_posts_max_field(): void {
		$value = get_option( 'xanh_ai_related_posts_max', 3 );
		?>
		<select id="xanh_ai_related_posts_max" name="xanh_ai_related_posts_max">
			<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
				<option value="<?php echo esc_attr( $i ); ?>" <?php selected( (int) $value, $i ); ?>>
					<?php echo esc_html( $i ); ?>
				</option>
			<?php endfor; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Số bài viết liên quan tự động chèn cuối mỗi bài (1-10).', 'xanh-ai-content' ); ?></p>
		<?php
	}

	/**
	 * Render the Internal Links repeater table.
	 */
	public function render_link_targets_field(): void {
		$targets  = Xanh_AI_Linker::get_link_map();
		$defaults = Xanh_AI_Linker::get_default_link_targets();

		// Ensure all defaults exist in saved data.
		foreach ( $defaults as $key => $default ) {
			if ( ! isset( $targets[ $key ] ) ) {
				$targets[ $key ] = $default;
			}
		}

		$groups = [
			'service' => __( 'Dịch Vụ', 'xanh-ai-content' ),
			'utility' => __( 'Trang Chính', 'xanh-ai-content' ),
		];

		foreach ( $groups as $group_key => $group_label ) :
			$group_targets = array_filter( $targets, function ( $t ) use ( $group_key ) {
				return ( $t['group'] ?? 'utility' ) === $group_key;
			} );

			if ( empty( $group_targets ) ) {
				continue;
			}
		?>
		<h4 style="margin: 15px 0 8px; font-size: 14px;"><?php echo esc_html( $group_label ); ?></h4>
		<table class="widefat xanh-ai-link-table" style="max-width: 800px; margin-bottom: 15px;">
			<thead>
				<tr>
					<th style="width: 50px;"><?php esc_html_e( 'Bật', 'xanh-ai-content' ); ?></th>
					<th style="width: 120px;"><?php esc_html_e( 'Key', 'xanh-ai-content' ); ?></th>
					<th><?php esc_html_e( 'URL', 'xanh-ai-content' ); ?></th>
					<th><?php esc_html_e( 'Anchor Text', 'xanh-ai-content' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $group_targets as $key => $config ) : ?>
				<tr>
					<td style="text-align: center;">
						<input type="hidden" name="xanh_ai_link_targets[<?php echo esc_attr( $key ); ?>][enabled]" value="0" />
						<input type="checkbox"
						       name="xanh_ai_link_targets[<?php echo esc_attr( $key ); ?>][enabled]"
						       value="1"
						       <?php checked( ! empty( $config['enabled'] ) ); ?> />
					</td>
					<td>
						<code style="font-size: 11px;"><?php echo esc_html( $key ); ?></code>
						<input type="hidden"
						       name="xanh_ai_link_targets[<?php echo esc_attr( $key ); ?>][group]"
						       value="<?php echo esc_attr( $group_key ); ?>" />
					</td>
					<td>
						<input type="text"
						       name="xanh_ai_link_targets[<?php echo esc_attr( $key ); ?>][url]"
						       value="<?php echo esc_attr( $config['url'] ?? '' ); ?>"
						       class="regular-text"
						       style="width: 100%;" />
					</td>
					<td>
						<input type="text"
						       name="xanh_ai_link_targets[<?php echo esc_attr( $key ); ?>][anchor]"
						       value="<?php echo esc_attr( $config['anchor'] ?? '' ); ?>"
						       class="regular-text"
						       style="width: 100%;" />
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		endforeach;

		echo '<p class="description">' . esc_html__( 'Bật/tắt, chỉnh URL và anchor text cho từng link target. Bài viết liên quan (Related Posts) được tạo tự động theo danh mục, không cần cấu hình.', 'xanh-ai-content' ) . '</p>';

		// "Add Custom Link" button.
		?>
		<div style="margin-top: 10px;">
			<button type="button" id="xanh-ai-add-custom-link" class="button">
				<span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
				<?php esc_html_e( 'Thêm Link Tùy Chỉnh', 'xanh-ai-content' ); ?>
			</button>
		</div>
		<script>
		(function(){
			var counter = 0;
			document.getElementById('xanh-ai-add-custom-link').addEventListener('click', function() {
				counter++;
				var key = 'custom-' + counter + '-' + Date.now();
				var tables = document.querySelectorAll('.xanh-ai-link-table');
				var lastTable = tables[tables.length - 1];
				if (!lastTable) return;
				var tbody = lastTable.querySelector('tbody');
				var row = document.createElement('tr');
				row.style.backgroundColor = '#fef9e7';
				row.innerHTML = '<td style="text-align:center;">'
					+ '<input type="hidden" name="xanh_ai_link_targets[' + key + '][enabled]" value="0">'
					+ '<input type="checkbox" name="xanh_ai_link_targets[' + key + '][enabled]" value="1" checked>'
					+ '</td>'
					+ '<td><input type="text" name="xanh_ai_link_targets[' + key + '][key]" value="' + key + '" style="width:100%;font-size:11px;" placeholder="key">'
					+ '<input type="hidden" name="xanh_ai_link_targets[' + key + '][group]" value="utility"></td>'
					+ '<td><input type="text" name="xanh_ai_link_targets[' + key + '][url]" value="" class="regular-text" style="width:100%;" placeholder="/duong-dan/"></td>'
					+ '<td><input type="text" name="xanh_ai_link_targets[' + key + '][anchor]" value="" class="regular-text" style="width:100%;" placeholder="Anchor text">'
					+ '<button type="button" class="button-link" style="color:#d63638;margin-left:4px;" onclick="this.closest(\'tr\').remove()">Xóa</button></td>';
				tbody.appendChild(row);
			});
		})();
		</script>
		<?php
	}

	/**
	 * Sanitize link targets from form submission.
	 *
	 * @param mixed $input Raw form input.
	 * @return array Sanitized link targets.
	 */
	public function sanitize_link_targets( $input ): array {
		if ( ! is_array( $input ) ) {
			return Xanh_AI_Linker::get_default_link_targets();
		}

		$sanitized = [];
		$defaults  = Xanh_AI_Linker::get_default_link_targets();

		foreach ( $input as $key => $config ) {
			$key = sanitize_key( $key );
			if ( empty( $key ) ) {
				continue;
			}

			$sanitized[ $key ] = [
				'url'     => sanitize_text_field( $config['url'] ?? '' ),
				'anchor'  => sanitize_text_field( $config['anchor'] ?? '' ),
				'group'   => in_array( ( $config['group'] ?? '' ), [ 'service', 'utility' ], true )
				             ? $config['group']
				             : 'utility',
				'enabled' => ! empty( $config['enabled'] ),
			];
		}

		// Ensure all defaults are present (merging with saved).
		foreach ( $defaults as $key => $default ) {
			if ( ! isset( $sanitized[ $key ] ) ) {
				$sanitized[ $key ] = $default;
			}
		}

		return $sanitized;
	}

	/*--------------------------------------------------------------
	 * Model Lists
	 *------------------------------------------------------------*/

	/**
	 * Get available text generation models.
	 *
	 * @return array<string, string>
	 */
	public static function get_text_models(): array {
		return apply_filters( 'xanh_ai_text_models', [
			'gemini-2.5-flash'      => 'Gemini 2.5 Flash (khuyến nghị)',
			'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (tiết kiệm)',
			'gemini-2.5-pro'        => 'Gemini 2.5 Pro (cao cấp)',
		] );
	}

	/**
	 * Get available image generation models.
	 *
	 * @return array<string, string>
	 */
	public static function get_image_models(): array {
		return apply_filters( 'xanh_ai_image_models', [
			'gemini-3.1-flash-image-preview' => 'Gemini 3.1 Flash Image (khuyến nghị)',
			'imagen-4.0-generate-001'        => 'Imagen 4 (chất lượng cao)',
			'imagen-4.0-fast-generate-001'   => 'Imagen 4 Fast (nhanh)',
		] );
	}

	/*--------------------------------------------------------------
	 * Helper: Get option with default
	 *------------------------------------------------------------*/

	/**
	 * Get a plugin option with fallback default.
	 *
	 * @param string $key Option key (without prefix).
	 * @return mixed
	 */
	public static function get( string $key ) {
		$full_key = 'xanh_ai_' . $key;
		$default  = self::$defaults[ $full_key ] ?? '';
		return get_option( $full_key, $default );
	}
}
