<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Stores raw incoming webhook events in a separate DB table.
 */
class WABMH_Webhook_Log {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'wabmh_webhook_log';
    }

    public static function create_table() {
        global $wpdb;
        $table           = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type  VARCHAR(30)  NOT NULL DEFAULT '',
            wamid       VARCHAR(150) NOT NULL DEFAULT '',
            direction   VARCHAR(10)  NOT NULL DEFAULT 'inbound',
            summary     TEXT         NOT NULL DEFAULT '',
            raw_payload LONGTEXT     NOT NULL DEFAULT '',
            received_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type  (event_type),
            KEY wamid       (wamid)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * @param string $event_type  e.g. 'status_update', 'incoming_message', 'signature_failed'
     * @param array  $summary     Human-readable key/value summary
     * @param string $raw         Full JSON payload from Meta
     */
    public static function insert( $event_type, array $summary, $raw = '' ) {
        global $wpdb;

        $direction = ( 'incoming_message' === $event_type ) ? 'inbound' : 'outbound';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table insert.
        $wpdb->insert( self::table_name(), [
            'event_type'  => sanitize_text_field( $event_type ),
            'wamid'       => sanitize_text_field( $summary['wamid'] ?? '' ),
            'direction'   => $direction,
            'summary'     => wp_json_encode( $summary ),
            'raw_payload' => $raw,
            'received_at' => current_time( 'mysql' ),
        ], [ '%s', '%s', '%s', '%s', '%s', '%s' ] );

        return $wpdb->insert_id;
    }

    public static function get_recent( $limit = 100 ) {
        global $wpdb;
        $table = self::table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, no core API equivalent; $table is a fixed internal value, not user input.
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY id DESC LIMIT %d", $limit ) );
    }
    
    public static function get_stats() {
        global $wpdb;
        $table = self::table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, no core API equivalent; $table is a fixed internal value, not user input.
        return $wpdb->get_results( "SELECT event_type, COUNT(*) AS total FROM $table GROUP BY event_type" );
    }

    /**
     * Check whether a status_update event for this wamid + status has
     * already been recorded, to guard against Meta re-delivering the same
     * webhook event (replay / retry) and processing it twice.
     */
    public static function status_event_exists( $wamid, $status ) {
        global $wpdb;
        if ( empty( $wamid ) ) {
            return false;
        }
        $table = self::table_name();
        $like  = '%' . $wpdb->esc_like( '"status":"' . $status . '"' ) . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, values passed through wpdb->prepare().
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE wamid = %s AND event_type = 'status_update' AND summary LIKE %s",
            $wamid,
            $like
        ) );
        return ! empty( $count );
    }

    /**
     * Check whether an incoming_message event for this wamid has already
     * been recorded (replay / retry protection).
     */
    public static function wamid_exists( $wamid, $event_type ) {
        global $wpdb;
        if ( empty( $wamid ) ) {
            return false;
        }
        $table = self::table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, values passed through wpdb->prepare().
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE wamid = %s AND event_type = %s",
            $wamid,
            $event_type
        ) );
        return ! empty( $count );
    }
}
