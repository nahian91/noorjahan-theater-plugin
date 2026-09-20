<?php
/**
 * Users / Staff Module - Delete Action Handler
 *
 * Path: admin/users/delete-user.php
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
$table_staff = $wpdb->prefix . 'bsc_staff_crew';
$staff_id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( $staff_id <= 0 ) {
    wp_die(
        esc_html__( 'Invalid staff identifier.', 'bcs-screen-manager' ),
        esc_html__( 'Error', 'bcs-screen-manager' ),
        array( 'response' => 400 )
    );
}

// Verify strict nonce matching the master controller and view links
check_admin_referer( 'bsc_delete_user_' . $staff_id, '_bsc_nonce' );

$deleted = $wpdb->delete(
    $table_staff,
    array( 'id' => $staff_id ),
    array( '%d' )
);

if ( false !== $deleted ) {
    wp_safe_redirect( admin_url( 'admin.php?page=bsc-users&subtab=all&staff_deleted=true' ) );
    exit;
} else {
    wp_safe_redirect( admin_url( 'admin.php?page=bsc-users&subtab=all&staff_error=db_failed' ) );
    exit;
}