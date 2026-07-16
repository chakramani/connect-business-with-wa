<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wabmh-wrap">

    <div class="wabmh-page-header">
        <span class="wabmh-logo">⚡</span>
        <div>
            <h1><?php esc_html_e( 'Webhook Log', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'All incoming events received from Meta — delivery updates and incoming messages.', 'business-messaging-hub' ); ?></p>
        </div>
    </div>

    <!-- Stats -->
    <?php if ( ! empty( $webhook_stats ) ) :
        $stat_map = [];
        foreach ( $webhook_stats as $row ) { $stat_map[ $row->event_type ] = (int) $row->total; }
    ?>
    <div class="wabmh-stats-row" style="grid-template-columns:repeat(3,1fr)">
        <div class="wabmh-stat-card wabmh-stat-card--green">
            <div class="wabmh-stat-card__number"><?php echo (int) ( $stat_map['status_update'] ?? 0 ); ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Status Updates', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wabmh-stat-card wabmh-stat-card--blue">
            <div class="wabmh-stat-card__number"><?php echo (int) ( $stat_map['incoming_message'] ?? 0 ); ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Incoming Messages', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wabmh-stat-card wabmh-stat-card--red">
            <div class="wabmh-stat-card__number"><?php echo (int) ( $stat_map['signature_failed'] ?? 0 ); ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Signature Failures', 'business-messaging-hub' ); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="wabmh-card">

        <?php if ( empty( $webhook_logs ) ) : ?>
            <div class="wabmh-notice wabmh-notice--info">
                <strong><?php esc_html_e( 'No webhook events received yet.', 'business-messaging-hub' ); ?></strong><br>
                <?php esc_html_e( 'Once you configure your webhook in Meta and send a message, events will appear here automatically.', 'business-messaging-hub' ); ?>
                <br><a href="<?php echo esc_url( admin_url( 'admin.php?page=wabmh-webhook' ) ); ?>">
                    <?php esc_html_e( '→ Go to Webhook Setup', 'business-messaging-hub' ); ?>
                </a>
            </div>
        <?php else : ?>

            <div class="wabmh-log-topbar">
                <span class="wabmh-log-count">
                    <?php 
                    printf(
                        /* translators: %d: Number of events. */
                        esc_html__( '%d events', 'business-messaging-hub' ),
                        count( $webhook_logs )
                    );
                    ?>
                </span>
                <input type="text" id="wabmh-wh-search" placeholder="🔍 Search events…" class="wabmh-log-search" />
            </div>

            <table class="wabmh-log-table widefat" id="wabmh-wh-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php esc_html_e( 'Event Type', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Direction', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Summary', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Raw Payload', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Received', 'business-messaging-hub' ); ?></th>
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
                    $event_label = $event_labels[ $wl->event_type ] ?? $wl->event_type;

                    $status_colors = [
                        'sent'      => 'sent',
                        'delivered' => 'delivered',
                        'read'      => 'read',
                        'failed'    => 'failed',
                    ];
                ?>
                    <tr class="wabmh-log-row">
                        <td><?php echo (int) $wl->id; ?></td>
                        <td><strong><?php echo esc_html( $event_label ); ?></strong></td>
                        <td>
                            <?php if ( 'inbound' === $wl->direction ) : ?>
                                <span class="wabmh-badge wabmh-badge--sent">⬇ Inbound</span>
                            <?php else : ?>
                                <span class="wabmh-badge wabmh-badge--pending">⬆ Outbound</span>
                            <?php endif; ?>
                        </td>
                        <td class="wabmh-wh-summary">
                            <?php if ( ! empty( $summary['status'] ) ) : ?>
                                <span class="wabmh-badge wabmh-badge--<?php echo esc_attr( $status_colors[ $summary['status'] ] ?? 'pending' ); ?>">
                                    <?php echo esc_html( ucfirst( $summary['status'] ) ); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ( ! empty( $summary['recipient'] ) ) : ?>
                                <span class="wabmh-wh-meta">To: <?php echo esc_html( $summary['recipient'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty( $summary['from'] ) ) : ?>
                                <span class="wabmh-wh-meta">From: <?php echo esc_html( $summary['from'] ); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty( $summary['text'] ) ) : ?>
                                <div class="wabmh-wh-text">"<?php echo esc_html( mb_strimwidth( $summary['text'], 0, 60, '…' ) ); ?>"</div>
                            <?php endif; ?>
                            <?php if ( ! empty( $summary['wamid'] ) ) : ?>
                                <div class="wabmh-msg-id-small"><?php echo esc_html( $summary['wamid'] ); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="button button-small wabmh-raw-btn"
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
    <div id="wabmh-modal-overlay" style="display:none;">
        <div id="wabmh-modal">
            <div id="wabmh-modal-header">
                <strong>📡 Raw Webhook Payload from Meta</strong>
                <button id="wabmh-modal-close">✕ Close</button>
            </div>
            <pre id="wabmh-modal-body"></pre>
        </div>
    </div>

</div>

