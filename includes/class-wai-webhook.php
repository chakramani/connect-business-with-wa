<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles incoming WhatsApp Cloud API webhook requests from Meta.
 *
 * Webhook URL:  https://yoursite.com/wp-json/wai/v1/webhook
 * Verify Token: Set in plugin Settings → Webhook
 */
class WAI_Webhook {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    // ------------------------------------------------------------------ //
    //  REST Route
    // ------------------------------------------------------------------ //

    public function register_routes() {
        register_rest_route( 'wai/v1', '/webhook', [
            [
                'methods'             => WP_REST_Server::READABLE,   // GET  — Meta verification
                'callback'            => [ $this, 'handle_verify' ],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,  // POST — incoming events
                'callback'            => [ $this, 'handle_event' ],
                'permission_callback' => '__return_true',
            ],
        ] );
    }

    // ------------------------------------------------------------------ //
    //  GET — Webhook verification challenge
    // ------------------------------------------------------------------ //

    public function handle_verify( WP_REST_Request $request ) {
        // PHP converts dots in query-string keys to underscores before populating $_GET
        // (e.g. hub.mode → hub_mode), so $_GET['hub.mode'] is always empty.
        // WordPress $request->get_param() has the same problem.
        // The only safe source is $_SERVER['QUERY_STRING'] parsed manually.
        $params = [];
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $qs = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
        // phpcs:enable
        foreach ( explode( '&', $qs ) as $pair ) {
            if ( strpos( $pair, '=' ) === false ) continue;
            list( $k, $v ) = explode( '=', $pair, 2 );
            $params[ urldecode( $k ) ] = urldecode( $v );
        }

        $mode      = isset( $params['hub.mode'] )         ? sanitize_text_field( $params['hub.mode'] )         : '';
        $token     = isset( $params['hub.verify_token'] ) ? sanitize_text_field( $params['hub.verify_token'] ) : '';
        $challenge = isset( $params['hub.challenge'] )    ? sanitize_text_field( $params['hub.challenge'] )    : '';

        $saved_token = WAI_Settings::get( 'webhook_verify_token' );

        if ( 'subscribe' === $mode && $token === $saved_token ) {
            // Meta requires the challenge returned as a bare plain-text string with HTTP 200.
            // - WP_REST_Response JSON-encodes strings → "TEST123" (with quotes) → Meta rejects.
            // - wp_die() wraps output in HTML → Meta rejects.
            // Correct approach: set headers manually and echo the raw string.
            status_header( 200 );
            header( 'Content-Type: text/plain; charset=UTF-8' );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $challenge;
            exit;
        }

        return new WP_REST_Response( 'Forbidden', 403 );
    }

    // ------------------------------------------------------------------ //
    //  POST — Incoming status / message events
    // ------------------------------------------------------------------ //

    public function handle_event( WP_REST_Request $request ) {
        // WordPress may not populate get_body() if Content-Type is missing or unexpected.
        // Fall back to php://input which always has the raw POST body.
        $body = $request->get_body();
        if ( empty( $body ) ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $body = file_get_contents( 'php://input' );
        }

        // Optional: verify X-Hub-Signature-256
        if ( ! $this->verify_signature( $body, $request->get_header( 'x_hub_signature_256' ) ) ) {
            WAI_Webhook_Log::insert( 'signature_failed', [], $body );
            return new WP_REST_Response( 'Forbidden', 403 );
        }

        $payload = json_decode( $body, true );

        if ( empty( $payload['entry'] ) ) {
            return new WP_REST_Response( 'ok', 200 );
        }

        foreach ( $payload['entry'] as $entry ) {
            foreach ( $entry['changes'] ?? [] as $change ) {
                $value = $change['value'] ?? [];

                // ---- Delivery / read status updates ----
                if ( ! empty( $value['statuses'] ) ) {
                    foreach ( $value['statuses'] as $status_obj ) {
                        $this->process_status( $status_obj, $payload );
                    }
                }

                // ---- Incoming messages (from customers) ----
                if ( ! empty( $value['messages'] ) ) {
                    foreach ( $value['messages'] as $msg ) {
                        $this->process_incoming( $msg, $value['metadata'] ?? [], $payload );
                    }
                }
            }
        }

        return new WP_REST_Response( 'ok', 200 );
    }

