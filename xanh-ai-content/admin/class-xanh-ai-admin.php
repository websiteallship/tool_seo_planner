<?php
/**
 * Admin pages — menu registration, asset enqueue, view routing.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_Admin {

	/**
	 * Initialize admin hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );

		// TinyMCE toolbar button (only if API key exists).
		$has_key = ! empty( Xanh_AI_Security::decrypt_key( get_option( 'xanh_ai_gemini_key', '' ) ) );
		if ( $has_key && current_user_can( 'upload_files' ) ) {
			add_filter( 'mce_buttons', [ $this, 'register_tinymce_button' ] );
			add_filter( 'mce_external_plugins', [ $this, 'register_tinymce_plugin' ] );
		}
	}

	/*--------------------------------------------------------------
	 * Menu Registration
	 *------------------------------------------------------------*/

	/**
	 * Register admin menu and submenus.
	 */
	public function register_menu(): void {
		// Top-level menu.
		add_menu_page(
			__( 'XANH AI Content', 'xanh-ai-content' ),
			__( 'XANH AI', 'xanh-ai-content' ),
			'edit_posts',
			'xanh-ai',
			[ $this, 'render_generator_page' ],
			'dashicons-edit-page',
			30
		);

		// Submenu: Generator (same as parent).
		add_submenu_page(
			'xanh-ai',
			__( 'Tạo Bài Viết', 'xanh-ai-content' ),
			__( 'Tạo Bài Viết', 'xanh-ai-content' ),
			'edit_posts',
			'xanh-ai',
			[ $this, 'render_generator_page' ]
		);

		// Submenu: Thống Kê (Dashboard).
		add_submenu_page(
			'xanh-ai',
			__( 'Thống Kê', 'xanh-ai-content' ),
			__( 'Thống Kê', 'xanh-ai-content' ),
			'manage_options',
			'xanh-ai-dashboard',
			[ $this, 'render_dashboard_page' ]
		);

		// Submenu: Calendar.
		add_submenu_page(
			'xanh-ai',
			__( 'Lịch Nội Dung', 'xanh-ai-content' ),
			__( 'Lịch Nội Dung', 'xanh-ai-content' ),
			'edit_posts',
			'xanh-ai-calendar',
			[ $this, 'render_calendar_page' ]
		);

		// Submenu: Settings (admin only).
		add_submenu_page(
			'xanh-ai',
			__( 'Cài Đặt', 'xanh-ai-content' ),
			__( 'Cài Đặt', 'xanh-ai-content' ),
			'manage_options',
			'xanh-ai-settings',
			[ $this, 'render_settings_page' ]
		);

		// Submenu: History.
		add_submenu_page(
			'xanh-ai',
			__( 'Lịch Sử', 'xanh-ai-content' ),
			__( 'Lịch Sử', 'xanh-ai-content' ),
			'edit_posts',
			'xanh-ai-history',
			[ $this, 'render_history_page' ]
		);
	}

	/*--------------------------------------------------------------
	 * Asset Enqueue
	 *------------------------------------------------------------*/

	/**
	 * Enqueue admin CSS and JS only on our plugin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// === Post Editor: enqueue AI Image Meta Box assets ===
		if ( in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			$this->enqueue_editor_assets();
		}

		// Only load plugin page assets on our plugin pages.
		if ( strpos( $hook_suffix, 'xanh-ai' ) === false ) {
			return;
		}

		// CSS.
		wp_enqueue_style(
			'xanh-ai-admin',
			XANH_AI_URL . 'admin/css/xanh-ai-admin.css',
			[],
			XANH_AI_VERSION
		);

		// No custom fonts; using WP dashicons.

		// JS — admin common (settings page).
		wp_enqueue_script(
			'xanh-ai-admin',
			XANH_AI_URL . 'admin/js/xanh-ai-admin.js',
			[ 'jquery' ],
			XANH_AI_VERSION,
			true
		);

		// Shared localization.
		wp_localize_script( 'xanh-ai-admin', 'xanhAI', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'xanh_ai_ajax' ),
			'i18n'    => [
				'testing'       => __( 'Đang kiểm tra...', 'xanh-ai-content' ),
				'testSuccess'   => __( 'Kết nối thành công!', 'xanh-ai-content' ),
				'testFailed'    => __( 'Kết nối thất bại.', 'xanh-ai-content' ),
				'saving'        => __( 'Đang lưu...', 'xanh-ai-content' ),
				'saved'         => __( 'Đã lưu!', 'xanh-ai-content' ),
				'confirmDelete' => __( 'Bạn có chắc muốn xóa?', 'xanh-ai-content' ),
			],
		] );

		// Generator page — extra JS + data.
		if ( strpos( $hook_suffix, 'xanh-ai' ) !== false && strpos( $hook_suffix, 'settings' ) === false && strpos( $hook_suffix, 'calendar' ) === false ) {
			wp_enqueue_script(
				'xanh-ai-generator',
				XANH_AI_URL . 'admin/js/xanh-ai-generator.js',
				[ 'jquery', 'xanh-ai-admin' ],
				XANH_AI_VERSION,
				true
			);

			wp_localize_script( 'xanh-ai-generator', 'xanhAIGen', [
				'angles'      => Xanh_AI_Angles::get_all(),
				'linkTargets' => Xanh_AI_Linker::get_grouped_link_keys(),
				'i18n'   => [
					'generating'     => __( 'Đang tạo nội dung...', 'xanh-ai-content' ),
					'analyzing'      => __( 'Đang phân tích chủ đề...', 'xanh-ai-content' ),
					'writing'        => __( 'Đang viết bài...', 'xanh-ai-content' ),
					'optimizing'     => __( 'Đang tối ưu SEO...', 'xanh-ai-content' ),
					'generated'      => __( 'Đã tạo xong!', 'xanh-ai-content' ),
					'generateFailed' => __( 'Tạo nội dung thất bại.', 'xanh-ai-content' ),
					'saving'         => __( 'Đang lưu draft...', 'xanh-ai-content' ),
					'saved'          => __( 'Đã lưu draft thành công!', 'xanh-ai-content' ),
					'saveFailed'     => __( 'Lưu draft thất bại.', 'xanh-ai-content' ),
					'regenerating'   => __( 'Đang viết lại section...', 'xanh-ai-content' ),
					'regenerated'    => __( 'Đã viết lại xong!', 'xanh-ai-content' ),
					'genImage'       => __( 'Đang tạo hình ảnh...', 'xanh-ai-content' ),
					'genImageDone'   => __( 'Đã tạo hình ảnh!', 'xanh-ai-content' ),
				],
			] );
		}

		// Calendar page — dedicated JS + data.
		if ( strpos( $hook_suffix, 'xanh-ai-calendar' ) !== false ) {
			wp_enqueue_script(
				'xanh-ai-calendar',
				XANH_AI_URL . 'admin/js/xanh-ai-calendar.js',
				[ 'jquery', 'xanh-ai-admin' ],
				XANH_AI_VERSION,
				true
			);

			// Get categories for color coding.
			$categories = get_categories( [ 'hide_empty' => false ] );
			$cat_data   = [];
			foreach ( $categories as $cat ) {
				$cat_data[] = [
					'id'   => $cat->term_id,
					'name' => $cat->name,
					'slug' => $cat->slug,
				];
			}

			wp_localize_script( 'xanh-ai-calendar', 'xanhAICal', [
				'generatorUrl' => admin_url( 'admin.php?page=xanh-ai' ),
				'editBaseUrl'  => admin_url( 'post.php?action=edit&post=' ),
				'categories'   => $cat_data,
				'frequency'    => get_option( 'xanh_ai_schedule_frequency', '2/week' ),
			] );
		}

		// History page — dedicated JS + data.
		if ( strpos( $hook_suffix, 'xanh-ai-history' ) !== false ) {
			wp_enqueue_script(
				'xanh-ai-history',
				XANH_AI_URL . 'admin/js/xanh-ai-history.js',
				[ 'jquery', 'xanh-ai-admin' ],
				XANH_AI_VERSION,
				true
			);

			wp_localize_script( 'xanh-ai-history', 'xanhAIHistory', [
				'angles'  => Xanh_AI_Angles::get_all(),
				'i18n'    => [
					'loading'    => __( 'Đang tải...', 'xanh-ai-content' ),
					'noData'     => __( 'Chưa có dữ liệu lịch sử.', 'xanh-ai-content' ),
					'error'      => __( 'Không thể tải dữ liệu.', 'xanh-ai-content' ),
					'post'       => __( 'Bài viết', 'xanh-ai-content' ),
					'image'      => __( 'Hình ảnh', 'xanh-ai-content' ),
					'suggest'    => __( 'Gợi ý', 'xanh-ai-content' ),
					'success'    => __( 'Thành công', 'xanh-ai-content' ),
					'errorLabel' => __( 'Lỗi', 'xanh-ai-content' ),
					'deleted'    => __( 'Đã xóa', 'xanh-ai-content' ),
					'view'       => __( 'Xem', 'xanh-ai-content' ),
					'edit'       => __( 'Sửa', 'xanh-ai-content' ),
				],
			] );
		}
	}

	/*--------------------------------------------------------------
	 * Page Renderers
	 *------------------------------------------------------------*/

	/**
	 * Render the Generator page.
	 */
	public function render_generator_page(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'xanh-ai-content' ) );
		}

		include XANH_AI_DIR . 'admin/views/generator-page.php';
	}

	/**
	 * Render the Settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'xanh-ai-content' ) );
		}

		include XANH_AI_DIR . 'admin/views/settings-page.php';
	}

	public function render_dashboard_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'xanh-ai-content' ) );
		}

		include XANH_AI_DIR . 'admin/views/dashboard-page.php';
	}

	/**
	 * Render the Content Calendar page.
	 */
	public function render_calendar_page(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'xanh-ai-content' ) );
		}

		include XANH_AI_DIR . 'admin/views/calendar-page.php';
	}

	/**
	 * Render the History page.
	 */
	public function render_history_page(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'xanh-ai-content' ) );
		}

		include XANH_AI_DIR . 'admin/views/history-page.php';
	}

	/*--------------------------------------------------------------
	 * Helper: Check if we're on a plugin page
	 *------------------------------------------------------------*/

	/**
	 * Check if current screen is a XANH AI page.
	 *
	 * @return bool
	 */
	public static function is_plugin_page(): bool {
		$screen = get_current_screen();
		return $screen && strpos( $screen->id, 'xanh-ai' ) !== false;
	}

	/*--------------------------------------------------------------
	 * Meta Boxes
	 *------------------------------------------------------------*/

	/**
	 * Register meta boxes for the post editor.
	 */
	public function register_meta_boxes(): void {
		// Only show if API key is configured.
		$has_key = ! empty( Xanh_AI_Security::decrypt_key( get_option( 'xanh_ai_gemini_key', '' ) ) );
		if ( ! $has_key ) {
			return;
		}

		add_meta_box(
			'xanh_ai_image_generator',
			__( '🖼️ XANH AI — Tạo Ảnh', 'xanh-ai-content' ),
			[ $this, 'render_image_meta_box' ],
			'post',
			'side',
			'default'
		);
	}

	/**
	 * Render the AI Image Generator meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_image_meta_box( $post ): void {
		include XANH_AI_DIR . 'admin/views/meta-box-ai-image.php';
	}

	/*--------------------------------------------------------------
	 * Editor Assets (for post.php / post-new.php)
	 *------------------------------------------------------------*/

	/**
	 * Enqueue CSS + JS for the AI Image meta box on post editor screens.
	 */
	private function enqueue_editor_assets(): void {
		// Check if API key exists.
		$has_key = ! empty( Xanh_AI_Security::decrypt_key( get_option( 'xanh_ai_gemini_key', '' ) ) );
		if ( ! $has_key ) {
			return;
		}

		// CSS — reuse plugin admin styles.
		wp_enqueue_style(
			'xanh-ai-admin',
			XANH_AI_URL . 'admin/css/xanh-ai-admin.css',
			[],
			XANH_AI_VERSION
		);

		// JS — editor-specific script.
		wp_enqueue_script(
			'xanh-ai-editor',
			XANH_AI_URL . 'admin/js/xanh-ai-editor.js',
			[ 'jquery' ],
			XANH_AI_VERSION,
			true
		);

		// Shared localization (AJAX URL + nonce).
		wp_localize_script( 'xanh-ai-editor', 'xanhAI', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'xanh_ai_ajax' ),
		] );

		// Editor-specific data.
		wp_localize_script( 'xanh-ai-editor', 'xanhAIEditor', [
			'setThumbnailNonce' => wp_create_nonce( 'set_post_thumbnail-' . ( get_the_ID() ?: 0 ) ),
		] );
	}

	/*--------------------------------------------------------------
	 * TinyMCE Toolbar Button
	 *------------------------------------------------------------*/

	/**
	 * Add custom button to TinyMCE toolbar row 1.
	 *
	 * @param array $buttons Existing buttons.
	 * @return array Modified buttons.
	 */
	public function register_tinymce_button( array $buttons ): array {
		$buttons[] = 'xanh_ai_image';
		return $buttons;
	}

	/**
	 * Register TinyMCE external plugin JS.
	 *
	 * @param array $plugins Existing plugins.
	 * @return array Modified plugins.
	 */
	public function register_tinymce_plugin( array $plugins ): array {
		$plugins['xanh_ai_image'] = XANH_AI_URL . 'admin/js/xanh-ai-tinymce.js';
		return $plugins;
	}
}
