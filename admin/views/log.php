<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wai-wrap">

    <div class="wai-page-header">
        <span class="wai-logo">📋</span>
        <div>
            <h1><?php esc_html_e( 'Message Log', 'connect-business-with-wa' ); ?></h1>
            <p><?php esc_html_e( 'Full history of outgoing messages with real-time Meta verification.', 'connect-business-with-wa' ); ?></p>
        </div>
    </div>

    <div class="wai-card">
        <?php if ( empty( $logs ) ) : ?>
            <p class="wai-empty"><?php esc_html_e( 'No messages in the log yet.', 'connect-business-with-wa' ); ?></p>
        <?php else : ?>

            <div class="wai-log-topbar">
                <span class="wai-log-count">
                    <?php
                        printf(
                            /* translators: %d: Number of messages shown. */
                            esc_html__( 'Showing %d messages', 'connect-business-with-wa' ),
                            count( $logs )
                        );
                    ?>
                </span>
                <input type="text" id="wai-log-search" placeholder="🔍 Search recipient or message…" class="wai-log-search" />
            </div>

            <table class="wai-log-table widefat" id="wai-log-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php esc_html_e( 'Recipient', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Meta Verify', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Sent By', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'connect-business-with-wa' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $logs as $log ) :
                    $user     = get_userdata( (int) $log->sent_by );
                    $username = $user ? esc_html( $user->display_name ) : '—';
                    $short    = mb_strimwidth( $log->message, 0, 80, '…' );
                    $is_long  = mb_strlen( $log->message ) > 80;
                ?>
                    <tr class="wai-log-row">
                        <td><?php echo (int) $log->id; ?></td>

                        <td class="wai-log-recipient">
                            <?php echo esc_html( $log->recipient ); ?>
                        </td>

                        <td class="wai-log-msg-cell">
                            <div class="wai-msg-preview">
                                <?php echo esc_html( $short ); ?>
                                <?php if ( $is_long ) : ?>
                                    <button class="wai-expand-btn">▼ Show full</button>
                                <?php endif; ?>
                            </div>
                            <?php if ( $is_long ) : ?>
                                <div class="wai-msg-full" style="display:none;">
                                    <div class="wai-msg-full-text"><?php echo esc_html( $log->message ); ?></div>
                                    <button class="wai-collapse-btn">▲ Collapse</button>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $log->error ) ) : ?>
                                <div class="wai-error-text">❌ <?php echo esc_html( $log->error ); ?></div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="wai-badge wai-badge--<?php echo esc_attr( $log->status ); ?>">
                                <?php echo esc_html( ucfirst( $log->status ) ); ?>
                            </span>
                        </td>

                        <td class="wai-verify-cell">
                            <?php if ( ! empty( $log->msg_id ) ) : ?>
                                <button class="button button-small wai-verify-btn"
                                    data-msg-id="<?php echo esc_attr( $log->msg_id ); ?>"
                                    data-log-id="<?php echo (int) $log->id; ?>">
                                    🔍 Verify on Meta
                                </button>
                                <div class="wai-verify-result" id="verify-<?php echo (int) $log->id; ?>"></div>
                                <div class="wai-msg-id-small"><?php echo esc_html( $log->msg_id ); ?></div>
                            <?php else : ?>
                                <span class="wai-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <td><?php echo esc_html( $username ); ?></td>
                        <td><?php echo esc_html( $log->created_at ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>

    <!-- Raw API response modal -->
    <div id="wai-modal-overlay" style="display:none;">
        <div id="wai-modal">
            <div id="wai-modal-header">
                <strong>📡 Meta API Raw Response</strong>
                <button id="wai-modal-close">✕ Close</button>
            </div>
            <pre id="wai-modal-body"></pre>
        </div>
    </div>

</div>

<style>
.wai-log-topbar {
    display:flex; justify-content:space-between;
    align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;
}
.wai-log-count  { color:var(--wai-muted); font-size:.88rem; }
.wai-log-search {
    padding:6px 12px; border:1px solid var(--wai-border);
    border-radius:6px; width:280px; font-size:.9rem;
}
.wai-log-search:focus { border-color:var(--wai-green); outline:none; }

.wai-log-msg-cell { max-width:300px; }
.wai-msg-preview  { color:var(--wai-text); font-size:.88rem; }
.wai-msg-full-text {
    background:#f9f9f9; border:1px solid var(--wai-border);
    border-radius:6px; padding:10px 12px; font-size:.88rem;
    white-space:pre-wrap; word-break:break-word;
    margin-top:6px; max-height:200px; overflow-y:auto;
}
.wai-expand-btn, .wai-collapse-btn {
    background:none; border:none; color:var(--wai-green-dark);
    cursor:pointer; font-size:.78rem; padding:2px 4px;
    margin-left:4px; text-decoration:underline;
}
.wai-verify-cell   { min-width:160px; }
.wai-verify-result { margin-top:6px; font-size:.8rem; }
.wai-msg-id-small  {
    font-size:.7rem; color:var(--wai-muted);
    word-break:break-all; margin-top:4px; max-width:160px;
}
.wai-muted       { color:var(--wai-muted); }
.wai-verify-ok   { color:#1a7a45; font-weight:600; }
.wai-verify-fail { color:var(--wai-red); }
.wai-verify-raw  {
    color:var(--wai-blue); cursor:pointer;
    text-decoration:underline; font-size:.78rem;
    display:block; margin-top:3px;
}

/* Modal */
#wai-modal-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,.55);
    z-index:99999; display:flex;
    align-items:center; justify-content:center;
}
#wai-modal {
    background:#fff; border-radius:10px;
    width:700px; max-width:95vw; max-height:80vh;
    display:flex; flex-direction:column;
    box-shadow:0 8px 32px rgba(0,0,0,.22);
}
#wai-modal-header {
    display:flex; justify-content:space-between;
    align-items:center; padding:14px 20px;
    border-bottom:1px solid var(--wai-border); font-size:1rem;
}
#wai-modal-close {
    background:none; border:1px solid #ccc; border-radius:5px;
    padding:3px 10px; font-size:.85rem; cursor:pointer; color:var(--wai-muted);
}
#wai-modal-close:hover { color:var(--wai-red); border-color:var(--wai-red); }
#wai-modal-body {
    padding:16px 20px; overflow-y:auto; font-size:.82rem;
    background:#1e1e1e; color:#d4d4d4;
    border-radius:0 0 10px 10px;
    margin:0; white-space:pre-wrap; word-break:break-word; flex:1;
}
.wai-log-row.wai-hidden { display:none; }
</style>

