<?php
/**
 * Plugin Name: Business Messaging Hub - Chakramani
 * Plugin URI:  https://github.com/chakramani/business-messaging-hub.git
 * Description: Send and receive WhatsApp Business API messages directly from your WordPress dashboard.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * Author:      Chakramani Joshi
 * Author URI:  https://chakramanijoshi.com.np/
 * License:     GPL-2.0+
 * Text Domain: business-messaging-hub
 * Domain Path: /languages
 * WC requires at least: 6.0
 * WC tested up to:   7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WABMH_VERSION', '1.2.0');
define('WABMH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WABMH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WABMH_OPTION_KEY', 'wabmh_settings');
define('WABMH_LOG_OPTION', 'wabmh_message_log');

require_once WABMH_PLUGIN_DIR . 'includes/class-wabmh-api.php';
require_once WABMH_PLUGIN_DIR . 'includes/class-wabmh-settings.php';
require_once WABMH_PLUGIN_DIR . 'includes/class-wabmh-messenger.php';
require_once WABMH_PLUGIN_DIR . 'includes/class-wabmh-log.php';
require_once WABMH_PLUGIN_DIR . 'includes/class-wabmh-webhook-log.php';
require_once WABMH_PLUGIN_DIR . 'includes/class-wabmh-webhook.php';
require_once WABMH_PLUGIN_DIR . 'includes/class-wabmh-bot.php';
require_once WABMH_PLUGIN_DIR . 'includes/class-wabmh-inbox.php';
require_once WABMH_PLUGIN_DIR . 'includes/class-wabmh-widget.php';
require_once WABMH_PLUGIN_DIR . 'admin/class-wabmh-admin.php';

/**
 * Bootstrap the plugin.
 */
function wabmh_init()
{
    WABMH_Settings::instance();
    WABMH_Webhook::instance();
    WABMH_Bot::instance();
    WABMH_Admin::instance();
    WABMH_Widget::instance();

    // Ensure DB tables always exist — runs on every load so new installs
    // and plugin updates don't require a deactivate/reactivate cycle.
    WABMH_Log::create_table();
    WABMH_Webhook_Log::create_table();
    WABMH_Bot::create_table();
}
add_action('plugins_loaded', 'wabmh_init');

/**
 * Activation hook – create DB tables.
 */
function wabmh_activate()
{
    WABMH_Log::create_table();
    WABMH_Webhook_Log::create_table();
    WABMH_Bot::create_table();
    $opts = get_option(WABMH_OPTION_KEY, []);
    if (empty($opts['webhook_verify_token'])) {
        $opts['webhook_verify_token'] = wp_generate_password(24, false);
        update_option(WABMH_OPTION_KEY, $opts);
    }
}
register_activation_hook(__FILE__, 'wabmh_activate');