    // ------------------------------------------------------------------ //
    //  Process a status update (sent / delivered / read / failed)
    // ------------------------------------------------------------------ //

    private function process_status( array $status_obj, array $raw_payload ) {
        $wamid     = $status_obj['id']        ?? '';
        $status    = $status_obj['status']    ?? '';   // sent|delivered|read|failed
        $timestamp = $status_obj['timestamp'] ?? '';
        $recipient = $status_obj['recipient_id'] ?? '';

        // Capture Meta's error reason when status is failed
        $error_reason = '';
        if ( 'failed' === $status && ! empty( $status_obj['errors'][0]['message'] ) ) {
            $error_reason = $status_obj['errors'][0]['message'];
            if ( ! empty( $status_obj['errors'][0]['error_data']['details'] ) ) {
                $error_reason .= ' — ' . $status_obj['errors'][0]['error_data']['details'];
            }
        }

        // Map Meta status → our DB status
        $db_status_map = [
            'sent'      => 'sent',
            'delivered' => 'delivered',
            'read'      => 'read',
            'failed'    => 'failed',
        ];
        $db_status = $db_status_map[ $status ] ?? $status;

        // Update the message log row that matches this wamid
        if ( $wamid ) {
            WAI_Log::update_status_by_msg_id( $wamid, $db_status, $error_reason );
        }

        // Log the raw webhook event
        WAI_Webhook_Log::insert( 'status_update', [
            'wamid'     => $wamid,
            'status'    => $status,
            'recipient' => $recipient,
            'timestamp' => $timestamp,
        ], wp_json_encode( $raw_payload ) );

        // Fire a WordPress action so other plugins/themes can hook in
        do_action( 'wai_message_status_updated', $wamid, $db_status, $status_obj );
    }

    // ------------------------------------------------------------------ //
    //  Process an incoming customer message
    // ------------------------------------------------------------------ //

    private function process_incoming( array $msg, array $metadata, array $raw_payload ) {
        $from      = $msg['from']      ?? '';
        $wamid     = $msg['id']        ?? '';
        $type      = $msg['type']      ?? 'text';
        $timestamp = $msg['timestamp'] ?? '';
        $text      = '';

        if ( 'text' === $type ) {
            $text = $msg['text']['body'] ?? '';
        } elseif ( 'image' === $type ) {
            $text = '[Image received]';
        } elseif ( 'audio' === $type ) {
            $text = '[Audio received]';
        } elseif ( 'document' === $type ) {
            $text = '[Document: ' . ( $msg['document']['filename'] ?? 'file' ) . ']';
        } else {
            $text = '[' . ucfirst( $type ) . ' received]';
        }

        WAI_Webhook_Log::insert( 'incoming_message', [
            'wamid'     => $wamid,
            'from'      => $from,
            'type'      => $type,
            'text'      => $text,
            'timestamp' => $timestamp,
        ], wp_json_encode( $raw_payload ) );

        // Also record in Message Log so incoming messages appear alongside outgoing ones.
        WAI_Log::insert( [
            'recipient'  => $from,
            'message'    => $text,
            'status'     => 'received',
            'msg_id'     => $wamid,
            'error'      => '',
            'sent_by'    => 0, // 0 = incoming from customer
        ] );

        // Fire action so other code can respond to incoming messages
        do_action( 'wai_incoming_message', $from, $text, $msg, $metadata );
    }

    // ------------------------------------------------------------------ //
    //  Signature verification
    // ------------------------------------------------------------------ //

    private function verify_signature( $body, $signature_header ) {
        $app_secret = WAI_Settings::get( 'app_secret' );

        // If no app secret configured, skip verification
        if ( empty( $app_secret ) ) {
            return true;
        }

        // If Meta didn't send a signature, skip (shouldn't happen in prod)
        if ( empty( $signature_header ) ) {
            return true;
        }

        $expected = 'sha256=' . hash_hmac( 'sha256', $body, $app_secret );
        return hash_equals( $expected, $signature_header );
    }
}
