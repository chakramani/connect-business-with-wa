<?php if ( ! defined( 'ABSPATH' ) ) exit;

$default_url    = rest_url( 'wabmh/v1/webhook' );
$custom_url     = WABMH_Settings::get( 'custom_webhook_url' );
$webhook_url    = ! empty( $custom_url ) ? $custom_url : $default_url;
$verify_token   = WABMH_Settings::get( 'webhook_verify_token' );
$is_local       = ( strpos( $default_url, 'localhost' ) !== false || strpos( $default_url, '.local' ) !== false );

// Build the test URL so the user can verify manually too
$test_url = $webhook_url
    . '?hub.mode=subscribe'
    . '&hub.verify_token=' . rawurlencode( $verify_token )
    . '&hub.challenge=TEST123';
?>
<div class="wrap wabmh-wrap">

    <div class="wabmh-page-header">
        <span class="wabmh-logo">⚡</span>
        <div>
            <h1><?php esc_html_e( 'Webhook Setup', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'Receive real-time delivery updates (sent → delivered → read) from Meta.', 'business-messaging-hub' ); ?></p>
        </div>
    </div>

    <?php if ( $is_local ) : ?>
    <div class="wabmh-notice wabmh-notice--warn">
        ⚠️ <strong><?php esc_html_e( 'You are on a local server.', 'business-messaging-hub' ); ?></strong>
        <?php esc_html_e( 'Meta cannot reach localhost directly. Use ngrok to get a public URL, then paste it in the field below.', 'business-messaging-hub' ); ?>
        <br><br>
        <strong><?php esc_html_e( 'Quick ngrok command:', 'business-messaging-hub' ); ?></strong>
        <code class="wabmh-inline-code">ngrok http 10003</code>
        <?php esc_html_e( '(replace 10003 with your Local site port)', 'business-messaging-hub' ); ?>
    </div>
    <?php endif; ?>

    <!-- ===================== STEP 1 — Verify Token ===================== -->
    <div class="wabmh-card">
        <h2>🔑 <?php esc_html_e( 'Step 1 — Verify Token', 'business-messaging-hub' ); ?></h2>
        <p><?php esc_html_e( 'This token proves to Meta that your site owns this webhook. Generate one, copy it, and keep it safe.', 'business-messaging-hub' ); ?></p>

        <div class="wabmh-token-box">
            <div class="wabmh-copy-row">
                <input
                    type="text"
                    id="wabmh-verify-token-display"
                    value="<?php echo esc_attr( $verify_token ); ?>"
                    class="regular-text wabmh-token-input"
                    readonly
                    placeholder="<?php esc_attr_e( 'Click Generate to create a token', 'business-messaging-hub' ); ?>"
                />
                <button type="button" class="button wabmh-copy-btn" data-target="wabmh-verify-token-display">
                    📋 <?php esc_html_e( 'Copy', 'business-messaging-hub' ); ?>
                </button>
                <button type="button" id="wabmh-regen-token" class="button button-secondary">
                    🔄 <?php esc_html_e( 'Generate New', 'business-messaging-hub' ); ?>
                </button>
            </div>

            <?php if ( empty( $verify_token ) ) : ?>
                <p class="wabmh-token-hint wabmh-token-hint--warn">
                    ⚠️ <?php esc_html_e( 'No token yet. Click "Generate New" to create one.', 'business-messaging-hub' ); ?>
                </p>
            <?php else : ?>
                <p class="wabmh-token-hint">
                    ✅ <?php esc_html_e( 'Token is set. Copy it and paste it into Meta\'s "Verify token" field.', 'business-messaging-hub' ); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Live test inside this card -->
        <div class="wabmh-token-test-row">
            <button type="button" id="wabmh-test-webhook" class="button button-primary">
                🧪 <?php esc_html_e( 'Test Token Now', 'business-messaging-hub' ); ?>
            </button>
            <span id="wabmh-webhook-test-result" class="wabmh-inline-result"></span>
        </div>

        <details class="wabmh-manual-test">
            <summary><?php esc_html_e( 'Manual browser test', 'business-messaging-hub' ); ?></summary>
            <p><?php esc_html_e( 'Open the URL below in your browser. If it shows "TEST123", your webhook is working correctly.', 'business-messaging-hub' ); ?></p>
            <code class="wabmh-url-preview" id="wabmh-manual-test-url"><?php echo esc_html( $test_url ); ?></code>
            <button type="button" class="button wabmh-copy-btn" data-target="wabmh-manual-test-url" style="margin-top:6px;">
                📋 <?php esc_html_e( 'Copy Test URL', 'business-messaging-hub' ); ?>
            </button>
        </details>
    </div>

    <!-- ===================== STEP 2 — Callback URL ===================== -->
    <div class="wabmh-card">
        <h2>🔗 <?php esc_html_e( 'Step 2 — Callback URL', 'business-messaging-hub' ); ?></h2>
        <p><?php esc_html_e( 'This is the URL Meta will send events to. Copy it and paste it into Meta\'s "Callback URL" field.', 'business-messaging-hub' ); ?></p>

        <div class="wabmh-copy-row" style="margin-bottom:8px;">
            <input type="text" id="wabmh-webhook-url-display" value="<?php echo esc_attr( $webhook_url ); ?>" class="large-text" readonly />
            <button type="button" class="button wabmh-copy-btn" data-target="wabmh-webhook-url-display">
                📋 <?php esc_html_e( 'Copy', 'business-messaging-hub' ); ?>
            </button>
        </div>

        <?php if ( ! empty( $custom_url ) ) : ?>
            <p class="description" style="color:var(--wabmh-green-dark);">✅ <?php esc_html_e( 'Using your custom URL.', 'business-messaging-hub' ); ?></p>
        <?php else : ?>
            <p class="description"><?php esc_html_e( 'Using auto-detected WordPress REST API URL.', 'business-messaging-hub' ); ?></p>
        <?php endif; ?>

        <!-- Custom URL override -->
        <form method="post" action="options.php" style="margin-top:16px;">
            <?php settings_fields( 'wabmh_settings_group' ); ?>
            <input type="hidden" name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[access_token]"         value="<?php echo esc_attr( WABMH_Settings::get( 'access_token' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[phone_number_id]"      value="<?php echo esc_attr( WABMH_Settings::get( 'phone_number_id' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[app_secret]"           value="<?php echo esc_attr( WABMH_Settings::get( 'app_secret' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[business_name]"        value="<?php echo esc_attr( WABMH_Settings::get( 'business_name' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[default_country]"      value="<?php echo esc_attr( WABMH_Settings::get( 'default_country' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[webhook_verify_token]" value="<?php echo esc_attr( $verify_token ); ?>" />

            <details class="wabmh-custom-url-details">
                <summary><?php esc_html_e( 'Override with a custom URL (e.g. ngrok)', 'business-messaging-hub' ); ?></summary>
                <div style="margin-top:10px;">
                    <input
                        type="url"
                        id="wabmh_custom_webhook_url"
                        name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[custom_webhook_url]"
                        value="<?php echo esc_attr( $custom_url ); ?>"
                        class="large-text"
                        placeholder="https://abc123.ngrok-free.app/wp-json/wabmh/v1/webhook"
                    />
                    <p class="description"><?php esc_html_e( 'Leave blank to use the auto-detected URL.', 'business-messaging-hub' ); ?></p>
                    <?php submit_button( __( 'Save Custom URL', 'business-messaging-hub' ), 'secondary', 'submit', false ); ?>
                </div>
            </details>
        </form>
    </div>

    <!-- ===================== STEP 3 — Meta instructions ===================== -->
    <div class="wabmh-card">
        <h2>🚀 <?php esc_html_e( 'Step 3 — Paste into Meta & Verify', 'business-messaging-hub' ); ?></h2>
        <ol class="wabmh-steps">
            <li><?php esc_html_e( 'Go to', 'business-messaging-hub' ); ?> 👉 <a href="https://developers.facebook.com/apps" target="_blank">developers.facebook.com/apps</a> → <?php esc_html_e( 'Your App → WhatsApp → Configuration', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Click "Edit" next to Webhook', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Paste the Callback URL from Step 2', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Paste the Verify Token from Step 1', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Click "Verify and Save" — Meta will ping your webhook and confirm it responds correctly', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Click "Manage" → enable the', 'business-messaging-hub' ); ?> <code>messages</code> <?php esc_html_e( 'field ✅', 'business-messaging-hub' ); ?></li>
        </ol>

        <div class="wabmh-meta-summary">
            <div class="wabmh-meta-field">
                <span class="wabmh-meta-label"><?php esc_html_e( 'Callback URL', 'business-messaging-hub' ); ?></span>
                <code class="wabmh-meta-value"><?php echo esc_html( $webhook_url ); ?></code>
            </div>
            <div class="wabmh-meta-field">
                <span class="wabmh-meta-label"><?php esc_html_e( 'Verify Token', 'business-messaging-hub' ); ?></span>
                <code class="wabmh-meta-value" id="wabmh-meta-token-preview"><?php echo esc_html( $verify_token ?: '— not set —' ); ?></code>
            </div>
        </div>
    </div>

    <!-- ===================== Flow ===================== -->
    <div class="wabmh-card">
        <h2>✅ <?php esc_html_e( 'What Happens After Setup', 'business-messaging-hub' ); ?></h2>
        <div class="wabmh-flow">
            <div class="wabmh-flow-step">
                <div class="wabmh-flow-icon">📤</div>
                <div class="wabmh-flow-label"><?php esc_html_e( 'You send message', 'business-messaging-hub' ); ?></div>
                <div class="wabmh-flow-sub">Status: sent</div>
            </div>
            <div class="wabmh-flow-arrow">→</div>
            <div class="wabmh-flow-step">
                <div class="wabmh-flow-icon">📱</div>
                <div class="wabmh-flow-label"><?php esc_html_e( 'Reaches device', 'business-messaging-hub' ); ?></div>
                <div class="wabmh-flow-sub">Status: delivered</div>
            </div>
            <div class="wabmh-flow-arrow">→</div>
            <div class="wabmh-flow-step">
                <div class="wabmh-flow-icon">👀</div>
                <div class="wabmh-flow-label"><?php esc_html_e( 'Recipient reads it', 'business-messaging-hub' ); ?></div>
                <div class="wabmh-flow-sub">Status: read</div>
            </div>
            <div class="wabmh-flow-arrow">→</div>
            <div class="wabmh-flow-step wabmh-flow-step--highlight">
                <div class="wabmh-flow-icon">🔄</div>
                <div class="wabmh-flow-label"><?php esc_html_e( 'WordPress updates', 'business-messaging-hub' ); ?></div>
                <div class="wabmh-flow-sub"><?php esc_html_e( 'Message Log updated', 'business-messaging-hub' ); ?></div>
            </div>
        </div>
        <p style="margin-top:16px;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wabmh-webhook-log' ) ); ?>" class="button">
                📋 <?php esc_html_e( 'View Webhook Log', 'business-messaging-hub' ); ?>
            </a>
        </p>
    </div>

</div>


