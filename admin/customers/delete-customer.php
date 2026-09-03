<?php
/**
 * Customer Module - Delete Action Handler
 *
 * Path: admin/customers/delete-customer.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
}

global $wpdb;
$table_customers = $wpdb->prefix . 'bsc_customers';
$customer_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$nonce           = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

if ( $customer_id > 0 && wp_verify_nonce( $nonce, 'bsc_delete_customer_' . $customer_id ) ) {
    $deleted = $wpdb->delete(
        $table_customers,
        array( 'id' => $customer_id ),
        array( '%d' )
    );

    if ( false !== $deleted ) {
        wp_safe_redirect( admin_url( 'admin.php?page=bsc-customers&subtab=all&customer_deleted=true' ) );
        exit;
    } else {
        wp_safe_redirect( admin_url( 'admin.php?page=bsc-customers&subtab=all&customer_error=db_failed' ) );
        exit;
    }
} else {
    wp_die(
        esc_html__( 'Security check failed or invalid customer identifier.', 'bsc-cinema-manager' ),
        esc_html__( 'Error', 'bsc-cinema-manager' ),
        array( 'response' => 403 )
    );
}