/* Business Messaging Hub — Inbox page behavior */
jQuery(function ($) {

	var activePhone  = null;
	var lastMsgId    = 0;
	var pollTimer    = null;
	var sidebarTimer = null;
	var ticks        = { pending: '🕐', sent: '✓', delivered: '✓✓', read: '<span style="color:#53bdeb">✓✓</span>', failed: '<span style="color:red">✗</span>', received: '' };

	// ── Helper: escape HTML ─────────────────────────────────────────
	function esc(str) { return $('<div>').text(str).html(); }

	// ── Build a message bubble ──────────────────────────────────────
	function buildBubble(msg) {
		var dir  = (msg.status === 'received') ? 'incoming' : 'outgoing';
		var time = msg.created_at ? msg.created_at.substr(11, 5) : '';
		var tick = (dir === 'outgoing') ? (ticks[msg.status] || '✓') : '';
		var err  = msg.error ? '<div class="wabmh-msg-error">❌ ' + esc(msg.error) + '</div>' : '';
		return '<div class="wabmh-msg-wrap wabmh-msg-' + dir + '">'
			+ '<div class="wabmh-msg-bubble">'
			+ '<div class="wabmh-msg-text">' + esc(msg.message).replace(/\n/g, '<br>') + '</div>'
			+ '<div class="wabmh-msg-meta"><span class="wabmh-msg-time">' + time + '</span>'
			+ (tick ? '<span class="wabmh-msg-tick">' + tick + '</span>' : '')
			+ '</div>' + err
			+ '</div></div>';
	}

	// ── Load conversation via AJAX ──────────────────────────────────
	function loadConversation(phone) {
		var $main = $('#wabmh-inbox-main');

		// Clone template and render chat shell
		var tpl = $('#wabmh-chat-tpl').html();
		$main.html(tpl);

		$('#wabmh-chat-phone').text(phone);
		$('#wabmh-chat-avatar').text(String(phone).slice(-2).toUpperCase());
		$('#wabmh-chat-messages').html('<div class="wabmh-loading">⏳ Loading messages…</div>');

		// Fetch messages
		$.post(wabmh.ajax_url, {
			action: 'wabmh_inbox_load',
			nonce:  wabmh.nonce,
			phone:  phone
		}, function (res) {
			var $msgs = $('#wabmh-chat-messages');
			if (!res.success) { $msgs.html('<div class="wabmh-loading">❌ ' + esc(res.data ? res.data.message : 'Unknown error') + '</div>'); return; }

			var rows = res.data.messages;
			if (!rows.length) { $msgs.html('<div class="wabmh-loading">💬 No messages yet.</div>'); lastMsgId = 0; return; }

			var html     = '';
			var prevDate = '';
			$.each(rows, function (i, msg) {
				var d = msg.created_at ? msg.created_at.substr(0, 10) : '';
				if (d && d !== prevDate) {
					var today = new Date().toISOString().substr(0, 10);
					var yest  = new Date(Date.now() - 86400000).toISOString().substr(0, 10);
					var label = d === today ? 'Today' : (d === yest ? 'Yesterday' : d);
					html += '<div class="wabmh-date-divider"><span>' + label + '</span></div>';
					prevDate = d;
				}
				html += buildBubble(msg);
			});
			$msgs.html(html);
			$msgs.scrollTop($msgs[0].scrollHeight);
			// Track last message ID for reliable polling
			lastMsgId = rows[rows.length - 1].id;
		});

		// Poll for new messages every 3 seconds
		clearInterval(pollTimer);
		pollTimer = setInterval(function () { pollNew(phone); }, 3000);

		// Refresh sidebar contact list every 5 seconds for new contacts/unread counts
		clearInterval(sidebarTimer);
		sidebarTimer = setInterval(function () { refreshSidebar(); }, 5000);

		// Bind reply
		bindReply(phone);
		bindRefresh(phone);
	}

	// ── Poll for new messages using last ID ────────────────────────
	function pollNew(phone) {
		var $msgs = $('#wabmh-chat-messages');
		if (!$msgs.length || activePhone !== phone) return;
		$.post(wabmh.ajax_url, { action: 'wabmh_inbox_poll_new', nonce: wabmh.nonce, phone: phone, last_id: lastMsgId }, function (res) {
			if (!res.success || !res.data.messages.length) return;
			var html = '';
			$.each(res.data.messages, function (i, msg) {
				html += buildBubble(msg);
				lastMsgId = Math.max(lastMsgId, msg.id);
			});
			// Remove "no messages" placeholder if present
			$msgs.find('.wabmh-loading').remove();
			$msgs.append(html);
			$msgs.scrollTop($msgs[0].scrollHeight);
			// Flash title to notify admin
			flashTitle('💬 New message!');
		});
	}

	// ── Refresh sidebar contact list ─────────────────────────────
	function refreshSidebar() {
		$.post(wabmh.ajax_url, { action: 'wabmh_inbox_sidebar', nonce: wabmh.nonce }, function (res) {
			if (!res.success || !res.data.html) return;
			var $list     = $('#wabmh-contact-list');
			var scrollTop = $list.scrollTop();
			$list.html(res.data.html);
			$list.scrollTop(scrollTop);
			// Restore active state
			if (activePhone) {
				$list.find('[data-phone="' + activePhone + '"]').addClass('active');
			}
		});
	}

	// ── Flash browser tab title on new message ───────────────────
	var flashInterval = null;
	var origTitle = document.title;
	function flashTitle(msg) {
		var count = 0;
		clearInterval(flashInterval);
		flashInterval = setInterval(function () {
			document.title = (count % 2 === 0) ? msg : origTitle;
			count++;
			if (count > 6) { clearInterval(flashInterval); document.title = origTitle; }
		}, 600);
	}

	// ── Bind reply box ──────────────────────────────────────────────
	function bindReply(phone) {
		$(document).off('click', '#wabmh-reply-send').on('click', '#wabmh-reply-send', function () { doSend(phone); });
		$(document).off('keydown', '#wabmh-reply-text').on('keydown', '#wabmh-reply-text', function (e) {
			if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); doSend(phone); }
			this.style.height = 'auto';
			this.style.height = Math.min(this.scrollHeight, 120) + 'px';
		});
	}

	function bindRefresh(phone) {
		$(document).off('click', '#wabmh-refresh-btn').on('click', '#wabmh-refresh-btn', function () { loadConversation(phone); });
	}

	// ── Send a message ──────────────────────────────────────────────
	function doSend(phone) {
		var $txt = $('#wabmh-reply-text');
		var $btn = $('#wabmh-reply-send');
		var msg  = $txt.val().trim();
		if (!msg) return;

		$btn.prop('disabled', true);
		$txt.prop('disabled', true);

		$.post(wabmh.ajax_url, { action: 'wabmh_send_message', nonce: wabmh.nonce, to: phone, message: msg }, function (res) {
			if (res.success) {
				var now    = new Date();
				var time   = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
				var pseudo = { id: lastMsgId + 1, status: 'sent', message: msg, created_at: '    ' + time, error: '' };
				var $msgs  = $('#wabmh-chat-messages');
				$msgs.append(buildBubble(pseudo));
				$msgs.scrollTop($msgs[0].scrollHeight);
				$txt.val('').css('height', 'auto');
				lastMsgId++; // prevent duplicate on next poll
			} else {
				alert('❌ ' + (res.data.message || 'Failed to send'));
			}
		}).always(function () { $btn.prop('disabled', false); $txt.prop('disabled', false).focus(); });
	}

	// ── Click contact ───────────────────────────────────────────────
	$(document).on('click', '.wabmh-contact-item', function () {
		var phone = String($(this).data('phone'));
		if (!phone) return;
		activePhone = phone;
		$('.wabmh-contact-item').removeClass('active');
		$(this).addClass('active');
		loadConversation(phone);
	});

	// ── Search ──────────────────────────────────────────────────────
	$('#wabmh-inbox-search').on('input', function () {
		var q = $(this).val().toLowerCase().replace(/\D/g, '');
		$('.wabmh-contact-item').each(function () {
			var p = $(this).data('phone').toString().replace(/\D/g, '');
			$(this).toggle(!q || p.indexOf(q) !== -1);
		});
	});
});
