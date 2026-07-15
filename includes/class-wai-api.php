<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles all HTTP communication with the WhatsApp Business Cloud API.
 */
class WAI_API {

    const API_BASE = 'https://graph.facebook.com/v21.0/';

    /** @var string */
    private $access_token;

    /** @var string */
    private $phone_number_id;

    /** @var string */
    private $app_secret;

    public function __construct( $access_token, $phone_number_id, $app_secret = '' ) {
        $this->access_token    = sanitize_text_field( $access_token );
        $this->phone_number_id = sanitize_text_field( $phone_number_id );
        $this->app_secret      = sanitize_text_field( $app_secret );
    }

    /**
     * Send a plain-text message.
     *
     * @param string $to    Recipient phone number in E.164 format (e.g. +9779800000000).
     * @param string $text  Message body.
     * @return array|WP_Error  Decoded API response or WP_Error on failure.
     */
    public function send_text( $to, $text ) {
        $body = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->sanitize_phone( $to ),
            'type'              => 'text',
            'text'              => [ 'preview_url' => false, 'body' => $text ],
        ];
        return $this->request( $body );
    }

    /**
     * Send a template message.
     *
     * @param string $to            Recipient phone in E.164.
     * @param string $template_name Approved template name.
     * @param string $language_code e.g. "en_US".
     * @param array  $components    Optional template components (header/body params).
     * @return array|WP_Error
     */
    public function send_template( $to, $template_name, $language_code = 'en_US', $components = [] ) {
        $body = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->sanitize_phone( $to ),
            'type'              => 'template',
            'template'          => [
                'name'       => sanitize_text_field( $template_name ),
                'language'   => [ 'code' => sanitize_text_field( $language_code ) ],
                'components' => $components,
            ],
        ];
        return $this->request( $body );
    }

    /**
     * Register the business phone number with Cloud API.
     *
     * @param string $pin  6-digit two-step verification PIN.
     * @return array|WP_Error
     */
    public function register_phone( $pin ) {
        $url      = self::API_BASE . $this->phone_number_id . '/register';
        $response = wp_remote_post( $url, [
            'headers' => array_merge( $this->auth_headers(), [ 'Content-Type' => 'application/json' ] ),
            'body'    => wp_json_encode( [
                'messaging_product' => 'whatsapp',
                'pin'               => sanitize_text_field( $pin ),
            ] ),
            'timeout' => 20,
        ] );
        if ( is_wp_error( $response ) ) return $response;
        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! in_array( (int) $code, [ 200, 201 ], true ) ) {
            $msg = $data['error']['message'] ?? __( 'Unknown API error.', 'business-messaging-hub' );
            return new WP_Error( 'wai_register_error', $msg, [ 'code' => $data['error']['code'] ?? $code ] );
        }
        return $data;
    }

    /**
     * Deregister the business phone number from Cloud API.
     * @return array|WP_Error
     */
    public function deregister_phone() {
        $url      = self::API_BASE . $this->phone_number_id . '/deregister';
        $response = wp_remote_post( $url, [
            'headers' => array_merge( $this->auth_headers(), [ 'Content-Type' => 'application/json' ] ),
            'body'    => wp_json_encode( [] ),
            'timeout' => 20,
        ] );
        if ( is_wp_error( $response ) ) return $response;
        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! in_array( (int) $code, [ 200, 201 ], true ) ) {
            $msg = $data['error']['message'] ?? __( 'Unknown API error.', 'business-messaging-hub' );
            return new WP_Error( 'wai_deregister_error', $msg );
        }
        return $data;
    }

    /**
     * Fetch the status of a sent message from Meta.
     *
     * @param string $message_id  The wamid returned when the message was sent.
     * @return array|WP_Error
     */
    public function get_message_status( $message_id ) {
        $url = self::API_BASE . sanitize_text_field( $message_id ) . '?' . $this->proof_query();
        $response = wp_remote_get( $url, [
            'headers' => $this->auth_headers(),
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== (int) $code ) {
            $msg = $data['error']['message'] ?? __( 'Unknown API error.', 'business-messaging-hub' );
            return new WP_Error( 'wai_api_error', $msg );
        }

        return $data;
    }

    /**
     *
     * @return true|WP_Error
     */

    /**
     * Get phone number registration status and details from Meta.
     *
     * @return array|WP_Error
     */
    public function get_phone_status() {
        $proof = $this->proof_query();
        $fields = 'id,display_phone_number,verified_name,code_verification_status,quality_rating,platform_type,throughput,last_onboarded_time,account_mode';
        $url = self::API_BASE . $this->phone_number_id . '?fields=' . $fields . ( $proof ? '&' . $proof : '' );
        $response = wp_remote_get( $url, [
            'headers' => $this->auth_headers(),
            'timeout' => 15,
        ] );
        if ( is_wp_error( $response ) ) return $response;
        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 200 !== (int) $code ) {
            $msg = $data['error']['message'] ?? __( 'Unknown API error.', 'business-messaging-hub' );
            return new WP_Error( 'wai_status_error', $msg );
        }
        return $data;
    }

    public function verify_credentials() {
        $proof    = $this->proof_query();
        $url      = self::API_BASE . $this->phone_number_id . ( $proof ? '?' . $proof : '' );
        $response = wp_remote_get( $url, [
            'headers' => $this->auth_headers(),
            'timeout' => 15,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== (int) $code ) {
            $msg = $data['error']['message'] ?? __( 'Unknown API error.', 'business-messaging-hub' );
            return new WP_Error( 'wai_api_error', $msg );
        }

        return true;
    }

    // ------------------------------------------------------------------ //
    //  Private helpers
    // ------------------------------------------------------------------ //

    private function request( array $body ) {
        $proof    = $this->proof_query();
        $url      = self::API_BASE . $this->phone_number_id . '/messages' . ( $proof ? '?' . $proof : '' );
        $response = wp_remote_post( $url, [
            'headers' => array_merge( $this->auth_headers(), [ 'Content-Type' => 'application/json' ] ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 20,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! in_array( (int) $code, [ 200, 201 ], true ) ) {
            $msg = $data['error']['message'] ?? __( 'Unknown API error.', 'business-messaging-hub' );
            return new WP_Error( 'wai_send_error', $msg );
        }

        return $data;
    }

    private function auth_headers() {
        return [ 'Authorization' => 'Bearer ' . $this->access_token ];
    }

    /**
     * Build the appsecret_proof query string.
     * If no app_secret is set, returns an empty string (no proof appended).
     */
    private function proof_query() {
        if ( empty( $this->app_secret ) ) {
            return '';
        }
        $proof = hash_hmac( 'sha256', $this->access_token, $this->app_secret );
        return 'appsecret_proof=' . $proof;
    }

    private function sanitize_phone( $phone ) {
        // Strip everything except digits and leading +
        return preg_replace( '/[^\d+]/', '', $phone );
    }
}
