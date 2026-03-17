<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MIM_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_mim_save_invoice', array( $this, 'save_invoice' ) );
        add_action( 'wp_ajax_mim_delete_invoice', array( $this, 'delete_invoice' ) );
        add_action( 'wp_ajax_mim_update_status', array( $this, 'update_status' ) );
    }

    public function save_invoice() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        MIM_Security::verify_nonce( $nonce );
        MIM_Security::check_user_logged_in();

        $invoice_id   = isset( $_POST['invoice_id'] ) ? intval( $_POST['invoice_id'] ) : 0;
        $client_name  = sanitize_text_field( $_POST['client_name'] ?? '' );
        $client_email = sanitize_email( $_POST['client_email'] ?? '' );
        $client_phone = sanitize_text_field( $_POST['client_phone'] ?? '' );
        $client_addr  = sanitize_textarea_field( $_POST['client_addr'] ?? '' );
        
        $sender_name  = sanitize_text_field( $_POST['sender_name'] ?? '' );
        $sender_email = sanitize_email( $_POST['sender_email'] ?? '' );
        $sender_phone = sanitize_text_field( $_POST['sender_phone'] ?? '' );
        $sender_addr  = sanitize_textarea_field( $_POST['sender_addr'] ?? '' );

        $payment_method = sanitize_text_field( $_POST['payment_method'] ?? '' );
        $payment_currency = sanitize_text_field( $_POST['payment_currency'] ?? 'USD' );

        $logo_url     = esc_url_raw( $_POST['logo_url'] ?? '' );
        $due_date     = sanitize_text_field( $_POST['due_date'] ?? '' );
        $notes        = sanitize_textarea_field( $_POST['notes'] ?? '' );
        
        if ( empty( $client_name ) || empty( $client_email ) ) {
            wp_send_json_error( array( 'message' => __( 'Client Name and Email are required.', 'mir-invoice-manager' ) ) );
        }

        $items = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();
        $sanitized_items = array();
        $total = 0;

        foreach ( $items as $item ) {
            $name  = sanitize_text_field( $item['name'] ?? '' );
            $type  = sanitize_text_field( $item['type'] ?? 'fixed' );
            $qty   = floatval( $item['qty'] ?? 0 );
            $price = floatval( $item['price'] ?? 0 );
            
            if ( $name && $qty > 0 && $price >= 0 ) {
                $sanitized_items[] = array(
                    'name'  => $name,
                    'type'  => $type,
                    'qty'   => $qty,
                    'price' => $price,
                );
                $total += ( $qty * $price );
            }
        }

        $post_data = array(
            'post_title'  => sprintf( __( 'Invoice - %s - %s', 'mir-invoice-manager' ), $client_name, wp_date('Y-m-d') ),
            'post_type'   => 'invoice',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        );

        if ( $invoice_id > 0 ) {
            if ( ! MIM_Security::user_owns_invoice( $invoice_id ) ) {
                wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this invoice.', 'mir-invoice-manager' ) ) );
            }
            $post_data['ID'] = $invoice_id;
            $post_id = wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
        }

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save invoice.', 'mir-invoice-manager' ) ) );
        }

        update_post_meta( $post_id, '_client_name', $client_name );
        update_post_meta( $post_id, '_client_email', $client_email );
        update_post_meta( $post_id, '_client_phone', $client_phone );
        update_post_meta( $post_id, '_client_addr', $client_addr );
        
        update_post_meta( $post_id, '_sender_name', $sender_name );
        update_post_meta( $post_id, '_sender_email', $sender_email );
        update_post_meta( $post_id, '_sender_phone', $sender_phone );
        update_post_meta( $post_id, '_sender_addr', $sender_addr );

        update_post_meta( $post_id, '_payment_method', $payment_method );
        update_post_meta( $post_id, '_payment_currency', $payment_currency );

        update_post_meta( $post_id, '_logo_url', $logo_url );
        update_post_meta( $post_id, '_due_date', $due_date );
        update_post_meta( $post_id, '_notes', $notes );
        update_post_meta( $post_id, '_items', $sanitized_items );
        update_post_meta( $post_id, '_total', $total );
        
        $status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'Draft';
        if ( ! get_post_meta( $post_id, '_status', true ) || isset( $_POST['status'] ) ) {
            update_post_meta( $post_id, '_status', $status );
        }

        wp_send_json_success( array( 
            'message' => __( 'Invoice saved successfully.', 'mir-invoice-manager' ),
            'invoice_id' => $post_id
        ) );
    }

    public function delete_invoice() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        MIM_Security::verify_nonce( $nonce );
        MIM_Security::check_user_logged_in();

        $invoice_id = intval( $_POST['invoice_id'] ?? 0 );
        
        if ( ! MIM_Security::user_owns_invoice( $invoice_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mir-invoice-manager' ) ) );
        }

        wp_trash_post( $invoice_id );
        wp_send_json_success( array( 'message' => __( 'Invoice deleted.', 'mir-invoice-manager' ) ) );
    }

    public function update_status() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        MIM_Security::verify_nonce( $nonce );
        MIM_Security::check_user_logged_in();

        $invoice_id = intval( $_POST['invoice_id'] ?? 0 );
        $status     = sanitize_text_field( $_POST['status'] ?? '' );
        
        $allowed_statuses = array( 'Draft', 'Sent', 'Paid', 'Overdue' );
        if ( ! in_array( $status, $allowed_statuses, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid status.', 'mir-invoice-manager' ) ) );
        }

        if ( ! MIM_Security::user_owns_invoice( $invoice_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mir-invoice-manager' ) ) );
        }

        update_post_meta( $invoice_id, '_status', $status );
        wp_send_json_success( array( 'message' => __( 'Status updated.', 'mir-invoice-manager' ) ) );
    }
}
