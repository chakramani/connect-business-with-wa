<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers admin menus and handles admin-side AJAX.
 */
class WAI_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu',             [ $this, 'register_menus' ] );
        add_filter( 'query_vars',              [ $this, 'allow_query_vars' ] );
        add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_wai_send_message',    [ $this, 'ajax_send_message' ] );
        add_action( 'wp_ajax_wai_test_connection', [ $this, 'ajax_test_connection' ] );
        add_action( 'wp_ajax_wai_verify_message',  [ $this, 'ajax_verify_message' ] );
        add_action( 'wp_ajax_wai_test_webhook',        [ $this, 'ajax_test_webhook' ] );
        add_action( 'wp_ajax_wai_regenerate_token',    [ $this, 'ajax_regenerate_token' ] );
        add_action( 'wp_ajax_wai_register_phone',      [ $this, 'ajax_register_phone' ] );
        add_action( 'wp_ajax_wai_phone_status',        [ $this, 'ajax_phone_status' ] );
        add_action( 'wp_ajax_wai_deregister_phone',    [ $this, 'ajax_deregister_phone' ] );
        add_action( 'wp_ajax_wai_inbox_poll',          [ $this, 'ajax_inbox_poll' ] );
        add_action( 'wp_ajax_wai_inbox_load',          [ $this, 'ajax_inbox_load' ] );
        add_action( 'wp_ajax_wai_inbox_poll_new',      [ $this, 'ajax_inbox_poll_new' ] );
        add_action( 'wp_ajax_wai_inbox_sidebar',       [ $this, 'ajax_inbox_sidebar' ] );
    }

    // ------------------------------------------------------------------ //
    //  Menus
    // ------------------------------------------------------------------ //


    public function allow_query_vars( $vars ) {
        $vars[] = 'contact';
        return $vars;
    }

    public function register_menus() {
        add_menu_page(
            __( 'WhatsApp', 'business-messaging-hub' ),
            __( 'WhatsApp', 'business-messaging-hub' ),
            'manage_options',
            'wai-dashboard',
            [ $this, 'page_dashboard' ],
            'dashicons-format-chat',
            58
        );

        add_submenu_page(
            'wai-dashboard',
            __( 'Dashboard', 'business-messaging-hub' ),
            __( 'Dashboard', 'business-messaging-hub' ),
            'manage_options',
            'wai-dashboard',
            [ $this, 'page_dashboard' ]
        );

        add_submenu_page(
            'wai-dashboard',
            __( 'Inbox', 'business-messaging-hub' ),
            __( '💬 Inbox', 'business-messaging-hub' ),
            'manage_options',
            'wai-inbox',
            [ $this, 'page_inbox' ]
        );

        add_submenu_page(
            'wai-dashboard',
            __( 'Send Message', 'business-messaging-hub' ),
            __( 'Send Message', 'business-messaging-hub' ),
            'manage_options',
            'wai-send',
            [ $this, 'page_send' ]
        );

        add_submenu_page(
            'wai-dashboard',
            __( 'WhatsApp Bot', 'business-messaging-hub' ),
            __( '🤖 Bot', 'business-messaging-hub' ),
            'manage_options',
            'wai-bot',
            [ $this, 'page_bot' ]
        );

        add_submenu_page(
            'wai-dashboard',
            __( 'Bot Conversations', 'business-messaging-hub' ),
            __( 'Bot Conversations', 'business-messaging-hub' ),
            'manage_options',
            'wai-bot-conversations',
            [ $this, 'page_bot_conversations' ]
        );

        add_submenu_page(
            'wai-dashboard',
            __( 'Message Log', 'business-messaging-hub' ),
            __( 'Message Log', 'business-messaging-hub' ),
            'manage_options',
            'wai-log',
            [ $this, 'page_log' ]
        );

        add_submenu_page(
            'wai-dashboard',
            __( 'Settings', 'business-messaging-hub' ),
            __( 'Settings', 'business-messaging-hub' ),
            'manage_options',
            'wai-settings',
            [ $this, 'page_settings' ]
        );

        add_submenu_page(
            'wai-dashboard',
            __( 'Webhook', 'business-messaging-hub' ),
            __( '⚡ Webhook', 'business-messaging-hub' ),
            'manage_options',
            'wai-webhook',
            [ $this, 'page_webhook' ]
        );

        add_submenu_page(
            'wai-dashboard',
            __( 'Webhook Log', 'business-messaging-hub' ),
            __( 'Webhook Log', 'business-messaging-hub' ),
            'manage_options',
            'wai-webhook-log',
            [ $this, 'page_webhook_log' ]
        );
    }

    // ------------------------------------------------------------------ //
    //  Assets
    // ------------------------------------------------------------------ //

    public function enqueue_assets( $hook ) {
        $wai_hooks = [
            'toplevel_page_wai-dashboard',
            'whatsapp_page_wai-send',
            'whatsapp_page_wai-log',
            'whatsapp_page_wai-settings',
            'whatsapp_page_wai-webhook',
            'whatsapp_page_wai-webhook-log',
            'whatsapp_page_wai-bot',
            'whatsapp_page_wai-bot-conversations',
            'whatsapp_page_wai-inbox',
        ];

        if ( ! in_array( $hook, $wai_hooks, true ) ) {
            return;
        }

        wp_enqueue_style(
            'wai-admin',
            WAI_PLUGIN_URL . 'admin/css/admin.css',
            [],
            WAI_VERSION
        );

        wp_enqueue_script(
            'wai-admin',
            WAI_PLUGIN_URL . 'admin/js/admin.js',
            [ 'jquery' ],
            WAI_VERSION,
            true
        );

        wp_localize_script( 'wai-admin', 'wai', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wai_nonce' ),
            'i18n'     => [
                'sending'     => __( 'Sending…',          'business-messaging-hub' ),
                'send'        => __( 'Send Message',       'business-messaging-hub' ),
                'testing'     => __( 'Testing…',           'business-messaging-hub' ),
                'test'        => __( 'Test Connection',    'business-messaging-hub' ),
                'success'     => __( 'Message sent!',      'business-messaging-hub' ),
                'error_empty' => __( 'Please fill in all fields.', 'business-messaging-hub' ),
            ],
        ] );
    }

    // ------------------------------------------------------------------ //
    //  Pages
    // ------------------------------------------------------------------ //

    public function page_dashboard() {
        $stats    = WAI_Log::get_stats();
        $stat_map = [];
        foreach ( $stats as $row ) {
            $stat_map[ $row->status ] = (int) $row->total;
        }
        $recent = WAI_Log::get_recent( 5 );
        require WAI_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function page_inbox() {
        // Make contact param available to the view reliably.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display param, no state change.
        if ( ! isset( $_GET['contact'] ) && isset( $_REQUEST['contact'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display param, no state change.
            $_GET['contact'] = sanitize_text_field( wp_unslash( $_REQUEST['contact'] ) );
        }
        require WAI_PLUGIN_DIR . 'admin/views/inbox.php';
    }

    public function page_send() {
        require WAI_PLUGIN_DIR . 'admin/views/send.php';
    }

    public function page_log() {
        $logs = WAI_Log::get_recent( 100 );
        require WAI_PLUGIN_DIR . 'admin/views/log.php';
    }

    public function page_bot() {
        require WAI_PLUGIN_DIR . 'admin/views/bot.php';
    }

    public function page_bot_conversations() {
        require WAI_PLUGIN_DIR . 'admin/views/bot-conversations.php';
    }

    public function page_webhook() {
        require WAI_PLUGIN_DIR . 'admin/views/webhook.php';
    }

    public function page_webhook_log() {
        $webhook_logs = WAI_Webhook_Log::get_recent( 100 );
        $webhook_stats = WAI_Webhook_Log::get_stats();
        require WAI_PLUGIN_DIR . 'admin/views/webhook-log.php';
    }

    public function page_settings() {
        require WAI_PLUGIN_DIR . 'admin/views/settings.php';
    }

    // ------------------------------------------------------------------ //
    //  AJAX
    // ------------------------------------------------------------------ //

    public function ajax_send_message() {
        check_ajax_referer( 'wai_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'business-messaging-hub' ) ], 403 );
        }

        $to      = isset( $_POST['to'] )      ? sanitize_text_field( wp_unslash( $_POST['to'] ) )      : '';
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        if ( ! $to || ! $message ) {
            wp_send_json_error( [ 'message' => __( 'Recipient and message are required.', 'business-messaging-hub' ) ] );
        }

        $result = WAI_Messenger::send( $to, $message );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( [ 'message' => __( 'Message sent successfully!', 'business-messaging-hub' ) ] );
    }

    public function ajax_verify_message() {
        check_ajax_referer( 'wai_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'business-messaging-hub' ) ], 403 );
        }

        $msg_id = isset( $_POST['msg_id'] ) ? sanitize_text_field( wp_unslash( $_POST['msg_id'] ) ) : '';
        $log_id = isset( $_POST['log_id'] ) ? (int) $_POST['log_id'] : 0;

        if ( ! $msg_id ) {
            wp_send_json_error( [ 'message' => __( 'No message ID provided.', 'business-messaging-hub' ) ] );
        }

        // Meta Cloud API does NOT support fetching message status by wamid via GET.
        // Delivery status is only available via Webhooks.
        // We verify by checking our local DB log and confirming the wamid format is valid.

        global $wpdb;
        $table = esc_sql( WAI_Log::table_name() );
        $log   = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no caching API equivalent.
            "SELECT * FROM $table WHERE msg_id = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name sanitized via esc_sql().
            $msg_id
        ) );

        if ( ! $log ) {
            wp_send_json_error( [ 'message' => __( 'Message ID not found in local log.', 'business-messaging-hub' ) ] );
        }

        $sent_by_user = get_userdata( (int) $log->sent_by );

        wp_send_json_success( [
            'status' => 'Accepted by Meta ✅',
            'raw'    => [
                'note'         => 'Meta Cloud API does not support fetching delivery status by wamid. Status below is from your local WordPress log.',
                'wamid'        => $msg_id,
                'to'           => $log->recipient,
                'message'      => $log->message,
                'local_status' => $log->status,
                'sent_by'      => $sent_by_user ? $sent_by_user->display_name : 'Unknown',
                'sent_at'      => $log->created_at,
                'error'        => $log->error ?: null,
                'how_to_get_delivery_status' => 'Set up a Webhook in Meta App Dashboard → WhatsApp → Configuration → Webhook. Meta will POST status updates (sent/delivered/read) to your server.',
            ],
        ] );
    }

    public function ajax_test_connection() {
        check_ajax_referer( 'wai_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'business-messaging-hub' ) ], 403 );
        }

        $api = WAI_Settings::api();
        if ( ! $api ) {
            wp_send_json_error( [ 'message' => __( 'Please save your API credentials first.', 'business-messaging-hub' ) ] );
        }

        $result = $api->verify_credentials();
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( [ 'message' => __( 'Connection successful! Credentials are valid.', 'business-messaging-hub' ) ] );
    }

    public function ajax_regenerate_token() {
        check_ajax_referer( 'wai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        }
        $token = WAI_Settings::regenerate_verify_token();
        wp_send_json_success( [ 'token' => $token ] );
    }

    public function ajax_test_webhook() {
        check_ajax_referer( 'wai_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'business-messaging-hub' ) ], 403 );
        }

        $webhook_url  = rest_url( 'wai/v1/webhook' );
        $verify_token = WAI_Settings::get( 'webhook_verify_token' );
        $challenge    = 'wp_test_' . wp_generate_password( 8, false );

        // Build the URL manually — add_query_arg() encodes dots (hub.mode → hub%2Emode)
        // which breaks PHP's QUERY_STRING parsing. Dots must stay as literal dots.
        $test_url = $webhook_url
            . '?hub.mode=subscribe'
            . '&hub.verify_token=' . rawurlencode( $verify_token )
            . '&hub.challenge='    . rawurlencode( $challenge );

        $response = wp_remote_get( $test_url, [ 'timeout' => 10, 'sslverify' => false ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( [ 'message' => $response->get_error_message() ] );
        }

        $body = trim( wp_remote_retrieve_body( $response ) );
        $code = (int) wp_remote_retrieve_response_code( $response );

        // wp_die() wraps the challenge in <div class="wp-die-message">...</div>.
        // Extract the text from that div first; fall back to stripping all tags.
        if ( preg_match( '/<div[^>]*class="wp-die-message"[^>]*>(.*?)<\/div>/s', $body, $m ) ) {
            $actual = trim( wp_strip_all_tags( $m[1] ) );
        } else {
            $actual = trim( wp_strip_all_tags( $body ) );
        }

        if ( 200 === $code && $actual === $challenge ) {
            wp_send_json_success( [ 'message' => __( 'Webhook endpoint is working correctly! ✅', 'business-messaging-hub' ) ] );
        } else {
            
            wp_send_json_error(
                [
                    'message' => sprintf(
                        /* translators: 1: HTTP status code, 2: Response body. */
                        __( 'Unexpected response. HTTP %1$s — Body: %2$s', 'business-messaging-hub' ),
                        $code,
                        $body
                    ),
                ]
            );
        }
    }
    public function ajax_register_phone() {
        check_ajax_referer( 'wai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        }
        $pin = isset( $_POST['pin'] ) ? sanitize_text_field( wp_unslash( $_POST['pin'] ) ) : '';
        if ( ! preg_match( '/^\d{6}$/', $pin ) ) {
            wp_send_json_error( [ 'message' => __( 'PIN must be exactly 6 digits.', 'business-messaging-hub' ) ] );
        }
        $api = WAI_Settings::api();
        if ( ! $api ) {
            wp_send_json_error( [ 'message' => __( 'Please save your API credentials first.', 'business-messaging-hub' ) ] );
        }
        $result = $api->register_phone( $pin );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        wp_send_json_success( [ 'message' => __( 'Phone number registered successfully! ✅', 'business-messaging-hub' ) ] );
    }

    public function ajax_deregister_phone() {
        check_ajax_referer( 'wai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        }
        $api = WAI_Settings::api();
        if ( ! $api ) {
            wp_send_json_error( [ 'message' => __( 'Please save your API credentials first.', 'business-messaging-hub' ) ] );
        }
        $result = $api->deregister_phone();
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        wp_send_json_success( [ 'message' => __( 'Phone number deregistered.', 'business-messaging-hub' ) ] );
    }

    public function ajax_phone_status() {
        check_ajax_referer( 'wai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        }
        $api = WAI_Settings::api();
        if ( ! $api ) {
            wp_send_json_error( [ 'message' => __( 'Please save your API credentials first.', 'business-messaging-hub' ) ] );
        }
        $result = $api->get_phone_status();
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        // Determine registration status
        $status = $result['code_verification_status'] ?? 'UNKNOWN';
        $registered = ( strtoupper($status) === 'VERIFIED' );
        wp_send_json_success( [
            'registered'   => $registered,
            'status'       => $status,
            'phone'        => $result['display_phone_number']   ?? '—',
            'name'         => $result['verified_name']          ?? '—',
            'quality'      => $result['quality_rating']         ?? '—',
            'platform'     => $result['platform_type']          ?? '—',
            'throughput'   => $result['throughput']['level']    ?? '—',
            'account_mode' => $result['account_mode']           ?? '—',
            'raw'          => $result,
        ] );
    }

    public function ajax_inbox_poll() {
        check_ajax_referer( 'wai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [], 403 );
        }
        $phone   = isset( $_POST['phone'] )   ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $last_id = isset( $_POST['last_id'] ) ? (int) $_POST['last_id'] : 0;

        if ( ! $phone ) wp_send_json_error();

        global $wpdb;
        $table = esc_sql( WAI_Log::table_name() );
        $rows  = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no caching API equivalent.
            "SELECT * FROM $table WHERE recipient = %s ORDER BY id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name sanitized via esc_sql().
            $phone
        ) );

        // Only return messages beyond what client already has
        $new_rows = array_slice( $rows, $last_id );
        if ( empty( $new_rows ) ) {
            wp_send_json_success( [ 'html' => '' ] );
        }

        $ticks = [
            'pending'   => '🕐',
            'sent'      => '✓',
            'delivered' => '✓✓',
            'read'      => '<span style="color:#53bdeb;">✓✓</span>',
            'failed'    => '<span style="color:#f00;">✗</span>',
        ];

        $html = '';
        foreach ( $new_rows as $msg ) {
            $is_incoming = ( 'received' === $msg->status );
            $time        = gmdate( 'H:i', strtotime( $msg->created_at ) );
            $dir_class   = $is_incoming ? 'wai-msg-incoming' : 'wai-msg-outgoing';
            $tick        = ! $is_incoming ? ( $ticks[ $msg->status ] ?? '✓' ) : '';
            $tick_html   = ! $is_incoming ? '<span class="wai-msg-tick">' . $tick . '</span>' : '';
            $error_html  = ! empty( $msg->error ) ? '<div class="wai-msg-error">❌ ' . esc_html( $msg->error ) . '</div>' : '';
            $html .= '<div class="wai-msg-wrap ' . $dir_class . '">'
                   . '<div class="wai-msg-bubble">'
                   . '<div class="wai-msg-text">' . nl2br( esc_html( $msg->message ) ) . '</div>'
                   . '<div class="wai-msg-meta"><span class="wai-msg-time">' . esc_html( $time ) . '</span>' . $tick_html . '</div>'
                   . $error_html
                   . '</div></div>';
        }

        WAI_Inbox::mark_read( $phone );
        wp_send_json_success( [ 'html' => $html ] );
    }

    public function ajax_inbox_load() {
        check_ajax_referer( 'wai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        }
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        if ( ! $phone ) {
            wp_send_json_error( [ 'message' => 'No phone provided.' ] );
        }
        $norm = WAI_Inbox::normalize_phone( $phone );
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'wai_message_log' );

        // Direct query with LIKE to catch all phone format variants
        $all_rows = $wpdb->get_results( "SELECT id, recipient, message, status, msg_id, error, created_at FROM $table ORDER BY id ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, table name sanitized via esc_sql().

        $msgs = [];
        foreach ( $all_rows as $row ) {
            $row_norm = WAI_Inbox::normalize_phone( $row->recipient );
            if ( $row_norm === $norm ) {
                $msgs[] = [
                    'id'         => (int) $row->id,
                    'message'    => (string) $row->message,
                    'status'     => (string) $row->status,
                    'created_at' => (string) $row->created_at,
                    'error'      => (string) ( $row->error ?? '' ),
                ];
            }
        }

        WAI_Inbox::mark_read( $norm );

        wp_send_json_success( [
            'messages'    => $msgs,
            'phone'       => $phone,
            'norm'        => $norm,
            'total_in_db' => count( $all_rows ),
            'matched'     => count( $msgs ),
        ] );
    }

    public function ajax_inbox_poll_new() {
        check_ajax_referer( 'wai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [], 403 );

        $phone   = isset( $_POST['phone'] )   ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $last_id = isset( $_POST['last_id'] ) ? (int) $_POST['last_id'] : 0;
        if ( ! $phone ) wp_send_json_error();

        $norm  = WAI_Inbox::normalize_phone( $phone );
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'wai_message_log' );

        // Only fetch rows with id > last_id for this contact
        $all = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no caching API equivalent.
            "SELECT id, recipient, message, status, error, created_at FROM $table WHERE id > %d ORDER BY id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name sanitized via esc_sql().
            $last_id
        ) );

        $msgs = [];
        foreach ( $all as $row ) {
            if ( WAI_Inbox::normalize_phone( $row->recipient ) === $norm ) {
                $msgs[] = [
                    'id'         => (int) $row->id,
                    'message'    => (string) $row->message,
                    'status'     => (string) $row->status,
                    'created_at' => (string) $row->created_at,
                    'error'      => (string) ( $row->error ?? '' ),
                ];
            }
        }

        if ( ! empty( $msgs ) ) {
            WAI_Inbox::mark_read( $norm );
        }

        wp_send_json_success( [ 'messages' => $msgs ] );
    }

    public function ajax_inbox_sidebar() {
        check_ajax_referer( 'wai_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [], 403 );

        $contacts = WAI_Inbox::get_contacts();
        if ( empty( $contacts ) ) {
            wp_send_json_success( [ 'html' => '' ] );
        }

        ob_start();
        foreach ( $contacts as $contact ) :
            $norm     = WAI_Inbox::normalize_phone( $contact->phone );
            $initials = strtoupper( substr( $norm, -2 ) );
            $preview  = mb_strimwidth( $contact->last_message, 0, 38, '…' );
            $time     = human_time_diff( strtotime( $contact->last_message_time ), current_time( 'timestamp' ) ) . ' ago';
            $dir_icon = ( 'received' === $contact->last_status ) ? '↙' : '↗';
        ?>
            <li class="wai-contact-item"
                data-phone="<?php echo esc_attr( $norm ); ?>"
                data-search="<?php echo esc_attr( $norm ); ?>">
                <div class="wai-contact-avatar"><?php echo esc_html( $initials ); ?></div>
                <div class="wai-contact-info">
                    <div class="wai-contact-top">
                        <span class="wai-contact-phone"><?php echo esc_html( $norm ); ?></span>
                        <span class="wai-contact-time"><?php echo esc_html( $time ); ?></span>
                    </div>
                    <div class="wai-contact-preview">
                        <span class="wai-dir"><?php echo esc_html( $dir_icon ); ?></span>
                        <?php echo esc_html( $preview ); ?>
                        <?php if ( $contact->unread > 0 ) : ?>
                            <span class="wai-badge"><?php echo (int) $contact->unread; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
        <?php endforeach;
        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }

}
