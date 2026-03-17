<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MIM_Post_Type {
    public function __construct() {
        add_action( 'init', array( $this, 'register_invoice_cpt' ) );
    }

    public function register_invoice_cpt() {
        $labels = array(
            'name'                  => _x( 'Invoices', 'Post type general name', 'mir-invoice-manager' ),
            'singular_name'         => _x( 'Invoice', 'Post type singular name', 'mir-invoice-manager' ),
            'menu_name'             => _x( 'Invoices', 'Admin Menu text', 'mir-invoice-manager' ),
            'add_new'               => __( 'Add New', 'mir-invoice-manager' ),
            'add_new_item'          => __( 'Add New Invoice', 'mir-invoice-manager' ),
            'new_item'              => __( 'New Invoice', 'mir-invoice-manager' ),
            'edit_item'             => __( 'Edit Invoice', 'mir-invoice-manager' ),
            'view_item'             => __( 'View Invoice', 'mir-invoice-manager' ),
            'all_items'             => __( 'All Invoices', 'mir-invoice-manager' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false, // Only frontend management via Shortcodes
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_icon'          => 'dashicons-media-document',
            'supports'           => array( 'title', 'author' ),
        );

        register_post_type( 'invoice', $args );
    }
}
