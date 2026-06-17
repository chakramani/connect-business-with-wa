<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Manages plugin settings (API credentials).
 */
class WAI_Settings {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_init', [ $this, 'ensure_verify_token' ] );
    }

    /**
     * Auto-generate a verify token if one doesn't exist yet.
     * If WAI_WEBHOOK_VERIFY_TOKEN is defined in wp-config.php, that value
     * is always used and kept in sync with the DB.
     */
    public function ensure_verify_token() {
        $opts = get_option( WAI_OPTION_KEY, [] );

        // If a token is defined as a constant (e.g. in wp-config.php), always use it.
        if ( defined( 'WAI_WEBHOOK_VERIFY_TOKEN' ) && WAI_WEBHOOK_VERIFY_TOKEN !== '' ) {
            if ( ( $opts['webhook_verify_token'] ?? '' ) !== WAI_WEBHOOK_VERIFY_TOKEN ) {
                $opts['webhook_verify_token'] = WAI_WEBHOOK_VERIFY_TOKEN;
                update_option( WAI_OPTION_KEY, $opts );
            }
            return;
        }

        if ( empty( $opts['webhook_verify_token'] ) ) {
            $opts['webhook_verify_token'] = wp_generate_password( 24, false );
            update_option( WAI_OPTION_KEY, $opts );
        }
    }

    /**
     * Regenerate the verify token and save it.
     * Has no effect if WAI_WEBHOOK_VERIFY_TOKEN constant is defined.
     */
    public static function regenerate_verify_token() {
        if ( defined( 'WAI_WEBHOOK_VERIFY_TOKEN' ) && WAI_WEBHOOK_VERIFY_TOKEN !== '' ) {
            return WAI_WEBHOOK_VERIFY_TOKEN;
        }
        $opts = get_option( WAI_OPTION_KEY, [] );
        $opts['webhook_verify_token'] = wp_generate_password( 24, false );
        update_option( WAI_OPTION_KEY, $opts );
        return $opts['webhook_verify_token'];
    }

    public function register_settings() {
        register_setting(
            'wai_settings_group',
            WAI_OPTION_KEY,
            [ 'sanitize_callback' => [ $this, 'sanitize_settings' ] ]
        );
    }

    public function sanitize_settings( $input ) {
        $clean = [];

        // ── Core API settings ──
        $clean['access_token']         = isset( $input['access_token'] )         ? sanitize_text_field( $input['access_token'] )         : '';
        $clean['phone_number_id']      = isset( $input['phone_number_id'] )      ? sanitize_text_field( $input['phone_number_id'] )      : '';
        $clean['app_secret']           = isset( $input['app_secret'] )           ? sanitize_text_field( $input['app_secret'] )           : '';
        $clean['business_name']        = isset( $input['business_name'] )        ? sanitize_text_field( $input['business_name'] )        : '';
        $clean['default_country']      = isset( $input['default_country'] )      ? sanitize_text_field( $input['default_country'] )      : '';
        $clean['webhook_verify_token'] = isset( $input['webhook_verify_token'] ) ? sanitize_text_field( $input['webhook_verify_token'] ) : wp_generate_password( 24, false );
        $clean['custom_webhook_url']   = isset( $input['custom_webhook_url'] )   ? esc_url_raw( $input['custom_webhook_url'] )           : '';

        // ── Frontend chat widget settings ──
        $clean['widget_display_phone'] = isset( $input['widget_display_phone'] ) ? preg_replace( '/[^0-9+]/', '', $input['widget_display_phone'] ) : '';
        $clean['widget_enabled']      = ! empty( $input['widget_enabled'] )      ? '1' : '';
        $clean['widget_welcome_msg']  = isset( $input['widget_welcome_msg'] )    ? sanitize_text_field( $input['widget_welcome_msg'] )   : '';
        $clean['widget_auto_reply']   = isset( $input['widget_auto_reply'] )     ? sanitize_text_field( $input['widget_auto_reply'] )    : '';
        $clean['widget_require_name'] = ! empty( $input['widget_require_name'] ) ? '1' : '';
        $clean['widget_email_notify']   = ! empty( $input['widget_email_notify'] )   ? '1' : '';
        $clean['widget_display_phone']  = isset( $input['widget_display_phone'] )   ? preg_replace( '/[^0-9+]/', '', $input['widget_display_phone'] ) : '';
        $clean['widget_default_msg']    = isset( $input['widget_default_msg'] )     ? sanitize_text_field( $input['widget_default_msg'] ) : 'Hi, I need help';

        return $clean;
    }

    // ------------------------------------------------------------------ //
    //  Getters
    // ------------------------------------------------------------------ //

    public static function get( $key = null ) {
        $options = get_option( WAI_OPTION_KEY, [] );
        if ( $key ) {
            return $options[ $key ] ?? '';
        }
        return $options;
    }

    public static function is_configured() {
        $opts = self::get();
        return ! empty( $opts['access_token'] ) && ! empty( $opts['phone_number_id'] );
    }

    /**
     * Returns a ready-to-use API instance (or null if not configured).
     */
    public static function api() {
        if ( ! self::is_configured() ) {
            return null;
        }
        return new WAI_API( self::get( 'access_token' ), self::get( 'phone_number_id' ), self::get( 'app_secret' ) );
    }
}
