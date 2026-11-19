<?php
/**
 * Customer Module - Delete Action Handler
 *
 * Path: admin/customers/delete-customer.php
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
$table_customers = $wpdb->prefix . 'nmbp_customers';
$customer_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( $customer_id <= 0 ) {
	wp_die(
		esc_html__( 'Invalid customer identifier.', 'nahian-movie-booking-plugin' ),
		esc_html__( 'Error', 'nahian-movie-booking-plugin' ),
		array( 'response' => 400 )
	);
}

// Strict nonce verification matching master controller and directory link.
check_admin_referer( 'nmbp_delete_customer_' . $customer_id, '_nmbp_nonce' );

$deleted      = $wpdb->delete(
	$table_customers,
	array( 'id' => $customer_id ),
	array( '%d' )
);
$redirect_arg = ( false !== $deleted && 0 !== $deleted ) ? 'customer_deleted=true' : 'customer_error=db_failed';

wp_safe_redirect( admin_url( 'admin.php?page=nmbp-customers&subtab=all&' . $redirect_arg ) );
exit;