<script>
jQuery(function($){

    /* Expand / Collapse full message */
    $(document).on('click', '.wai-expand-btn', function(){
        var $cell = $(this).closest('.wai-log-msg-cell');
        $cell.find('.wai-msg-preview').hide();
        $cell.find('.wai-msg-full').show();
    });
    $(document).on('click', '.wai-collapse-btn', function(){
        var $cell = $(this).closest('.wai-log-msg-cell');
        $cell.find('.wai-msg-full').hide();
        $cell.find('.wai-msg-preview').show();
    });

    /* Verify message on Meta */
    $(document).on('click', '.wai-verify-btn', function(){
        var $btn    = $(this);
        var msgId   = $btn.data('msg-id');
        var logId   = $btn.data('log-id');
        var $result = $('#verify-' + logId);

        $btn.prop('disabled', true).text('⏳ Checking…');
        $result.html('');

        $.post(wai.ajax_url, {
            action: 'wai_verify_message',
            nonce:  wai.nonce,
            msg_id: msgId,
            log_id: logId
        })
        .done(function(res){
            if(res.success){
                $result.html(
                    '<span class="wai-verify-ok">✅ ' + res.data.status + '</span>' +
                    '<span class="wai-verify-raw" data-raw="' +
                        encodeURIComponent(JSON.stringify(res.data.raw, null, 2)) +
                    '">📡 View raw response</span>'
                );
            } else {
                $result.html('<span class="wai-verify-fail">❌ ' + res.data.message + '</span>');
            }
        })
        .fail(function(){
            $result.html('<span class="wai-verify-fail">❌ Network error</span>');
        })
        .always(function(){
            $btn.prop('disabled', false).text('🔍 Verify on Meta');
        });
    });

    /* Show raw response in modal */
    $(document).on('click', '.wai-verify-raw', function(){
        var raw = decodeURIComponent($(this).data('raw'));
        $('#wai-modal-body').text(raw);
        $('#wai-modal-overlay').fadeIn(150);
    });
    $('#wai-modal-close').on('click', function(){
        $('#wai-modal-overlay').fadeOut(150);
    });
    $('#wai-modal-overlay').on('click', function(e){
        if(e.target === this) $('#wai-modal-overlay').fadeOut(150);
    });

    /* Live search */
    $('#wai-log-search').on('input', function(){
        var q = $(this).val().toLowerCase();
        $('#wai-log-table tbody tr').each(function(){
            $(this).toggleClass('wai-hidden', q.length > 0 && $(this).text().toLowerCase().indexOf(q) === -1);
        });
    });

});
</script>
