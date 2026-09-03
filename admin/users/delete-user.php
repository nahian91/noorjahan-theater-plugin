<?php
/**
 * Users / Staff Module - Delete Action Handler
 *
 * Path: admin/users/delete-user.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
}

global $wpdb;
$table_staff = $wpdb->prefix . 'bsc_staff_crew';
$staff_id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$nonce       = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

if ( $staff_id > 0 && wp_verify_nonce( $nonce, 'bsc_delete_staff_' . $staff_id ) ) {
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
} else {
    wp_die(
        esc_html__( 'Security check failed or invalid staff identifier.', 'bsc-cinema-manager' ),
        esc_html__( 'Error', 'bsc-cinema-manager' ),
        array( 'response' => 403 )
    );
}