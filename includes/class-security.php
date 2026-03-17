<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MIM_Security {
    public static function verify_nonce( $nonce, $action = 'mim_frontend_nonce' ) {
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'mir-invoice-manager' ) ) );
            exit;
        }
    }

    public static function check_user_logged_in() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to perform this action.', 'mir-invoice-manager' ) ) );
            exit;
        }
    }
    
    public static function user_owns_invoice( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'invoice' || (int) $post->post_author !== get_current_user_id() ) {
            return false;
        }
        return true;
    }
}
