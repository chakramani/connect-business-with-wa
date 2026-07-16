<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles incoming WhatsApp Cloud API webhook requests from Meta.
 *
 * Webhook URL:  https://yoursite.com/wp-json/wabmh/v1/webhook
 * Verify Token: Set in plugin Settings → Webhook
 */
class WABMH_Webhook {

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
        // permission_callback is intentionally '__return_true' on both routes:
        // Meta calls this endpoint anonymously and cannot supply a WP nonce or
        // cookie, so auth cannot be done via WP's normal capability checks.
        // Authenticity is instead enforced inside the callbacks:
        //  - GET  (handle_verify) requires the configured verify_token to match.
        //  - POST (handle_event)  requires a valid HMAC-SHA256 signature
        //    (X-Hub-Signature-256, checked with hash_equals()) computed with the
        //    app secret; requests are rejected outright if the secret is empty,
        //    the header is missing, or the signature doesn't match.
        register_rest_route( 'wabmh/v1', '/webhook', [
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

        $saved_token = WABMH_Settings::get( 'webhook_verify_token' );

        if ( 'subscribe' === $mode && ! empty( $saved_token ) && hash_equals( (string) $saved_token, $token ) ) {
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

        // Signature verification is mandatory: reject if the app secret isn't
        // configured, if Meta didn't send a signature, or if it doesn't match.
        // The raw, unsanitized $body is required here (and when logging a
        // failure below) because HMAC verification must run over the exact
        // bytes Meta signed.
        if ( ! $this->verify_signature( $body, $request->get_header( 'x_hub_signature_256' ) ) ) {
            // Intentionally raw/unsanitized: this is the one exception carved out for
            // logging failed-signature attempts, so an admin can diff the exact bytes
            // Meta sent against what verify_signature() computed. It is never treated
            // as trusted data afterwards (no further processing happens on this path),
            // and every place that later displays it (webhook-log.php) escapes it with
            // esc_attr()/esc_html() before output. Capped defensively so a malicious
            // sender can't bloat the log table with an oversized request body.
            WABMH_Webhook_Log::insert( 'signature_failed', [], substr( $body, 0, 20000 ) );
            return new WP_REST_Response( 'Forbidden', 403 );
        }

        $decoded = json_decode( $body, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_REST_Response( 'Bad Request', 400 );
        }

        // json_decode() only parses JSON — it does NOT sanitize the values.
        // Everything below this point must use the sanitized copy, never $decoded.
        $payload = $this->sanitize_payload( $decoded );

        if ( empty( $payload['entry'] ) || ! is_array( $payload['entry'] ) ) {
            return new WP_REST_Response( 'ok', 200 );
        }

        foreach ( $payload['entry'] as $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }
            foreach ( $entry['changes'] ?? [] as $change ) {
                $value = is_array( $change ) ? ( $change['value'] ?? [] ) : [];
                $value = is_array( $value ) ? $value : [];

                // ---- Delivery / read status updates ----
                if ( ! empty( $value['statuses'] ) && is_array( $value['statuses'] ) ) {
                    foreach ( $value['statuses'] as $status_obj ) {
                        if ( is_array( $status_obj ) ) {
                            $this->process_status( $status_obj, $payload );
                        }
                    }
                }

                // ---- Incoming messages (from customers) ----
                if ( ! empty( $value['messages'] ) && is_array( $value['messages'] ) ) {
                    foreach ( $value['messages'] as $msg ) {
                        if ( is_array( $msg ) ) {
                            $this->process_incoming( $msg, is_array( $value['metadata'] ?? null ) ? $value['metadata'] : [], $payload );
                        }
                    }
                }
            }
        }

        return new WP_REST_Response( 'ok', 200 );
    }

    // ------------------------------------------------------------------ //
    //  Recursively sanitize a decoded JSON structure
    // ------------------------------------------------------------------ //

    /**
     * json_decode() returns raw, untrusted scalars/arrays — it performs no
     * sanitization. Recursively sanitize every string value in the decoded
     * webhook payload before any of it is used, stored, or passed to actions.
     *
     * @param mixed $data Decoded JSON value (array, string, scalar, etc.).
     * @return mixed Sanitized value of the same shape.
     */
    private function sanitize_payload( $data ) {
        if ( is_array( $data ) ) {
            $clean = [];
            foreach ( $data as $key => $value ) {
                $clean_key           = is_string( $key ) ? sanitize_key( $key ) : $key;
                $clean[ $clean_key ] = $this->sanitize_payload( $value );
            }
            return $clean;
        }

        if ( is_string( $data ) ) {
            return sanitize_text_field( wp_unslash( $data ) );
        }

        // Numbers, booleans, null pass through unchanged.
        return $data;
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
        if ( 'failed' === $status && is_array( $status_obj['errors'] ?? null ) && is_array( $status_obj['errors'][0] ?? null ) ) {
            $error_reason = $status_obj['errors'][0]['message'] ?? '';
            $error_details = is_array( $status_obj['errors'][0]['error_data'] ?? null )
                ? ( $status_obj['errors'][0]['error_data']['details'] ?? '' )
                : '';
            if ( $error_details ) {
                $error_reason .= ' — ' . $error_details;
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

        // Replay protection: Meta may re-deliver the same event on retry.
        // Skip work we've already done for this exact wamid + status pair (a
        // message legitimately gets one status_update event per transition
        // -- sent, then delivered, then read -- so we key the dedup check on
        // status too, not just the wamid, otherwise later legitimate
        // transitions would be silently dropped).
        if ( $wamid && WABMH_Webhook_Log::status_event_exists( $wamid, $status ) ) {
            return;
        }

        // Update the message log row that matches this wamid
        if ( $wamid ) {
            WABMH_Log::update_status_by_msg_id( $wamid, $db_status, $error_reason );
        }

        // Log the raw webhook event
        WABMH_Webhook_Log::insert( 'status_update', [
            'wamid'     => $wamid,
            'status'    => $status,
            'recipient' => $recipient,
            'timestamp' => $timestamp,
        ], wp_json_encode( $raw_payload ) );

        // Fire a WordPress action so other plugins/themes can hook in
        do_action( 'wabmh_message_status_updated', $wamid, $db_status, $status_obj );
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
            $doc_name = is_array( $msg['document'] ?? null ) ? ( $msg['document']['filename'] ?? 'file' ) : 'file';
            $text     = '[Document: ' . $doc_name . ']';
        } else {
            $text = '[' . ucfirst( $type ) . ' received]';
        }

        // Replay protection: skip if we've already recorded this exact
        // incoming message (Meta may re-deliver on retry).
        if ( $wamid && WABMH_Webhook_Log::wamid_exists( $wamid, 'incoming_message' ) ) {
            return;
        }

        WABMH_Webhook_Log::insert( 'incoming_message', [
            'wamid'     => $wamid,
            'from'      => $from,
            'type'      => $type,
            'text'      => $text,
            'timestamp' => $timestamp,
        ], wp_json_encode( $raw_payload ) );

        // Also record in Message Log so incoming messages appear alongside outgoing ones.
        WABMH_Log::insert( [
            'recipient'  => $from,
            'message'    => $text,
            'status'     => 'received',
            'msg_id'     => $wamid,
            'error'      => '',
            'sent_by'    => 0, // 0 = incoming from customer
        ] );

        // Fire action so other code can respond to incoming messages
        do_action( 'wabmh_incoming_message', $from, $text, $msg, $metadata );
    }

    // ------------------------------------------------------------------ //
    //  Signature verification
    // ------------------------------------------------------------------ //

    private function verify_signature( $body, $signature_header ) {
        $app_secret = WABMH_Settings::get( 'app_secret' );

        // No app secret configured means we cannot verify authenticity —
        // reject rather than silently trust the request.
        if ( empty( $app_secret ) ) {
            return false;
        }

        // Meta always sends X-Hub-Signature-256 when an app secret is set.
        // A missing header is rejected rather than treated as trusted.
        if ( empty( $signature_header ) ) {
            return false;
        }

        // Header is "sha256=<hex>"; compare with hash_equals() to avoid timing attacks.
        $expected = 'sha256=' . hash_hmac( 'sha256', $body, $app_secret );
        return hash_equals( $expected, (string) $signature_header );
    }
}
