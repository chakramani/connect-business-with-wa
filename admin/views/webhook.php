<?php if ( ! defined( 'ABSPATH' ) ) exit;

$default_url    = rest_url( 'wai/v1/webhook' );
$custom_url     = WAI_Settings::get( 'custom_webhook_url' );
$webhook_url    = ! empty( $custom_url ) ? $custom_url : $default_url;
$verify_token   = WAI_Settings::get( 'webhook_verify_token' );
$is_local       = ( strpos( $default_url, 'localhost' ) !== false || strpos( $default_url, '.local' ) !== false );

// Build the test URL so the user can verify manually too
$test_url = $webhook_url
    . '?hub.mode=subscribe'
    . '&hub.verify_token=' . rawurlencode( $verify_token )
    . '&hub.challenge=TEST123';
?>
<div class="wrap wai-wrap">

    <div class="wai-page-header">
        <span class="wai-logo">⚡</span>
        <div>
            <h1><?php esc_html_e( 'Webhook Setup', 'connect-business-with-wa' ); ?></h1>
            <p><?php esc_html_e( 'Receive real-time delivery updates (sent → delivered → read) from Meta.', 'connect-business-with-wa' ); ?></p>
        </div>
    </div>

    <?php if ( $is_local ) : ?>
    <div class="wai-notice wai-notice--warn">
        ⚠️ <strong><?php esc_html_e( 'You are on a local server.', 'connect-business-with-wa' ); ?></strong>
        <?php esc_html_e( 'Meta cannot reach localhost directly. Use ngrok to get a public URL, then paste it in the field below.', 'connect-business-with-wa' ); ?>
        <br><br>
        <strong><?php esc_html_e( 'Quick ngrok command:', 'connect-business-with-wa' ); ?></strong>
        <code class="wai-inline-code">ngrok http 10003</code>
        <?php esc_html_e( '(replace 10003 with your Local site port)', 'connect-business-with-wa' ); ?>
    </div>
    <?php endif; ?>

    <!-- ===================== STEP 1 — Verify Token ===================== -->
    <div class="wai-card">
        <h2>🔑 <?php esc_html_e( 'Step 1 — Verify Token', 'connect-business-with-wa' ); ?></h2>
        <p><?php esc_html_e( 'This token proves to Meta that your site owns this webhook. Generate one, copy it, and keep it safe.', 'connect-business-with-wa' ); ?></p>

        <div class="wai-token-box">
            <div class="wai-copy-row">
                <input
                    type="text"
                    id="wai-verify-token-display"
                    value="<?php echo esc_attr( $verify_token ); ?>"
                    class="regular-text wai-token-input"
                    readonly
                    placeholder="<?php esc_attr_e( 'Click Generate to create a token', 'connect-business-with-wa' ); ?>"
                />
                <button type="button" class="button wai-copy-btn" data-target="wai-verify-token-display">
                    📋 <?php esc_html_e( 'Copy', 'connect-business-with-wa' ); ?>
                </button>
                <button type="button" id="wai-regen-token" class="button button-secondary">
                    🔄 <?php esc_html_e( 'Generate New', 'connect-business-with-wa' ); ?>
                </button>
            </div>

            <?php if ( empty( $verify_token ) ) : ?>
                <p class="wai-token-hint wai-token-hint--warn">
                    ⚠️ <?php esc_html_e( 'No token yet. Click "Generate New" to create one.', 'connect-business-with-wa' ); ?>
                </p>
            <?php else : ?>
                <p class="wai-token-hint">
                    ✅ <?php esc_html_e( 'Token is set. Copy it and paste it into Meta\'s "Verify token" field.', 'connect-business-with-wa' ); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Live test inside this card -->
        <div class="wai-token-test-row">
            <button type="button" id="wai-test-webhook" class="button button-primary">
                🧪 <?php esc_html_e( 'Test Token Now', 'connect-business-with-wa' ); ?>
            </button>
            <span id="wai-webhook-test-result" class="wai-inline-result"></span>
        </div>

        <details class="wai-manual-test">
            <summary><?php esc_html_e( 'Manual browser test', 'connect-business-with-wa' ); ?></summary>
            <p><?php esc_html_e( 'Open the URL below in your browser. If it shows "TEST123", your webhook is working correctly.', 'connect-business-with-wa' ); ?></p>
            <code class="wai-url-preview" id="wai-manual-test-url"><?php echo esc_html( $test_url ); ?></code>
            <button type="button" class="button wai-copy-btn" data-target="wai-manual-test-url" style="margin-top:6px;">
                📋 <?php esc_html_e( 'Copy Test URL', 'connect-business-with-wa' ); ?>
            </button>
        </details>
    </div>

    <!-- ===================== STEP 2 — Callback URL ===================== -->
    <div class="wai-card">
        <h2>🔗 <?php esc_html_e( 'Step 2 — Callback URL', 'connect-business-with-wa' ); ?></h2>
        <p><?php esc_html_e( 'This is the URL Meta will send events to. Copy it and paste it into Meta\'s "Callback URL" field.', 'connect-business-with-wa' ); ?></p>

        <div class="wai-copy-row" style="margin-bottom:8px;">
            <input type="text" id="wai-webhook-url-display" value="<?php echo esc_attr( $webhook_url ); ?>" class="large-text" readonly />
            <button type="button" class="button wai-copy-btn" data-target="wai-webhook-url-display">
                📋 <?php esc_html_e( 'Copy', 'connect-business-with-wa' ); ?>
            </button>
        </div>

        <?php if ( ! empty( $custom_url ) ) : ?>
            <p class="description" style="color:var(--wai-green-dark);">✅ <?php esc_html_e( 'Using your custom URL.', 'connect-business-with-wa' ); ?></p>
        <?php else : ?>
            <p class="description"><?php esc_html_e( 'Using auto-detected WordPress REST API URL.', 'connect-business-with-wa' ); ?></p>
        <?php endif; ?>

        <!-- Custom URL override -->
        <form method="post" action="options.php" style="margin-top:16px;">
            <?php settings_fields( 'wai_settings_group' ); ?>
            <input type="hidden" name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[access_token]"         value="<?php echo esc_attr( WAI_Settings::get( 'access_token' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[phone_number_id]"      value="<?php echo esc_attr( WAI_Settings::get( 'phone_number_id' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[app_secret]"           value="<?php echo esc_attr( WAI_Settings::get( 'app_secret' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[business_name]"        value="<?php echo esc_attr( WAI_Settings::get( 'business_name' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[default_country]"      value="<?php echo esc_attr( WAI_Settings::get( 'default_country' ) ); ?>" />
            <input type="hidden" name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[webhook_verify_token]" value="<?php echo esc_attr( $verify_token ); ?>" />

            <details class="wai-custom-url-details">
                <summary><?php esc_html_e( 'Override with a custom URL (e.g. ngrok)', 'connect-business-with-wa' ); ?></summary>
                <div style="margin-top:10px;">
                    <input
                        type="url"
                        id="wai_custom_webhook_url"
                        name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[custom_webhook_url]"
                        value="<?php echo esc_attr( $custom_url ); ?>"
                        class="large-text"
                        placeholder="https://abc123.ngrok-free.app/wp-json/wai/v1/webhook"
                    />
                    <p class="description"><?php esc_html_e( 'Leave blank to use the auto-detected URL.', 'connect-business-with-wa' ); ?></p>
                    <?php submit_button( __( 'Save Custom URL', 'connect-business-with-wa' ), 'secondary', 'submit', false ); ?>
                </div>
            </details>
        </form>
    </div>

    <!-- ===================== STEP 3 — Meta instructions ===================== -->
    <div class="wai-card">
        <h2>🚀 <?php esc_html_e( 'Step 3 — Paste into Meta & Verify', 'connect-business-with-wa' ); ?></h2>
        <ol class="wai-steps">
            <li><?php esc_html_e( 'Go to', 'connect-business-with-wa' ); ?> 👉 <a href="https://developers.facebook.com/apps" target="_blank">developers.facebook.com/apps</a> → <?php esc_html_e( 'Your App → WhatsApp → Configuration', 'connect-business-with-wa' ); ?></li>
            <li><?php esc_html_e( 'Click "Edit" next to Webhook', 'connect-business-with-wa' ); ?></li>
            <li><?php esc_html_e( 'Paste the Callback URL from Step 2', 'connect-business-with-wa' ); ?></li>
            <li><?php esc_html_e( 'Paste the Verify Token from Step 1', 'connect-business-with-wa' ); ?></li>
            <li><?php esc_html_e( 'Click "Verify and Save" — Meta will ping your webhook and confirm it responds correctly', 'connect-business-with-wa' ); ?></li>
            <li><?php esc_html_e( 'Click "Manage" → enable the', 'connect-business-with-wa' ); ?> <code>messages</code> <?php esc_html_e( 'field ✅', 'connect-business-with-wa' ); ?></li>
        </ol>

        <div class="wai-meta-summary">
            <div class="wai-meta-field">
                <span class="wai-meta-label"><?php esc_html_e( 'Callback URL', 'connect-business-with-wa' ); ?></span>
                <code class="wai-meta-value"><?php echo esc_html( $webhook_url ); ?></code>
            </div>
            <div class="wai-meta-field">
                <span class="wai-meta-label"><?php esc_html_e( 'Verify Token', 'connect-business-with-wa' ); ?></span>
                <code class="wai-meta-value" id="wai-meta-token-preview"><?php echo esc_html( $verify_token ?: '— not set —' ); ?></code>
            </div>
        </div>
    </div>

    <!-- ===================== Flow ===================== -->
    <div class="wai-card">
        <h2>✅ <?php esc_html_e( 'What Happens After Setup', 'connect-business-with-wa' ); ?></h2>
        <div class="wai-flow">
            <div class="wai-flow-step">
                <div class="wai-flow-icon">📤</div>
                <div class="wai-flow-label"><?php esc_html_e( 'You send message', 'connect-business-with-wa' ); ?></div>
                <div class="wai-flow-sub">Status: sent</div>
            </div>
            <div class="wai-flow-arrow">→</div>
            <div class="wai-flow-step">
                <div class="wai-flow-icon">📱</div>
                <div class="wai-flow-label"><?php esc_html_e( 'Reaches device', 'connect-business-with-wa' ); ?></div>
                <div class="wai-flow-sub">Status: delivered</div>
            </div>
            <div class="wai-flow-arrow">→</div>
            <div class="wai-flow-step">
                <div class="wai-flow-icon">👀</div>
                <div class="wai-flow-label"><?php esc_html_e( 'Recipient reads it', 'connect-business-with-wa' ); ?></div>
                <div class="wai-flow-sub">Status: read</div>
            </div>
            <div class="wai-flow-arrow">→</div>
            <div class="wai-flow-step wai-flow-step--highlight">
                <div class="wai-flow-icon">🔄</div>
                <div class="wai-flow-label"><?php esc_html_e( 'WordPress updates', 'connect-business-with-wa' ); ?></div>
                <div class="wai-flow-sub"><?php esc_html_e( 'Message Log updated', 'connect-business-with-wa' ); ?></div>
            </div>
        </div>
        <p style="margin-top:16px;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wai-webhook-log' ) ); ?>" class="button">
                📋 <?php esc_html_e( 'View Webhook Log', 'connect-business-with-wa' ); ?>
            </a>
        </p>
    </div>

</div>

<style>
.wai-copy-row           { display:flex; gap:8px; align-items:center; }
.wai-copy-row input     { flex:1; }
.wai-token-box          { background:var(--wai-bg,#f9f9f9); border:1px solid var(--wai-border,#ddd); border-radius:8px; padding:16px; margin-bottom:12px; }
.wai-token-input        { font-family:monospace; font-size:.95rem; letter-spacing:.04em; }
.wai-token-hint         { margin:8px 0 0; font-size:.85rem; color:#2a7a2a; }
.wai-token-hint--warn   { color:#b45309; }
.wai-token-test-row     { display:flex; align-items:center; gap:12px; margin-top:4px; }
.wai-manual-test        { margin-top:16px; border-top:1px solid var(--wai-border,#ddd); padding-top:12px; }
.wai-manual-test summary{ cursor:pointer; color:#555; font-size:.85rem; }
.wai-custom-url-details summary{ cursor:pointer; color:#555; font-size:.85rem; }
.wai-inline-code        { background:#1e1e1e; color:#d4d4d4; padding:4px 10px; border-radius:5px; font-size:.85rem; margin:0 6px; }
.wai-url-preview        { display:block; background:#f0f0f0; padding:10px 14px; border-radius:6px; font-size:.82rem; word-break:break-all; margin:8px 0; border:1px solid var(--wai-border,#ddd); }
.wai-steps              { padding-left:20px; }
.wai-steps li           { margin-bottom:14px; line-height:1.7; }
.wai-steps code         { background:#f0f0f0; padding:2px 6px; border-radius:4px; }
.wai-meta-summary       { background:#f0f7ff; border:1px solid #bcd4f0; border-radius:8px; padding:14px 18px; margin-top:16px; display:flex; flex-direction:column; gap:10px; }
.wai-meta-field         { display:flex; align-items:baseline; gap:12px; flex-wrap:wrap; }
.wai-meta-label         { font-weight:600; min-width:110px; font-size:.85rem; color:#444; }
.wai-meta-value         { background:#fff; border:1px solid #bcd4f0; border-radius:5px; padding:4px 10px; font-size:.85rem; word-break:break-all; }
.wai-flow               { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:10px; }
.wai-flow-step          { background:var(--wai-bg,#f9f9f9); border:1px solid var(--wai-border,#ddd); border-radius:10px; padding:14px 18px; text-align:center; min-width:130px; }
.wai-flow-step--highlight{ background:#d4f5e2; border-color:#a3d9b5; }
.wai-flow-icon          { font-size:1.8rem; margin-bottom:4px; }
.wai-flow-label         { font-weight:600; font-size:.85rem; }
.wai-flow-sub           { font-size:.75rem; color:var(--wai-muted,#888); margin-top:2px; }
.wai-flow-arrow         { font-size:1.4rem; color:var(--wai-green,#2a7a2a); font-weight:700; }
.wai-inline-result.success { color:#2a7a2a; font-weight:600; }
.wai-inline-result.error   { color:#c0392b; font-weight:600; }
</style>

<script>
jQuery(function($){

    // ── Copy to clipboard ──────────────────────────────────────────────
    $(document).on('click', '.wai-copy-btn', function(){
        var id  = $(this).data('target');
        var el  = document.getElementById(id);
        var val = el ? (el.value || el.textContent) : '';
        navigator.clipboard.writeText(val.trim()).then(function(){
            var $b = $('[data-target="' + id + '"]');
            var orig = $b.text();
            $b.text('✅ Copied!');
            setTimeout(function(){ $b.text(orig); }, 2000);
        });
    });

    // ── Regenerate token ───────────────────────────────────────────────
    $('#wai-regen-token').on('click', function(){
        var $btn = $(this);
        if ( ! confirm('<?php echo esc_js( __( 'Generate a new token? You will need to update Meta with the new token before clicking Verify and Save again.', 'connect-business-with-wa' ) ); ?>') ) return;
        $btn.prop('disabled', true).text('⏳');
        $.post(wai.ajax_url, { action: 'wai_regenerate_token', nonce: wai.nonce })
        .done(function(res){
            if ( res.success ) {
                var token = res.data.token;
                $('#wai-verify-token-display').val(token);
                $('#wai-meta-token-preview').text(token);
                // Update manual test URL live
                var base = $('#wai-manual-test-url').text().split('?')[0];
                $('#wai-manual-test-url').text(
                    base + '?hub.mode=subscribe&hub.verify_token=' + encodeURIComponent(token) + '&hub.challenge=TEST123'
                );
                $btn.text('✅ Done! Copy the new token into Meta.');
                setTimeout(function(){ $btn.prop('disabled',false).text('🔄 Generate New'); }, 3000);
                // Clear any old test result
                $('#wai-webhook-test-result').text('').removeClass('success error');
            }
        });
    });

    // ── Test webhook ───────────────────────────────────────────────────
    $('#wai-test-webhook').on('click', function(){
        var $btn = $(this), $r = $('#wai-webhook-test-result');
        $btn.prop('disabled', true).text('⏳ Testing…');
        $r.text('').removeClass('success error');
        $.post(wai.ajax_url, { action:'wai_test_webhook', nonce:wai.nonce })
        .done(function(res){
            if(res.success){
                $r.addClass('success').text('✅ ' + res.data.message + ' — Ready to verify in Meta!');
            } else {
                $r.addClass('error').text('❌ ' + res.data.message);
            }
        })
        .fail(function(){ $r.addClass('error').text('❌ Network error.'); })
        .always(function(){ $btn.prop('disabled',false).text('🧪 Test Token Now'); });
    });

});
</script>
