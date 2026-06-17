<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wai-wrap">

    <div class="wai-page-header">
        <span class="wai-logo">✉️</span>
        <div>
            <h1><?php esc_html_e( 'Send WhatsApp Message', 'connect-business-with-wa' ); ?></h1>
            <p><?php esc_html_e( 'Compose and send a message to any WhatsApp number.', 'connect-business-with-wa' ); ?></p>
        </div>
    </div>

    <?php if ( ! WAI_Settings::is_configured() ) : ?>
        <div class="wai-notice wai-notice--warn">
            <?php
            printf(
                wp_kses(
                    /* translators: %s: URL to the WhatsApp Integration settings page. */
                    __( '⚠️ API not configured. <a href="%s">Add your credentials</a> first.', 'connect-business-with-wa' ),
                    [ 'a' => [ 'href' => [] ] ]
                ),
                esc_url( admin_url( 'admin.php?page=wai-settings' ) )
            );
?>
            ?>
        </div>
    <?php endif; ?>

    <div class="wai-card wai-card--send">
        <div id="wai-send-result"></div>

        <table class="wai-form-table">
            <tr>
                <th><label for="wai-to"><?php esc_html_e( 'Recipient Number', 'connect-business-with-wa' ); ?> <span class="wai-required">*</span></label></th>
                <td>
                    <input
                        type="text"
                        id="wai-to"
                        class="regular-text"
                        placeholder="+977980XXXXXXX"
                    />
                    <p class="description"><?php esc_html_e( 'Include country code in E.164 format, e.g. +977980XXXXXXX.', 'connect-business-with-wa' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="wai-message"><?php esc_html_e( 'Message', 'connect-business-with-wa' ); ?> <span class="wai-required">*</span></label></th>
                <td>
                    <textarea id="wai-message" rows="6" class="large-text" maxlength="4096" placeholder="<?php esc_attr_e( 'Type your message here…', 'connect-business-with-wa' ); ?>"></textarea>
                    <p class="description wai-char-count">
                        <span id="wai-char-left">4096</span> <?php esc_html_e( 'characters remaining', 'connect-business-with-wa' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <div class="wai-form-actions">
            <button
                id="wai-send-btn"
                class="button button-primary wai-send-btn"
                data-to-field="wai-to"
                data-msg-field="wai-message"
                <?php disabled( ! WAI_Settings::is_configured() ); ?>
            >
                <?php esc_html_e( 'Send Message', 'connect-business-with-wa' ); ?>
            </button>
            <button type="button" id="wai-clear-btn" class="button">
                <?php esc_html_e( 'Clear', 'connect-business-with-wa' ); ?>
            </button>
        </div>
    </div>

    <div class="wai-card">
        <h3><?php esc_html_e( 'Tips', 'connect-business-with-wa' ); ?></h3>
        <ul class="wai-tips">
            <li><?php esc_html_e( 'Recipient must have an active WhatsApp account.', 'connect-business-with-wa' ); ?></li>
            <li><?php esc_html_e( 'Free-form text messages can only be sent within a 24-hour customer-service window. Outside that window, use approved templates.', 'connect-business-with-wa' ); ?></li>
            <li><?php esc_html_e( 'Maximum message length is 4,096 characters.', 'connect-business-with-wa' ); ?></li>
        </ul>
    </div>

</div>
