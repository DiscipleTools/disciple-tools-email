<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Disciple_Tools_Email_Endpoints
{
    public $permissions = [ 'access_contacts', 'dt_all_access_contacts', 'view_project_metrics' ];


    public function add_api_routes() {
        $namespace = 'dt-email/v1';

        $permission_callback = function( WP_REST_Request $request ){
            return $this->has_permission();
        };

        register_rest_route(
            $namespace, '/send', [
                'methods'  => 'POST',
                'callback' => [ $this, 'send_email' ],
                'permission_callback' => $permission_callback,
            ]
        );

        register_rest_route(
            $namespace, '/dt-public/receive', [
                'methods'  => 'POST',
                'callback' => [ $this, 'receive_email' ],
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            $namespace, '/query-stats', [
                'methods'  => 'GET',
                'callback' => [ $this, 'query_stats' ],
                'permission_callback' => $permission_callback,
            ]
        );
    }


    public function send_email( WP_REST_Request $request ) {
        $params = $request->get_params();
        $args = dt_recursive_sanitize_array( $params );

        if ( !isset( $args['contact_id'] ) && !DT_Posts::can_update( 'contacts', $args['contact_id'] ) ){
            return new WP_Error( __METHOD__, 'Missing required fields', [ 'status' => 400 ] );
        }

        if ( empty( $args['email'] ) || empty( $args['subject'] ) || empty( $args['message'] ) ) {
            return new WP_Error( __METHOD__, 'Missing required fields', [ 'status' => 400 ] );
        }

        $message = wp_kses_post( $params['message'] );

        $options = get_option( 'dt_email_settings' );
        $api_key = $options['key'] ?? '';
        if ( empty( $api_key ) || empty( $options['domain'] ) || empty( $options['email'] ) ){
            return new WP_Error( __METHOD__, 'Missing email setup', [ 'status' => 400 ] );
        }
        $sent = wp_remote_post( 'https://api:' . $api_key . '@api.mailgun.net/v3/' . $options['domain'] . '/messages', [
            'body' => [
                'from' => '<' . $options['email'] . '>',
                'to' => $args['email'],
                'subject' => $args['subject'],
                'text' => $message,
            ]
        ] );
        if ( is_wp_error( $sent ) ) {
            return new WP_Error( __METHOD__, 'Error sending email', [ 'status' => 400 ] );
        }


        DT_Posts::add_post_comment(
            'contacts',
            $args['contact_id'],
            'Email sent to: ' . $args['email'] . "\n" . $message,
            'email',
            [],
            true,
            true
        );
        return true;
    }


    public function forward_to_list( $sender, $recipient, $email_params ){
        $parts = explode( '_', $recipient );
        $user_id = null;
        $query_hash = null;

        if ( is_numeric( str_replace( 'fw-user-', '', $parts[0] ) ) ){
            $user_id = str_replace( 'fw-user-', '', $parts[0] );
        }
        if ( isset( explode( '@', $parts[1] )[1] ) ){
            $query_hash = explode( '@', $parts[1] )[0];
        }
        if ( empty( $user_id ) || empty( $query_hash ) ){
            return new WP_Error( __METHOD__, 'Missing required fields', [ 'status' => 400 ] );
        }
        $query = get_user_meta( $user_id, 'email_query_' . $query_hash, true );
        if ( empty( $query ) ){
            return new WP_Error( __METHOD__, 'Missing configuration', [ 'status' => 400 ] );
        }

        wp_set_current_user( $user_id );
        $contacts = DT_Posts::search_viewable_post( 'contacts', $query );
        foreach ( $contacts['posts'] as $contact ){
//            @todo get correct email
            if ( isset( $contact->contact_email[0]['value'] ) ){
                $email = $contact->contact_email[0]['value'];
                wp_mail( $email, $email_params['subject'], $email_params['body-html'] );

            }

            DT_Posts::add_post_comment(
                'contacts',
                $contact->ID,
                'Email sent from: ' . $sender . "\n" . wp_kses_post( $email_params['stripped-text'] ),
                'email',
                [],
                true,
                true
            );
        }


        return true;
    }

    public function receive_email( WP_REST_Request $request ){
        $params = $request->get_params();
        dt_write_log( $params );

        if ( !isset( $params['sender'] ) || !isset( $params['recipient'] ) || !isset( $params['subject'] ) || !isset( $params['body-plain'] ) ){
            return new WP_Error( __METHOD__, 'Missing required fields', [ 'status' => 400 ] );
        }

        $args = dt_recursive_sanitize_array( $params );
        $sender = $args['sender'];
        $recipient = $args['recipient'];
        $subject = $args['subject'];
        $body_plain = wp_kses_post( $params['body-plain'] );
        $body_plain_without_quotes = wp_kses_post( $params['stripped-text'] ?? '' );

        if ( strpos( $recipient, 'fw-user' ) !== false ){
            return $this->forward_to_list( $sender, $recipient, $params );
        }

        $contact_id = self::find_contact_by_email_address( $sender, false );

        if ( empty( $contact_id ) ){
            $contact = DT_Posts::create_post( 'contacts', [
                'title' => $sender,
                'contact_email' => [ 'values' => [ [ 'value' => $sender ] ] ],
            ], false, false );
            if ( !is_wp_error( $contact ) ){
                $contact_id = $contact['ID'];
            }
        }
        $comment = $sender . ' send an email to: ' . $recipient . "\n";
        $comment .= 'Subject: ' . $subject . "\n";
        $comment .= 'Body: ' . $body_plain . "\n";


        DT_Posts::add_post_comment(
            'contacts',
            $contact_id,
            $comment,
            'email',
            [],
            false,
            false
        );

        return true;
    }

    public function query_stats( WP_REST_Request $request ){
        $params = $request->get_params();
        if ( empty( $params['query'] ) ){
            return new WP_Error( __METHOD__, 'Missing required fields', [ 'status' => 400 ] );
        }
        $query = $params['query'];
        $all_contacts = DT_Posts::search_viewable_post( 'contacts', $query, true );
        $query['contact_email'] = [ '@' ];
        $contacts_with_email = DT_Posts::search_viewable_post( 'contacts', $query, true );

        $query_string = JSON_encode( $query );

        $email_hash = md5( $query_string );

        update_user_meta( get_current_user_id(), 'email_query_' . $email_hash, $query );

        return [
            'total' => $all_contacts['total'],
            'with_email' => $contacts_with_email['total'],
            'email_address' => 'fw-user-' . get_current_user_id() . '_' . $email_hash . '@mail.gospelambition.org',
        ];
    }

    public static function find_contact_by_email_address( $email_address, $check_permissions = true ){
        if ( empty( $email_address ) ){
            return false;
        }

        $contacts_search = DT_Posts::search_viewable_post( 'contacts', [
            'sort' => 'overall_status',
            'fields' => [ 'contact_email' => [ $email_address ] ]
        ], $check_permissions );
        $contact_id = null;
        if ( sizeof( $contacts_search['posts'] ) > 1 ){
            $contact_id = $contacts_search['posts'][0]->ID;
        } elseif ( sizeof( $contacts_search['posts'] ) === 1 ){
            $contact_id = $contacts_search['posts'][0]->ID;
        }
        return $contact_id;
    }


    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }
    public function has_permission(){
        $pass = false;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }
}
Disciple_Tools_Email_Endpoints::instance();
