<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wai-wrap">

    <div class="wai-page-header">
        <span class="wai-logo">📱</span>
        <div>
            <h1><?php esc_html_e( 'WhatsApp Integration', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'Send WhatsApp messages from your WordPress dashboard.', 'business-messaging-hub' ); ?></p>
        </div>
    </div>

    <?php if ( ! WAI_Settings::is_configured() ) : ?>
        <div class="wai-notice wai-notice--warn">
            <?php 
                printf(
                    wp_kses(
                        /* translators: %s: Settings page URL. */
                        __( '⚠️ WhatsApp API is not configured. <a href="%s">Go to Settings</a> to add your credentials.', 'business-messaging-hub' ),
                        [ 'a' => [ 'href' => [] ] ]
                    ),
                    esc_url( admin_url( 'admin.php?page=wai-settings' ) )
                );
            ?>
        </div>
    <?php endif; ?>

    <!-- Stats row -->
    <div class="wai-stats-row">
        <?php
        $sent   = $stat_map['sent']   ?? 0;
        $failed = $stat_map['failed'] ?? 0;
        $total  = $sent + $failed;
        ?>
        <div class="wai-stat-card">
            <div class="wai-stat-card__number"><?php echo esc_html( $total ); ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Total Messages', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--green">
            <div class="wai-stat-card__number"><?php echo esc_html( $sent ); ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Sent', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--red">
            <div class="wai-stat-card__number"><?php echo esc_html( $failed ); ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Failed', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--blue">
            <div class="wai-stat-card__number">
                <?php echo $total > 0 ? esc_html( round( ( $sent / $total ) * 100 ) ) . '%' : '—'; ?>
            </div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Delivery Rate', 'business-messaging-hub' ); ?></div>
        </div>
    </div>

    <div class="wai-two-col">

        <!-- Quick send -->
        <div class="wai-card">
            <h2><?php esc_html_e( 'Quick Send', 'business-messaging-hub' ); ?></h2>
            <div id="wai-quick-send-result"></div>
            <table class="wai-form-table">
                <tr>
                    <th><label for="wai-qs-to"><?php esc_html_e( 'To', 'business-messaging-hub' ); ?></label></th>
                    <td>
                        <input type="text" id="wai-qs-to" class="regular-text" placeholder="+977980XXXXXXX" />
                    </td>
                </tr>
                <tr>
                    <th><label for="wai-qs-msg"><?php esc_html_e( 'Message', 'business-messaging-hub' ); ?></label></th>
                    <td>
                        <textarea id="wai-qs-msg" rows="4" class="large-text"></textarea>
                    </td>
                </tr>
            </table>
            <p>
                <button id="wai-qs-send" class="button button-primary wai-send-btn" data-to-field="wai-qs-to" data-msg-field="wai-qs-msg">
                    <?php esc_html_e( 'Send Message', 'business-messaging-hub' ); ?>
                </button>
            </p>
        </div>

        <!-- Recent messages -->
        <div class="wai-card">
            <h2>
                <?php esc_html_e( 'Recent Messages', 'business-messaging-hub' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wai-log' ) ); ?>" class="wai-see-all">
                    <?php esc_html_e( 'See all →', 'business-messaging-hub' ); ?>
                </a>
            </h2>
            <?php if ( empty( $recent ) ) : ?>
                <p class="wai-empty"><?php esc_html_e( 'No messages sent yet.', 'business-messaging-hub' ); ?></p>
            <?php else : ?>
                <table class="wai-log-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Recipient', 'business-messaging-hub' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'business-messaging-hub' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'business-messaging-hub' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $recent as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( $log->recipient ); ?></td>
                            <td>
                                <span class="wai-badge wai-badge--<?php echo esc_attr( $log->status ); ?>">
                                    <?php echo esc_html( ucfirst( $log->status ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $log->created_at ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>
