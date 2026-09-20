<?php
/**
 * Customer Module - Delete Action Handler
 *
 * Path: admin/customers/delete-customer.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
}

global $wpdb;
$table_customers = $wpdb->prefix . 'bsc_customers';
$customer_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( $customer_id <= 0 ) {
    wp_die(
        esc_html__( 'Invalid customer identifier.', 'bcs-screen-manager' ),
        esc_html__( 'Error', 'bcs-screen-manager' ),
        array( 'response' => 400 )
    );
}

// Strict nonce verification matching master controller and directory link
check_admin_referer( 'bsc_delete_customer_' . $customer_id, '_bsc_nonce' );

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