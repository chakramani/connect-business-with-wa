<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wabmh-wrap">

    <div class="wabmh-page-header">
        <span class="wabmh-logo">📋</span>
        <div>
            <h1><?php esc_html_e( 'Message Log', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'Full history of outgoing messages with real-time Meta verification.', 'business-messaging-hub' ); ?></p>
        </div>
    </div>

    <div class="wabmh-card">
        <?php if ( empty( $logs ) ) : ?>
            <p class="wabmh-empty"><?php esc_html_e( 'No messages in the log yet.', 'business-messaging-hub' ); ?></p>
        <?php else : ?>

            <div class="wabmh-log-topbar">
                <span class="wabmh-log-count">
                    <?php
                        printf(
                            /* translators: %d: Number of messages shown. */
                            esc_html__( 'Showing %d messages', 'business-messaging-hub' ),
                            count( $logs )
                        );
                    ?>
                </span>
                <input type="text" id="wabmh-log-search" placeholder="🔍 Search recipient or message…" class="wabmh-log-search" />
            </div>

            <table class="wabmh-log-table widefat" id="wabmh-log-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php esc_html_e( 'Recipient', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Message', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Meta Verify', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Sent By', 'business-messaging-hub' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'business-messaging-hub' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $logs as $log ) :
                    $user     = get_userdata( (int) $log->sent_by );
                    $username = $user ? $user->display_name : '—';
                    $short    = mb_strimwidth( $log->message, 0, 80, '…' );
                    $is_long  = mb_strlen( $log->message ) > 80;
                ?>
                    <tr class="wabmh-log-row">
                        <td><?php echo (int) $log->id; ?></td>

                        <td class="wabmh-log-recipient">
                            <?php echo esc_html( $log->recipient ); ?>
                        </td>

                        <td class="wabmh-log-msg-cell">
                            <div class="wabmh-msg-preview">
                                <?php echo esc_html( $short ); ?>
                                <?php if ( $is_long ) : ?>
                                    <button class="wabmh-expand-btn">▼ Show full</button>
                                <?php endif; ?>
                            </div>
                            <?php if ( $is_long ) : ?>
                                <div class="wabmh-msg-full" style="display:none;">
                                    <div class="wabmh-msg-full-text"><?php echo esc_html( $log->message ); ?></div>
                                    <button class="wabmh-collapse-btn">▲ Collapse</button>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $log->error ) ) : ?>
                                <div class="wabmh-error-text">❌ <?php echo esc_html( $log->error ); ?></div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="wabmh-badge wabmh-badge--<?php echo esc_attr( $log->status ); ?>">
                                <?php echo esc_html( ucfirst( $log->status ) ); ?>
                            </span>
                        </td>

                        <td class="wabmh-verify-cell">
                            <?php if ( ! empty( $log->msg_id ) ) : ?>
                                <button class="button button-small wabmh-verify-btn"
                                    data-msg-id="<?php echo esc_attr( $log->msg_id ); ?>"
                                    data-log-id="<?php echo (int) $log->id; ?>">
                                    🔍 Verify on Meta
                                </button>
                                <div class="wabmh-verify-result" id="verify-<?php echo (int) $log->id; ?>"></div>
                                <div class="wabmh-msg-id-small"><?php echo esc_html( $log->msg_id ); ?></div>
                            <?php else : ?>
                                <span class="wabmh-muted">—</span>
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
    <div id="wabmh-modal-overlay" style="display:none;">
        <div id="wabmh-modal">
            <div id="wabmh-modal-header">
                <strong>📡 Meta API Raw Response</strong>
                <button id="wabmh-modal-close">✕ Close</button>
            </div>
            <pre id="wabmh-modal-body"></pre>
        </div>
    </div>

</div>

