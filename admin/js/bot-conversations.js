/* Business Messaging Hub — Bot Conversations page behavior */
jQuery(function ($) {
	$('#wabmh-conv-search').on('input', function () {
		var q = $(this).val().toLowerCase();
		$('#wabmh-conv-table tbody tr').each(function () {
			$(this).toggleClass('wabmh-hidden', q.length > 0 && $(this).text().toLowerCase().indexOf(q) === -1);
		});
	});
});
