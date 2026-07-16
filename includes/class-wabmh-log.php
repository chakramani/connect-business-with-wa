<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Stores and retrieves message-send logs in a custom DB table.
 */
class WABMH_Log {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'wabmh_message_log';
    }

    public static function create_table() {
        global $wpdb;
        $table      = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            recipient    VARCHAR(30)  NOT NULL,
            message      TEXT         NOT NULL,
            status       VARCHAR(15)  NOT NULL DEFAULT 'pending',
            msg_id       VARCHAR(150) NOT NULL DEFAULT '',
            error        TEXT         NOT NULL DEFAULT '',
            sent_by      BIGINT UNSIGNED NOT NULL DEFAULT 0,
            delivered_at DATETIME     NULL DEFAULT NULL,
            read_at      DATETIME     NULL DEFAULT NULL,
            created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status   (status),
            KEY msg_id   (msg_id),
            KEY sent_by  (sent_by)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Update message status when a webhook delivery event arrives.
     *
     * @param string $msg_id  The wamid returned when the message was sent.
     * @param string $status  sent|delivered|read|failed
     */
    public static function update_status_by_msg_id( $msg_id, $status, $error = '' ) {
        global $wpdb;
        $table = esc_sql( self::table_name() );

        $data   = [ 'status' => sanitize_text_field( $status ) ];
        $format = [ '%s' ];

        if ( $error ) {
            $data['error'] = sanitize_text_field( $error );
            $format[]      = '%s';
        }

        if ( 'delivered' === $status ) {
            $data['delivered_at'] = current_time( 'mysql' );
            $format[]             = '%s';
        } elseif ( 'read' === $status ) {
            $data['read_at'] = current_time( 'mysql' );
            $format[]        = '%s';
            // Also mark delivered if not already
            $data['delivered_at'] = current_time( 'mysql' );
            $format[]             = '%s';
        }

        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no caching API equivalent.
            $table,
            $data,
            [ 'msg_id' => sanitize_text_field( $msg_id ) ],
            $format,
            [ '%s' ]
        );
    }

    public static function insert( array $data ) {
        global $wpdb;
        $wpdb->insert( self::table_name(), [ // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table, no caching API equivalent.
            'recipient'  => $data['recipient']  ?? '',
            'message'    => $data['message']    ?? '',
            'status'     => $data['status']     ?? 'pending',
            'msg_id'     => $data['msg_id']     ?? '',
            'error'      => $data['error']      ?? '',
            'sent_by'    => (int) ( $data['sent_by'] ?? 0 ),
            'created_at' => current_time( 'mysql' ),
        ], [ '%s', '%s', '%s', '%s', '%s', '%d', '%s' ] );
        return $wpdb->insert_id;
    }


    
   public static function get_recent( $limit = 50 ) {
    global $wpdb;
    $table = self::table_name();
    return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY id DESC LIMIT %d", $limit ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- custom table, no core API equivalent; $table is a fixed internal value, not user input.
}

    public static function get_stats() {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM $table GROUP BY status" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, table name sanitized via esc_sql().
    }
}
