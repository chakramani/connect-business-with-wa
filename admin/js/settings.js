/* Business Messaging Hub — Settings page behavior */
jQuery(function ($) {
	var qualityLabel = { GREEN: '🟢 High', YELLOW: '🟡 Medium', RED: '🔴 Low', UNKNOWN: '⚪ Unknown' };
	var modeLabel    = { SANDBOX: '🧪 Sandbox (Test)', LIVE: '🚀 Live (Production)' };

	// ---- Check phone status ----
	$('#wabmh-check-status-btn').on('click', function () {
		var $btn = $(this), $r = $('#wabmh-phone-status-result'), $err = $('#wabmh-check-status-error');
		$btn.prop('disabled', true).text('⏳ Checking…');
		$r.hide();
		$err.text('');

		$.post(wabmh.ajax_url, { action: 'wabmh_phone_status', nonce: wabmh.nonce })
			.done(function (res) {
				if (res.success) {
					var d = res.data;
					var regText = d.registered
						? '<span style="color:#2a7a2a;font-weight:700;">✅ Registered</span>'
						: '<span style="color:#c0392b;font-weight:700;">❌ Not Registered (status: ' + d.status + ')</span>';
					$('#wabmh-ps-registered').html(regText);
					$('#wabmh-ps-phone').text(d.phone);
					$('#wabmh-ps-name').text(d.name);
					$('#wabmh-ps-quality').html(qualityLabel[d.quality] || d.quality);
					$('#wabmh-ps-platform').text(d.platform);
					$('#wabmh-ps-throughput').text(d.throughput);
					$('#wabmh-ps-mode').html(modeLabel[d.account_mode] || d.account_mode);
					$('#wabmh-ps-raw').text(JSON.stringify(d.raw, null, 2));
					$r.show();
				} else {
					$err.text('❌ ' + res.data.message);
				}
			})
			.fail(function () { $err.text('❌ Network error.'); })
			.always(function () { $btn.prop('disabled', false).text('🔍 Check Registration Status'); });
	});

	// ---- Register phone number ----
	$('#wabmh-register-btn').on('click', function () {
		var pin = $('#wabmh-register-pin').val().trim();
		var $r  = $('#wabmh-register-result');
		if (!/^\d{6}$/.test(pin)) {
			$r.removeClass('success').addClass('error').text('❌ PIN must be exactly 6 digits.');
			return;
		}
		if (!confirm('Register this phone number with PIN: ' + pin + '?\n\nSave this PIN — you will need it if you re-register.')) return;
		var $btn = $(this);
		$btn.prop('disabled', true).text('⏳ Registering…');
		$r.text('').removeClass('success error');
		$.post(wabmh.ajax_url, { action: 'wabmh_register_phone', nonce: wabmh.nonce, pin: pin })
			.done(function (res) {
				if (res.success) {
					$r.addClass('success').text(res.data.message);
				} else {
					$r.addClass('error').text('❌ ' + res.data.message);
				}
			})
			.fail(function () { $r.addClass('error').text('❌ Network error.'); })
			.always(function () { $btn.prop('disabled', false).text('✅ Register Number'); });
	});

	// Only allow digits in PIN field
	$('#wabmh-register-pin').on('input', function () {
		this.value = this.value.replace(/\D/g, '').slice(0, 6);
	});

	// ---- Deregister phone number ----
	$('#wabmh-deregister-btn').on('click', function () {
		if (!confirm('Are you sure you want to deregister this phone number? It will stop working with Cloud API until re-registered.')) return;
		var $btn = $(this), $r = $('#wabmh-deregister-result');
		$btn.prop('disabled', true).text('⏳ Deregistering…');
		$r.text('').removeClass('success error');
		$.post(wabmh.ajax_url, { action: 'wabmh_deregister_phone', nonce: wabmh.nonce })
			.done(function (res) {
				if (res.success) {
					$r.addClass('success').text(res.data.message);
				} else {
					$r.addClass('error').text('❌ ' + res.data.message);
				}
			})
			.fail(function () { $r.addClass('error').text('❌ Network error.'); })
			.always(function () { $btn.prop('disabled', false).text('⛔ Deregister Number'); });
	});
});
