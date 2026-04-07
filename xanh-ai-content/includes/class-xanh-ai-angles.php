<?php
/**
 * Content Angles Registry — 9 angle configurations.
 *
 * Data sourced from PLUGIN_AI_ANGLES.md.
 * Each angle is a complete AI configuration: prompt, tone, CTA, links, image style.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Xanh_AI_Angles {

	/**
	 * Get all 8 content angles.
	 *
	 * @return array<string, array>
	 */
	public static function get_all(): array {
		return apply_filters( 'xanh_ai_angles', [

			'service_intro' => [
				'id'              => 'service_intro',
				'label'           => 'Giới Thiệu Dịch Vụ',
				'icon'            => 'dashicons-building',
				'category'        => 'kinh-nghiem-xay-nha',
				'tone'            => 'Expert + Warm Luxury',
				'cta_primary'     => 'Đặt Lịch Tư Vấn Riêng',
				'cta_secondary'   => 'Khám Phá Các Tác Phẩm',
				'internal_links'  => [ 'svc-design', 'svc-construction', 'contact', 'portfolio' ],
				'keyword_cluster' => 1,
				'min_words'       => 1000,
				'image_style'     => 'Professional interior, warm ambient, editorial',
				'persona'         => 'Giám đốc — Kinh doanh + chuyên môn',
				'opening_style'   => 'aspiration',
				'closing_style'   => 'invitation',
				'prompt_template' => 'Viết bài giới thiệu dịch vụ của XANH - Design & Build. Thể hiện expertise và quy trình chuyên nghiệp. Bắt đầu bằng ASPIRATION (vẽ ra không gian sống mơ ước), sau đó SOLUTION (cách XANH thực hiện), rồi PROOF (số liệu 47+ dự án, 98% sát 3D). Kết thúc bằng INVITATION nhẹ nhàng.',
				'title_patterns'  => [
					'{Dịch vụ} Tại Nha Trang — Quy Trình & Cam Kết Từ XANH',
					'Tại Sao Chọn {Dịch vụ} Trọn Gói? Góc Nhìn Từ 47+ Công Trình',
				],
			],

			'product_material' => [
				'id'              => 'product_material',
				'label'           => 'Sản Phẩm / Vật Liệu',
				'icon'            => 'dashicons-archive',
				'category'        => 'vat-lieu-xanh',
				'tone'            => 'Expert + Data-driven',
				'cta_primary'     => 'Khám Phá Dự Toán Của Bạn',
				'cta_secondary'   => '',
				'internal_links'  => [ 'svc-furniture', 'estimator', 'portfolio' ],
				'keyword_cluster' => 2,
				'min_words'       => 1200,
				'image_style'     => 'Material close-up, texture detail, warm lighting',
				'persona'         => 'KTS / Kỹ sư — Technical expert',
				'opening_style'   => 'fact',
				'closing_style'   => 'tip',
				'prompt_template' => 'Viết bài review/so sánh vật liệu hoặc sản phẩm nội thất. Dùng data-driven storytelling: số liệu thực tế từ công trình XANH. Format: bảng so sánh, ưu/nhược điểm, trường hợp nào nên dùng. KHÔNG nói chung chung — phải có con số cụ thể (giá/m², tuổi thọ, chỉ số cách nhiệt). Viết từ góc độ KTS đã trải nghiệm thực tế.',
				'title_patterns'  => [
					'{Vật liệu A} vs {Vật liệu B}: So Sánh Chi Tiết Từ {N} Công Trình',
					'Sự Thật Về {Vật liệu}: Góc Nhìn Từ Người Trong Cuộc',
				],
			],

			'local_seo' => [
				'id'              => 'local_seo',
				'label'           => 'SEO Local',
				'icon'            => 'dashicons-location',
				'category'        => 'xu-huong',
				'tone'            => 'Warm + Local Authority',
				'cta_primary'     => 'Đặt Lịch Tư Vấn Riêng',
				'cta_secondary'   => '',
				'internal_links'  => [ 'svc-design', 'contact', 'portfolio' ],
				'keyword_cluster' => 3,
				'min_words'       => 800,
				'image_style'     => 'Nha Trang cityscape, local architecture, coastal living',
				'persona'         => 'Chuyên gia địa phương — Am hiểu Nha Trang',
				'opening_style'   => 'story',
				'closing_style'   => 'cta_local',
				'prompt_template' => 'Viết bài hướng đến khách hàng tại Nha Trang / Khánh Hòa. BẮT BUỘC mention tự nhiên: "Nha Trang", "Khánh Hòa", hoặc "miền Trung" ít nhất 3-5 lần. Nội dung phải liên quan đến đặc thù địa phương (khí hậu biển, phong cách sống, benchmark giá khu vực). Viết với tư cách chuyên gia địa phương am hiểu thị trường.',
				'title_patterns'  => [
					'Top {N} {Topic} Tại Nha Trang {Năm}',
					'{Topic} Tại Khánh Hòa: Những Điều Gia Chủ Cần Biết',
				],
			],

			'knowledge' => [
				'id'              => 'knowledge',
				'label'           => 'Kiến Thức',
				'icon'            => 'dashicons-book',
				'category'        => 'kinh-nghiem-xay-nha',
				'tone'            => 'Expert + Friendly',
				'cta_primary'     => 'Nhận Cẩm Nang Xây Dựng',
				'cta_secondary'   => '',
				'internal_links'  => [ 'svc-design', 'svc-construction', 'estimator' ],
				'keyword_cluster' => 2,
				'min_words'       => 1500,
				'image_style'     => 'Infographic style, architectural blueprints, warm tones',
				'persona'         => 'KTS Senior — Thầy hướng dẫn gia chủ',
				'opening_style'   => 'question',
				'closing_style'   => 'story_open',
				'prompt_template' => 'Viết bài hướng dẫn chi tiết, từ A đến Z. Đối tượng: gia chủ lần đầu xây/cải tạo nhà. Format: bước-bước rõ ràng, có sub-headings H2/H3. Bao gồm: checklist, bảng tóm tắt, tips thực tế. Dùng ngôn ngữ dễ hiểu nhưng chuyên nghiệp — không nói xuống, không quá kỹ thuật.',
				'title_patterns'  => [
					'{Topic} Từ A-Z: Hướng Dẫn Chi Tiết Cho Gia Chủ',
					'Những Điều Cần Biết Trước Khi {Action}',
				],
			],

			'experience' => [
				'id'              => 'experience',
				'label'           => 'Kinh Nghiệm',
				'icon'            => 'dashicons-lightbulb',
				'category'        => 'kinh-nghiem-xay-nha',
				'tone'            => 'Empathy + Expert',
				'cta_primary'     => 'Đặt Lịch Tư Vấn Riêng',
				'cta_secondary'   => '',
				'internal_links'  => [ 'svc-construction', 'svc-renovation', 'portfolio', 'contact' ],
				'keyword_cluster' => 2,
				'min_words'       => 1000,
				'image_style'     => 'Before/after, construction process, real projects',
				'persona'         => 'KTS hiện trường — Trải nghiệm thực tế',
				'opening_style'   => 'story',
				'closing_style'   => 'insight',
				'prompt_template' => 'Viết bài chia sẻ kinh nghiệm thực tế từ quá trình xây dựng/thiết kế. Bắt đầu bằng EMPATHY (hiểu nỗi lo gia chủ), sau đó chia sẻ bài học. Mỗi bài học phải có: tình huống thực tế → hậu quả → cách phòng tránh. Dùng số liệu cụ thể: "98% sát 3D", "47+ gia chủ", "giảm 4°C". Viết với sự chân thành.',
				'title_patterns'  => [
					'{N} Sai Lầm Khi {Action} Lần Đầu',
					'{N} Bài Học Từ {N} Công Trình {Type}',
				],
			],

			'trends' => [
				'id'              => 'trends',
				'label'           => 'Xu Hướng',
				'icon'            => 'dashicons-admin-customizer',
				'category'        => 'xu-huong',
				'tone'            => 'Thought Leader + Visionary',
				'cta_primary'     => 'Khám Phá Các Tác Phẩm',
				'cta_secondary'   => '',
				'internal_links'  => [ 'svc-design', 'svc-furniture', 'portfolio' ],
				'keyword_cluster' => 2,
				'min_words'       => 1000,
				'image_style'     => 'Modern interior design, trend mood board, editorial',
				'persona'         => 'KTS — Thought leader, visionary',
				'opening_style'   => 'contrarian',
				'closing_style'   => 'vision',
				'prompt_template' => 'Viết bài phân tích xu hướng thiết kế/xây dựng. Positioning XANH như thought leader. Format: macro trend → tại sao nó phù hợp với Việt Nam → cách áp dụng thực tế → XANH đã áp dụng như thế nào. KHÔNG copy trends từ nước ngoài — phải localize cho thị trường VN.',
				'title_patterns'  => [
					'Xu Hướng {Topic} {Năm}: {Insight Chính}',
					'{Topic} — Trào Lưu Hay Giá Trị Bền Vững?',
				],
			],

			'construction_diary' => [
				'id'              => 'construction_diary',
				'label'           => 'Nhật Ký Xanh',
				'icon'            => 'dashicons-hammer',
				'category'        => 'nhat-ky-xanh',
				'tone'            => 'Storytelling + Warm',
				'cta_primary'     => 'Xem Hành Trình Dự Án',
				'cta_secondary'   => '',
				'internal_links'  => [ 'svc-construction', 'portfolio' ],
				'keyword_cluster' => 1,
				'min_words'       => 800,
				'image_style'     => 'Construction in progress, behind-the-scenes, workers',
				'persona'         => 'Chỉ huy công trình — Storyteller',
				'opening_style'   => 'scene',
				'closing_style'   => 'teaser',
				'prompt_template' => 'Viết nhật ký thi công theo dạng storytelling. Cấu trúc: tuần/giai đoạn → công việc chính → thách thức gặp phải → cách giải quyết → kết quả. Giọng văn ấm áp, tạo cảm giác gia chủ đang được đồng hành. Include tiến độ (% hoàn thành), chi tiết kỹ thuật thú vị, behind-the-scenes insights.',
				'title_patterns'  => [
					'Nhật Ký Thi Công: {Dự án} — Tuần {N}',
					'Hành Trình Kiến Tạo {Dự án}: Từ Bản Vẽ Đến Hiện Thực',
				],
			],

			'case_study' => [
				'id'              => 'case_study',
				'label'           => 'Case Study',
				'icon'            => 'dashicons-awards',
				'category'        => 'kinh-nghiem-xay-nha',
				'tone'            => 'Proof + Warm Luxury',
				'cta_primary'     => 'Bắt Đầu Câu Chuyện Của Bạn',
				'cta_secondary'   => '',
				'internal_links'  => [ 'svc-design', 'svc-construction', 'portfolio', 'contact' ],
				'keyword_cluster' => 1,
				'min_words'       => 1200,
				'image_style'     => 'Finished interior, before/after, warm editorial',
				'persona'         => 'KTS trưởng dự án — Kể chuyện thành công',
				'opening_style'   => 'result',
				'closing_style'   => 'invitation',
				'prompt_template' => 'Viết case study dự án hoàn thành. Cấu trúc: Thông tin dự án (diện tích, type, location) → Yêu cầu gia chủ → Thách thức → Giải pháp thiết kế → Kết quả (% sát 3D, timeline) → Testimonial gia chủ. Dùng số liệu: "120m²", "98% sát 3D", "90 ngày hoàn thành". Kết thúc bằng invitation nhẹ nhàng.',
				'title_patterns'  => [
					'{Type} {Diện tích}m² {Location}: Hành Trình Từ 3D Đến Thực Tế',
					'Câu Chuyện {Dự án}: Khi Mong Ước Thành Hiện Thực',
				],
			],

			'qa_problem_solving' => [
				'id'              => 'qa_problem_solving',
				'label'           => 'Giải Đáp Thắc Mắc',
				'icon'            => 'dashicons-editor-help',
				'category'        => 'kinh-nghiem-xay-nha',
				'tone'            => 'Direct + Expert',
				'cta_primary'     => 'Đặt Lịch Tư Vấn Riêng',
				'cta_secondary'   => '',
				'internal_links'  => [ 'svc-renovation', 'contact', 'estimator' ],
				'keyword_cluster' => 2,
				'min_words'       => 800,
				'image_style'     => 'Problem/solution visual, FAQ infographic, clean editorial',
				'persona'         => 'KTS Senior — Chuyên gia giải đáp trực tiếp',
				'opening_style'   => 'direct_answer',
				'closing_style'   => 'checklist',
				'prompt_template' => 'Viết bài giải đáp 1 câu hỏi hoặc 1 vấn đề cụ thể của gia chủ. Bắt đầu bằng DIRECT ANSWER (trả lời trực diện, ngắn gọn trong 2 câu đầu). Sau đó phân tích DEEP DIVE (nguyên nhân, các yếu tố ảnh hưởng). Cuối cùng là ACTIONABLE ADVICE (giải pháp cụ thể, checklist). Không viết lan man, tập trung 100% vào giải quyết vấn đề. Dùng format Q&A nếu phù hợp.',
				'title_patterns'  => [
					'[Giải Đáp] {Câu hỏi/Vấn đề của gia chủ}?',
					'Tại Sao {Vấn đề}? Nguyên Nhân & Cách Xử Lý Triệt Để',
				],
			],

		] );
	}

	/**
	 * Get a single angle by ID.
	 *
	 * @param string $id Angle ID.
	 * @return array|null Angle config or null if not found.
	 */
	public static function get( string $id ): ?array {
		$angles = self::get_all();
		return $angles[ $id ] ?? null;
	}

	/**
	 * Get angle IDs as a flat list (for validation).
	 *
	 * @return string[]
	 */
	public static function get_ids(): array {
		return array_keys( self::get_all() );
	}

	/**
	 * Category mapping: WP category slug → which angles use it.
	 *
	 * @return array<string, array>
	 */
	public static function get_category_map(): array {
		return [
			'kinh-nghiem-xay-nha' => [ 'service_intro', 'knowledge', 'experience', 'case_study', 'qa_problem_solving' ],
			'vat-lieu-xanh'       => [ 'product_material' ],
			'xu-huong'            => [ 'local_seo', 'trends' ],
			'nhat-ky-xanh'        => [ 'construction_diary' ],
		];
	}

	/**
	 * Keyword clusters from GOV_SEO_STRATEGY §2.
	 *
	 * @return array<int, string[]>
	 */
	public static function get_keyword_clusters(): array {
		return apply_filters( 'xanh_ai_keyword_clusters', [
			1 => [
				'thiết kế nội thất nha trang',
				'thi công nội thất nha trang',
				'thiết kế nội thất trọn gói nha trang',
				'công ty nội thất nha trang',
				'xây dựng nhà trọn gói nha trang',
				'báo giá thiết kế nội thất',
			],
			2 => [
				'kinh nghiệm xây nhà không phát sinh',
				'vật liệu xanh là gì',
				'chi phí xây nhà phố',
				'nội thất biệt thự hiện đại',
				'quy trình thiết kế nội thất',
			],
			3 => [
				'nội thất khánh hòa',
				'xây nhà nha trang',
				'kiến trúc sư nha trang',
				'showroom nội thất nha trang',
			],
		] );
	}

	/**
	 * Get suggested keywords for an angle.
	 *
	 * @param string $angle_id Angle ID.
	 * @return string[] Keyword suggestions.
	 */
	public static function get_keywords_for_angle( string $angle_id ): array {
		$angle = self::get( $angle_id );
		if ( ! $angle ) {
			return [];
		}

		$clusters = self::get_keyword_clusters();
		$cluster_id = $angle['keyword_cluster'] ?? 1;

		return $clusters[ $cluster_id ] ?? [];
	}
}
