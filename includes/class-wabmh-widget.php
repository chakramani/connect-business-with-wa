<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WABMH Frontend Chat Widget
 * Only shown to logged-in users.
 * First visit → redirect to WhatsApp.
 * Subsequent visits → show chat history from DB.
 */
class WABMH_Widget {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts',                 [ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer',                          [ $this, 'render_widget' ] );
        add_action( 'wp_ajax_wabmh_widget_send',            [ $this, 'ajax_send' ] );
        add_action( 'wp_ajax_wabmh_widget_start',           [ $this, 'ajax_start' ] );
        add_action( 'wp_ajax_wabmh_widget_history',         [ $this, 'ajax_history' ] );
        add_action( 'wp_ajax_wabmh_widget_poll',            [ $this, 'ajax_poll' ] );
    }

    /**
     * Whether the widget should appear for the current request: enabled in
     * settings and the visitor is a logged-in user.
     */
    private function should_render() {
        $opts = get_option( WABMH_OPTION_KEY, [] );
        return ! empty( $opts['widget_enabled'] ) && is_user_logged_in();
    }

    // ------------------------------------------------------------------ //
    //  Assets — registered on wp_enqueue_scripts, not inline in the markup
    // ------------------------------------------------------------------ //

    public function enqueue_assets() {
        if ( ! $this->should_render() ) return;

        $opts        = get_option( WABMH_OPTION_KEY, [] );
        $user        = wp_get_current_user();
        $biz_phone   = preg_replace( '/\D/', '', $opts['widget_display_phone'] ?? '' );
        $user_phone  = preg_replace( '/\D/', '', get_user_meta( $user->ID, 'wabmh_phone', true ) ?? '' );
        $has_started = (bool) get_user_meta( $user->ID, 'wabmh_chat_started', true );

        wp_enqueue_style(
            'wabmh-chat-widget',
            WABMH_PLUGIN_URL . 'public/css/chat-widget.css',
            [],
            WABMH_VERSION
        );

        wp_enqueue_script(
            'wabmh-chat-widget',
            WABMH_PLUGIN_URL . 'public/js/chat-widget.js',
            [ 'jquery' ],
            WABMH_VERSION,
            [ 'in_footer' => true, 'strategy' => 'defer' ]
        );

        wp_localize_script( 'wabmh-chat-widget', 'wabmhWidget', [
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'wabmh_widget_nonce' ),
            'business_name'  => $opts['business_name'] ?? get_bloginfo( 'name' ),
            'business_phone' => $biz_phone,
            'default_msg'    => $opts['widget_default_msg'] ?? 'Hi, I need help',
            'user_id'        => $user->ID,
            'user_phone'     => $user_phone,
            'has_started'    => $has_started,
        ] );
    }

    // ------------------------------------------------------------------ //
    //  Render
    // ------------------------------------------------------------------ //

    public function render_widget() {
        if ( ! $this->should_render() ) return;

        $opts        = get_option( WABMH_OPTION_KEY, [] );
        $user        = wp_get_current_user();
        $welcome_msg = $opts['widget_welcome_msg'] ?? 'Hi there! 👋 How can we help you today?';
        $biz_phone   = preg_replace( '/\D/', '', $opts['widget_display_phone'] ?? '' );
        $user_phone  = preg_replace( '/\D/', '', get_user_meta( $user->ID, 'wabmh_phone', true ) ?? '' );
        $has_started = (bool) get_user_meta( $user->ID, 'wabmh_chat_started', true );
        ?>

        <!-- WABMH Launcher -->
        <button class="wabmh-chat-launcher" aria-label="Chat on WhatsApp">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="#fff">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            <span class="wabmh-launcher-badge"></span>
        </button>

        <!-- WABMH Chat Widget -->
        <div class="wabmh-chat-widget" role="dialog" aria-label="WhatsApp Chat">

            <!-- Header -->
            <div class="wabmh-widget-header">
                <div class="wabmh-widget-avatar">💬</div>
                <div class="wabmh-widget-header-info">
                    <strong><?php echo esc_html( $opts['business_name'] ?? get_bloginfo('name') ); ?></strong>
                    <span><span class="wabmh-online-dot"></span> Typically replies instantly</span>
                </div>
                <button class="wabmh-widget-close" aria-label="Close">✕</button>
            </div>

            <!-- Screen 1: Welcome (first time) -->
            <div class="wabmh-widget-screen wabmh-welcome-screen <?php echo ! $has_started ? 'active' : ''; ?>">
                <div class="wabmh-welcome-inner">
                    <div class="wabmh-welcome-bubble">
                        <p><?php echo esc_html( $welcome_msg ); ?></p>
                        <p style="font-size:.82rem;color:#666;margin-top:6px;">
                            👋 Hi <?php echo esc_html( $user->display_name ); ?>! Enter your WhatsApp number to start chatting.
                        </p>
                    </div>
                    <div style="width:100%;max-width:280px;">
                        <input
                            type="tel"
                            id="wabmh-user-phone"
                            placeholder="Your WhatsApp number e.g. 9779843673682"
                            style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:22px;font-size:.88rem;box-sizing:border-box;outline:none;margin-bottom:10px;"
                            maxlength="20"
                            value="<?php echo esc_attr( $user_phone ); ?>"
                        />
                        <button type="button" id="wabmh-open-whatsapp" class="wabmh-start-btn" style="width:100%;">
                            💬 Start Chat on WhatsApp
                        </button>
                        <p id="wabmh-phone-error" style="color:#c0392b;font-size:.8rem;margin-top:6px;display:none;"></p>
                        <?php if ( ! $biz_phone ) : ?>
                        <p style="color:#c0392b;font-size:.8rem;margin-top:8px;">⚠️ Business phone not configured in settings.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Screen 2: Chat history (returning users) -->
            <div class="wabmh-widget-screen wabmh-chat-screen <?php echo $has_started ? 'active' : ''; ?>">
                <div class="wabmh-widget-messages" id="wabmh-widget-messages">
                    <div class="wabmh-widget-typing">
                        <div class="wabmh-typing-bubble">
                            <div class="wabmh-typing-dot"></div>
                            <div class="wabmh-typing-dot"></div>
                            <div class="wabmh-typing-dot"></div>
                        </div>
                    </div>
                </div>
                <!-- Reply box -->
                <div class="wabmh-widget-reply" style="background:#f0f0f0;padding:8px 10px;display:flex;align-items:flex-end;gap:8px;border-top:1px solid #ddd;flex-shrink:0;">
                    <textarea
                        id="wabmh-widget-textarea"
                        class="wabmh-widget-textarea"
                        placeholder="Type a message…"
                        rows="1"
                        maxlength="4096"
                        style="flex:1;padding:9px 14px;border:none;border-radius:22px;background:#fff;font-size:.86rem;resize:none;outline:none;max-height:100px;overflow-y:auto;line-height:1.5;font-family:inherit;"
                    ></textarea>
                    <button id="wabmh-widget-send" class="wabmh-widget-send" aria-label="Send" style="width:40px;height:40px;border-radius:50%;background:#075e54;color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="#ffffff" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.101 21.757L23.8 12.028 1.101 2.3l.011 7.912 13.623 1.816-13.623 1.817-.011 7.912z"/>
                        </svg>
                    </button>
                </div>
            </div>

        </div>

        <?php
    }

    // ------------------------------------------------------------------ //
    //  AJAX — Mark chat as started, save user phone
    // ------------------------------------------------------------------ //


    public function ajax_send() {
        check_ajax_referer( 'wabmh_widget_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Not logged in.' ] );
        }

        $user_id = get_current_user_id();
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

        if ( ! $message ) {
            wp_send_json_error( [ 'message' => 'Empty message.' ] );
        }

        // Get the customer's own phone number
        $phone = get_user_meta( $user_id, 'wabmh_phone', true );
        if ( ! $phone ) {
            wp_send_json_error( [ 'message' => 'No phone linked. Please restart chat.' ] );
        }

        // Customer is SENDING TO the business — store as 'received' (incoming to business)
        // Do NOT call WABMH_Messenger::send() here — that sends FROM business TO customer
        // which would be the wrong direction and trigger re-engagement errors.
        WABMH_Log::insert( [
            'recipient' => $phone,
            'message'   => $message,
            'status'    => 'received',
            'msg_id'    => 'widget_' . time() . '_' . $user_id,
            'error'     => '',
            'sent_by'   => 0,
        ] );

        // Notify admin via email if enabled
        $opts = get_option( WABMH_OPTION_KEY, [] );
        if ( ! empty( $opts['widget_email_notify'] ) ) {
            wp_mail(
                get_option( 'admin_email' ),
                '💬 New message from ' . $phone,
                "Phone: $phone
Message:
$message"
            );
        }

        // Return the new row ID so JS can update lastMsgId and avoid duplicate on poll
        global $wpdb;
        $new_id = (int) $wpdb->insert_id;
        wp_send_json_success( [ 'message' => 'Sent.', 'id' => $new_id ] );
    }

    public function ajax_start() {
        check_ajax_referer( 'wabmh_widget_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Not logged in.' ] );

        $user_id = get_current_user_id();

        // Save phone from form input
        $phone = isset( $_POST['phone'] ) ? preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : '';

        if ( ! $phone ) {
            wp_send_json_error( [ 'message' => 'Please enter a valid phone number.' ] );
        }

        // Save phone and mark chat as started
        update_user_meta( $user_id, 'wabmh_phone', $phone );
        update_user_meta( $user_id, 'wabmh_chat_started', '1' );

        wp_send_json_success( [ 'phone' => $phone ] );
    }

    // ------------------------------------------------------------------ //
    //  AJAX — Load chat history for logged-in user
    // ------------------------------------------------------------------ //

    public function ajax_history() {
        check_ajax_referer( 'wabmh_widget_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => 'Not logged in.' ] );

        $user_id    = get_current_user_id();
        $user       = get_userdata( $user_id );
        $user_phone = preg_replace( '/\D/', '', get_user_meta( $user_id, 'wabmh_phone', true ) ?: '' );

        // Fallback: match by billing phone or WP user email
        if ( ! $user_phone ) {
            $user_phone = preg_replace( '/\D/', '', get_user_meta( $user_id, 'billing_phone', true ) ?: '' );
        }

        if ( ! $user_phone ) {
            wp_send_json_success( [ 'messages' => [], 'note' => 'No phone linked to this user.' ] );
        }

        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'wabmh_message_log' );
        $rows  = $wpdb->get_results( "SELECT id, recipient, message, status, created_at FROM $table ORDER BY id ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, table name sanitized via esc_sql().

        $msgs = [];
        foreach ( $rows as $row ) {
            if ( WABMH_Inbox::normalize_phone( $row->recipient ) !== $user_phone ) continue;
            $msgs[] = [
                'id'   => (int) $row->id,
                // outgoing from widget = received by business; incoming = sent by business
                'dir'  => ( 'received' === $row->status ) ? 'outgoing' : 'incoming',
                'text' => $row->message,
                'time' => substr( $row->created_at, 11, 5 ),
            ];
        }

        wp_send_json_success( [ 'messages' => $msgs ] );
    }

    // ------------------------------------------------------------------ //
    //  AJAX — Poll for new messages
    // ------------------------------------------------------------------ //

    public function ajax_poll() {
        check_ajax_referer( 'wabmh_widget_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error();

        $last_id    = isset( $_POST['last_id'] ) ? (int) $_POST['last_id'] : 0;
        $user_id    = get_current_user_id();
        $user_phone = preg_replace( '/\D/', '', get_user_meta( $user_id, 'wabmh_phone', true ) ?: '' );

        if ( ! $user_phone ) {
            $user_phone = preg_replace( '/\D/', '', get_user_meta( $user_id, 'billing_phone', true ) ?: '' );
        }

        if ( ! $user_phone ) wp_send_json_success( [ 'messages' => [] ] );

        global $wpdb;
        $table = esc_sql( $wpdb->prefix . 'wabmh_message_log' );
        $rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no caching API equivalent.
            "SELECT id, recipient, message, status, created_at FROM $table WHERE id > %d ORDER BY id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name sanitized via esc_sql(), no user input.
            $last_id
        ) );

        $msgs = [];
        foreach ( $rows as $row ) {
            if ( WABMH_Inbox::normalize_phone( $row->recipient ) !== $user_phone ) continue;
            $msgs[] = [
                'id'   => (int) $row->id,
                'dir'  => ( 'received' === $row->status ) ? 'outgoing' : 'incoming',
                'text' => $row->message,
                'time' => substr( $row->created_at, 11, 5 ),
            ];
        }

        wp_send_json_success( [ 'messages' => $msgs ] );
    }
}
