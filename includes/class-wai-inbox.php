<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Inbox — groups all messages (sent + received) by contact phone number.
 */
class WAI_Inbox {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'wai_message_log';
    }

    /**
     * Normalize a phone number to digits only (no +, spaces, dashes).
     */
    public static function normalize_phone( $phone ) {
        return preg_replace( '/\D/', '', $phone );
    }

    /**
     * Get all unique contacts normalized, with their last message and unread count.
     */
    public static function get_contacts() {
        global $wpdb;
        $table = esc_sql( self::table_name() );
        // Fetch all rows and group in PHP so we can normalize phone numbers
        $rows = $wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, table name sanitized via esc_sql().
        
        $contacts = [];
        foreach ( $rows as $row ) {
            $norm = self::normalize_phone( $row->recipient );
            if ( ! isset( $contacts[ $norm ] ) ) {
                $contacts[ $norm ] = [
                    'phone'             => $norm,
                    'raw_phone'         => $row->recipient,
                    'last_message_time' => $row->created_at,
                    'last_message'      => $row->message,
                    'last_status'       => $row->status,
                    'unread'            => 0,
                ];
            }
            // Always update with latest
            $contacts[ $norm ]['last_message_time'] = $row->created_at;
            $contacts[ $norm ]['last_message']      = $row->message;
            $contacts[ $norm ]['last_status']        = $row->status;
            if ( 'received' === $row->status && empty( $row->read_at ) ) {
                $contacts[ $norm ]['unread']++;
            }
        }

        // Sort by last message time descending
        usort( $contacts, function( $a, $b ) {
            return strtotime( $b['last_message_time'] ) - strtotime( $a['last_message_time'] );
        } );

        return array_map( function( $c ) {
            return (object) $c;
        }, array_values( $contacts ) );
    }

    /**
     * Get full conversation for a contact — matches all phone format variants.
     */
    public static function get_conversation( $phone ) {
        global $wpdb;
        $table = esc_sql( self::table_name() );
        $norm  = self::normalize_phone( $phone );
        $rows = $wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, table name sanitized via esc_sql().

        if ( empty( $rows ) ) return [];

        $filtered = [];
        foreach ( $rows as $row ) {
            if ( self::normalize_phone( $row->recipient ) === $norm ) {
                $filtered[] = $row;
            }
        }
        return $filtered;
    }

    /**
     * Mark all received messages from a contact as read (all format variants).
     */
    public static function mark_read( $phone ) {
    global $wpdb;
    $table = self::table_name(); // no esc_sql needed — hardcoded prefix + suffix
    $norm  = self::normalize_phone( $phone );

    // Build both variants: with and without leading '+'
    $with_plus    = '+' . ltrim( $norm, '+' );
    $without_plus = ltrim( $norm, '+' );

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no core API equivalent.
    $wpdb->query( $wpdb->prepare(
        "UPDATE `{$table}` SET read_at = NOW()" . // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is a fixed internal value, not user input.
        " WHERE recipient IN (%s, %s)
           AND status = %s
           AND read_at IS NULL",
        $with_plus,
        $without_plus,
        'received'
    ) );
}

    /**
     * Get total unread count across all contacts.
     */
    public static function get_unread_count() {
        global $wpdb;
        $table = esc_sql( self::table_name() );
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'received' AND read_at IS NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, table name sanitized via esc_sql().
    }
}
