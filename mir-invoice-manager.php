<?php
/**
 * Plugin Name: Mir Invoice Manager (Frontend)
 * Description: A full-featured frontend invoice management system for WordPress.
 * Version: 1.0.0
 * Author: Mir M.
 * Text Domain: mir-invoice-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MIM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MIM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MIM_VERSION', '1.0.0' );

// Include core files
require_once MIM_PLUGIN_DIR . 'includes/class-security.php';
require_once MIM_PLUGIN_DIR . 'includes/class-post-type.php';
require_once MIM_PLUGIN_DIR . 'includes/class-ajax.php';
require_once MIM_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once MIM_PLUGIN_DIR . 'includes/class-pdf.php';
require_once MIM_PLUGIN_DIR . 'includes/class-admin.php';

// Initialize the plugin
function mim_init_plugin() {
    new MIM_Security();
    new MIM_Post_Type();
    new MIM_Ajax();
    new MIM_Shortcodes();
    new MIM_PDF();
    if ( is_admin() ) {
        new MIM_Admin();
    }
}
add_action( 'plugins_loaded', 'mim_init_plugin' );

// Enqueue scripts and styles
function mim_enqueue_scripts() {
    wp_enqueue_style( 'mim-style', MIM_PLUGIN_URL . 'assets/css/style.css', array(), MIM_VERSION );
    
    wp_enqueue_script( 'mim-script', MIM_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), MIM_VERSION, array( 'in_footer' => true, 'strategy'  => 'defer' ) );
    
    wp_localize_script( 'mim-script', 'mim_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'mim_frontend_nonce' )
    ));
}
add_action( 'wp_enqueue_scripts', 'mim_enqueue_scripts' );

// Flush rewrite rules on activation
register_activation_hook( __FILE__, 'mim_activate' );
function mim_activate() {
    // 1. Register the post type so the rules can be flushed
    require_once MIM_PLUGIN_DIR . 'includes/class-post-type.php';
    $post_type = new MIM_Post_Type();
    $post_type->register_invoice_cpt();
    
    // 2. Clear the permalinks
    flush_rewrite_rules();

    // 3. Auto-create Application Page if it doesn't exist
    $page_slug = 'invoice-manager';
    $page = get_page_by_path( $page_slug );
    
    if ( ! $page ) {
        wp_insert_post( array(
            'post_title'   => 'Invoice Manager',
            'post_name'    => $page_slug,
            'post_content' => '[mir_invoice_manager]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );
    }
}

// Flush rewrite rules on deactivation
register_deactivation_hook( __FILE__, 'mim_deactivate' );
function mim_deactivate() {
    flush_rewrite_rules();
}
