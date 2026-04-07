/**
 * XANH AI Generator - client-side logic for the content generation page.
 *
 * Handles: angle card selection, generate AJAX, preview rendering,
 * section regeneration, image generation, save draft, and live score recalc.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */
(function ($) {
	'use strict';

	// Banned phrases for client-side score recalculation.
	var BANNED_PHRASES = [
		'Giá rẻ', 'Tiết kiệm', 'Khuyến mãi', 'Ưu đãi sốc', 'Số 1', 'Bậc nhất',
		'Click here', 'Nhấn vào đây', 'Liên hệ ngay', 'Đăng ký',
		'Trong thế giới ngày nay', 'Bạn có bao giờ tự hỏi',
		'Điều quan trọng cần lưu ý là', 'Như chúng ta đã biết',
		'Không thể phủ nhận rằng', 'Nói một cách khác', 'Trong bối cảnh'
	];

	// State.
	var state = {
		angleId: '',
		generatedData: null,
		isGenerating: false,
		isSaving: false,
		attachmentId: 0,
		sectionImages: [],
		sectionAttachments: {},
		sessionTokens: 0 // Track total tokens used in this session
	};

	var STORAGE_KEY = 'xanh_ai_draft_session';

	function saveToSession() {
		if (!state.generatedData) return;
		var sessionData = {
			state: state,
			formData: {
				topic: $('#xanh-ai-topic').val(),
				keyword: $('#xanh-ai-keyword').val(),
				secondary: $('#xanh-ai-secondary').val(),
				length: $('#xanh-ai-length').val(),
				notes: $('#xanh-ai-notes').val(),
				prompt: $('#xanh-ai-prompt-editor').val(),
				selectedLinks: getSelectedLinks()
			},
			previewData: {
				title: $('#xanh-ai-preview-title').val(),
				meta: $('#xanh-ai-preview-meta').val(),
				contentHtml: $('#xanh-ai-preview-content').html(),
				tags: $('#xanh-ai-preview-tags').val(),
				imageHtml: $('#xanh-ai-preview-image').html(),
				tokenHtml: $('#xanh-ai-token-usage').html()
			}
		};
		sessionStorage.setItem(STORAGE_KEY, JSON.stringify(sessionData));
	}

	function loadFromSession() {
		var data = sessionStorage.getItem(STORAGE_KEY);
		if (!data) return false;
		try {
			return JSON.parse(data);
		} catch (e) {
			return false;
		}
	}

	function clearSession() {
		sessionStorage.removeItem(STORAGE_KEY);
	}

	$(document).ready(function () {
		initAngleSelector();
		initFormActions();
		initPromptPreviewActions();
		initPreviewActions();
		initAutoSave();
	});

	/*--------------------------------------------------------------
	 * Auto-Save & Recovery
	 *------------------------------------------------------------*/
	function initAutoSave() {
		var sessionData = loadFromSession();
		if (sessionData && sessionData.state && sessionData.state.generatedData) {
			$('#xanh-ai-recovery-banner').show();
		}

		$('#xanh-ai-btn-discard').on('click', function() {
			clearSession();
			$('#xanh-ai-recovery-banner').slideUp();
		});

		$('#xanh-ai-btn-restore').on('click', function() {
			var data = loadFromSession();
			if (!data) return;

			// Restore state
			state = data.state;
			$('#xanh-ai-data-angle-id').val(state.angleId);

			// Restore form
			if (data.formData) {
				$('#xanh-ai-topic').val(data.formData.topic);
				$('#xanh-ai-keyword').val(data.formData.keyword);
				$('#xanh-ai-secondary').val(data.formData.secondary);
				$('#xanh-ai-length').val(data.formData.length);
				$('#xanh-ai-notes').val(data.formData.notes);
				$('#xanh-ai-prompt-editor').val(data.formData.prompt);

				// Restore internal link checkboxes.
				if (state.angleId) {
					updateLinkCheckboxes(state.angleId);
					// Re-check the saved selections (override angle defaults).
					if (data.formData.selectedLinks && data.formData.selectedLinks.length > 0) {
						$('.xanh-ai-link-cb').each(function() {
							$(this).prop('checked', data.formData.selectedLinks.indexOf($(this).val()) > -1);
						});
					}
				}
			}

			// Render baseline preview from generatedData
			if (state.generatedData) {
				// Re-bind angle info for UX
				var angle = xanhAIGen.angles[state.angleId];
				if (angle) {
					$('#xanh-ai-selected-angle').html(
						'<strong><span class="dashicons ' + angle.icon + '"></span> ' + angle.label + '</strong> — ' +
						angle.tone + ' | Min ' + angle.min_words + ' từ | CTA: ' + angle.cta_primary
					);
				}

				renderPreview(state.generatedData);
				
				// Override with user's edited previewData
				if (data.previewData) {
					$('#xanh-ai-preview-title').val(data.previewData.title);
					$('#xanh-ai-preview-meta').val(data.previewData.meta);
					$('#xanh-ai-preview-content').html(data.previewData.contentHtml);
					$('#xanh-ai-preview-tags').val(data.previewData.tags);
					$('#xanh-ai-preview-image').html(data.previewData.imageHtml);
					
					if (data.previewData.tokenHtml) {
						$('#xanh-ai-token-usage').html(data.previewData.tokenHtml).show();
					}

					// Re-init events for the restored content
					$('#xanh-ai-preview-content .xanh-ai-btn-rewrite').on('click', function() {
						rewriteSection($(this).data('section'), '', $(this));
					});
					updateCharCount($('#xanh-ai-preview-title'));
					updateCharCount($('#xanh-ai-preview-meta'));
					
					recalculateScore();
				}

				$('#xanh-ai-recovery-banner').slideUp();
				$('.xanh-ai-step').hide();
				$('#xanh-ai-step-preview').show();
			}
		});

		// beforeunload warning
		$(window).on('beforeunload', function() {
			if (state.generatedData && !state.isSaving) {
				return 'Bạn có nội dung chưa lưu. Bạn có chắc muốn rời trang?';
			}
		});
	}

	/*--------------------------------------------------------------
	 * STEP 1: Angle Selector
	 *------------------------------------------------------------*/
	function initAngleSelector() {
		$('.xanh-ai-angle-card').on('click', function () {
			var $card = $(this);
			var angleId = $card.data('angle-id');

			// Update state.
			state.angleId = angleId;
			$('#xanh-ai-data-angle-id').val(angleId);

			// Visual selection.
			$('.xanh-ai-angle-card').removeClass('selected');
			$card.addClass('selected');

			// Show selected angle info.
			var angle = xanhAIGen.angles[angleId];
			if (angle) {
				$('#xanh-ai-selected-angle').html(
					'<strong><span class="dashicons ' + angle.icon + '"></span> ' + angle.label + '</strong> — ' +
					angle.tone + ' | Min ' + angle.min_words + ' từ | CTA: ' + angle.cta_primary
				);

				// Show keyword suggestions.
				showKeywordSuggestions(angleId);

				// Update internal link checkboxes.
				updateLinkCheckboxes(angleId);
			}

			// Transition to step 2.
			$('#xanh-ai-step-angle').slideUp(300);
			$('#xanh-ai-step-form').slideDown(300, function () {
				// Auto-scroll to form.
				$('html, body').animate({ scrollTop: $('#xanh-ai-step-form').offset().top - 40 }, 300);
				$('#xanh-ai-topic').focus();
			});
		});
	}

	function showKeywordSuggestions(angleId) {
		var angle = xanhAIGen.angles[angleId];
		if (!angle) return;

		var $container = $('#xanh-ai-keyword-suggestions');
		$container.empty();

		var clusterKeywords = getClusterKeywords(angle.keyword_cluster);
		if (clusterKeywords.length === 0) return;

		// Static cluster keywords → click fills Primary Keyword.
		$container.append('<span class="xanh-ai-suggestion-label">Gợi ý: </span>');
		clusterKeywords.forEach(function (kw) {
			$container.append(
				$('<button type="button" class="button xanh-ai-keyword-btn">')
					.text(kw)
					.on('click', function () {
						$('#xanh-ai-keyword').val(kw);
					})
			);
		});

		// AI suggest button placed under secondary keywords
		var $secondaryContainer = $('#xanh-ai-secondary-suggestions');
		$secondaryContainer.empty();

		$secondaryContainer.append(
			$('<div class="xanh-ai-ai-suggest-row" style="margin-bottom: 10px;">')
				.append(
					$('<button type="button" class="button xanh-ai-btn-ai-suggest" id="xanh-ai-btn-ai-suggest">')
						.html('<span class="dashicons dashicons-lightbulb" style="vertical-align:middle"></span> Gợi ý từ khoá phụ AI')
						.on('click', function () {
							requestAIKeywords(angleId);
						})
				)
		);

		// Container for AI results.
		$secondaryContainer.append('<div id="xanh-ai-ai-keywords" class="xanh-ai-ai-keywords"></div>');
	}

	/**
	 * Update internal link checkboxes based on selected angle defaults.
	 */
	function updateLinkCheckboxes(angleId) {
		var angle = xanhAIGen.angles[angleId];
		var targets = xanhAIGen.linkTargets || {};
		var $container = $('#xanh-ai-link-checkboxes');
		$container.empty();

		var angleLinks = angle ? (angle.internal_links || []) : [];
		var groupLabels = { service: 'Dịch Vụ', utility: 'Trang Chính' };

		$.each(groupLabels, function(groupKey, groupLabel) {
			var groupTargets = targets[groupKey];
			if (!groupTargets || $.isEmptyObject(groupTargets)) return;

			$container.append('<strong style="display:block; margin: 8px 0 4px; font-size: 12px; color: #646970;">' + groupLabel + '</strong>');

			$.each(groupTargets, function(key, config) {
				if (!config.enabled) return;
				var isChecked = angleLinks.indexOf(key) > -1;
				var $label = $('<label style="display: inline-block; margin: 2px 12px 2px 0; font-size: 12px; cursor: pointer;">')
					.append(
						$('<input type="checkbox" class="xanh-ai-link-cb" value="' + key + '"' + (isChecked ? ' checked' : '') + ' style="margin-right: 4px;">')
					)
					.append(config.anchor + ' <code style="font-size:10px; color:#999;">' + config.url + '</code>');
				$container.append($label);
			});
		});
	}

	/**
	 * Get currently selected link keys from checkboxes.
	 */
	function getSelectedLinks() {
		var links = [];
		$('.xanh-ai-link-cb:checked').each(function() {
			links.push($(this).val());
		});
		return links;
	}

	/**
	 * Request AI-powered LSI keyword suggestions via AJAX.
	 */
	function requestAIKeywords(angleId) {
		var topic   = $('#xanh-ai-topic').val().trim();
		var keyword = $('#xanh-ai-keyword').val().trim();

		if (!topic) {
			alert('Vui lòng nhập chủ đề trước khi gợi ý từ khóa AI.');
			$('#xanh-ai-topic').focus();
			return;
		}

		var $btn = $('#xanh-ai-btn-ai-suggest');
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> Đang gợi ý...');

		$.ajax({
			url: xanhAI.ajaxUrl,
			method: 'POST',
			data: {
				action: 'xanh_ai_suggest_keywords',
				nonce: xanhAI.nonce,
				angle_id: angleId,
				topic: topic,
				primary_keyword: keyword,
			},
			timeout: 20000,
			success: function (res) {
				if (res.success && res.data.keywords && res.data.keywords.length > 0) {
					renderAIKeywords(res.data.keywords);
				} else {
					var msg = (res.data && res.data.message) ? res.data.message : 'Không có gợi ý.';
					$('#xanh-ai-ai-keywords').html('<p class="description" style="color:#d63638;">' + msg + '</p>');
				}
			},
			error: function () {
				$('#xanh-ai-ai-keywords').html('<p class="description" style="color:#d63638;">Lỗi kết nối. Vui lòng thử lại.</p>');
			},
			complete: function () {
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-lightbulb" style="vertical-align:middle"></span> Gợi ý thêm từ AI');
			},
		});
	}

	/**
	 * Render AI keyword chips. Click toggles into Secondary Keywords input.
	 */
	function renderAIKeywords(keywords) {
		var $container = $('#xanh-ai-ai-keywords');
		$container.empty();

		$container.append('<span class="xanh-ai-suggestion-label xanh-ai-suggestion-label--ai">AI gợi ý: </span>');
		keywords.forEach(function (kw) {
			$container.append(
				$('<button type="button" class="button xanh-ai-keyword-btn xanh-ai-keyword-btn--ai">')
					.text(kw)
					.on('click', function () {
						toggleSecondaryKeyword(kw, $(this));
					})
			);
		});
	}

	/**
	 * Toggle a keyword in the Secondary Keywords input.
	 */
	function toggleSecondaryKeyword(kw, $btn) {
		var $input   = $('#xanh-ai-secondary');
		var current  = $input.val().trim();
		var existing = current ? current.split(',').map(function (s) { return s.trim(); }) : [];

		var idx = existing.indexOf(kw);
		if (idx > -1) {
			// Remove.
			existing.splice(idx, 1);
			$btn.removeClass('selected');
		} else {
			// Add.
			existing.push(kw);
			$btn.addClass('selected');
		}

		$input.val(existing.filter(Boolean).join(', '));
	}

	function getClusterKeywords(clusterId) {
		var clusters = {
			1: [
				'thiết kế nội thất nha trang',
				'thi công nội thất nha trang',
				'thiết kế nội thất trọn gói nha trang',
				'công ty nội thất nha trang',
				'xây dựng nhà trọn gói nha trang',
				'báo giá thiết kế nội thất',
			],
			2: [
				'kinh nghiệm xây nhà không phát sinh',
				'vật liệu xanh là gì',
				'chi phí xây nhà phố',
				'nội thất biệt thự hiện đại',
				'quy trình thiết kế nội thất',
			],
			3: [
				'nội thất khánh hòa',
				'xây nhà nha trang',
				'kiến trúc sư nha trang',
				'showroom nội thất nha trang',
			],
		};
		return clusters[clusterId] || [];
	}

	/*--------------------------------------------------------------
	 * STEP 2: Form Actions
	 *------------------------------------------------------------*/
	function initFormActions() {
		// Back to angle selection — keep form data.
		$('#xanh-ai-btn-back').on('click', function () {
			$('#xanh-ai-step-form').slideUp(300);
			$('#xanh-ai-step-angle').slideDown(300, function () {
				$('html, body').animate({ scrollTop: $('#xanh-ai-step-angle').offset().top - 40 }, 300);
			});
		});

		// Preview prompt (Step 2 → Step 2.5).
		$('#xanh-ai-btn-preview-prompt').on('click', function () {
			var topic = $('#xanh-ai-topic').val().trim();
			var keyword = $('#xanh-ai-keyword').val().trim();

			if (!topic || !keyword) {
				alert('Vui lòng nhập chủ đề và từ khóa chính.');
				return;
			}

			var $btn = $(this);
			$btn.prop('disabled', true).text('Đang tải...');

			$.ajax({
				url: xanhAI.ajaxUrl,
				type: 'POST',
				data: {
					action: 'xanh_ai_preview_prompt',
					nonce: xanhAI.nonce,
					topic: topic,
					keyword: keyword,
					secondary: $('#xanh-ai-secondary').val().trim(),
					angle_id: state.angleId,
					length: $('#xanh-ai-length').val(),
					notes: $('#xanh-ai-notes').val().trim(),
				},
				success: function (response) {
					if (response.success) {
						$('#xanh-ai-prompt-editor').val(response.data.full_prompt);
						updateTokenEstimate(response.data.token_estimate);

						$('#xanh-ai-step-form').slideUp(300);
						$('#xanh-ai-step-prompt').slideDown(300, function () {
							$('html, body').animate({ scrollTop: $('#xanh-ai-step-prompt').offset().top - 40 }, 300);
						});
					} else {
						alert(response.data?.message || 'Không thể tạo prompt.');
					}
				},
				error: function () {
					alert('Lỗi kết nối. Vui lòng thử lại.');
				},
				complete: function () {
					$btn.prop('disabled', false).text('Xem Prompt →');
				},
			});
		});
	}

	function updateTokenEstimate(tokens) {
		$('#xanh-ai-token-estimate').html('Ước tính: ~<strong>' + tokens.toLocaleString() + '</strong> tokens input');
	}

	/*--------------------------------------------------------------
	 * STEP 2.5: Prompt Preview Actions
	 *------------------------------------------------------------*/
	function initPromptPreviewActions() {
		// Back to form.
		$('#xanh-ai-btn-back-to-form').on('click', function () {
			$('#xanh-ai-step-prompt').slideUp(300);
			$('#xanh-ai-step-form').slideDown(300, function () {
				$('html, body').animate({ scrollTop: $('#xanh-ai-step-form').offset().top - 40 }, 300);
			});
		});

		// Live token estimate on edit.
		$('#xanh-ai-prompt-editor').on('input', function () {
			var chars = $(this).val().length;
			updateTokenEstimate(Math.ceil(chars / 4));
		});

		// Generate content from prompt step.
		$('#xanh-ai-btn-generate').on('click', function () {
			if (state.isGenerating) return;

			var customPrompt = $('#xanh-ai-prompt-editor').val().trim();
			if (!customPrompt) {
				alert('Prompt không được để trống.');
				return;
			}

			generateContent({
				topic: $('#xanh-ai-topic').val().trim(),
				keyword: $('#xanh-ai-keyword').val().trim(),
				secondary: $('#xanh-ai-secondary').val().trim(),
				angle_id: state.angleId,
				length: $('#xanh-ai-length').val(),
				notes: $('#xanh-ai-notes').val().trim(),
				custom_prompt: customPrompt,
				'custom_links[]': getSelectedLinks(),
			});
		});
	}

	function generateContent(params) {
		state.isGenerating = true;
		var $btn = $('#xanh-ai-btn-generate');
		var $progress = $('#xanh-ai-progress');
		var $progressText = $('#xanh-ai-progress-text');

		$btn.prop('disabled', true);
		$progress.show();

		// Progress animation.
		var steps = [
			xanhAIGen.i18n.analyzing,
			xanhAIGen.i18n.writing,
			xanhAIGen.i18n.optimizing,
		];
		var stepIndex = 0;
		$progressText.text(steps[0]).css('color', '');

		var progressTimer = setInterval(function () {
			stepIndex++;
			if (stepIndex < steps.length) {
				$progressText.text(steps[stepIndex]);
			}
		}, 5000);

		$.ajax({
			url: xanhAI.ajaxUrl,
			type: 'POST',
			data: $.extend({}, params, {
				action: 'xanh_ai_generate_content',
				nonce: xanhAI.nonce,
			}),
			timeout: 120000,
			success: function (response) {
				clearInterval(progressTimer);

				if (response.success) {
					state.generatedData = response.data;
					renderPreview(response.data);
					saveToSession(); // Auto-save after generating content
					$progressText.text(xanhAIGen.i18n.generated).css('color', '#00a32a');
					setTimeout(function () {
						$progress.hide();
						$('#xanh-ai-step-prompt').slideUp(300);
						$('#xanh-ai-step-preview').slideDown(300, function () {
							// Auto-scroll to preview.
							$('html, body').animate({ scrollTop: $('#xanh-ai-step-preview').offset().top - 40 }, 400);
						});
					}, 800);
				} else {
					$progressText.text(response.data?.message || xanhAIGen.i18n.generateFailed).css('color', '#d63638');
					setTimeout(function () { $progress.hide(); $progressText.css('color', ''); }, 3000);
				}
			},
			error: function (xhr) {
				clearInterval(progressTimer);
				var msg = xhr.responseJSON?.data?.message || xanhAIGen.i18n.generateFailed;
				$progressText.text(msg).css('color', '#d63638');
				setTimeout(function () { $progress.hide(); $progressText.css('color', ''); }, 3000);
			},
			complete: function () {
				state.isGenerating = false;
				$btn.prop('disabled', false);
			},
		});
	}

	/*--------------------------------------------------------------
	 * STEP 3: Preview Rendering
	 *------------------------------------------------------------*/
	function renderPreview(data) {
		// Title.
		$('#xanh-ai-preview-title').val(data.title || '');
		updateCharCount($('#xanh-ai-preview-title'));

		// Meta.
		$('#xanh-ai-preview-meta').val(data.meta_description || '');
		updateCharCount($('#xanh-ai-preview-meta'));

		// Store section image prompts BEFORE injecting buttons (so findSectionImagePrompt works).
		state.sectionImages = data.section_images || [];
		state.sectionAttachments = {};
		
		// Update session tokens
		state.sessionTokens += (data.tokens || 0);

		// Content with section rewrite + image buttons.
		var content = data.content_html || '';
		content = injectSectionButtons(content);
		$('#xanh-ai-preview-content').html(content);

		// Tags.
		var tags = (data.tags || []).join(', ');
		$('#xanh-ai-preview-tags').val(tags);

		// FAQ.
		renderFAQ(data.faq || []);

		// Score.
		renderScore(data.score || {});
		
		// Token Usage Bar.
		renderTokenUsage(data.prompt_tokens || 0, data.output_tokens || 0);

		// Hidden data.
		$('#xanh-ai-data-image-prompt').val(data.image_prompt || '');
		$('#xanh-ai-data-faq').val(JSON.stringify(data.faq || []));
		$('#xanh-ai-data-score').val(JSON.stringify(data.score || {}));
		$('#xanh-ai-data-tokens').val(state.sessionTokens); // Save total session tokens to post meta
		$('#xanh-ai-data-excerpt').val(data.excerpt || '');
		$('#xanh-ai-data-slug').val(data.slug || '');

		// Init char counters + live score recalculation.
		initCharCounters();
		initLiveScoreRecalc();
	}

	function injectSectionButtons(html) {
		return html.replace(/<h2([^>]*)>(.*?)<\/h2>/gi, function (match, attrs, title) {
			var plainTitle = title.replace(/<[^>]+>/g, '');
			var hasPrompt = findSectionImagePrompt(plainTitle);
			var imgBtn = hasPrompt
				? '<button type="button" class="button xanh-ai-btn-section-img" data-section="' + escapeHtml(plainTitle) + '"><span class="dashicons dashicons-format-image" style="vertical-align:middle"></span> Tạo Ảnh</button>'
				: '';
			var btn = '<div class="xanh-ai-section-actions">' +
				'<button type="button" class="button xanh-ai-btn-rewrite" data-section="' +
				escapeHtml(plainTitle) + '">Viết Lại</button>' +
				imgBtn +
				'</div>';
			return btn + match;
		});
	}

	function renderFAQ(faqs) {
		var $container = $('#xanh-ai-preview-faq');
		$container.empty();

		if (!faqs || faqs.length === 0) return;

		$container.append('<h3>FAQ (' + faqs.length + ' câu hỏi)</h3>');
		faqs.forEach(function (faq) {
			$container.append(
				'<details class="xanh-ai-faq-item">' +
				'<summary>' + escapeHtml(faq.question) + '</summary>' +
				'<p>' + faq.answer + '</p>' +
				'</details>'
			);
		});
	}

	function renderScore(scoreData) {
		if (!scoreData.score && scoreData.score !== 0) return;

		var score = scoreData.score;
		var level = scoreData.level || 'Tốt';
		var color = scoreData.color || 'blue';

		// Badge.
		$('#xanh-ai-score-badge').html(
			'<span class="xanh-ai-score-number xanh-ai-score-' + color + '">' +
			score + '/100</span> ' +
			'<span class="xanh-ai-score-label">' + level + '</span>'
		);

		// Breakdown.
		var $details = $('#xanh-ai-score-details');
		$details.empty();

		if (scoreData.checks) {
			var html = '<table class="widefat striped"><thead><tr><th>Tiêu chí</th><th>Điểm</th><th>Chi tiết</th></tr></thead><tbody>';
			$.each(scoreData.checks, function (key, check) {
				var icon = check.pass ? '<span class="dashicons dashicons-yes" style="color:#00a32a"></span>' : '<span class="dashicons dashicons-no" style="color:#d63638"></span>';
				html += '<tr>';
				html += '<td>' + icon + ' ' + key + '</td>';
				html += '<td>' + check.score + '/' + check.max + '</td>';
				html += '<td>' + escapeHtml(check.message) + '</td>';
				html += '</tr>';
			});
			html += '</tbody></table>';
			$details.html(html);
		}
	}

	/*--------------------------------------------------------------
	 * Client-Side Score Recalculation (Item 4)
	 *------------------------------------------------------------*/
	function initLiveScoreRecalc() {
		// Debounce recalculation on title/meta edits.
		var recalcTimer;
		$('#xanh-ai-preview-title, #xanh-ai-preview-meta').off('input.score').on('input.score', function () {
			clearTimeout(recalcTimer);
			recalcTimer = setTimeout(recalculateScore, 500);
		});
	}

	function recalculateScore() {
		var title   = $('#xanh-ai-preview-title').val() || '';
		var meta    = $('#xanh-ai-preview-meta').val() || '';
		var keyword = $('#xanh-ai-keyword').val() || '';
		var angle   = xanhAIGen.angles[state.angleId] || {};
		var minWords = angle.min_words || 800;

		// Get clean content.
		var $contentClone = $('#xanh-ai-preview-content').clone();
		$contentClone.find('.xanh-ai-section-actions').remove();
		var contentHtml = $contentClone.html() || '';
		var plainText   = $contentClone.text().trim();

		var score  = 0;
		var checks = {};

		// 1. Title (max 10).
		var titleLen = title.length;
		var titleScore = 0;
		if (titleLen > 10 && titleLen <= 60) {
			titleScore = 5;
			if (keyword && title.toLowerCase().indexOf(keyword.toLowerCase()) >= 0 && title.toLowerCase().indexOf(keyword.toLowerCase()) <= 10) {
				titleScore += 3;
			}
			if (title.indexOf('XANH') >= 0) {
				titleScore += 2;
			}
		}
		score += titleScore;
		checks.title = { pass: titleScore >= 8, score: titleScore, max: 10, message: 'Title: ' + titleLen + '/60 ký tự' };

		// 2. Meta (max 10).
		var metaLen = meta.length;
		var metaScore = 0;
		if (metaLen > 50 && metaLen <= 160) {
			metaScore = 5;
			if (keyword && meta.toLowerCase().indexOf(keyword.toLowerCase()) >= 0) {
				metaScore += 3;
			}
			if (/→|Xem|Đọc|Khám phá|Đặt lịch/u.test(meta)) {
				metaScore += 2;
			}
		}
		score += metaScore;
		var metaMsg = metaLen > 160 ? 'Meta quá dài: ' + metaLen + '/160 ký tự' : 'Meta: ' + metaLen + '/160 ký tự';
		checks.meta = { pass: metaScore >= 8, score: metaScore, max: 10, message: metaMsg };

		// 3. Headings (max 10).
		var h2Count = (contentHtml.match(/<h2\b/gi) || []).length;
		var h3Count = (contentHtml.match(/<h3\b/gi) || []).length;
		var h1Count = (contentHtml.match(/<h1\b/gi) || []).length;
		var headingScore = 0;
		if (h1Count <= 1) headingScore += 4;
		if (h2Count >= 2) headingScore += 4;
		if (h3Count >= 1) headingScore += 2;
		score += headingScore;
		checks.headings = { pass: headingScore >= 8, score: headingScore, max: 10, message: 'H2: ' + h2Count + ', H3: ' + h3Count };

		// 4. Word count (max 15).
		var wordCount = plainText ? plainText.split(/\s+/).length : 0;
		var wordScore = 0;
		if (wordCount >= minWords) wordScore = 15;
		else if (wordCount >= minWords * 0.7) wordScore = 10;
		else if (wordCount >= minWords * 0.5) wordScore = 5;
		score += wordScore;
		checks.words = { pass: wordScore >= 10, score: wordScore, max: 15, message: 'Số từ: ' + wordCount + '/' + minWords };

		// 5. Internal links (max 15).
		var hasPortfolio = /href=['"][^'"]*\/du-an\//i.test(contentHtml);
		var hasContact   = /href=['"][^'"]*\/lien-he\//i.test(contentHtml);
		var linkCount    = (contentHtml.match(/<a\s+[^>]*href=['"][^'"]+['"]/gi) || []).length;
		var linkScore = 0;
		if (hasPortfolio) linkScore += 5;
		if (hasContact) linkScore += 5;
		if (linkCount >= 3) linkScore += 5;
		else if (linkCount >= 2) linkScore += 3;
		score += linkScore;
		checks.links = { pass: linkScore >= 10, score: linkScore, max: 15, message: 'Links: ' + linkCount + ' (Portfolio: ' + (hasPortfolio ? 'OK' : 'X') + ', Contact: ' + (hasContact ? 'OK' : 'X') + ')' };

		// 6. Banned words (max 15).
		var foundBanned = [];
		var lowerText = plainText.toLowerCase();
		BANNED_PHRASES.forEach(function (phrase) {
			if (lowerText.indexOf(phrase.toLowerCase()) >= 0) {
				foundBanned.push(phrase);
			}
		});
		var bannedScore = foundBanned.length === 0 ? 15 : 0;
		score += bannedScore;
		checks.banned = { pass: bannedScore > 0, score: bannedScore, max: 15, message: foundBanned.length === 0 ? 'Không có từ cấm' : 'Từ cấm: ' + foundBanned.slice(0, 3).join(', ') };

		// 7. Image (max 10).
		var hasImage = $('#xanh-ai-preview-image img').length > 0;
		var imageScore = hasImage ? 10 : 0;
		score += imageScore;
		checks.image = { pass: hasImage, score: imageScore, max: 10, message: hasImage ? 'Có featured image' : 'Chưa có featured image' };

		// 8. External link (max 5).
		var hasExternal = /<a\s+[^>]*href=['"]https?:\/\/(?!xanhdesignbuild)/i.test(contentHtml);
		var extScore = hasExternal ? 5 : 0;
		score += extScore;
		checks.external = { pass: hasExternal, score: extScore, max: 5, message: hasExternal ? 'Có external link' : 'Chưa có external link' };

		// 9. Keyword density (max 10).
		var kwScore = 0;
		if (keyword && wordCount > 0) {
			var kwLower = keyword.toLowerCase();
			var kwCount = 0;
			var idx = lowerText.indexOf(kwLower);
			while (idx !== -1) {
				kwCount++;
				idx = lowerText.indexOf(kwLower, idx + 1);
			}
			var kwDensity = (kwCount / Math.max(1, wordCount)) * 100;
			if (kwDensity >= 0.5 && kwDensity <= 1.5) kwScore = 10;
			else if (kwDensity > 0 && kwDensity < 2.5) kwScore = 5;
		}
		score += kwScore;
		checks.keyword = { pass: kwScore >= 5, score: kwScore, max: 10, message: keyword ? 'Keyword "' + keyword + '": ' + (typeof kwCount !== 'undefined' ? kwCount : 0) + ' lần' : 'Chưa có keyword' };

		// Clamp score.
		score = Math.min(100, score);

		// Get level.
		var level, color;
		if (score >= 90) { level = 'Xuất sắc'; color = 'green'; }
		else if (score >= 70) { level = 'Tốt'; color = 'blue'; }
		else if (score >= 50) { level = 'Trung bình'; color = 'yellow'; }
		else { level = 'Yếu'; color = 'red'; }

		var newScoreData = { score: score, level: level, color: color, checks: checks };
		renderScore(newScoreData);

		// Update hidden score data.
		$('#xanh-ai-data-score').val(JSON.stringify(newScoreData));
		
		// Auto-save when score/content is updated
		saveToSession();
	}

	/*--------------------------------------------------------------
	 * STEP 3: Preview Actions
	 *------------------------------------------------------------*/
	function initPreviewActions() {
		// Back to prompt step — keep all data.
		$('#xanh-ai-btn-back-form').on('click', function () {
			$('#xanh-ai-step-preview').slideUp(300);
			$('#xanh-ai-step-prompt').slideDown(300, function () {
				$('html, body').animate({ scrollTop: $('#xanh-ai-step-prompt').offset().top - 40 }, 300);
			});
		});

		// Save Draft.
		$('#xanh-ai-btn-save-draft').on('click', function () {
			if (state.isSaving) return;
			saveDraft();
		});

		// Generate Image.
		$('#xanh-ai-btn-gen-image').on('click', function () {
			generateImage();
		});

		// Section Rewrite (delegated).
		$(document).on('click', '.xanh-ai-btn-rewrite', function () {
			var $btn = $(this);
			var sectionTitle = $btn.data('section');
			var notes = prompt('Ghi chú cho section "' + sectionTitle + '" (hoặc để trống):');
			if (notes === null) return;
			rewriteSection(sectionTitle, notes, $btn);
		});

		// Section Image Generation (delegated).
		$(document).on('click', '.xanh-ai-btn-section-img', function () {
			var sectionTitle = $(this).data('section');
			showSectionImageModal(sectionTitle);
		});
	}

	function saveDraft() {
		state.isSaving = true;
		var $btn = $('#xanh-ai-btn-save-draft');
		var $result = $('#xanh-ai-save-result');

		$btn.prop('disabled', true).text(xanhAIGen.i18n.saving);
		$result.hide();

		// Collect editable data from preview.
		var contentHtml = $('#xanh-ai-preview-content').clone();
		contentHtml.find('.xanh-ai-section-actions').remove();
		var cleanContent = contentHtml.html();

		$.ajax({
			url: xanhAI.ajaxUrl,
			type: 'POST',
			data: {
				action: 'xanh_ai_save_draft',
				nonce: xanhAI.nonce,
				angle_id: state.angleId,
				keyword: $('#xanh-ai-keyword').val(),
				title: $('#xanh-ai-preview-title').val(),
				slug: $('#xanh-ai-data-slug').val(),
				meta_description: $('#xanh-ai-preview-meta').val(),
				excerpt: $('#xanh-ai-data-excerpt').val(),
				content_html: cleanContent,
				tags: $('#xanh-ai-preview-tags').val().split(',').map(function(t) { return t.trim(); }),
				faq: $('#xanh-ai-data-faq').val(),
				image_prompt: $('#xanh-ai-data-image-prompt').val(),
				attachment_id: state.attachmentId,
				score: $('#xanh-ai-data-score').val(),
				tokens: $('#xanh-ai-data-tokens').val(),
				history_id: (state.generatedData && state.generatedData.history_id) ? state.generatedData.history_id : 0,
			},
			success: function (response) {
				if (response.success) {
					clearSession(); // Clear session explicitly on success
					state.generatedData = null; // Prevent beforeunload warning
					$result
						.html(
							'<strong>' + response.data.message + '</strong> ' +
							'<a href="' + response.data.edit_url + '" class="button">Mở trong Editor →</a>'
						)
						.css('color', '#00a32a').show();
				} else {
					$result
						.text(response.data?.message || xanhAIGen.i18n.saveFailed)
						.css('color', '#d63638').show();
				}
			},
			error: function () {
				$result.text(xanhAIGen.i18n.saveFailed).css('color', '#d63638').show();
			},
			complete: function () {
				state.isSaving = false;
				$btn.prop('disabled', false).text('Lưu Draft');
			},
		});
	}

	function renderTokenUsage(promptTokens, outputTokens) {
		var $usageBar = $('#xanh-ai-token-usage');
		var totalText = promptTokens + outputTokens;
		var html = '<span class="dashicons dashicons-chart-bar" style="vertical-align:middle"></span> <strong>' + state.sessionTokens.toLocaleString() + '</strong>&nbsp;tokens phiên này (' +
			'Tạo bài: ' + totalText.toLocaleString() + ' = ' +
			promptTokens.toLocaleString() + ' in + ' +
			outputTokens.toLocaleString() + ' out)';
			
		$usageBar.html(html).show();
	}

	function rewriteSection(sectionTitle, notes, $btn) {
		var contentHtml = $('#xanh-ai-preview-content').clone();
		contentHtml.find('.xanh-ai-section-actions').remove();
		var cleanContent = contentHtml.html();

		$btn.prop('disabled', true).text(xanhAIGen.i18n.regenerating);

		$.ajax({
			url: xanhAI.ajaxUrl,
			type: 'POST',
			data: {
				action: 'xanh_ai_regenerate_section',
				nonce: xanhAI.nonce,
				content: cleanContent,
				section_title: sectionTitle,
				notes: notes,
				angle_id: state.angleId,
			},
			timeout: 60000,
			success: function (response) {
				if (response.success && response.data.section_html) {
					replaceSectionInPreview(sectionTitle, response.data.section_html);
					$btn.text(xanhAIGen.i18n.regenerated);
					// Recalculate score after section change.
					setTimeout(recalculateScore, 300);
				} else {
					alert(response.data?.message || xanhAIGen.i18n.generateFailed);
					$btn.text('Viết Lại');
				}
			},
			error: function () {
				alert(xanhAIGen.i18n.generateFailed);
				$btn.text('Viết Lại');
			},
			complete: function () {
				$btn.prop('disabled', false);
				setTimeout(function () { $btn.text('Viết Lại'); }, 2000);
			},
		});
	}

	function replaceSectionInPreview(sectionTitle, newHtml) {
		var $content = $('#xanh-ai-preview-content');
		var $h2s = $content.find('h2');

		$h2s.each(function () {
			var h2Text = $(this).text().trim();
			if (h2Text === sectionTitle) {
				var $sectionActions = $(this).prev('.xanh-ai-section-actions');

				// Collect elements until next H2 or section-actions.
				var $toRemove = $();
				var $next = $(this).next();
				while ($next.length && !$next.is('h2') && !$next.hasClass('xanh-ai-section-actions')) {
					$toRemove = $toRemove.add($next);
					$next = $next.next();
				}

				// Remove old and replace.
				$toRemove.remove();
				$(this).replaceWith(newHtml);

				// Re-inject rewrite buttons cleanly.
				$content.find('.xanh-ai-section-actions').remove();
				$content.html(injectSectionButtons($content.html()));

				return false; // break
			}
		});
	}

	function generateImage() {
		var prompt = $('#xanh-ai-data-image-prompt').val();
		if (!prompt) {
			alert('Chưa có prompt cho hình ảnh.');
			return;
		}

		var $btn = $('#xanh-ai-btn-gen-image');
		$btn.prop('disabled', true);

		// Countdown timer so user sees progress.
		var seconds = 0;
		var timer = setInterval(function () {
			seconds++;
			$btn.text('⏳ Đang tạo ảnh... ' + seconds + 's');
		}, 1000);

		$.ajax({
			url: xanhAI.ajaxUrl,
			type: 'POST',
			data: {
				action: 'xanh_ai_generate_image',
				nonce: xanhAI.nonce,
				image_prompt: prompt,
				seo_keyword: $('#xanh-ai-keyword').val() || '',
				seo_title: $('#xanh-ai-preview-title').val() || '',
			},
			timeout: 150000,
			success: function (response) {
				if (response.success && response.data.url) {
					// Store attachment ID for save draft.
					state.attachmentId = response.data.attachment_id || 0;
					var imgAlt = ($('#xanh-ai-keyword').val() || 'Generated Image');
					$('#xanh-ai-preview-image').html(
						'<img src="' + response.data.url + '" style="max-width:100%; height:auto;" alt="' + escapeHtml(imgAlt) + '">'
					);
					$btn.text('Đã tạo ảnh!');
					// Recalculate score (image now exists).
					setTimeout(recalculateScore, 300);
				} else {
					alert(response.data?.message || 'Tạo ảnh thất bại.');
				}
			},
			error: function (jqXHR, textStatus) {
				if (textStatus === 'timeout') {
					alert('Tạo ảnh quá thời gian (>120s). API có thể đang quá tải. Vui lòng thử lại.');
				} else {
					alert('Tạo ảnh thất bại: ' + (textStatus || 'Unknown error'));
				}
			},
			complete: function () {
				clearInterval(timer);
				$btn.prop('disabled', false);
				setTimeout(function () { $btn.html('<span class="dashicons dashicons-format-image"></span> Tạo Ảnh Ngay'); }, 3000);
			},
		});
	}

	/*--------------------------------------------------------------
	 * Section Image Generation
	 *------------------------------------------------------------*/

	/**
	 * Find AI-generated image prompt for a section H2 title.
	 */
	function findSectionImagePrompt(sectionTitle) {
		if (!state.sectionImages || !state.sectionImages.length) return null;
		var title = sectionTitle.trim();
		for (var i = 0; i < state.sectionImages.length; i++) {
			var si = state.sectionImages[i];
			if (si.after_h2 && si.after_h2.trim() === title) {
				return si.prompt;
			}
		}
		// Fuzzy: try includes match.
		for (var j = 0; j < state.sectionImages.length; j++) {
			var sj = state.sectionImages[j];
			if (sj.after_h2 && (title.indexOf(sj.after_h2.trim()) >= 0 || sj.after_h2.trim().indexOf(title) >= 0)) {
				return sj.prompt;
			}
		}
		return null;
	}

	/**
	 * Show modal to preview/edit image prompt, then generate.
	 */
	function showSectionImageModal(sectionTitle) {
		var prompt = findSectionImagePrompt(sectionTitle) || '';

		// Remove existing modal.
		$('#xanh-ai-img-modal-overlay').remove();

		var modalHtml =
			'<div id="xanh-ai-img-modal-overlay" class="xanh-ai-modal-overlay">' +
			'<div class="xanh-ai-modal">' +
			'<h3>Tạo Ảnh Cho Section: ' + escapeHtml(sectionTitle) + '</h3>' +
			'<p class="description">Chỉnh sửa prompt ảnh trước khi tạo.</p>' +
			'<textarea id="xanh-ai-img-modal-prompt" rows="6" class="large-text" style="font-family:monospace; font-size:13px;">' + escapeHtml(prompt) + '</textarea>' +
			'<div class="xanh-ai-modal-actions">' +
			'<button type="button" class="button" id="xanh-ai-img-modal-cancel">Hủy</button>' +
			'<button type="button" class="button button-primary" id="xanh-ai-img-modal-generate"><span class="dashicons dashicons-format-image" style="vertical-align:middle"></span> Tạo Ảnh</button>' +
			'</div>' +
			'<div id="xanh-ai-img-modal-status" style="margin-top:10px;"></div>' +
			'</div>' +
			'</div>';

		$('body').append(modalHtml);

		// Cancel.
		$('#xanh-ai-img-modal-cancel, #xanh-ai-img-modal-overlay').on('click', function (e) {
			if (e.target === this) {
				$('#xanh-ai-img-modal-overlay').remove();
			}
		});
		// Prevent modal content clicks from closing.
		$('.xanh-ai-modal').on('click', function (e) { e.stopPropagation(); });

		// Generate.
		$('#xanh-ai-img-modal-generate').on('click', function () {
			var editedPrompt = $('#xanh-ai-img-modal-prompt').val().trim();
			if (!editedPrompt) {
				alert('Prompt không được để trống.');
				return;
			}
			generateAndInsertSectionImage(sectionTitle, editedPrompt);
		});
	}

	/**
	 * Call API to generate image and insert into content preview.
	 */
	function generateAndInsertSectionImage(sectionTitle, imagePrompt) {
		var $btn = $('#xanh-ai-img-modal-generate');
		var $status = $('#xanh-ai-img-modal-status');
		$btn.prop('disabled', true);

		var seconds = 0;
		var timer = setInterval(function () {
			seconds++;
			$status.html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> Đang tạo ảnh... ' + seconds + 's');
		}, 1000);
		$status.html('<span class="spinner is-active" style="float:none;margin:0 4px 0 0;"></span> Đang tạo ảnh...');

		$.ajax({
			url: xanhAI.ajaxUrl,
			type: 'POST',
			data: {
				action: 'xanh_ai_generate_image',
				nonce: xanhAI.nonce,
				image_prompt: imagePrompt,
				seo_keyword: $('#xanh-ai-keyword').val() || '',
				seo_title: $('#xanh-ai-preview-title').val() || '',
				seo_section_title: sectionTitle,
			},
			timeout: 150000,
			success: function (response) {
				if (response.success && response.data.url) {
					var attId = response.data.attachment_id || 0;
					state.sectionAttachments[sectionTitle] = attId;

					// Insert <figure> after the matching H2 in preview.
					var $content = $('#xanh-ai-preview-content');
					var $h2s = $content.find('h2');
					$h2s.each(function () {
						if ($(this).text().trim() === sectionTitle) {
							// Remove any existing figure for this section.
							var $next = $(this).next();
							if ($next.hasClass('xanh-ai-section-figure')) {
								$next.remove();
							}
							// Insert figure.
							var seoAlt = ($('#xanh-ai-keyword').val() || '') + ' - ' + sectionTitle;
							var figureHtml =
								'<figure class="xanh-ai-section-figure" style="margin:1em 0;">' +
								'<img src="' + response.data.url + '" alt="' + escapeHtml(seoAlt) + '" style="max-width:100%; height:auto; border-radius:8px;" loading="lazy">' +
								'</figure>';
							$(this).after(figureHtml);
							return false;
						}
					});

					// Update button text.
					$('.xanh-ai-btn-section-img[data-section="' + sectionTitle + '"]').text('Đã tạo');

					// Close modal.
					$('#xanh-ai-img-modal-overlay').remove();

					// Recalculate score.
					setTimeout(recalculateScore, 300);
				} else {
					$status.html('<span style="color:#d63638;">' + (response.data?.message || 'Tạo ảnh thất bại.') + '</span>');
				}
			},
			error: function (jqXHR, textStatus) {
				var msg = textStatus === 'timeout' ? 'Tạo ảnh quá thời gian (>120s).' : 'Lỗi kết nối.';
				$status.html('<span style="color:#d63638;">' + msg + '</span>');
			},
			complete: function () {
				clearInterval(timer);
				$btn.prop('disabled', false);
			},
		});
	}

	/*--------------------------------------------------------------
	 * Utilities
	 *------------------------------------------------------------*/
	function escapeHtml(str) {
		var div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	function initCharCounters() {
		$('#xanh-ai-preview-title, #xanh-ai-preview-meta').off('input.charcount').on('input.charcount', function () {
			updateCharCount($(this));
		});
	}

	function updateCharCount($input) {
		var $counter = $input.siblings('.xanh-ai-char-count');
		if (!$counter.length) return;

		var max = parseInt($counter.data('max'), 10);
		var len = $input.val().length;
		$counter.text(len + '/' + max);

		if (len > max) {
			$counter.addClass('over-limit');
		} else {
			$counter.removeClass('over-limit');
		}
	}

})(jQuery);
