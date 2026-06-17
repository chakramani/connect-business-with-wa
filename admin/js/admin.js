/* global wai, jQuery */
(function ($) {
    'use strict';

    /* ---------------------------------------------------------------
       Utility: show result message inside a container
    --------------------------------------------------------------- */
    function showResult($container, isSuccess, message) {
        var cls = isSuccess ? 'wai-result-success' : 'wai-result-error';
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
    $(document).on('click', '.wai-send-btn', function () {
        var $btn      = $(this);
        var toId      = $btn.data('to-field');
        var msgId     = $btn.data('msg-field');
        var to        = $('#' + toId).val().trim();
        var message   = $('#' + msgId).val().trim();
        var $result   = $btn.closest('.wai-card').find('[id$="-result"], [id$="send-result"]').first();

        if (!$result.length) {
            // Fallback to nearest sibling
            $result = $btn.closest('.wai-card').find('div[id]').first();
        }

        if (!to || !message) {
            showResult($result, false, wai.i18n.error_empty);
            return;
        }

        $btn.prop('disabled', true).text(wai.i18n.sending);

        $.post(wai.ajax_url, {
            action:  'wai_send_message',
            nonce:   wai.nonce,
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
            $btn.prop('disabled', false).text(wai.i18n.send);
        });
    });

    /* ---------------------------------------------------------------
       Test Connection
    --------------------------------------------------------------- */
    $('#wai-test-connection').on('click', function () {
        var $btn    = $(this);
        var $result = $('#wai-test-result');

        $btn.prop('disabled', true).text(wai.i18n.testing);
        $result.text('').removeClass('success error');

        $.post(wai.ajax_url, {
            action: 'wai_test_connection',
            nonce:  wai.nonce
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
            $btn.prop('disabled', false).text(wai.i18n.test);
        });
    });

    /* ---------------------------------------------------------------
       Toggle password visibility
    --------------------------------------------------------------- */
    $(document).on('click', '.wai-toggle-visibility', function () {
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
        var $msg = $('#wai-message');
        if (!$msg.length) return;
        var remaining = 4096 - $msg.val().length;
        $('#wai-char-left').text(remaining);
    }

    $('#wai-message').on('input', updateCharCount);
    updateCharCount();

    /* ---------------------------------------------------------------
       Clear button on send page
    --------------------------------------------------------------- */
    $('#wai-clear-btn').on('click', function () {
        $('#wai-to').val('');
        $('#wai-message').val('');
        $('#wai-send-result').hide().empty();
        updateCharCount();
    });

}(jQuery));
