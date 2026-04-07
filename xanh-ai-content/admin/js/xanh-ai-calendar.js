/**
 * XANH AI Calendar — Content calendar with monthly grid view.
 *
 * @package Xanh_AI_Content
 * @since   1.0.0
 */
(function ($) {
	'use strict';

	var MONTH_NAMES = [
		'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4',
		'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8',
		'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
	];

	// Category color palette (cycled for categories).
	var CAT_COLORS = [
		'#2271b1', '#00a32a', '#d63638', '#dba617',
		'#8b5cf6', '#e04e6a', '#1e8a7a', '#c36e0a'
	];

	var state = {
		year: new Date().getFullYear(),
		month: new Date().getMonth() + 1, // 1-indexed
		posts: [],
		loading: false,
		catColorMap: {}
	};

	$(document).ready(function () {
		buildCatColorMap();
		bindEvents();
		loadMonth();
	});

	/*--------------------------------------------------------------
	 * Color mapping
	 *------------------------------------------------------------*/
	function buildCatColorMap() {
		var cats = xanhAICal.categories || [];
		cats.forEach(function (cat, i) {
			state.catColorMap[cat.slug] = CAT_COLORS[i % CAT_COLORS.length];
		});
	}

	function getCatColor(slug) {
		return state.catColorMap[slug] || '#646970';
	}

	/*--------------------------------------------------------------
	 * Events
	 *------------------------------------------------------------*/
	function bindEvents() {
		$('#xanh-cal-prev').on('click', function () {
			state.month--;
			if (state.month < 1) { state.month = 12; state.year--; }
			loadMonth();
		});

		$('#xanh-cal-next').on('click', function () {
			state.month++;
			if (state.month > 12) { state.month = 1; state.year++; }
			loadMonth();
		});

		$('#xanh-cal-today').on('click', function () {
			state.year = new Date().getFullYear();
			state.month = new Date().getMonth() + 1;
			loadMonth();
		});

		$('#xanh-cal-filter-cat, #xanh-cal-filter-status').on('change', function () {
			loadMonth();
		});

		$('#xanh-cal-filter-ai').on('change', function () {
			loadMonth();
		});

		// Drag & Drop events
		var $grid = $('#xanh-cal-grid');
		$grid.on('dragstart', '.xanh-cal-post[draggable="true"]', handleDragStart);
		$grid.on('dragend', '.xanh-cal-post[draggable="true"]', handleDragEnd);
		$grid.on('dragover', '.xanh-cal-cell', handleDragOver);
		$grid.on('dragenter', '.xanh-cal-cell', handleDragEnter);
		$grid.on('dragleave', '.xanh-cal-cell', handleDragLeave);
		$grid.on('drop', '.xanh-cal-cell', handleDrop);
	}

	/*--------------------------------------------------------------
	 * Data loading
	 *------------------------------------------------------------*/
	function loadMonth() {
		if (state.loading) return;
		state.loading = true;

		// Update title immediately.
		$('#xanh-cal-month-title').text(MONTH_NAMES[state.month - 1] + ', ' + state.year);

		// Show loading state.
		$('#xanh-cal-grid').html('<div class="xanh-cal-loading"><span class="spinner is-active"></span> Đang tải...</div>');

		$.ajax({
			url: xanhAI.ajaxUrl,
			method: 'POST',
			data: {
				action: 'xanh_ai_calendar_data',
				nonce: xanhAI.nonce,
				year: state.year,
				month: state.month,
				category: $('#xanh-cal-filter-cat').val() || 0,
				status: $('#xanh-cal-filter-status').val() || '',
				ai_only: $('#xanh-cal-filter-ai').is(':checked') ? 1 : 0
			},
			success: function (res) {
				if (res.success) {
					state.posts = res.data.posts || [];
					renderGrid();
					renderStats();
				}
			},
			error: function () {
				$('#xanh-cal-grid').html('<p style="padding:20px;color:#d63638;">Lỗi tải dữ liệu. Vui lòng thử lại.</p>');
			},
			complete: function () {
				state.loading = false;
			}
		});
	}

	/*--------------------------------------------------------------
	 * Calendar grid rendering
	 *------------------------------------------------------------*/
	function renderGrid() {
		var $grid = $('#xanh-cal-grid');
		$grid.empty();

		var year = state.year;
		var month = state.month;
		var today = new Date();
		var isCurrentMonth = (today.getFullYear() === year && today.getMonth() + 1 === month);
		var todayDay = today.getDate();

		// First day of month (0=Sun, 1=Mon, ...).
		var firstDayObj = new Date(year, month - 1, 1);
		var firstDayOfWeek = firstDayObj.getDay(); // 0=Sun
		// Convert to Mon-based (0=Mon, ..., 6=Sun).
		var startOffset = (firstDayOfWeek === 0) ? 6 : firstDayOfWeek - 1;

		// Days in month.
		var daysInMonth = new Date(year, month, 0).getDate();

		// Group posts by day.
		var postsByDay = {};
		state.posts.forEach(function (post) {
			var day = post.day;
			if (!postsByDay[day]) postsByDay[day] = [];
			postsByDay[day].push(post);
		});

		// Gap detection: schedule days (Mon=1, Thu=4 for 2/week; Mon=1 for 1/week).
		var scheduleDays = getScheduleDays(year, month);

		// Empty cells before first day.
		for (var e = 0; e < startOffset; e++) {
			$grid.append('<div class="xanh-cal-cell xanh-cal-cell--empty"></div>');
		}

		// Day cells.
		for (var d = 1; d <= daysInMonth; d++) {
			var isToday = isCurrentMonth && d === todayDay;
			var dayPosts = postsByDay[d] || [];
			var isGap = scheduleDays.indexOf(d) > -1 && dayPosts.length === 0;
			var isPast = new Date(year, month - 1, d) < new Date(today.getFullYear(), today.getMonth(), today.getDate());
			var isWeekend = ((startOffset + d - 1) % 7 >= 5);

			var classes = ['xanh-cal-cell'];
			if (isToday) classes.push('xanh-cal-cell--today');
			if (isGap && isPast) classes.push('xanh-cal-cell--gap');
			if (isWeekend) classes.push('xanh-cal-cell--weekend');

			var $cell = $('<div class="' + classes.join(' ') + '" data-day="' + d + '"></div>');

			// Day number.
			var $dayNum = $('<div class="xanh-cal-day-num"></div>').text(d);
			if (isToday) $dayNum.append('<span class="xanh-cal-today-dot"></span>');
			$cell.append($dayNum);

			// Post indicators.
			if (dayPosts.length > 0) {
				var $posts = $('<div class="xanh-cal-posts"></div>');
				dayPosts.forEach(function (post) {
					var catColor = post.category ? getCatColor(post.category.slug) : '#646970';
					var statusClass = 'xanh-cal-status--' + post.status;
					var aiIcon = post.ai ? '<span class="dashicons dashicons-admin-generic xanh-cal-ai-icon" title="AI Generated"></span>' : '';
					var title = post.title.length > 28 ? post.title.substring(0, 28) + '...' : post.title;

					var isDraggable = post.status !== 'publish';
					var attrDraggable = isDraggable ? ' draggable="true"' : ' draggable="false"';
					var dragClass = isDraggable ? '' : ' xanh-cal-post--no-drag';
					var titleHover = isDraggable ? escapeAttr(post.title) + ' (Kéo thả để dời ngày)' : escapeAttr(post.title);

					var $postItem = $('<a class="xanh-cal-post ' + statusClass + dragClass + '" href="' + (post.edit_url || '#') + '" title="' + titleHover + '" data-id="' + post.id + '"' + attrDraggable + '>' +
						'<span class="xanh-cal-post-dot" style="background:' + catColor + '"></span>' +
						'<span class="xanh-cal-post-title">' + escapeHtml(title) + '</span>' +
						aiIcon +
						'</a>');
					$posts.append($postItem);
				});
				$cell.append($posts);
			}

			// Gap indicator.
			if (isGap && isPast) {
				$cell.append('<div class="xanh-cal-gap-badge" title="Cần bài viết mới"><span class="dashicons dashicons-warning"></span></div>');
			}

			// Click empty area → go to generator.
			$cell.on('click', function (e) {
				if ($(e.target).closest('a').length) return; // Don't override post links.
				window.location.href = xanhAICal.generatorUrl;
			});

			$grid.append($cell);
		}

		// Fill remaining cells to complete the grid.
		var totalCells = startOffset + daysInMonth;
		var remainder = totalCells % 7;
		if (remainder > 0) {
			for (var r = 0; r < (7 - remainder); r++) {
				$grid.append('<div class="xanh-cal-cell xanh-cal-cell--empty"></div>');
			}
		}
	}

	/*--------------------------------------------------------------
	 * Gap detection — which days should have posts
	 *------------------------------------------------------------*/
	function getScheduleDays(year, month) {
		var freq = xanhAICal.frequency || '2/week';
		var days = [];
		var daysInMonth = new Date(year, month, 0).getDate();

		for (var d = 1; d <= daysInMonth; d++) {
			var dayOfWeek = new Date(year, month - 1, d).getDay(); // 0=Sun
			if (freq === '2/week') {
				// Monday (1) and Thursday (4).
				if (dayOfWeek === 1 || dayOfWeek === 4) {
					days.push(d);
				}
			} else if (freq === '1/week') {
				// Monday only.
				if (dayOfWeek === 1) {
					days.push(d);
				}
			}
		}
		return days;
	}

	/*--------------------------------------------------------------
	 * Stats summary
	 *------------------------------------------------------------*/
	function renderStats() {
		var total = state.posts.length;
		var published = state.posts.filter(function (p) { return p.status === 'publish'; }).length;
		var drafts = state.posts.filter(function (p) { return p.status === 'draft'; }).length;
		var scheduled = state.posts.filter(function (p) { return p.status === 'future'; }).length;
		var aiCount = state.posts.filter(function (p) { return p.ai; }).length;

		var scheduleDays = getScheduleDays(state.year, state.month);
		var today = new Date();
		var pastScheduleDays = scheduleDays.filter(function (d) {
			return new Date(state.year, state.month - 1, d) < today;
		});
		var gaps = pastScheduleDays.length - Math.min(total, pastScheduleDays.length);

		$('#xanh-cal-stats').html(
			'<div class="xanh-cal-stats-grid">' +
			statCard('dashicons-media-text', total, 'Tổng bài', '#2271b1') +
			statCard('dashicons-yes-alt', published, 'Đã đăng', '#00a32a') +
			statCard('dashicons-edit', drafts, 'Bản nháp', '#dba617') +
			statCard('dashicons-clock', scheduled, 'Hẹn giờ', '#8b5cf6') +
			statCard('dashicons-admin-generic', aiCount, 'AI Generated', '#e04e6a') +
			(gaps > 0 ? statCard('dashicons-warning', gaps, 'Ngày thiếu bài', '#d63638') : '') +
			'</div>'
		);
	}

	function statCard(icon, value, label, color) {
		return '<div class="xanh-cal-stat-card">' +
			'<span class="dashicons ' + icon + '" style="color:' + color + ';font-size:20px;width:20px;height:20px;"></span>' +
			'<div><strong>' + value + '</strong><br><span class="description">' + label + '</span></div>' +
			'</div>';
	}

	/*--------------------------------------------------------------
	 * Drag and Drop functionality
	 *------------------------------------------------------------*/
	var dragState = {
		postElement: null,
		originalCell: null,
		postId: null
	};

	function handleDragStart(e) {
		var $post = $(this);
		dragState.postElement = $post;
		dragState.originalCell = $post.closest('.xanh-cal-cell');
		dragState.postId = $post.data('id');

		setTimeout(function () {
			$post.addClass('xanh-cal-post--dragging');
		}, 0);

		if (e.originalEvent && e.originalEvent.dataTransfer) {
			e.originalEvent.dataTransfer.effectAllowed = 'move';
			e.originalEvent.dataTransfer.setData('text/plain', dragState.postId);
		}
	}

	function handleDragEnd(e) {
		$(this).removeClass('xanh-cal-post--dragging');
		$('.xanh-cal-cell').removeClass('xanh-cal-cell--dragover');
		dragState = { postElement: null, originalCell: null, postId: null };
	}

	function handleDragOver(e) {
		e.preventDefault();
		if (dragState.postElement) {
			if (e.originalEvent && e.originalEvent.dataTransfer) {
				e.originalEvent.dataTransfer.dropEffect = 'move';
			}
		}
	}

	function handleDragEnter(e) {
		e.preventDefault();
		if (dragState.postElement) {
			$(this).addClass('xanh-cal-cell--dragover');
		}
	}

	function handleDragLeave(e) {
		if (dragState.postElement) {
			$(this).removeClass('xanh-cal-cell--dragover');
		}
	}

	function handleDrop(e) {
		e.preventDefault();
		$(this).removeClass('xanh-cal-cell--dragover');

		if (!dragState.postElement || !dragState.postId) return;

		var $targetCell = $(this).closest('.xanh-cal-cell');
		if ($targetCell.length === 0 || $targetCell.is(dragState.originalCell)) return;

		var day = parseInt($targetCell.data('day'), 10);
		if (!day) return; // Cannot drop on empty/padded cells before first day

		// Move element optimally in DOM (Optimistic UI)
		var $targetPosts = $targetCell.find('.xanh-cal-posts');
		if ($targetPosts.length === 0) {
			$targetPosts = $('<div class="xanh-cal-posts"></div>');
			$targetCell.append($targetPosts);
		}
		
		var $post = dragState.postElement;
		// Add small loading indicator to the post title
		$post.find('.xanh-cal-post-title').append(' <span class="spinner is-active xanh-cal-drag-spinner" style="float:none; width: 10px; height: 10px; min-width: 10px; background-size: 10px; margin:0; display:inline-block; vertical-align:middle;"></span>');
		
		$targetPosts.append($post);

		// Format date YYYY-MM-DD
		var newDateStr = state.year + '-' + (state.month < 10 ? '0' : '') + state.month + '-' + (day < 10 ? '0' : '') + day;

		// Keep a copy of original state in case of failure
		var $originalCell = dragState.originalCell;
		var postId = dragState.postId;

		$.ajax({
			url: xanhAI.ajaxUrl,
			method: 'POST',
			data: {
				action: 'xanh_ai_reschedule_post',
				nonce: xanhAI.nonce,
				post_id: postId,
				new_date: newDateStr
			},
			success: function (res) {
				$post.find('.xanh-cal-drag-spinner').remove();
				if (res.success) {
					// Reload month data to ensure statuses (draft->future) and colors accurately reflect db
					loadMonth();
				} else {
					alert(res.data && res.data.message ? res.data.message : 'Lỗi khi cập nhật.');
					revertDrop($post, $originalCell);
				}
			},
			error: function () {
				$post.find('.xanh-cal-drag-spinner').remove();
				alert('Lỗi kết nối máy chủ.');
				revertDrop($post, $originalCell);
			}
		});
	}

	function revertDrop($post, $originalCell) {
		var $originalPosts = $originalCell.find('.xanh-cal-posts');
		if ($originalPosts.length === 0) {
			$originalPosts = $('<div class="xanh-cal-posts"></div>');
			$originalCell.append($originalPosts);
		}
		$originalPosts.append($post);
	}

	/*--------------------------------------------------------------
	 * Utilities
	 *------------------------------------------------------------*/
	function escapeHtml(str) {
		var div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	function escapeAttr(str) {
		return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

})(jQuery);
