<?php
/**
 * Movie Module - Delete Action Handler
 *
 * Path: admin/movies/delete-movie.php
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
$table    = $wpdb->prefix . 'nmbp_control_movies';
$movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( $movie_id <= 0 ) {
	wp_die(
		esc_html__( 'Invalid movie identifier.', 'nahian-movie-booking-plugin' ),
		esc_html__( 'Error', 'nahian-movie-booking-plugin' ),
		array( 'response' => 400 )
	);
}

// Strict nonce verification matching master controller and directory links.
check_admin_referer( 'nmbp_delete_movie_' . $movie_id, '_nmbp_nonce' );

$deleted      = $wpdb->delete(
	$table,
	array( 'id' => $movie_id ),
	array( '%d' )
);
$redirect_arg = ( false !== $deleted && 0 !== $deleted ) ? 'movie_deleted=true' : 'movie_error=db_failed';

wp_safe_redirect( admin_url( 'admin.php?page=nmbp-movies&subtab=all&' . $redirect_arg ) );
exit;