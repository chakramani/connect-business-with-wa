/* Business Messaging Hub — Message Log page behavior */
jQuery(function ($) {

    /* Expand / Collapse full message */
    $(document).on('click', '.wabmh-expand-btn', function(){
        var $cell = $(this).closest('.wabmh-log-msg-cell');
        $cell.find('.wabmh-msg-preview').hide();
        $cell.find('.wabmh-msg-full').show();
    });
    $(document).on('click', '.wabmh-collapse-btn', function(){
        var $cell = $(this).closest('.wabmh-log-msg-cell');
        $cell.find('.wabmh-msg-full').hide();
        $cell.find('.wabmh-msg-preview').show();
    });

    /* Verify message on Meta */
    $(document).on('click', '.wabmh-verify-btn', function(){
        var $btn    = $(this);
        var msgId   = $btn.data('msg-id');
        var logId   = $btn.data('log-id');
        var $result = $('#verify-' + logId);

        $btn.prop('disabled', true).text('⏳ Checking…');
        $result.html('');

        $.post(wabmh.ajax_url, {
            action: 'wabmh_verify_message',
            nonce:  wabmh.nonce,
            msg_id: msgId,
            log_id: logId
        })
        .done(function(res){
            if(res.success){
                $result.html(
                    '<span class="wabmh-verify-ok">✅ ' + res.data.status + '</span>' +
                    '<span class="wabmh-verify-raw" data-raw="' +
                        encodeURIComponent(JSON.stringify(res.data.raw, null, 2)) +
                    '">📡 View raw response</span>'
                );
            } else {
                $result.html('<span class="wabmh-verify-fail">❌ ' + res.data.message + '</span>');
            }
        })
        .fail(function(){
            $result.html('<span class="wabmh-verify-fail">❌ Network error</span>');
        })
        .always(function(){
            $btn.prop('disabled', false).text('🔍 Verify on Meta');
        });
    });

    /* Show raw response in modal */
    $(document).on('click', '.wabmh-verify-raw', function(){
        var raw = decodeURIComponent($(this).data('raw'));
        $('#wabmh-modal-body').text(raw);
        $('#wabmh-modal-overlay').fadeIn(150);
    });
    $('#wabmh-modal-close').on('click', function(){
        $('#wabmh-modal-overlay').fadeOut(150);
    });
    $('#wabmh-modal-overlay').on('click', function(e){
        if(e.target === this) $('#wabmh-modal-overlay').fadeOut(150);
    });

    /* Live search */
    $('#wabmh-log-search').on('input', function(){
        var q = $(this).val().toLowerCase();
        $('#wabmh-log-table tbody tr').each(function(){
            $(this).toggleClass('wabmh-hidden', q.length > 0 && $(this).text().toLowerCase().indexOf(q) === -1);
        });
    });

});
