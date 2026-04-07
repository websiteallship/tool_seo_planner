/**
 * XANH AI History — AJAX-driven history table with filters, tabs, pagination.
 *
 * @package Xanh_AI_Content
 * @since   1.1.0
 */

/* global jQuery, xanhAI, xanhAIHistory */
(function ($) {
	'use strict';

	/* ------------------------------------------------------------------
	 * State
	 * ----------------------------------------------------------------*/
	const state = {
		page:     1,
		type:     '',
		status:   '',
		angle:    '',
		search:   '',
		dateFrom: '',
		dateTo:   '',
	};

	let debounceTimer = null;

	/* ------------------------------------------------------------------
	 * Init
	 * ----------------------------------------------------------------*/
	$(document).ready(function () {
		populateAngleFilter();
		bindEvents();
		loadHistory();
	});

	/* ------------------------------------------------------------------
	 * Populate angle filter dropdown from localized data
	 * ----------------------------------------------------------------*/
	function populateAngleFilter() {
		const $sel = $('#filter-angle');
		if (!xanhAIHistory || !xanhAIHistory.angles) return;

		const angles = xanhAIHistory.angles;
		for (const id in angles) {
			if (angles.hasOwnProperty(id)) {
				$sel.append(
					$('<option>').val(id).text(angles[id].label || id)
				);
			}
		}
	}

	/* ------------------------------------------------------------------
	 * Bind UI events
	 * ----------------------------------------------------------------*/
	function bindEvents() {
		// Tabs.
		$('#history-tabs').on('click', '.xanh-ai-tab', function () {
			$('#history-tabs .xanh-ai-tab').removeClass('active');
			$(this).addClass('active');
			state.type = $(this).data('type') || '';
			state.page = 1;
			loadHistory();
		});

		// Filters.
		$('#filter-status').on('change', function () {
			state.status = $(this).val();
			state.page = 1;
			loadHistory();
		});

		$('#filter-angle').on('change', function () {
			state.angle = $(this).val();
			state.page = 1;
			loadHistory();
		});

		$('#filter-date-from').on('change', function () {
			state.dateFrom = $(this).val();
			state.page = 1;
			loadHistory();
		});

		$('#filter-date-to').on('change', function () {
			state.dateTo = $(this).val();
			state.page = 1;
			loadHistory();
		});

		// Search with debounce.
		$('#filter-search').on('input', function () {
			const val = $(this).val();
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(function () {
				state.search = val;
				state.page = 1;
				loadHistory();
			}, 400);
		});

		// Pagination (delegated).
		$('#history-pagination').on('click', '.xanh-ai-page-btn', function (e) {
			e.preventDefault();
			const p = parseInt($(this).data('page'), 10);
			if (p && p !== state.page) {
				state.page = p;
				loadHistory();
			}
		});

		// Suggestion Modal Open.
		$('#history-tbody').on('click', '.xanh-ai-view-suggest', function (e) {
			e.preventDefault();
			const $btn = $(this);
			
			$('#modal-suggest-topic').text($btn.data('topic') || '—');
			$('#modal-suggest-angle').text($btn.data('angle') || '—');
			$('#modal-suggest-seed').text($btn.data('seed') || '—');
			
			const rawOutput = $btn.data('output');
			const $list = $('#modal-suggest-list');
			$list.empty();
			
			try {
				let keywords = Array.isArray(rawOutput) ? rawOutput : JSON.parse(rawOutput || '[]');
				if (Array.isArray(keywords) && keywords.length > 0) {
					keywords.forEach(function(kw) {
						$list.append('<li>' + escHtml(kw) + '</li>');
					});
				} else {
					$list.append('<li><em>Không có từ khóa nào</em></li>');
				}
			} catch(e) {
				$list.append('<li><em>Lỗi hiển thị dữ liệu</em></li>');
			}
			
			$('#xanh-ai-suggest-overlay').fadeIn(200);
		});

		// Suggestion Modal Close.
		$('#xanh-ai-suggest-close, #xanh-ai-suggest-overlay').on('click', function (e) {
			if (e.target === this) {
				$('#xanh-ai-suggest-overlay').fadeOut(200);
			}
		});

		// Image Modal Open.
		$('#history-tbody').on('click', '.xanh-ai-view-image', function (e) {
			e.preventDefault();
			const $btn = $(this);
			
			$('#modal-image-prompt').text($btn.data('topic') || '—');
			$('#modal-image-preview').attr('src', $btn.data('image') || '');
			
			$('#xanh-ai-image-overlay').fadeIn(200);
		});

		// Image Modal Close.
		$('#xanh-ai-image-close, #xanh-ai-image-overlay').on('click', function (e) {
			if (e.target === this) {
				$('#xanh-ai-image-overlay').fadeOut(200);
			}
		});

		// AI Image Prompt Modal Open.
		$('#history-tbody').on('click', '.xanh-ai-view-imgprompt', function (e) {
			e.preventDefault();
			const $btn = $(this);

			// Post info.
			const postTitle = $btn.data('post-title') || '';
			const editUrl   = $btn.data('edit-url') || '';
			const dateStr   = $btn.data('date') || '';

			if (postTitle && editUrl) {
				$('#modal-imgprompt-post').html(
					'<a href="' + editUrl + '" target="_blank" style="color:#2271b1; text-decoration:none;">' +
					'<span class="dashicons dashicons-admin-post" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:4px;"></span>' +
					escHtml(postTitle) + '</a>'
				);
			} else {
				$('#modal-imgprompt-post').text('—');
			}

			$('#modal-imgprompt-date').text(dateStr ? formatDate(dateStr) : '—');
			$('#modal-imgprompt-text').text($btn.data('topic') || '—');
			$('#modal-imgprompt-model').text($btn.data('model') || '—');
			$('#modal-imgprompt-input').text(formatNumber($btn.data('input') || 0));
			$('#modal-imgprompt-output').text(formatNumber($btn.data('output') || 0));

			const status = $btn.data('status') || '';
			const prompt = $btn.data('prompt') || '';
			const error  = $btn.data('error') || '';

			const $result = $('#modal-imgprompt-result');
			if (status === 'error') {
				$result.html('<span style="color:#d63638;">❌ Lỗi: ' + escHtml(error || 'Không rõ lý do') + '</span>');
				$result.css({'background': '#fef1f1', 'border-color': '#f0b8b8'});
			} else if (prompt) {
				$result.text(prompt);
				$result.css({'background': '#f0faf0', 'border-color': '#ade2b5'});
			} else {
				$result.text('Không có dữ liệu');
				$result.css({'background': '#f9f9f9', 'border-color': '#dcdcde'});
			}

			$('#xanh-ai-imgprompt-overlay').fadeIn(200);
		});

		// AI Image Prompt Modal Close.
		$('#xanh-ai-imgprompt-close, #xanh-ai-imgprompt-overlay').on('click', function (e) {
			if (e.target === this) {
				$('#xanh-ai-imgprompt-overlay').fadeOut(200);
			}
		});
	}

	/* ------------------------------------------------------------------
	 * AJAX: Load history
	 * ----------------------------------------------------------------*/
	function loadHistory() {
		const $tbody = $('#history-tbody');
		const i18n   = xanhAIHistory.i18n;

		$tbody.html(
			'<tr class="xanh-ai-loading-row"><td colspan="8">' +
			'<span class="spinner is-active" style="float:none;margin:0 8px 0 0;"></span>' +
			i18n.loading +
			'</td></tr>'
		);

		$.post(xanhAI.ajaxUrl, {
			action:    'xanh_ai_get_history',
			_ajax_nonce: xanhAI.nonce,
			type:      state.type,
			status:    state.status,
			angle:     state.angle,
			search:    state.search,
			date_from: state.dateFrom,
			date_to:   state.dateTo,
			page:      state.page,
			per_page:  20,
		})
		.done(function (res) {
			if (!res.success) {
				$tbody.html('<tr><td colspan="8">' + i18n.error + '</td></tr>');
				return;
			}

			renderStats(res.data.stats);
			renderTable(res.data.items);
			renderPagination(res.data.page, res.data.pages, res.data.total);
		})
		.fail(function () {
			$tbody.html('<tr><td colspan="8">' + i18n.error + '</td></tr>');
		});
	}

	/* ------------------------------------------------------------------
	 * Render: Stats bar
	 * ----------------------------------------------------------------*/
	function renderStats(stats) {
		$('#stat-total').text(stats.total || 0);
		$('#stat-success-rate').text((stats.success_rate || 0) + '%');
		$('#stat-avg-score').text(stats.avg_score || '—');
		$('#stat-tokens').text(formatNumber(stats.total_tokens || 0));
		$('#stat-posts').text(stats.total_posts || 0);
		$('#stat-images').text(stats.total_images || 0);
		$('#stat-suggests').text(stats.total_suggests || 0);
		$('#stat-img-prompts').text(stats.total_img_prompts || 0);
	}

	/* ------------------------------------------------------------------
	 * Render: Table rows
	 * ----------------------------------------------------------------*/
	function renderTable(items) {
		const $tbody = $('#history-tbody');
		const i18n   = xanhAIHistory.i18n;

		if (!items || items.length === 0) {
			$tbody.html(
				'<tr><td colspan="8" class="xanh-ai-no-data">' +
				'<span class="dashicons dashicons-info-outline"></span> ' +
				i18n.noData +
				'</td></tr>'
			);
			return;
		}

		let html = '';

		items.forEach(function (item) {
			const date     = formatDate(item.created_at);
			const typeIcon = item.type === 'image'
				? '<span class="dashicons dashicons-format-image"></span>'
				: item.type === 'suggest'
					? '<span class="dashicons dashicons-lightbulb"></span>'
					: item.type === 'img_prompt'
						? '<span class="dashicons dashicons-camera"></span>'
						: '<span class="dashicons dashicons-admin-post"></span>';
			const typeLabel = item.type === 'image'
				? i18n.image
				: item.type === 'suggest'
					? (i18n.suggest || 'Gợi ý')
					: item.type === 'img_prompt'
						? 'Gợi Ý Ảnh'
						: i18n.post;
			const topic    = escHtml(truncate(item.topic || '—', 60));
			const angle    = item.angle_label
				? escHtml(item.angle_label)
				: '—';
			const score    = item.score > 0 ? item.score : '—';
			const tokens   = item.tokens_used > 0 ? formatNumber(item.tokens_used) : '—';
			const status   = renderStatus(item.status);
			const actions  = renderActions(item);

			// Score color.
			let scoreClass = '';
			if (item.score >= 80) scoreClass = 'xanh-ai-score-good';
			else if (item.score >= 60) scoreClass = 'xanh-ai-score-ok';
			else if (item.score > 0) scoreClass = 'xanh-ai-score-low';

			html += '<tr>' +
				'<td class="xanh-ai-date-cell">' + date + '</td>' +
				'<td>' + typeIcon + ' ' + typeLabel + '</td>' +
				'<td class="xanh-ai-topic-cell" title="' + escHtml(item.topic || '') + '">' + topic + '</td>' +
				'<td>' + angle + '</td>' +
				'<td class="' + scoreClass + '">' + score + '</td>' +
				'<td>' + tokens + '</td>' +
				'<td>' + status + '</td>' +
				'<td class="xanh-ai-actions-cell">' + actions + '</td>' +
				'</tr>';
		});

		$tbody.html(html);
	}

	/* ------------------------------------------------------------------
	 * Render: Status badge
	 * ----------------------------------------------------------------*/
	function renderStatus(status) {
		const i18n = xanhAIHistory.i18n;
		const map  = {
			success:      { label: i18n.success,    cls: 'xanh-ai-badge-success' },
			saved:        { label: 'Đã lưu',        cls: 'xanh-ai-badge-success' },
			error:        { label: i18n.errorLabel,  cls: 'xanh-ai-badge-error' },
			post_deleted: { label: i18n.deleted,     cls: 'xanh-ai-badge-muted' },
			pending:      { label: 'Pending',        cls: 'xanh-ai-badge-pending' },
		};

		const s = map[status] || { label: status, cls: 'xanh-ai-badge-muted' };
		return '<span class="xanh-ai-badge ' + s.cls + '">' + s.label + '</span>';
	}

	/* ------------------------------------------------------------------
	 * Render: Action buttons
	 * ----------------------------------------------------------------*/
	function renderActions(item) {
		const i18n = xanhAIHistory.i18n;
		let html   = '';

		if (item.edit_url) {
			html += '<a href="' + item.edit_url + '" class="xanh-ai-action-link" title="' + i18n.edit + '">' +
				'<span class="dashicons dashicons-edit"></span>' +
				'</a>';
		}

		if (item.type === 'image') {
			if (item.view_url) {
				html += '<button type="button" class="xanh-ai-action-link xanh-ai-view-image" style="background:none; border:none; padding:0; cursor:pointer;" ' +
					'data-topic="' + escAttr(item.topic || '') + '" ' +
					'data-image="' + escAttr(item.view_url || '') + '" ' +
					'title="' + i18n.view + '">' +
					'<span class="dashicons dashicons-visibility"></span>' +
					'</button>';
			}
			if (item.thumb_url) {
				html += '<img src="' + item.thumb_url + '" class="xanh-ai-history-thumb" alt="" />';
			}
		} else if (item.view_url) {
			html += '<a href="' + item.view_url + '" target="_blank" class="xanh-ai-action-link" title="' + i18n.view + '">' +
				'<span class="dashicons dashicons-visibility"></span>' +
				'</a>';
		}

		if (item.status === 'error' && item.error_message) {
			html += '<span class="xanh-ai-error-hint" title="' + escHtml(item.error_message) + '">' +
				'<span class="dashicons dashicons-warning"></span>' +
				'</span>';
		}

		if (item.type === 'suggest') {
			html += '<button type="button" class="xanh-ai-action-link xanh-ai-view-suggest" style="background:none; border:none; padding:0; cursor:pointer;" ' +
				'data-topic="' + escAttr(item.topic || '') + '" ' +
				'data-angle="' + escAttr(item.angle_label || '') + '" ' +
				'data-seed="' + escAttr(item.keywords || '') + '" ' +
				'data-output="' + escAttr(item.error_message || '[]') + '" ' +
				'title="' + i18n.view + '">' +
				'<span class="dashicons dashicons-visibility"></span>' +
				'</button>';
		}

		if (item.type === 'img_prompt') {
			html += '<button type="button" class="xanh-ai-action-link xanh-ai-view-imgprompt" style="background:none; border:none; padding:0; cursor:pointer;" ' +
				'data-topic="' + escAttr(item.topic || '') + '" ' +
				'data-prompt="' + escAttr(item.keywords || '') + '" ' +
				'data-model="' + escAttr(item.model || '') + '" ' +
				'data-input="' + (item.input_tokens || 0) + '" ' +
				'data-output="' + (item.output_tokens || 0) + '" ' +
				'data-status="' + escAttr(item.status || '') + '" ' +
				'data-error="' + escAttr(item.error_message || '') + '" ' +
				'data-post-title="' + escAttr(item.post_title || '') + '" ' +
				'data-edit-url="' + escAttr(item.edit_url || '') + '" ' +
				'data-date="' + escAttr(item.created_at || '') + '" ' +
				'title="' + i18n.view + '">' +
				'<span class="dashicons dashicons-visibility"></span>' +
				'</button>';
		}

		return html || '—';
	}

	/* ------------------------------------------------------------------
	 * Render: Pagination
	 * ----------------------------------------------------------------*/
	function renderPagination(currentPage, totalPages, total) {
		const $wrap = $('#history-pagination');

		if (totalPages <= 1) {
			$wrap.html('');
			return;
		}

		let html = '<span class="xanh-ai-page-info">Trang ' + currentPage + '/' + totalPages +
		           ' (' + total + ' bản ghi)</span>';

		if (currentPage > 1) {
			html += '<button class="xanh-ai-page-btn" data-page="' + (currentPage - 1) + '">' +
				'<span class="dashicons dashicons-arrow-left-alt2"></span>' +
				'</button>';
		}

		// Show max 5 page buttons around current.
		const start = Math.max(1, currentPage - 2);
		const end   = Math.min(totalPages, currentPage + 2);

		for (let i = start; i <= end; i++) {
			const cls = i === currentPage ? ' active' : '';
			html += '<button class="xanh-ai-page-btn' + cls + '" data-page="' + i + '">' + i + '</button>';
		}

		if (currentPage < totalPages) {
			html += '<button class="xanh-ai-page-btn" data-page="' + (currentPage + 1) + '">' +
				'<span class="dashicons dashicons-arrow-right-alt2"></span>' +
				'</button>';
		}

		$wrap.html(html);
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ----------------------------------------------------------------*/
	function formatDate(dateStr) {
		if (!dateStr) return '—';
		const d = new Date(dateStr);
		const day   = String(d.getDate()).padStart(2, '0');
		const month = String(d.getMonth() + 1).padStart(2, '0');
		const year  = d.getFullYear();
		const hour  = String(d.getHours()).padStart(2, '0');
		const min   = String(d.getMinutes()).padStart(2, '0');
		return day + '/' + month + '/' + year + ' ' + hour + ':' + min;
	}

	function formatNumber(n) {
		if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
		if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
		return String(n);
	}

	function truncate(str, max) {
		return str.length > max ? str.substring(0, max) + '...' : str;
	}

	function escHtml(str) {
		const div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

	function escAttr(str) {
		if (!str) return '';
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');
	}

})(jQuery);
