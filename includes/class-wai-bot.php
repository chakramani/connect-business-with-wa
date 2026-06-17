<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WhatsApp Bot Engine
 * Listens to wai_incoming_message and auto-replies based on rules.
 */
class WAI_Bot {

    const RULES_OPTION    = 'wai_bot_rules';
    const CONVOS_TABLE    = 'wai_bot_conversations';
    const SESSION_TIMEOUT = 1800; // 30 minutes inactivity = reset session

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wai_incoming_message', [ $this, 'handle_incoming' ], 10, 4 );
    }

    // ------------------------------------------------------------------ //
    //  Main handler — called by webhook on every incoming message
    // ------------------------------------------------------------------ //

    public function handle_incoming( $from, $text, $msg, $metadata ) {
        if ( ! self::is_enabled() ) return;

        $text = trim( $text );
        if ( empty( $text ) ) return;

        // Update / create conversation session
        $session = $this->get_or_create_session( $from );

        // Try to match a rule
        $reply = $this->match_rule( $text, $session );

        if ( $reply !== null ) {
            // Send the reply
            $result = WAI_Messenger::send( $from, $reply, 0 );

            // Log the bot conversation
            $this->log_conversation( $from, $text, $reply, ! is_wp_error( $result ) );

            // Update session
            $this->update_session( $from, $text, $reply );
        } elseif ( self::get_setting( 'fallback_enabled' ) ) {
            // Send fallback message
            $fallback = self::get_setting( 'fallback_message' );
            if ( $fallback ) {
                WAI_Messenger::send( $from, $fallback, 0 );
                $this->log_conversation( $from, $text, $fallback, true );
                $this->update_session( $from, $text, $fallback );
            }
        }
    }

    // ------------------------------------------------------------------ //
    //  Rule matching
    // ------------------------------------------------------------------ //

    private function match_rule( $text, $session ) {
        $rules = self::get_rules();
        if ( empty( $rules ) ) return null;

        $text_lower = mb_strtolower( $text );

        foreach ( $rules as $rule ) {
            if ( empty( $rule['enabled'] ) ) continue;

            $keyword  = mb_strtolower( trim( $rule['keyword'] ?? '' ) );
            $match    = $rule['match_type'] ?? 'contains';
            $reply    = $rule['reply']      ?? '';

            if ( empty( $keyword ) || empty( $reply ) ) continue;

            $matched = false;
            switch ( $match ) {
                case 'exact':
                    $matched = ( $text_lower === $keyword );
                    break;
                case 'starts_with':
                    $matched = ( strpos( $text_lower, $keyword ) === 0 );
                    break;
                case 'contains':
                    $matched = ( strpos( $text_lower, $keyword ) !== false );
                    break;
                case 'regex':
                    $matched = (bool) preg_match( '/' . $keyword . '/i', $text );
                    break;
            }

            if ( $matched ) {
                // Replace variables in reply
                return $this->parse_reply( $reply, $text, $session );
            }
        }

        return null;
    }

    // ------------------------------------------------------------------ //
    //  Variable replacement in replies
    // ------------------------------------------------------------------ //

    private function parse_reply( $reply, $incoming_text, $session ) {
        $vars = [
            '{message}'    => $incoming_text,
            '{date}'       => current_time( 'F j, Y' ),
            '{time}'       => current_time( 'g:i A' ),
            '{site_name}'  => get_bloginfo( 'name' ),
            '{site_url}'   => home_url(),
            '{count}'      => (int) ( $session['message_count'] ?? 0 ) + 1,
        ];
        return str_replace( array_keys( $vars ), array_values( $vars ), $reply );
    }

    // ------------------------------------------------------------------ //
    //  Session management (stored in transients)
    // ------------------------------------------------------------------ //

    private function get_or_create_session( $from ) {
        $key     = 'wai_session_' . md5( $from );
        $session = get_transient( $key );
        if ( ! $session ) {
            $session = [
                'from'          => $from,
                'started_at'    => current_time( 'mysql' ),
                'message_count' => 0,
                'last_message'  => '',
                'last_reply'    => '',
            ];
        }
        return $session;
    }

    private function update_session( $from, $incoming, $reply ) {
        $key     = 'wai_session_' . md5( $from );
        $session = $this->get_or_create_session( $from );
        $session['message_count']++;
        $session['last_message'] = $incoming;
        $session['last_reply']   = $reply;
        set_transient( $key, $session, self::SESSION_TIMEOUT );
    }

    // ------------------------------------------------------------------ //
    //  Conversation log
    // ------------------------------------------------------------------ //

    public static function create_table() {
        global $wpdb;
        $table           = $wpdb->prefix . self::CONVOS_TABLE;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            phone        VARCHAR(30)  NOT NULL,
            incoming     TEXT         NOT NULL DEFAULT '',
            reply        TEXT         NOT NULL DEFAULT '',
            bot_replied  TINYINT(1)   NOT NULL DEFAULT 1,
            created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY phone (phone)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    private function log_conversation( $from, $incoming, $reply, $success ) {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . self::CONVOS_TABLE );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table insert.
        $wpdb->insert( $table, [
            'phone'       => sanitize_text_field( $from ),
            'incoming'    => $incoming,
            'reply'       => $reply,
            'bot_replied' => $success ? 1 : 0,
            'created_at'  => current_time( 'mysql' ),
        ], [ '%s', '%s', '%s', '%d', '%s' ] );
    }

    public static function get_conversations( $limit = 100 ) {
    global $wpdb;
    $table = esc_sql( $wpdb->prefix . self::CONVOS_TABLE );
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY id DESC LIMIT %d", $limit ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- custom table, no core API equivalent; $table is a fixed internal value, not user input.
}

    public static function get_conversation_stats() {
        global $wpdb;
        $table = esc_sql( $wpdb->prefix . self::CONVOS_TABLE );
        $total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, table name sanitized via esc_sql().
        $unique  = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT phone) FROM $table" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, table name sanitized via esc_sql().
        $replied = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE bot_replied = 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, table name sanitized via esc_sql().
        return compact( 'total', 'unique', 'replied' );
    }

    // ------------------------------------------------------------------ //
    //  Rules CRUD
    // ------------------------------------------------------------------ //

    public static function get_rules() {
        return get_option( self::RULES_OPTION, [] );
    }

    public static function save_rules( array $rules ) {
        update_option( self::RULES_OPTION, $rules );
    }

    public static function add_rule( array $rule ) {
        $rules   = self::get_rules();
        $rule['id'] = uniqid( 'rule_' );
        $rules[] = $rule;
        self::save_rules( $rules );
        return $rule['id'];
    }

    public static function update_rule( $id, array $data ) {
        $rules = self::get_rules();
        foreach ( $rules as &$rule ) {
            if ( $rule['id'] === $id ) {
                $rule = array_merge( $rule, $data );
                break;
            }
        }
        self::save_rules( $rules );
    }

    public static function delete_rule( $id ) {
        $rules = array_filter( self::get_rules(), fn( $r ) => $r['id'] !== $id );
        self::save_rules( array_values( $rules ) );
    }

    // ------------------------------------------------------------------ //
    //  Bot settings helpers
    // ------------------------------------------------------------------ //

    public static function is_enabled() {
        return (bool) self::get_setting( 'enabled' );
    }

    public static function get_setting( $key ) {
        $settings = get_option( 'wai_bot_settings', [] );
        return $settings[ $key ] ?? null;
    }

    public static function save_settings( array $data ) {
        $clean = [
            'enabled'          => ! empty( $data['enabled'] ) ? 1 : 0,
            'fallback_enabled' => ! empty( $data['fallback_enabled'] ) ? 1 : 0,
            'fallback_message' => sanitize_textarea_field( $data['fallback_message'] ?? '' ),
            'greeting_enabled' => ! empty( $data['greeting_enabled'] ) ? 1 : 0,
            'greeting_message' => sanitize_textarea_field( $data['greeting_message'] ?? '' ),
            'working_hours_enabled' => ! empty( $data['working_hours_enabled'] ) ? 1 : 0,
            'working_hours_start'   => sanitize_text_field( $data['working_hours_start'] ?? '09:00' ),
            'working_hours_end'     => sanitize_text_field( $data['working_hours_end']   ?? '17:00' ),
            'outside_hours_message' => sanitize_textarea_field( $data['outside_hours_message'] ?? '' ),
        ];
        update_option( 'wai_bot_settings', $clean );
    }
}
