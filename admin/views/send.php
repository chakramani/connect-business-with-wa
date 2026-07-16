<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wabmh-wrap">

    <div class="wabmh-page-header">
        <span class="wabmh-logo">✉️</span>
        <div>
            <h1><?php esc_html_e( 'Send WhatsApp Message', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'Compose and send a message to any WhatsApp number.', 'business-messaging-hub' ); ?></p>
        </div>
    </div>

    <?php if ( ! WABMH_Settings::is_configured() ) : ?>
        <div class="wabmh-notice wabmh-notice--warn">
            <?php
            printf(
                wp_kses(
                    /* translators: %s: URL to the WhatsApp Integration settings page. */
                    __( '⚠️ API not configured. <a href="%s">Add your credentials</a> first.', 'business-messaging-hub' ),
                    [ 'a' => [ 'href' => [] ] ]
                ),
                esc_url( admin_url( 'admin.php?page=wabmh-settings' ) )
            );
            ?>
        </div>
    <?php endif; ?>

    <div class="wabmh-card wabmh-card--send">
        <div id="wabmh-send-result"></div>

        <table class="wabmh-form-table">
            <tr>
                <th><label for="wabmh-to"><?php esc_html_e( 'Recipient Number', 'business-messaging-hub' ); ?> <span class="wabmh-required">*</span></label></th>
                <td>
                    <input
                        type="text"
                        id="wabmh-to"
                        class="regular-text"
                        placeholder="+977980XXXXXXX"
                    />
                    <p class="description"><?php esc_html_e( 'Include country code in E.164 format, e.g. +977980XXXXXXX.', 'business-messaging-hub' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="wabmh-message"><?php esc_html_e( 'Message', 'business-messaging-hub' ); ?> <span class="wabmh-required">*</span></label></th>
                <td>
                    <textarea id="wabmh-message" rows="6" class="large-text" maxlength="4096" placeholder="<?php esc_attr_e( 'Type your message here…', 'business-messaging-hub' ); ?>"></textarea>
                    <p class="description wabmh-char-count">
                        <span id="wabmh-char-left">4096</span> <?php esc_html_e( 'characters remaining', 'business-messaging-hub' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <div class="wabmh-form-actions">
            <button
                id="wabmh-send-btn"
                class="button button-primary wabmh-send-btn"
                data-to-field="wabmh-to"
                data-msg-field="wabmh-message"
                <?php disabled( ! WABMH_Settings::is_configured() ); ?>
            >
                <?php esc_html_e( 'Send Message', 'business-messaging-hub' ); ?>
            </button>
            <button type="button" id="wabmh-clear-btn" class="button">
                <?php esc_html_e( 'Clear', 'business-messaging-hub' ); ?>
            </button>
        </div>
    </div>

    <div class="wabmh-card">
        <h3><?php esc_html_e( 'Tips', 'business-messaging-hub' ); ?></h3>
        <ul class="wabmh-tips">
            <li><?php esc_html_e( 'Recipient must have an active WhatsApp account.', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Free-form text messages can only be sent within a 24-hour customer-service window. Outside that window, use approved templates.', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Maximum message length is 4,096 characters.', 'business-messaging-hub' ); ?></li>
        </ul>
    </div>

</div>
