<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wai-wrap">

    <div class="wai-page-header">
        <span class="wai-logo">⚡</span>
        <div>
            <h1><?php esc_html_e( 'Webhook Log', 'connect-business-with-wa' ); ?></h1>
            <p><?php esc_html_e( 'All incoming events received from Meta — delivery updates and incoming messages.', 'connect-business-with-wa' ); ?></p>
        </div>
    </div>

    <!-- Stats -->
    <?php if ( ! empty( $webhook_stats ) ) :
        $stat_map = [];
        foreach ( $webhook_stats as $row ) { $stat_map[ $row->event_type ] = (int) $row->total; }
    ?>
    <div class="wai-stats-row" style="grid-template-columns:repeat(3,1fr)">
        <div class="wai-stat-card wai-stat-card--green">
            <div class="wai-stat-card__number"><?php echo (int) ( $stat_map['status_update'] ?? 0 ); ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Status Updates', 'connect-business-with-wa' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--blue">
            <div class="wai-stat-card__number"><?php echo (int) ( $stat_map['incoming_message'] ?? 0 ); ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Incoming Messages', 'connect-business-with-wa' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--red">
            <div class="wai-stat-card__number"><?php echo (int) ( $stat_map['signature_failed'] ?? 0 ); ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Signature Failures', 'connect-business-with-wa' ); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="wai-card">

        <?php if ( empty( $webhook_logs ) ) : ?>
            <div class="wai-notice wai-notice--info">
                <strong><?php esc_html_e( 'No webhook events received yet.', 'connect-business-with-wa' ); ?></strong><br>
                <?php esc_html_e( 'Once you configure your webhook in Meta and send a message, events will appear here automatically.', 'connect-business-with-wa' ); ?>
                <br><a href="<?php echo esc_url( admin_url( 'admin.php?page=wai-webhook' ) ); ?>">
                    <?php esc_html_e( '→ Go to Webhook Setup', 'connect-business-with-wa' ); ?>
                </a>
            </div>
        <?php else : ?>

            <div class="wai-log-topbar">
                <span class="wai-log-count">
                    <?php 
                    printf(
                        /* translators: %d: Number of events. */
                        esc_html__( '%d events', 'connect-business-with-wa' ),
                        count( $webhook_logs )
                    );
                    ?>
                </span>
                <input type="text" id="wai-wh-search" placeholder="🔍 Search events…" class="wai-log-search" />
            </div>

            <table class="wai-log-table widefat" id="wai-wh-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php esc_html_e( 'Event Type', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Direction', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Summary', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Raw Payload', 'connect-business-with-wa' ); ?></th>
                        <th><?php esc_html_e( 'Received', 'connect-business-with-wa' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $webhook_logs as $wl ) :
                    $summary = json_decode( $wl->summary, true ) ?: [];
                    $event_labels = [
                        'status_update'    => '📬 Status Update',
                        'incoming_message' => '💬 Incoming Message',
                        'signature_failed' => '🚫 Signature Failed',
                    ];
                    $event_label = $event_labels[ $wl->event_type ] ?? esc_html( $wl->event_type );

                    $status_colors = [
                        'sent'      => 'sent',
                        'delivered' => 'delivered',
                        'read'      => 'read',
                        'failed'    => 'failed',
                    ];
                ?>
                    <tr class="wai-log-row">
                        <td><?php echo (int) $wl->id; ?></td>
                        <td><strong><?php echo esc_html( $event_label ); ?></strong></td>
                        <td>
                            <?php if ( 'inbound' === $wl->direction ) : ?>
                                <span class="wai-badge wai-badge--sent">⬇ Inbound</span>
                            <?php else : ?>
                                <span class="wai-badge wai-badge--pending">⬆ Outbound</span>
                            <?php endif; ?>
                        </td>
                        <td class="wai-wh-summary">
                            <?php if ( ! empty( $summary['status'] ) ) : ?>
                                <span class="wai-badge wai-badge--<?php echo esc_attr( $status_colors[ $summary['status'] ] ?? 'pending' ); ?>">
                                    <?php echo esc_html( ucfirst( $summary['status'] ) ); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ( ! empty( $summary['recipient'] ) ) : ?>
                                <span class="wai-wh-meta">To: <?php echo esc_html( $summary['recipient'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty( $summary['from'] ) ) : ?>
                                <span class="wai-wh-meta">From: <?php echo esc_html( $summary['from'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty( $summary['text'] ) ) : ?>
                                <div class="wai-wh-text">"<?php echo esc_html( mb_strimwidth( $summary['text'], 0, 60, '…' ) ); ?>"</div>
                            <?php endif; ?>
                            <?php if ( ! empty( $summary['wamid'] ) ) : ?>
                                <div class="wai-msg-id-small"><?php echo esc_html( $summary['wamid'] ); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="button button-small wai-raw-btn"
                                data-raw="<?php echo esc_attr( $wl->raw_payload ); ?>">
                                📡 View Raw
                            </button>
                        </td>
                        <td><?php echo esc_html( $wl->received_at ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>

    <!-- Raw payload modal -->
    <div id="wai-modal-overlay" style="display:none;">
        <div id="wai-modal">
            <div id="wai-modal-header">
                <strong>📡 Raw Webhook Payload from Meta</strong>
                <button id="wai-modal-close">✕ Close</button>
            </div>
            <pre id="wai-modal-body"></pre>
        </div>
    </div>

</div>

<style>
.wai-wh-summary { max-width:280px; }
.wai-wh-meta    { display:block; font-size:.8rem; color:var(--wai-muted); margin-top:3px; }
.wai-wh-text    { font-size:.85rem; color:var(--wai-text); margin-top:4px; font-style:italic; }
.wai-badge--delivered { background:#d0eeff; color:#0a5a8a; }
.wai-badge--read      { background:#e8d5f5; color:#5a0a8a; }

/* Reuse modal styles from log.php */
#wai-modal-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,.55);
    z-index:99999; display:flex; align-items:center; justify-content:center;
}
#wai-modal {
    background:#fff; border-radius:10px; width:700px;
    max-width:95vw; max-height:80vh; display:flex;
    flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,.22);
}
#wai-modal-header {
    display:flex; justify-content:space-between; align-items:center;
    padding:14px 20px; border-bottom:1px solid var(--wai-border); font-size:1rem;
}
#wai-modal-close {
    background:none; border:1px solid #ccc; border-radius:5px;
    padding:3px 10px; font-size:.85rem; cursor:pointer; color:var(--wai-muted);
}
#wai-modal-close:hover { color:var(--wai-red); border-color:var(--wai-red); }
#wai-modal-body {
    padding:16px 20px; overflow-y:auto; font-size:.82rem;
    background:#1e1e1e; color:#d4d4d4; border-radius:0 0 10px 10px;
    margin:0; white-space:pre-wrap; word-break:break-word; flex:1;
}
.wai-log-row.wai-hidden { display:none; }
</style>

<script>
jQuery(function($){
    /* Raw payload modal */
    $(document).on('click', '.wai-raw-btn', function(){
        // Use .attr() not .data() — jQuery .data() auto-parses valid JSON into an object
        var raw = $(this).attr('data-raw');
        try { raw = JSON.stringify(JSON.parse(raw), null, 2); } catch(e){}
        $('#wai-modal-body').text(raw);
        $('#wai-modal-overlay').fadeIn(150);
    });
    $('#wai-modal-close').on('click', function(){ $('#wai-modal-overlay').fadeOut(150); });
    $('#wai-modal-overlay').on('click', function(e){ if(e.target===this) $('#wai-modal-overlay').fadeOut(150); });

    /* Search */
    $('#wai-wh-search').on('input', function(){
        var q = $(this).val().toLowerCase();
        $('#wai-wh-table tbody tr').each(function(){
            $(this).toggleClass('wai-hidden', q.length > 0 && $(this).text().toLowerCase().indexOf(q) === -1);
        });
    });
});
</script>
