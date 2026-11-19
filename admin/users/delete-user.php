<?php
/**
 * Users / Staff Module - Delete Action Handler
 *
 * Path: admin/users/delete-user.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
	wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
}

global $wpdb;
$table_staff = $wpdb->prefix . 'nmbp_staff_crew';
$staff_id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( $staff_id <= 0 ) {
	wp_die(
		esc_html__( 'Invalid staff identifier.', 'nahian-movie-booking-plugin' ),
		esc_html__( 'Error', 'nahian-movie-booking-plugin' ),
		array( 'response' => 400 )
	);
}

// Verify strict nonce matching the master controller and view links.
check_admin_referer( 'nmbp_delete_user_' . $staff_id, '_nmbp_nonce' );

$deleted      = $wpdb->delete(
	$table_staff,
	array( 'id' => $staff_id ),
	array( '%d' )
);
$redirect_arg = ( false !== $deleted && 0 !== $deleted ) ? 'staff_deleted=true' : 'staff_error=db_failed';

wp_safe_redirect( admin_url( 'admin.php?page=nmbp-users&subtab=all&' . $redirect_arg ) );
exit;