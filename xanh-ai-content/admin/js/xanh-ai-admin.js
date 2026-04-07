/**
 * XANH AI Content Generator — Admin JavaScript
 *
 * Handles: Test Connection AJAX, temperature slider, API key UX.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */

(function ($) {
	'use strict';

	/**
	 * Test API Connection.
	 */
	function initTestConnection() {
		const $btn    = $('#xanh-ai-test-connection');
		const $result = $('#xanh-ai-test-result');

		if (!$btn.length) return;

		$btn.on('click', function (e) {
			e.preventDefault();

			$btn.prop('disabled', true);
			$result
				.text(xanhAI.i18n.testing)
				.removeClass('xanh-ai-test-success xanh-ai-test-error')
				.addClass('xanh-ai-test-loading');

			// Send the currently-entered key so test works before saving.
			var currentKey = $('#xanh_ai_gemini_key').val() || '';

			$.ajax({
				url: xanhAI.ajaxUrl,
				type: 'POST',
				data: {
					action: 'xanh_ai_test_connection',
					nonce: xanhAI.nonce,
					api_key: currentKey,
				},
				success: function (response) {
					if (response.success) {
						$result
							.text(xanhAI.i18n.testSuccess + ' (' + response.data.model + ')')
							.removeClass('xanh-ai-test-loading xanh-ai-test-error')
							.addClass('xanh-ai-test-success');

						// Nếu user có nhập mã mới thì UI cập nhật trạng thái đã lưu
						var $input = $('#xanh_ai_gemini_key');
						if ($input.val() !== '') {
							$input.val(''); // Xóa value để hiển thị placeholder
							$input.attr('placeholder', '**********************');
							if ($('.xanh-ai-key-status').length === 0) {
								$input.after('<span class="xanh-ai-key-status xanh-ai-key-status--active">Đã cấu hình</span>');
							}
						}
					} else {
						$result
							.text(response.data?.message || xanhAI.i18n.testFailed)
							.removeClass('xanh-ai-test-loading xanh-ai-test-success')
							.addClass('xanh-ai-test-error');
					}
				},
				error: function () {
					$result
						.text(xanhAI.i18n.testFailed)
						.removeClass('xanh-ai-test-loading xanh-ai-test-success')
						.addClass('xanh-ai-test-error');
				},
				complete: function () {
					$btn.prop('disabled', false);
				},
			});
		});
	}

	/**
	 * Temperature slider — live value display.
	 */
	function initTemperatureSlider() {
		const $slider = $('#xanh_ai_temperature');
		const $value  = $('#xanh-ai-temp-value');

		if (!$slider.length) return;

		$slider.on('input', function () {
			$value.text(parseFloat(this.value).toFixed(1));
		});
	}

	/**
	 * API Key field — enable/disable Test button based on key existence.
	 */
	function initApiKeyField() {
		const $input = $('#xanh_ai_gemini_key');
		const $btn   = $('#xanh-ai-test-connection');

		if (!$input.length) return;

		$input.on('input', function () {
			// If user is typing a new key, enable the test button.
			if (this.value.length > 10) {
				$btn.prop('disabled', false);
			}
		});
	}

	/**
	 * Initialize all modules on DOM ready.
	 */
	$(function () {
		initTestConnection();
		initTemperatureSlider();
		initApiKeyField();
	});

})(jQuery);
