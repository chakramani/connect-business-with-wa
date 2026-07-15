<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wai-wrap">

    <div class="wai-page-header">
        <span class="wai-logo">📱</span>
        <div>
            <h1><?php esc_html_e( 'WhatsApp Integration — Settings', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'Connect your WhatsApp Business Cloud API credentials below.', 'business-messaging-hub' ); ?></p>
        </div>
    </div>

    <?php settings_errors( 'wai_settings_group' ); ?>

    <!-- How-to notice -->
    <div class="wai-notice wai-notice--info">
        <strong><?php esc_html_e( 'How to get your credentials:', 'business-messaging-hub' ); ?></strong>
        <ol>
            <li><?php esc_html_e( 'Go to Meta for Developers → Your App → WhatsApp → API Setup.', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Copy the Temporary (or Permanent) Access Token.', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Copy the Phone Number ID shown on that same page.', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Paste them below and click Save, then Test Connection.', 'business-messaging-hub' ); ?></li>
        </ol>
    </div>

    <div class="wai-card">
        <form method="post" action="options.php">
            <?php settings_fields( 'wai_settings_group' ); ?>

            <table class="wai-form-table">

                <tr>
                    <th>
                        <label for="wai_access_token">
                            <?php esc_html_e( 'Access Token', 'business-messaging-hub' ); ?>
                            <span class="wai-required">*</span>
                        </label>
                    </th>
                    <td>
                        <div class="wai-input-wrap wai-toggle-wrap">
                            <input
                                type="password"
                                id="wai_access_token"
                                name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[access_token]"
                                value="<?php echo esc_attr( WAI_Settings::get( 'access_token' ) ); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            />
                            <button type="button" class="wai-toggle-visibility button" data-target="wai_access_token">
                                👁
                            </button>
                        </div>
                        <p class="description"><?php esc_html_e( 'Your permanent or temporary WhatsApp Cloud API Bearer token.', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="wai_app_secret">
                            <?php esc_html_e( 'App Secret', 'business-messaging-hub' ); ?>
                            <span class="wai-required">*</span>
                        </label>
                    </th>
                    <td>
                        <div class="wai-input-wrap wai-toggle-wrap">
                            <input
                                type="password"
                                id="wai_app_secret"
                                name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[app_secret]"
                                value="<?php echo esc_attr( WAI_Settings::get( 'app_secret' ) ); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            />
                            <button type="button" class="wai-toggle-visibility button" data-target="wai_app_secret">
                                👁
                            </button>
                        </div>
                        <p class="description"><?php esc_html_e( 'Found in Meta App Dashboard → App Settings → Basic → App Secret.', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

                <tr>
                            <?php esc_html_e( 'Phone Number ID', 'business-messaging-hub' ); ?>
                            <span class="wai-required">*</span>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wai_phone_number_id"
                            name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[phone_number_id]"
                            value="<?php echo esc_attr( WAI_Settings::get( 'phone_number_id' ) ); ?>"
                            class="regular-text"
                            placeholder="123456789012345"
                        />
                        <p class="description"><?php esc_html_e( 'The numeric Phone Number ID from Meta Developer Console.', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="wai_business_name">
                            <?php esc_html_e( 'Business / Display Name', 'business-messaging-hub' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wai_business_name"
                            name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[business_name]"
                            value="<?php echo esc_attr( WAI_Settings::get( 'business_name' ) ); ?>"
                            class="regular-text"
                            placeholder="<?php esc_attr_e( 'My Business', 'business-messaging-hub' ); ?>"
                        />
                        <p class="description"><?php esc_html_e( 'Optional label for internal reference only.', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="wai_default_country">
                            <?php esc_html_e( 'Default Country Code', 'business-messaging-hub' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wai_default_country"
                            name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[default_country]"
                            value="<?php echo esc_attr( WAI_Settings::get( 'default_country' ) ); ?>"
                            class="small-text"
                            placeholder="+977"
                            maxlength="5"
                        />
                        <p class="description"><?php esc_html_e( 'Prepended when recipient number has no country code (e.g. +977 for Nepal).', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

            </table>

            <div class="wai-form-actions">
                <?php submit_button( __( 'Save Settings', 'business-messaging-hub' ), 'primary wai-btn-save', 'submit', false ); ?>
                <button type="button" id="wai-test-connection" class="button wai-btn-test">
                    <?php esc_html_e( 'Test Connection', 'business-messaging-hub' ); ?>
                </button>
                <span id="wai-test-result" class="wai-inline-result"></span>
            </div>

        </form>
    </div>

    <!-- Connection status card -->
    <div class="wai-card wai-card--status">
        <h3><?php esc_html_e( 'Connection Status', 'business-messaging-hub' ); ?></h3>
        <?php if ( WAI_Settings::is_configured() ) : ?>
            <div class="wai-status wai-status--ok">
                <span class="wai-status__dot"></span>
                <?php esc_html_e( 'Credentials saved. Click "Test Connection" to verify.', 'business-messaging-hub' ); ?>
            </div>
        <?php else : ?>
            <div class="wai-status wai-status--warn">
                <span class="wai-status__dot"></span>
                <?php esc_html_e( 'No credentials saved yet.', 'business-messaging-hub' ); ?>
            </div>
        <?php endif; ?>
    </div>

</div>


<!-- ===================== Phone Number Status ===================== -->
<div class="wai-card" style="margin-top:20px;">
    <h3>📊 <?php esc_html_e( 'Phone Number Status', 'business-messaging-hub' ); ?></h3>
    <p><?php esc_html_e( 'Check the current registration status of your phone number directly from Meta.', 'business-messaging-hub' ); ?></p>

    <button type="button" id="wai-check-status-btn" class="button button-primary">
        🔍 <?php esc_html_e( 'Check Registration Status', 'business-messaging-hub' ); ?>
    </button>

    <div id="wai-phone-status-result" style="margin-top:16px;display:none;">
        <table class="widefat wai-status-table" style="max-width:600px;">
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e( 'Registration', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wai-ps-registered">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Phone Number', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wai-ps-phone">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Display Name', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wai-ps-name">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Quality Rating', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wai-ps-quality">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Platform', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wai-ps-platform">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Throughput', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wai-ps-throughput">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Account Mode', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wai-ps-mode">—</td>
                </tr>
            </tbody>
        </table>
        <details style="margin-top:10px;">
            <summary style="cursor:pointer;font-size:.85rem;color:#555;"><?php esc_html_e( 'View raw response', 'business-messaging-hub' ); ?></summary>
            <pre id="wai-ps-raw" style="background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:6px;font-size:.8rem;overflow:auto;max-height:300px;margin-top:8px;"></pre>
        </details>
    </div>
    <p id="wai-check-status-error" class="wai-inline-result error" style="margin-top:10px;"></p>
</div>

<script>
jQuery(function($){
    var qualityLabel = { GREEN: '🟢 High', YELLOW: '🟡 Medium', RED: '🔴 Low', UNKNOWN: '⚪ Unknown' };
    var modeLabel    = { SANDBOX: '🧪 Sandbox (Test)', LIVE: '🚀 Live (Production)' };

    $('#wai-check-status-btn').on('click', function(){
        var $btn = $(this), $r = $('#wai-phone-status-result'), $err = $('#wai-check-status-error');
        $btn.prop('disabled', true).text('⏳ Checking…');
        $r.hide();
        $err.text('');

        $.post(wai.ajax_url, { action: 'wai_phone_status', nonce: wai.nonce })
        .done(function(res){
            if ( res.success ) {
                var d = res.data;
                var regText = d.registered
                    ? '<span style="color:#2a7a2a;font-weight:700;">✅ Registered</span>'
                    : '<span style="color:#c0392b;font-weight:700;">❌ Not Registered (status: ' + d.status + ')</span>';
                $('#wai-ps-registered').html(regText);
                $('#wai-ps-phone').text(d.phone);
                $('#wai-ps-name').text(d.name);
                $('#wai-ps-quality').html(qualityLabel[d.quality] || d.quality);
                $('#wai-ps-platform').text(d.platform);
                $('#wai-ps-throughput').text(d.throughput);
                $('#wai-ps-mode').html(modeLabel[d.account_mode] || d.account_mode);
                $('#wai-ps-raw').text(JSON.stringify(d.raw, null, 2));
                $r.show();
            } else {
                $err.text('❌ ' + res.data.message);
            }
        })
        .fail(function(){ $err.text('❌ Network error.'); })
        .always(function(){ $btn.prop('disabled', false).text('🔍 Check Registration Status'); });
    });
});
</script>

<!-- ===================== Register Phone Number ===================== -->
<div class="wai-card" style="margin-top:20px;">
    <h3>📱 <?php esc_html_e( 'Register Phone Number', 'business-messaging-hub' ); ?></h3>
    <p><?php esc_html_e( 'Before you can send messages with your real business number, you must register it with Meta\'s Cloud API. This is a one-time step.', 'business-messaging-hub' ); ?></p>

    <div class="wai-register-box">
        <label for="wai-register-pin"><strong><?php esc_html_e( 'Two-Step Verification PIN', 'business-messaging-hub' ); ?></strong></label>
        <p class="description" style="margin-bottom:8px;">
            <?php esc_html_e( 'Enter a 6-digit PIN. If your number already has two-step verification enabled in WhatsApp, use that PIN. Otherwise choose any 6 digits — this will become your PIN.', 'business-messaging-hub' ); ?>
        </p>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input
                type="text"
                id="wai-register-pin"
                maxlength="6"
                pattern="\d{6}"
                placeholder="e.g. 123456"
                class="regular-text"
                style="font-size:1.1rem;letter-spacing:.2em;width:140px;"
            />
            <button type="button" id="wai-register-btn" class="button button-primary">
                ✅ <?php esc_html_e( 'Register Number', 'business-messaging-hub' ); ?>
            </button>
            <span id="wai-register-result" class="wai-inline-result"></span>
        </div>
        <p class="description" style="margin-top:10px;color:#b45309;">
            ⚠️ <?php esc_html_e( 'Save your PIN somewhere safe. You\'ll need it if you re-register. Requests are limited to 10 per 72 hours.', 'business-messaging-hub' ); ?>
        </p>
    </div>

    <hr style="margin:20px 0;">

    <details>
        <summary style="cursor:pointer;color:#c0392b;font-size:.85rem;">
            ⛔ <?php esc_html_e( 'Deregister phone number (advanced)', 'business-messaging-hub' ); ?>
        </summary>
        <div style="margin-top:12px;">
            <p class="description"><?php esc_html_e( 'Deregistering makes your number unusable with Cloud API until re-registered. Only do this if you need to migrate or reset.', 'business-messaging-hub' ); ?></p>
            <button type="button" id="wai-deregister-btn" class="button button-secondary" style="color:#c0392b;border-color:#c0392b;">
                ⛔ <?php esc_html_e( 'Deregister Number', 'business-messaging-hub' ); ?>
            </button>
            <span id="wai-deregister-result" class="wai-inline-result" style="margin-left:10px;"></span>
        </div>
    </details>
</div>

<style>
.wai-register-box { background:#f9f9f9; border:1px solid #ddd; border-radius:8px; padding:16px; margin-top:10px; }
.wai-inline-result.success { color:#2a7a2a; font-weight:600; }
.wai-inline-result.error   { color:#c0392b; font-weight:600; }
</style>

<script>
jQuery(function($){

    // Register
    $('#wai-register-btn').on('click', function(){
        var pin = $('#wai-register-pin').val().trim();
        var $r  = $('#wai-register-result');
        if ( !/^\d{6}$/.test(pin) ) {
            $r.removeClass('success').addClass('error').text('❌ PIN must be exactly 6 digits.');
            return;
        }
        if ( ! confirm('Register this phone number with PIN: ' + pin + '?\n\nSave this PIN — you will need it if you re-register.') ) return;
        var $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Registering…');
        $r.text('').removeClass('success error');
        $.post(wai.ajax_url, { action: 'wai_register_phone', nonce: wai.nonce, pin: pin })
        .done(function(res){
            if (res.success) {
                $r.addClass('success').text(res.data.message);
            } else {
                $r.addClass('error').text('❌ ' + res.data.message);
            }
        })
        .fail(function(){ $r.addClass('error').text('❌ Network error.'); })
        .always(function(){ $btn.prop('disabled', false).text('✅ Register Number'); });
    });

    // Only allow digits in PIN field
    $('#wai-register-pin').on('input', function(){
        this.value = this.value.replace(/\D/g, '').slice(0,6);
    });

    // Deregister
    $('#wai-deregister-btn').on('click', function(){
        if ( ! confirm('Are you sure you want to deregister this phone number? It will stop working with Cloud API until re-registered.') ) return;
        var $btn = $(this), $r = $('#wai-deregister-result');
        $btn.prop('disabled', true).text('⏳ Deregistering…');
        $r.text('').removeClass('success error');
        $.post(wai.ajax_url, { action: 'wai_deregister_phone', nonce: wai.nonce })
        .done(function(res){
            if (res.success) {
                $r.addClass('success').text(res.data.message);
            } else {
                $r.addClass('error').text('❌ ' + res.data.message);
            }
        })
        .fail(function(){ $r.addClass('error').text('❌ Network error.'); })
        .always(function(){ $btn.prop('disabled', false).text('⛔ Deregister Number'); });
    });

});
</script>
<!-- ===================== Frontend Chat Widget ===================== -->
<div class="wai-card" style="margin-top:20px;">
    <h3>💬 <?php esc_html_e( 'Frontend Chat Widget', 'business-messaging-hub' ); ?></h3>
    <p><?php esc_html_e( 'Show a WhatsApp chat popup button on your website so visitors can message you directly.', 'business-messaging-hub' ); ?></p>

    <form method="post" action="options.php">
        <?php settings_fields( 'wai_settings_group' ); ?>

        <?php
        // Preserve ALL existing settings as hidden fields so nothing gets wiped
        $all_settings = WAI_Settings::get();
        $widget_keys  = [ 'widget_enabled', 'widget_welcome_msg', 'widget_auto_reply', 'widget_require_name', 'widget_email_notify' ];
        foreach ( $all_settings as $key => $val ) {
            if ( in_array( $key, $widget_keys, true ) ) continue; // rendered below
            echo '<input type="hidden" name="' . esc_attr( WAI_OPTION_KEY ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" />';
        }
        ?>

        <table class="form-table" style="max-width:600px;">
            <tr>
                <th><?php esc_html_e( 'Enable Widget', 'business-messaging-hub' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[widget_enabled]" value="1"
                            <?php checked( WAI_Settings::get( 'widget_enabled' ), '1' ); ?> />
                        <?php esc_html_e( 'Show chat widget on all frontend pages', 'business-messaging-hub' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Your WhatsApp Number', 'business-messaging-hub' ); ?></th>
                <td>
                    <input type="text"
                        name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[widget_display_phone]"
                        value="<?php echo esc_attr( WAI_Settings::get( 'widget_display_phone' ) ); ?>"
                        class="regular-text"
                        placeholder="e.g. 9779803182844"
                    />
                    <p class="description"><?php esc_html_e( 'Your business WhatsApp number. Visitors will be directed to message this number. Include country code, no + sign (e.g. 9779803182844).', 'business-messaging-hub' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Welcome Message', 'business-messaging-hub' ); ?></th>
                <td>
                    <input type="text"
                        name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[widget_welcome_msg]"
                        value="<?php echo esc_attr( WAI_Settings::get( 'widget_welcome_msg' ) ?: 'Hi there! 👋 How can we help you today?' ); ?>"
                        class="large-text"
                    />
                    <p class="description"><?php esc_html_e( 'Shown to visitors when they open the chat.', 'business-messaging-hub' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Default WhatsApp Message', 'business-messaging-hub' ); ?></th>
                <td>
                    <input type="text"
                        name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[widget_default_msg]"
                        value="<?php echo esc_attr( WAI_Settings::get( 'widget_default_msg' ) ?: 'Hi, I need help' ); ?>"
                        class="large-text"
                        placeholder="Hi, I need help"
                    />
                    <p class="description"><?php esc_html_e( 'Pre-filled message sent when visitor clicks "Start Chat on WhatsApp".', 'business-messaging-hub' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Auto Reply', 'business-messaging-hub' ); ?></th>
                <td>
                    <input type="text"
                        name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[widget_auto_reply]"
                        value="<?php echo esc_attr( WAI_Settings::get( 'widget_auto_reply' ) ?: '' ); ?>"
                        class="large-text"
                        placeholder="e.g. Thanks for reaching out! We'll reply shortly."
                    />
                    <p class="description"><?php esc_html_e( 'Automatically sent to visitor/\'s WhatsApp when they start chat. Leave blank to disable.', 'business-messaging-hub' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Require Name', 'business-messaging-hub' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[widget_require_name]" value="1"
                            <?php checked( WAI_Settings::get( 'widget_require_name' ), '1' ); ?> />
                        <?php esc_html_e( 'Ask visitor for their name before starting chat', 'business-messaging-hub' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Email Notification', 'business-messaging-hub' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( WAI_OPTION_KEY ); ?>[widget_email_notify]" value="1"
                            <?php checked( WAI_Settings::get( 'widget_email_notify' ), '1' ); ?> />
                        <?php esc_html_e( 'Send email to admin when a new message arrives', 'business-messaging-hub' ); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Widget Settings', 'business-messaging-hub' ), 'primary', 'submit', false ); ?>
    </form>
</div>
