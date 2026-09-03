<?php
/**
 * Booking Module - Delete Action Handler
 *
 * Path: admin/booking/delete-booking.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bsc_process_booking_deletion() {
    // Only intercept if we are on the booking page with delete subtab
    $page   = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
    $subtab = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : '';

    if ( 'bsc-booking' !== $page || 'delete' !== $subtab ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_error=unauthorized' ) );
        exit;
    }

    global $wpdb;
    $table_bookings = $wpdb->prefix . 'bsc_bookings';
    $del_id         = absint( $_GET['id'] ?? 0 );
    $nonce          = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

    if ( $del_id > 0 && wp_verify_nonce( $nonce, 'bsc_delete_booking_' . $del_id ) ) {
        $deleted = $wpdb->delete(
            $table_bookings,
            array( 'id' => $del_id ),
            array( '%d' )
        );

        if ( false !== $deleted && 0 !== $deleted ) {
            wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_deleted=true' ) );
            exit;
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_error=db_failed' ) );
            exit;
        }
    } else {
        wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_error=security_failed' ) );
        exit;
    }
}
add_action( 'admin_init', 'bsc_process_booking_deletion' );