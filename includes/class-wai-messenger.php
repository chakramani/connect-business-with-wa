<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * High-level façade for sending messages + logging the result.
 */
class WAI_Messenger {

    /**
     * Send a text message and log the attempt.
     *
     * @param string $to
     * @param string $message
     * @param int    $sent_by  WP user ID (0 = programmatic).
     * @return true|WP_Error
     */
    public static function send( $to, $message, $sent_by = 0 ) {
        $api = WAI_Settings::api();
        if ( ! $api ) {
            return new WP_Error( 'wai_not_configured', __( 'WhatsApp API is not configured.', 'business-messaging-hub' ) );
        }

        $result = $api->send_text( $to, $message );

        $status  = is_wp_error( $result ) ? 'failed'  : 'sent';
        $message_id = ! is_wp_error( $result ) ? ( $result['messages'][0]['id'] ?? '' ) : '';

        WAI_Log::insert( [
            'recipient'  => $to,
            'message'    => $message,
            'status'     => $status,
            'msg_id'     => $message_id,
            'error'      => is_wp_error( $result ) ? $result->get_error_message() : '',
            'sent_by'    => (int) $sent_by ?: get_current_user_id(),
        ] );

        return is_wp_error( $result ) ? $result : true;
    }
}
