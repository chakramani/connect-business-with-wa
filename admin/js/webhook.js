/* Business Messaging Hub — Webhook page behavior */
jQuery(function ($) {

	// ── Copy to clipboard ──────────────────────────────────────────────
	$(document).on('click', '.wabmh-copy-btn', function () {
		var id  = $(this).data('target');
		var el  = document.getElementById(id);
		var val = el ? (el.value || el.textContent) : '';
		navigator.clipboard.writeText(val.trim()).then(function () {
			var $b   = $('[data-target="' + id + '"]');
			var orig = $b.text();
			$b.text('✅ Copied!');
			setTimeout(function () { $b.text(orig); }, 2000);
		});
	});

	// ── Regenerate token ───────────────────────────────────────────────
	$('#wabmh-regen-token').on('click', function () {
		var $btn = $(this);
		var confirmMsg = ( window.wabmhWebhook && wabmhWebhook.i18n && wabmhWebhook.i18n.confirmRegen )
			? wabmhWebhook.i18n.confirmRegen
			: 'Generate a new token? You will need to update Meta with the new token before clicking Verify and Save again.';
		if (!confirm(confirmMsg)) return;
		$btn.prop('disabled', true).text('⏳');
		$.post(wabmh.ajax_url, { action: 'wabmh_regenerate_token', nonce: wabmh.nonce })
			.done(function (res) {
				if (res.success) {
					var token = res.data.token;
					$('#wabmh-verify-token-display').val(token);
					$('#wabmh-meta-token-preview').text(token);
					// Update manual test URL live
					var base = $('#wabmh-manual-test-url').text().split('?')[0];
					$('#wabmh-manual-test-url').text(
						base + '?hub.mode=subscribe&hub.verify_token=' + encodeURIComponent(token) + '&hub.challenge=TEST123'
					);
					$btn.text('✅ Done! Copy the new token into Meta.');
					setTimeout(function () { $btn.prop('disabled', false).text('🔄 Generate New'); }, 3000);
					// Clear any old test result
					$('#wabmh-webhook-test-result').text('').removeClass('success error');
				}
			});
	});

	// ── Test webhook ───────────────────────────────────────────────────
	$('#wabmh-test-webhook').on('click', function () {
		var $btn = $(this), $r = $('#wabmh-webhook-test-result');
		$btn.prop('disabled', true).text('⏳ Testing…');
		$r.text('').removeClass('success error');
		$.post(wabmh.ajax_url, { action: 'wabmh_test_webhook', nonce: wabmh.nonce })
			.done(function (res) {
				if (res.success) {
					$r.addClass('success').text('✅ ' + res.data.message + ' — Ready to verify in Meta!');
				} else {
					$r.addClass('error').text('❌ ' + res.data.message);
				}
			})
			.fail(function () { $r.addClass('error').text('❌ Network error.'); })
			.always(function () { $btn.prop('disabled', false).text('🧪 Test Token Now'); });
	});
});
