<?php
/**
 * Plugin Name: Connect Business with WA
 * Plugin URI:  https://github.com/chakramani/connect-business-with-wa.git
 * Description: Send and receive WhatsApp Business API messages directly from your WordPress dashboard.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * Author:      Chakramani Joshi
 * Author URI:  https://chakramanijoshi.com.np/
 * License:     GPL-2.0+
 * Text Domain: connect-business-with-wa
 * Domain Path: /languages
 * WC requires at least: 6.0
 * WC tested up to:   7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WAI_VERSION',     '1.2.0' );
define( 'WAI_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WAI_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WAI_OPTION_KEY',  'wai_settings' );
define( 'WAI_LOG_OPTION',  'wai_message_log' );

require_once WAI_PLUGIN_DIR . 'includes/class-wai-api.php';
require_once WAI_PLUGIN_DIR . 'includes/class-wai-settings.php';
require_once WAI_PLUGIN_DIR . 'includes/class-wai-messenger.php';
require_once WAI_PLUGIN_DIR . 'includes/class-wai-log.php';
require_once WAI_PLUGIN_DIR . 'includes/class-wai-webhook-log.php';
require_once WAI_PLUGIN_DIR . 'includes/class-wai-webhook.php';
require_once WAI_PLUGIN_DIR . 'includes/class-wai-bot.php';
require_once WAI_PLUGIN_DIR . 'includes/class-wai-inbox.php';
require_once WAI_PLUGIN_DIR . 'includes/class-wai-widget.php';
require_once WAI_PLUGIN_DIR . 'admin/class-wai-admin.php';

/**
 * Bootstrap the plugin.
 */
function wai_init() {
    WAI_Settings::instance();
    WAI_Webhook::instance();
    WAI_Bot::instance();
    WAI_Admin::instance();
    WAI_Widget::instance();

    // Ensure DB tables always exist — runs on every load so new installs
    // and plugin updates don't require a deactivate/reactivate cycle.
    WAI_Log::create_table();
    WAI_Webhook_Log::create_table();
    WAI_Bot::create_table();
}
add_action( 'plugins_loaded', 'wai_init' );

/**
 * Activation hook – create DB tables.
 */
function wai_activate() {
    WAI_Log::create_table();
    WAI_Webhook_Log::create_table();
    WAI_Bot::create_table();
    $opts = get_option( WAI_OPTION_KEY, [] );
    if ( empty( $opts['webhook_verify_token'] ) ) {
        $opts['webhook_verify_token'] = wp_generate_password( 24, false );
        update_option( WAI_OPTION_KEY, $opts );
    }
}
register_activation_hook( __FILE__, 'wai_activate' );
