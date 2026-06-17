<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wai-wrap">

    <div class="wai-page-header">
        <span class="wai-logo">📱</span>
        <div>
            <h1><?php esc_html_e( 'WhatsApp Integration', 'connect-business-with-wa' ); ?></h1>
            <p><?php esc_html_e( 'Send WhatsApp messages from your WordPress dashboard.', 'connect-business-with-wa' ); ?></p>
        </div>
    </div>

    <?php if ( ! WAI_Settings::is_configured() ) : ?>
        <div class="wai-notice wai-notice--warn">
            <?php 
                printf(
                    wp_kses(
                        /* translators: %s: Settings page URL. */
                        __( '⚠️ WhatsApp API is not configured. <a href="%s">Go to Settings</a> to add your credentials.', 'connect-business-with-wa' ),
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
            <div class="wai-stat-card__label"><?php esc_html_e( 'Total Messages', 'connect-business-with-wa' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--green">
            <div class="wai-stat-card__number"><?php echo esc_html( $sent ); ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Sent', 'connect-business-with-wa' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--red">
            <div class="wai-stat-card__number"><?php echo esc_html( $failed ); ?></div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Failed', 'connect-business-with-wa' ); ?></div>
        </div>
        <div class="wai-stat-card wai-stat-card--blue">
            <div class="wai-stat-card__number">
                <?php echo $total > 0 ? esc_html( round( ( $sent / $total ) * 100 ) ) . '%' : '—'; ?>
            </div>
            <div class="wai-stat-card__label"><?php esc_html_e( 'Delivery Rate', 'connect-business-with-wa' ); ?></div>
        </div>
    </div>

    <div class="wai-two-col">

        <!-- Quick send -->
        <div class="wai-card">
            <h2><?php esc_html_e( 'Quick Send', 'connect-business-with-wa' ); ?></h2>
            <div id="wai-quick-send-result"></div>
            <table class="wai-form-table">
                <tr>
                    <th><label for="wai-qs-to"><?php esc_html_e( 'To', 'connect-business-with-wa' ); ?></label></th>
                    <td>
                        <input type="text" id="wai-qs-to" class="regular-text" placeholder="+977980XXXXXXX" />
                    </td>
                </tr>
                <tr>
                    <th><label for="wai-qs-msg"><?php esc_html_e( 'Message', 'connect-business-with-wa' ); ?></label></th>
                    <td>
                        <textarea id="wai-qs-msg" rows="4" class="large-text"></textarea>
                    </td>
                </tr>
            </table>
            <p>
                <button id="wai-qs-send" class="button button-primary wai-send-btn" data-to-field="wai-qs-to" data-msg-field="wai-qs-msg">
                    <?php esc_html_e( 'Send Message', 'connect-business-with-wa' ); ?>
                </button>
            </p>
        </div>

        <!-- Recent messages -->
        <div class="wai-card">
            <h2>
                <?php esc_html_e( 'Recent Messages', 'connect-business-with-wa' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wai-log' ) ); ?>" class="wai-see-all">
                    <?php esc_html_e( 'See all →', 'connect-business-with-wa' ); ?>
                </a>
            </h2>
            <?php if ( empty( $recent ) ) : ?>
                <p class="wai-empty"><?php esc_html_e( 'No messages sent yet.', 'connect-business-with-wa' ); ?></p>
            <?php else : ?>
                <table class="wai-log-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Recipient', 'connect-business-with-wa' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'connect-business-with-wa' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'connect-business-with-wa' ); ?></th>
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
