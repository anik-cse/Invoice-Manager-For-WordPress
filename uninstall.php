<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When a site administrator uninstalls the plugin, we thoroughly clean
 * up all invoices and associated meta data from the database.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Ensure the post type is recognized
$args = array(
    'post_type'      => 'invoice',
    'posts_per_page' => -1,
    'post_status'    => 'any',
);

$invoices = get_posts( $args );

if ( ! empty( $invoices ) ) {
    foreach ( $invoices as $invoice ) {
        wp_delete_post( $invoice->ID, true ); // Force delete, skip trash
    }
}
