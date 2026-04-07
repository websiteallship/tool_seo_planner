/**
 * XANH AI — TinyMCE Plugin.
 *
 * Adds a custom "AI Ảnh" button to the Classic Editor toolbar.
 * When clicked, opens the AI Image Generation popup.
 *
 * @package Xanh_AI_Content
 * @since   1.3.0
 */
(function () {
	'use strict';

	tinymce.PluginManager.add('xanh_ai_image', function (editor) {

		// Register toolbar button.
		editor.addButton('xanh_ai_image', {
			title: 'XANH AI — Tạo Ảnh Minh Họa',
			image: '', // Will use text+icon via CSS
			text: '✨ AI Ảnh',
			classes: 'xanh-ai-mce-btn',
			onclick: function () {
				// Get selected text from editor (plain text).
				var selectedText = editor.selection.getContent({ format: 'text' }).trim();

				// Get full content (plain text for context).
				var fullContent = editor.getContent({ format: 'text' }).trim();

				// Get post title.
				var titleEl = document.getElementById('title');
				var postTitle = titleEl ? titleEl.value : '';

				// Get focus keyword (RankMath).
				var kwEl = document.querySelector('input[name="rank_math_focus_keyword"]');
				var keyword = kwEl ? kwEl.value : '';

				// Dispatch custom event to open popup (handled by xanh-ai-editor.js).
				var event = new CustomEvent('xanh-ai-open-image-popup', {
					detail: {
						selectedText: selectedText,
						fullContent: fullContent,
						postTitle: postTitle,
						keyword: keyword,
						editor: editor,
					},
				});
				document.dispatchEvent(event);
			},
		});
	});
})();
