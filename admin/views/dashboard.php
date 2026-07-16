<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wabmh-wrap">

    <div class="wabmh-page-header">
        <span class="wabmh-logo">📱</span>
        <div>
            <h1><?php esc_html_e( 'WhatsApp Integration', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'Send WhatsApp messages from your WordPress dashboard.', 'business-messaging-hub' ); ?></p>
        </div>
    </div>

    <?php if ( ! WABMH_Settings::is_configured() ) : ?>
        <div class="wabmh-notice wabmh-notice--warn">
            <?php 
                printf(
                    wp_kses(
                        /* translators: %s: Settings page URL. */
                        __( '⚠️ WhatsApp API is not configured. <a href="%s">Go to Settings</a> to add your credentials.', 'business-messaging-hub' ),
                        [ 'a' => [ 'href' => [] ] ]
                    ),
                    esc_url( admin_url( 'admin.php?page=wabmh-settings' ) )
                );
            ?>
        </div>
    <?php endif; ?>

    <!-- Stats row -->
    <div class="wabmh-stats-row">
        <?php
        $sent   = $stat_map['sent']   ?? 0;
        $failed = $stat_map['failed'] ?? 0;
        $total  = $sent + $failed;
        ?>
        <div class="wabmh-stat-card">
            <div class="wabmh-stat-card__number"><?php echo esc_html( $total ); ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Total Messages', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wabmh-stat-card wabmh-stat-card--green">
            <div class="wabmh-stat-card__number"><?php echo esc_html( $sent ); ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Sent', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wabmh-stat-card wabmh-stat-card--red">
            <div class="wabmh-stat-card__number"><?php echo esc_html( $failed ); ?></div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Failed', 'business-messaging-hub' ); ?></div>
        </div>
        <div class="wabmh-stat-card wabmh-stat-card--blue">
            <div class="wabmh-stat-card__number">
                <?php echo $total > 0 ? esc_html( round( ( $sent / $total ) * 100 ) ) . '%' : '—'; ?>
            </div>
            <div class="wabmh-stat-card__label"><?php esc_html_e( 'Delivery Rate', 'business-messaging-hub' ); ?></div>
        </div>
    </div>

    <div class="wabmh-two-col">

        <!-- Quick send -->
        <div class="wabmh-card">
            <h2><?php esc_html_e( 'Quick Send', 'business-messaging-hub' ); ?></h2>
            <div id="wabmh-quick-send-result"></div>
            <table class="wabmh-form-table">
                <tr>
                    <th><label for="wabmh-qs-to"><?php esc_html_e( 'To', 'business-messaging-hub' ); ?></label></th>
                    <td>
                        <input type="text" id="wabmh-qs-to" class="regular-text" placeholder="+977980XXXXXXX" />
                    </td>
                </tr>
                <tr>
                    <th><label for="wabmh-qs-msg"><?php esc_html_e( 'Message', 'business-messaging-hub' ); ?></label></th>
                    <td>
                        <textarea id="wabmh-qs-msg" rows="4" class="large-text"></textarea>
                    </td>
                </tr>
            </table>
            <p>
                <button id="wabmh-qs-send" class="button button-primary wabmh-send-btn" data-to-field="wabmh-qs-to" data-msg-field="wabmh-qs-msg">
                    <?php esc_html_e( 'Send Message', 'business-messaging-hub' ); ?>
                </button>
            </p>
        </div>

        <!-- Recent messages -->
        <div class="wabmh-card">
            <h2>
                <?php esc_html_e( 'Recent Messages', 'business-messaging-hub' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wabmh-log' ) ); ?>" class="wabmh-see-all">
                    <?php esc_html_e( 'See all →', 'business-messaging-hub' ); ?>
                </a>
            </h2>
            <?php if ( empty( $recent ) ) : ?>
                <p class="wabmh-empty"><?php esc_html_e( 'No messages sent yet.', 'business-messaging-hub' ); ?></p>
            <?php else : ?>
                <table class="wabmh-log-table">
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
                                <span class="wabmh-badge wabmh-badge--<?php echo esc_attr( $log->status ); ?>">
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
