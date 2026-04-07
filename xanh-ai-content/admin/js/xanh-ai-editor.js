/**
 * XANH AI — Editor Image Generator (Popup Mode).
 *
 * Handles the AI Image Generation popup inside the WordPress post editor.
 * Works with the TinyMCE toolbar button and sidebar meta box.
 *
 * @package Xanh_AI_Content
 * @since   1.3.0
 */
(function ($) {
	'use strict';

	var state = {
		isGenerating: false,
		isPrompting: false,
		lastAttachmentId: 0,
		lastImageUrl: '',
		editorRef: null, // TinyMCE editor reference
	};

	$(document).ready(function () {
		// Listen for TinyMCE toolbar button event.
		document.addEventListener('xanh-ai-open-image-popup', function (e) {
			var detail = e.detail || {};
			state.editorRef = detail.editor || null;
			openImagePopup(detail);
		});

		// Sidebar meta box button also opens popup.
		$(document).on('click', '#xanh-ai-img-open-popup', function () {
			var editor = (typeof tinymce !== 'undefined' && tinymce.activeEditor) ? tinymce.activeEditor : null;
			var selectedText = editor ? editor.selection.getContent({ format: 'text' }).trim() : '';
			var fullContent = editor ? editor.getContent({ format: 'text' }).trim() : '';
			state.editorRef = editor;

			openImagePopup({
				selectedText: selectedText,
				fullContent: fullContent,
				postTitle: $('#title').val() || '',
				keyword: getKeyword(),
			});
		});
	});

	/*--------------------------------------------------------------
	 * Popup: Open
	 *------------------------------------------------------------*/
	function openImagePopup(detail) {
		// Remove existing popup.
		$('#xanh-ai-img-popup-overlay').remove();

		var selectedText = detail.selectedText || '';
		var postTitle = detail.postTitle || '';
		var keyword = detail.keyword || '';

		// Build selected text preview.
		var selectedPreview = '';
		if (selectedText) {
			var truncated = selectedText.length > 200 ? selectedText.substring(0, 200) + '...' : selectedText;
			selectedPreview =
				'<div class="xanh-ai-popup-selected">' +
				'<span class="dashicons dashicons-editor-quote" style="color:#2271b1; margin-right:4px; vertical-align:middle;"></span>' +
				'<strong>Đoạn văn đã chọn:</strong>' +
				'<div class="xanh-ai-popup-selected-text">"' + escapeHtml(truncated) + '"</div>' +
				'</div>';
		} else {
			selectedPreview =
				'<div class="xanh-ai-popup-selected xanh-ai-popup-selected--empty">' +
				'<span class="dashicons dashicons-info" style="color:#646970; margin-right:4px; vertical-align:middle;"></span>' +
				'<em>Chưa chọn đoạn văn. Bôi đen đoạn cần minh họa trước khi bấm nút để có prompt chính xác hơn.</em>' +
				'</div>';
		}

		var popupHtml =
			'<div id="xanh-ai-img-popup-overlay" class="xanh-ai-popup-overlay">' +
			'<div class="xanh-ai-popup">' +

			// Header
			'<div class="xanh-ai-popup-header">' +
			'<h3><span class="dashicons dashicons-format-image" style="margin-right:6px; color:#2271b1;"></span>XANH AI — Tạo Ảnh Minh Họa</h3>' +
			'<button type="button" class="xanh-ai-popup-close" title="Đóng">&times;</button>' +
			'</div>' +

			// Body
			'<div class="xanh-ai-popup-body">' +

			// Selected text preview
			selectedPreview +

			// Prompt area
			'<div class="xanh-ai-popup-field" style="margin-top:14px;">' +
			'<label for="xanh-ai-popup-prompt">🎨 Prompt Ảnh:</label>' +
			'<textarea id="xanh-ai-popup-prompt" rows="5" class="widefat" ' +
			'style="font-family:monospace; font-size:12px; resize:vertical;" ' +
			'placeholder="Prompt sẽ được tạo tự động..."></textarea>' +
			'<div style="display:flex; gap:6px; margin-top:6px;">' +
			'<button type="button" id="xanh-ai-popup-auto-prompt" class="button button-small">' +
			'<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span> Auto Prompt' +
			'</button>' +
			(selectedText ?
				'<button type="button" id="xanh-ai-popup-ai-prompt" class="button button-small" style="color:#7c3aed; border-color:#c4b5fd;">' +
				'<span class="dashicons dashicons-superhero" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span> AI Gợi Ý Prompt' +
				'</button>' : '') +
			'</div>' +
			'</div>' +

			// Insert mode
			'<div class="xanh-ai-popup-field" style="margin-top:14px;">' +
			'<label>Chèn ảnh:</label>' +
			'<div style="display:flex; gap:16px; margin-top:4px;">' +
			'<label style="font-weight:normal; cursor:pointer;"><input type="radio" name="xanh_ai_insert_mode" value="inline" checked /> Tại vị trí cursor</label>' +
			'<label style="font-weight:normal; cursor:pointer;"><input type="radio" name="xanh_ai_insert_mode" value="featured" /> Làm Featured Image</label>' +
			'</div>' +
			'</div>' +

			// Status
			'<div id="xanh-ai-popup-status" style="display:none; margin-top:10px;"></div>' +

			// Preview
			'<div id="xanh-ai-popup-preview" style="display:none; margin-top:16px; position:relative; border-radius:12px; overflow:hidden; box-shadow:0 6px 16px rgba(0,0,0,0.1); border:1px solid #dcdcde;">' +
			'<img id="xanh-ai-popup-preview-img" src="" alt="" style="display:block; width:100%; height:auto;" />' +
			'<div style="position:absolute; bottom:0; left:0; right:0; padding:20px 16px 12px; background:linear-gradient(to top, rgba(0,0,0,0.85), transparent); display:flex; justify-content:space-between; align-items:center;">' +
			'  <div id="xanh-ai-popup-preview-msg" style="color:#fff; font-size:13px; font-weight:500; text-shadow:0 1px 2px rgba(0,0,0,0.5);"></div>' +
			'  <a id="xanh-ai-popup-preview-link" href="#" target="_blank" class="button button-small" style="background:rgba(255,255,255,0.95); border:none; color:#1d2327; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">' +
			'    <span class="dashicons dashicons-external" style="font-size:14px; width:14px; height:14px;"></span> Mở ảnh gốc' +
			'  </a>' +
			'</div>' +
			'</div>' +

			'</div>' + // end body

			// Footer
			'<div class="xanh-ai-popup-footer">' +
			'<button type="button" id="xanh-ai-popup-cancel" class="button">Hủy</button>' +
			'<button type="button" id="xanh-ai-popup-generate" class="button button-primary">' +
			'<span class="dashicons dashicons-format-image" style="vertical-align:middle;"></span> Tạo Ảnh' +
			'</button>' +
			'</div>' +

			'</div>' + // end popup
			'</div>'; // end overlay

		$('body').append(popupHtml);

		// Auto-fill prompt.
		if (selectedText) {
			// If text is selected, try AI Prompt automatically.
			generateAIPrompt(selectedText, detail.fullContent || '', postTitle, keyword);
		} else {
			// Fallback: simple auto prompt.
			$('#xanh-ai-popup-prompt').val(buildAutoPrompt(postTitle, keyword, ''));
		}

		// Event handlers.
		initPopupEvents(detail);
	}

	/*--------------------------------------------------------------
	 * Popup: Events
	 *------------------------------------------------------------*/
	function initPopupEvents(detail) {
		// Close popup.
		$('#xanh-ai-popup-cancel, .xanh-ai-popup-close').on('click', function () {
			$('#xanh-ai-img-popup-overlay').remove();
		});

		// Click overlay to close.
		$('#xanh-ai-img-popup-overlay').on('click', function (e) {
			if (e.target === this) {
				$(this).remove();
			}
		});

		// Prevent popup content clicks from closing.
		$('.xanh-ai-popup').on('click', function (e) { e.stopPropagation(); });

		// ESC to close.
		$(document).on('keydown.xanhPopup', function (e) {
			if (e.key === 'Escape') {
				$('#xanh-ai-img-popup-overlay').remove();
				$(document).off('keydown.xanhPopup');
			}
		});

		// Auto Prompt button.
		$('#xanh-ai-popup-auto-prompt').on('click', function () {
			$('#xanh-ai-popup-prompt').val(
				buildAutoPrompt(detail.postTitle || '', detail.keyword || '', detail.selectedText || '')
			).focus();
		});

		// AI Prompt button (context-aware).
		$('#xanh-ai-popup-ai-prompt').on('click', function () {
			generateAIPrompt(
				detail.selectedText || '',
				detail.fullContent || '',
				detail.postTitle || '',
				detail.keyword || ''
			);
		});

		// Generate Image.
		$('#xanh-ai-popup-generate').on('click', function () {
			var prompt = $('#xanh-ai-popup-prompt').val().trim();
			if (!prompt) {
				alert('Vui lòng nhập hoặc tạo prompt trước.');
				return;
			}
			var mode = $('input[name="xanh_ai_insert_mode"]:checked').val() || 'inline';
			generateImage(prompt, mode);
		});
	}

	/*--------------------------------------------------------------
	 * AI Prompt Generation (Context-Aware)
	 *------------------------------------------------------------*/
	function generateAIPrompt(selectedText, fullContent, postTitle, keyword) {
		if (state.isPrompting) return;
		state.isPrompting = true;

		var $btn = $('#xanh-ai-popup-ai-prompt');
		var $prompt = $('#xanh-ai-popup-prompt');

		$btn.prop('disabled', true);
		$prompt.val('').attr('placeholder', '⏳ AI đang tạo prompt...');

		$.ajax({
			url: xanhAI.ajaxUrl,
			type: 'POST',
			data: {
				action: 'xanh_ai_generate_image_prompt',
				nonce: xanhAI.nonce,
				selected_text: selectedText,
				full_content: fullContent.substring(0, 3000),
				post_title: postTitle,
				keyword: keyword,
				post_id: $('#post_ID').val() || 0,
			},
			timeout: 30000,
			success: function (response) {
				if (response.success && response.data.prompt) {
					$prompt.val(response.data.prompt).focus();
				} else {
					console.warn('[XANH AI] Prompt generation failed:', response.data?.message || 'Unknown');
					$prompt.val(buildAutoPrompt(postTitle, keyword, selectedText));
					$prompt.attr('placeholder', 'Nhập prompt mô tả ảnh...');
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {
				// Fallback to auto prompt with selected text.
				console.error('[XANH AI] Prompt AJAX error:', textStatus, errorThrown, jqXHR.status);
				$prompt.val(buildAutoPrompt(postTitle, keyword, selectedText));
			},
			complete: function () {
				state.isPrompting = false;
				$btn.prop('disabled', false);
				$prompt.attr('placeholder', 'Nhập prompt mô tả ảnh...');
			},
		});
	}

	/*--------------------------------------------------------------
	 * Image Generation
	 *------------------------------------------------------------*/
	function generateImage(prompt, mode) {
		if (state.isGenerating) return;
		state.isGenerating = true;

		var $status = $('#xanh-ai-popup-status');
		var $preview = $('#xanh-ai-popup-preview');
		var $btn = $('#xanh-ai-popup-generate');

		$btn.prop('disabled', true);
		$preview.hide();

		// Countdown timer.
		var seconds = 0;
		var timer = setInterval(function () {
			seconds++;
			$status.html(
				'<div class="xanh-ai-img-box-status">' +
				'<span class="spinner is-active" style="float:none; margin:0 6px 0 0;"></span>' +
				'Đang tạo ảnh... ' + seconds + 's</div>'
			);
		}, 1000);
		$status.html(
			'<div class="xanh-ai-img-box-status">' +
			'<span class="spinner is-active" style="float:none; margin:0 6px 0 0;"></span>' +
			'Đang tạo ảnh...</div>'
		).show();

		$.ajax({
			url: xanhAI.ajaxUrl,
			type: 'POST',
			data: {
				action: 'xanh_ai_generate_image',
				nonce: xanhAI.nonce,
				image_prompt: prompt,
				seo_keyword: getKeyword(),
				seo_title: $('#title').val() || '',
			},
			timeout: 150000,
			success: function (response) {
				if (response.success && response.data.url) {
					state.lastAttachmentId = response.data.attachment_id || 0;
					state.lastImageUrl = response.data.url;

					// Show preview.
					$('#xanh-ai-popup-preview-img').attr('src', response.data.url);
					$('#xanh-ai-popup-preview-link').attr('href', response.data.url);
					
					var successMsg = '';
					if (mode === 'featured') {
						setFeaturedImage(state.lastAttachmentId);
						successMsg = '<span class="dashicons dashicons-yes-alt" style="color:#4ade80; margin-right:4px;"></span> Đã tạo & đặt làm Featured Image';
					} else {
						insertImageToEditor(response.data.url, getKeyword());
						successMsg = '<span class="dashicons dashicons-yes-alt" style="color:#4ade80; margin-right:4px;"></span> Đã tạo & chèn vào bài viết';
					}
					
					$('#xanh-ai-popup-preview-msg').html(successMsg);
					$status.hide(); // Hide the old status box
					$preview.slideDown(250);

					// Change button to "Đóng".
					$btn.text('Đóng').off('click').on('click', function () {
						$('#xanh-ai-img-popup-overlay').remove();
					});
				} else {
					$status.html('<div class="xanh-ai-img-box-status" style="background:#fef1f1; border-color:#f0b8b8;">❌ ' + (response.data?.message || 'Tạo ảnh thất bại.') + '</div>');
				}
			},
			error: function (jqXHR, textStatus) {
				var msg = textStatus === 'timeout'
					? 'Tạo ảnh quá thời gian (>120s). Vui lòng thử lại.'
					: 'Lỗi kết nối: ' + textStatus;
				$status.html('<div class="xanh-ai-img-box-status" style="background:#fef1f1; border-color:#f0b8b8;">❌ ' + msg + '</div>');
			},
			complete: function () {
				clearInterval(timer);
				state.isGenerating = false;
				$btn.prop('disabled', false);
			},
		});
	}

	/*--------------------------------------------------------------
	 * Set Featured Image
	 *------------------------------------------------------------*/
	function setFeaturedImage(attachmentId) {
		if (!attachmentId) return;

		var postId = $('#post_ID').val();
		if (!postId) return;

		$.post(xanhAI.ajaxUrl, {
			action: 'set-post-thumbnail',
			post_id: postId,
			thumbnail_id: attachmentId,
			_ajax_nonce: xanhAIEditor.setThumbnailNonce,
			cookie: encodeURIComponent(document.cookie),
		}, function (response) {
			if (response && response !== '0') {
				$('#postimagediv .inside').html(response);
			}
		});
	}

	/*--------------------------------------------------------------
	 * Insert Image into Classic Editor
	 *------------------------------------------------------------*/
	function insertImageToEditor(imageUrl, altText) {
		var html = '<figure class="xanh-ai-section-figure" style="margin:1.5em 0;">' +
			'<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(altText || 'AI Generated') + '" ' +
			'style="max-width:100%; height:auto; border-radius:8px;" loading="lazy" />' +
			'</figure>';

		var editor = state.editorRef;

		// Try TinyMCE (Visual mode).
		if (editor && !editor.isHidden()) {
			editor.execCommand('mceInsertContent', false, html);
		}
		// Fallback: TinyMCE activeEditor.
		else if (typeof tinymce !== 'undefined' && tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
			tinymce.activeEditor.execCommand('mceInsertContent', false, html);
		}
		// Fallback: Text mode.
		else {
			var $textarea = $('#content');
			if ($textarea.length) {
				var cursorPos = $textarea[0].selectionStart || $textarea.val().length;
				var textBefore = $textarea.val().substring(0, cursorPos);
				var textAfter = $textarea.val().substring(cursorPos);
				$textarea.val(textBefore + '\n' + html + '\n' + textAfter);
			}
		}
	}

	/*--------------------------------------------------------------
	 * Utilities
	 *------------------------------------------------------------*/
	function buildAutoPrompt(title, keyword, selectedText) {
		var subject = keyword || title || '';
		if (!subject && !selectedText) return '';

		var prompt = 'Professional editorial photography, warm ambient lighting, ' +
			'cream and emerald green color palette. ';

		if (selectedText) {
			var snippet = selectedText.length > 300 ? selectedText.substring(0, 300) + '...' : selectedText;
			prompt += 'Illustrate this paragraph: "' + snippet + '". ';
		}

		if (subject) {
			prompt += 'Subject: ' + subject + '. ';
		}

		prompt += 'High quality, no text overlay, no watermark. ' +
			'Set in Vietnam, Nha Trang, bright airy natural sunlight. ' +
			'Luxurious and modern interior/architecture setting.';

		return prompt;
	}

	function getKeyword() {
		var $rk = $('input[name="rank_math_focus_keyword"]');
		if ($rk.length && $rk.val()) return $rk.val();
		return $('#title').val() || '';
	}

	function escapeHtml(str) {
		var div = document.createElement('div');
		div.textContent = str || '';
		return div.innerHTML;
	}

})(jQuery);
