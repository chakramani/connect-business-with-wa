/* global wabmh, jQuery */
(function ($) {
    'use strict';

    /* ---------------------------------------------------------------
       Utility: show result message inside a container
    --------------------------------------------------------------- */
    function showResult($container, isSuccess, message) {
        var cls = isSuccess ? 'wabmh-result-success' : 'wabmh-result-error';
        $container
            .html('<div class="' + cls + '">' + $('<div>').text(message).html() + '</div>')
            .show();
        if (isSuccess) {
            setTimeout(function () { $container.fadeOut(400); }, 5000);
        }
    }

    /* ---------------------------------------------------------------
       Send message (shared by dashboard quick-send + send page)
    --------------------------------------------------------------- */
    $(document).on('click', '.wabmh-send-btn', function () {
        var $btn      = $(this);
        var toId      = $btn.data('to-field');
        var msgId     = $btn.data('msg-field');
        var to        = $('#' + toId).val().trim();
        var message   = $('#' + msgId).val().trim();
        var $result   = $btn.closest('.wabmh-card').find('[id$="-result"], [id$="send-result"]').first();

        if (!$result.length) {
            // Fallback to nearest sibling
            $result = $btn.closest('.wabmh-card').find('div[id]').first();
        }

        if (!to || !message) {
            showResult($result, false, wabmh.i18n.error_empty);
            return;
        }

        $btn.prop('disabled', true).text(wabmh.i18n.sending);

        $.post(wabmh.ajax_url, {
            action:  'wabmh_send_message',
            nonce:   wabmh.nonce,
            to:      to,
            message: message
        })
        .done(function (response) {
            if (response.success) {
                showResult($result, true, response.data.message);
                $('#' + msgId).val('');
                updateCharCount();
            } else {
                showResult($result, false, response.data.message);
            }
        })
        .fail(function () {
            showResult($result, false, 'Network error. Please try again.');
        })
        .always(function () {
            $btn.prop('disabled', false).text(wabmh.i18n.send);
        });
    });

    /* ---------------------------------------------------------------
       Test Connection
    --------------------------------------------------------------- */
    $('#wabmh-test-connection').on('click', function () {
        var $btn    = $(this);
        var $result = $('#wabmh-test-result');

        $btn.prop('disabled', true).text(wabmh.i18n.testing);
        $result.text('').removeClass('success error');

        $.post(wabmh.ajax_url, {
            action: 'wabmh_test_connection',
            nonce:  wabmh.nonce
        })
        .done(function (response) {
            if (response.success) {
                $result.addClass('success').text('✅ ' + response.data.message);
            } else {
                $result.addClass('error').text('❌ ' + response.data.message);
            }
        })
        .fail(function () {
            $result.addClass('error').text('❌ Network error.');
        })
        .always(function () {
            $btn.prop('disabled', false).text(wabmh.i18n.test);
        });
    });

    /* ---------------------------------------------------------------
       Toggle password visibility
    --------------------------------------------------------------- */
    $(document).on('click', '.wabmh-toggle-visibility', function () {
        var targetId = $(this).data('target');
        var $input   = $('#' + targetId);
        var type     = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        $(this).text(type === 'password' ? '👁' : '🙈');
    });

    /* ---------------------------------------------------------------
       Character counter on send page
    --------------------------------------------------------------- */
    function updateCharCount() {
        var $msg = $('#wabmh-message');
        if (!$msg.length) return;
        var remaining = 4096 - $msg.val().length;
        $('#wabmh-char-left').text(remaining);
    }

    $('#wabmh-message').on('input', updateCharCount);
    updateCharCount();

    /* ---------------------------------------------------------------
       Clear button on send page
    --------------------------------------------------------------- */
    $('#wabmh-clear-btn').on('click', function () {
        $('#wabmh-to').val('');
        $('#wabmh-message').val('');
        $('#wabmh-send-result').hide().empty();
        updateCharCount();
    });

}(jQuery));
