<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wabmh-wrap">

    <div class="wabmh-page-header">
        <span class="wabmh-logo">📱</span>
        <div>
            <h1><?php esc_html_e( 'WhatsApp Integration — Settings', 'business-messaging-hub' ); ?></h1>
            <p><?php esc_html_e( 'Connect your WhatsApp Business Cloud API credentials below.', 'business-messaging-hub' ); ?></p>
        </div>
    </div>

    <?php settings_errors( 'wabmh_settings_group' ); ?>

    <!-- How-to notice -->
    <div class="wabmh-notice wabmh-notice--info">
        <strong><?php esc_html_e( 'How to get your credentials:', 'business-messaging-hub' ); ?></strong>
        <ol>
            <li><?php esc_html_e( 'Go to Meta for Developers → Your App → WhatsApp → API Setup.', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Copy the Temporary (or Permanent) Access Token.', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Copy the Phone Number ID shown on that same page.', 'business-messaging-hub' ); ?></li>
            <li><?php esc_html_e( 'Paste them below and click Save, then Test Connection.', 'business-messaging-hub' ); ?></li>
        </ol>
    </div>

    <div class="wabmh-card">
        <form method="post" action="options.php">
            <?php settings_fields( 'wabmh_settings_group' ); ?>

            <table class="wabmh-form-table">

                <tr>
                    <th>
                        <label for="wabmh_access_token">
                            <?php esc_html_e( 'Access Token', 'business-messaging-hub' ); ?>
                            <span class="wabmh-required">*</span>
                        </label>
                    </th>
                    <td>
                        <div class="wabmh-input-wrap wabmh-toggle-wrap">
                            <input
                                type="password"
                                id="wabmh_access_token"
                                name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[access_token]"
                                value="<?php echo esc_attr( WABMH_Settings::get( 'access_token' ) ); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            />
                            <button type="button" class="wabmh-toggle-visibility button" data-target="wabmh_access_token">
                                👁
                            </button>
                        </div>
                        <p class="description"><?php esc_html_e( 'Your permanent or temporary WhatsApp Cloud API Bearer token.', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="wabmh_app_secret">
                            <?php esc_html_e( 'App Secret', 'business-messaging-hub' ); ?>
                            <span class="wabmh-required">*</span>
                        </label>
                    </th>
                    <td>
                        <div class="wabmh-input-wrap wabmh-toggle-wrap">
                            <input
                                type="password"
                                id="wabmh_app_secret"
                                name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[app_secret]"
                                value="<?php echo esc_attr( WABMH_Settings::get( 'app_secret' ) ); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            />
                            <button type="button" class="wabmh-toggle-visibility button" data-target="wabmh_app_secret">
                                👁
                            </button>
                        </div>
                        <p class="description"><?php esc_html_e( 'Found in Meta App Dashboard → App Settings → Basic → App Secret.', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="wabmh_phone_number_id">
                            <?php esc_html_e( 'Phone Number ID', 'business-messaging-hub' ); ?>
                            <span class="wabmh-required">*</span>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wabmh_phone_number_id"
                            name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[phone_number_id]"
                            value="<?php echo esc_attr( WABMH_Settings::get( 'phone_number_id' ) ); ?>"
                            class="regular-text"
                            placeholder="123456789012345"
                        />
                        <p class="description"><?php esc_html_e( 'The numeric Phone Number ID from Meta Developer Console.', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="wabmh_business_name">
                            <?php esc_html_e( 'Business / Display Name', 'business-messaging-hub' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wabmh_business_name"
                            name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[business_name]"
                            value="<?php echo esc_attr( WABMH_Settings::get( 'business_name' ) ); ?>"
                            class="regular-text"
                            placeholder="<?php esc_attr_e( 'My Business', 'business-messaging-hub' ); ?>"
                        />
                        <p class="description"><?php esc_html_e( 'Optional label for internal reference only.', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="wabmh_default_country">
                            <?php esc_html_e( 'Default Country Code', 'business-messaging-hub' ); ?>
                        </label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wabmh_default_country"
                            name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[default_country]"
                            value="<?php echo esc_attr( WABMH_Settings::get( 'default_country' ) ); ?>"
                            class="small-text"
                            placeholder="+977"
                            maxlength="5"
                        />
                        <p class="description"><?php esc_html_e( 'Prepended when recipient number has no country code (e.g. +977 for Nepal).', 'business-messaging-hub' ); ?></p>
                    </td>
                </tr>

            </table>

            <div class="wabmh-form-actions">
                <?php submit_button( __( 'Save Settings', 'business-messaging-hub' ), 'primary wabmh-btn-save', 'submit', false ); ?>
                <button type="button" id="wabmh-test-connection" class="button wabmh-btn-test">
                    <?php esc_html_e( 'Test Connection', 'business-messaging-hub' ); ?>
                </button>
                <span id="wabmh-test-result" class="wabmh-inline-result"></span>
            </div>

        </form>
    </div>

    <!-- Connection status card -->
    <div class="wabmh-card wabmh-card--status">
        <h3><?php esc_html_e( 'Connection Status', 'business-messaging-hub' ); ?></h3>
        <?php if ( WABMH_Settings::is_configured() ) : ?>
            <div class="wabmh-status wabmh-status--ok">
                <span class="wabmh-status__dot"></span>
                <?php esc_html_e( 'Credentials saved. Click "Test Connection" to verify.', 'business-messaging-hub' ); ?>
            </div>
        <?php else : ?>
            <div class="wabmh-status wabmh-status--warn">
                <span class="wabmh-status__dot"></span>
                <?php esc_html_e( 'No credentials saved yet.', 'business-messaging-hub' ); ?>
            </div>
        <?php endif; ?>
    </div>

</div>


<!-- ===================== Phone Number Status ===================== -->
<div class="wabmh-card" style="margin-top:20px;">
    <h3>📊 <?php esc_html_e( 'Phone Number Status', 'business-messaging-hub' ); ?></h3>
    <p><?php esc_html_e( 'Check the current registration status of your phone number directly from Meta.', 'business-messaging-hub' ); ?></p>

    <button type="button" id="wabmh-check-status-btn" class="button button-primary">
        🔍 <?php esc_html_e( 'Check Registration Status', 'business-messaging-hub' ); ?>
    </button>

    <div id="wabmh-phone-status-result" style="margin-top:16px;display:none;">
        <table class="widefat wabmh-status-table" style="max-width:600px;">
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e( 'Registration', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wabmh-ps-registered">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Phone Number', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wabmh-ps-phone">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Display Name', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wabmh-ps-name">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Quality Rating', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wabmh-ps-quality">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Platform', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wabmh-ps-platform">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Throughput', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wabmh-ps-throughput">—</td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e( 'Account Mode', 'business-messaging-hub' ); ?></strong></td>
                    <td id="wabmh-ps-mode">—</td>
                </tr>
            </tbody>
        </table>
        <details style="margin-top:10px;">
            <summary style="cursor:pointer;font-size:.85rem;color:#555;"><?php esc_html_e( 'View raw response', 'business-messaging-hub' ); ?></summary>
            <pre id="wabmh-ps-raw" style="background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:6px;font-size:.8rem;overflow:auto;max-height:300px;margin-top:8px;"></pre>
        </details>
    </div>
    <p id="wabmh-check-status-error" class="wabmh-inline-result error" style="margin-top:10px;"></p>
</div>

<!-- ===================== Register Phone Number ===================== -->
<div class="wabmh-card" style="margin-top:20px;">
    <h3>📱 <?php esc_html_e( 'Register Phone Number', 'business-messaging-hub' ); ?></h3>
    <p><?php esc_html_e( 'Before you can send messages with your real business number, you must register it with Meta\'s Cloud API. This is a one-time step.', 'business-messaging-hub' ); ?></p>

    <div class="wabmh-register-box">
        <label for="wabmh-register-pin"><strong><?php esc_html_e( 'Two-Step Verification PIN', 'business-messaging-hub' ); ?></strong></label>
        <p class="description" style="margin-bottom:8px;">
            <?php esc_html_e( 'Enter a 6-digit PIN. If your number already has two-step verification enabled in WhatsApp, use that PIN. Otherwise choose any 6 digits — this will become your PIN.', 'business-messaging-hub' ); ?>
        </p>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input
                type="text"
                id="wabmh-register-pin"
                maxlength="6"
                pattern="\d{6}"
                placeholder="e.g. 123456"
                class="regular-text"
                style="font-size:1.1rem;letter-spacing:.2em;width:140px;"
            />
            <button type="button" id="wabmh-register-btn" class="button button-primary">
                ✅ <?php esc_html_e( 'Register Number', 'business-messaging-hub' ); ?>
            </button>
            <span id="wabmh-register-result" class="wabmh-inline-result"></span>
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
            <button type="button" id="wabmh-deregister-btn" class="button button-secondary" style="color:#c0392b;border-color:#c0392b;">
                ⛔ <?php esc_html_e( 'Deregister Number', 'business-messaging-hub' ); ?>
            </button>
            <span id="wabmh-deregister-result" class="wabmh-inline-result" style="margin-left:10px;"></span>
        </div>
    </details>
</div>

<!-- ===================== Frontend Chat Widget ===================== -->
<div class="wabmh-card" style="margin-top:20px;">
    <h3>💬 <?php esc_html_e( 'Frontend Chat Widget', 'business-messaging-hub' ); ?></h3>
    <p><?php esc_html_e( 'Show a WhatsApp chat popup button on your website so visitors can message you directly.', 'business-messaging-hub' ); ?></p>

    <form method="post" action="options.php">
        <?php settings_fields( 'wabmh_settings_group' ); ?>

        <?php
        // Preserve ALL existing settings as hidden fields so nothing gets wiped
        $all_settings = WABMH_Settings::get();
        $widget_keys  = [ 'widget_enabled', 'widget_welcome_msg', 'widget_auto_reply', 'widget_require_name', 'widget_email_notify' ];
        foreach ( $all_settings as $key => $val ) {
            if ( in_array( $key, $widget_keys, true ) ) continue; // rendered below
            echo '<input type="hidden" name="' . esc_attr( WABMH_OPTION_KEY ) . '[' . esc_attr( $key ) . ']" value="' . esc_attr( $val ) . '" />';
        }
        ?>

        <table class="form-table" style="max-width:600px;">
            <tr>
                <th><?php esc_html_e( 'Enable Widget', 'business-messaging-hub' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[widget_enabled]" value="1"
                            <?php checked( WABMH_Settings::get( 'widget_enabled' ), '1' ); ?> />
                        <?php esc_html_e( 'Show chat widget on all frontend pages', 'business-messaging-hub' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Your WhatsApp Number', 'business-messaging-hub' ); ?></th>
                <td>
                    <input type="text"
                        name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[widget_display_phone]"
                        value="<?php echo esc_attr( WABMH_Settings::get( 'widget_display_phone' ) ); ?>"
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
                        name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[widget_welcome_msg]"
                        value="<?php echo esc_attr( WABMH_Settings::get( 'widget_welcome_msg' ) ?: 'Hi there! 👋 How can we help you today?' ); ?>"
                        class="large-text"
                    />
                    <p class="description"><?php esc_html_e( 'Shown to visitors when they open the chat.', 'business-messaging-hub' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Default WhatsApp Message', 'business-messaging-hub' ); ?></th>
                <td>
                    <input type="text"
                        name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[widget_default_msg]"
                        value="<?php echo esc_attr( WABMH_Settings::get( 'widget_default_msg' ) ?: 'Hi, I need help' ); ?>"
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
                        name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[widget_auto_reply]"
                        value="<?php echo esc_attr( WABMH_Settings::get( 'widget_auto_reply' ) ?: '' ); ?>"
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
                        <input type="checkbox" name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[widget_require_name]" value="1"
                            <?php checked( WABMH_Settings::get( 'widget_require_name' ), '1' ); ?> />
                        <?php esc_html_e( 'Ask visitor for their name before starting chat', 'business-messaging-hub' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Email Notification', 'business-messaging-hub' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( WABMH_OPTION_KEY ); ?>[widget_email_notify]" value="1"
                            <?php checked( WABMH_Settings::get( 'widget_email_notify' ), '1' ); ?> />
                        <?php esc_html_e( 'Send email to admin when a new message arrives', 'business-messaging-hub' ); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Widget Settings', 'business-messaging-hub' ), 'primary', 'submit', false ); ?>
    </form>
</div>
