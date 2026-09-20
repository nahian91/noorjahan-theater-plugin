<?php
/**
 * Movie Module - Delete Action Handler
 *
 * Path: admin/movies/delete-movie.php
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
$table    = $wpdb->prefix . 'bsc_control_movies';
$movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( $movie_id <= 0 ) {
    wp_die(
        esc_html__( 'Invalid movie identifier.', 'bcs-screen-manager' ),
        esc_html__( 'Error', 'bcs-screen-manager' ),
        array( 'response' => 400 )
    );
}

// Strict nonce verification matching master controller and directory links
check_admin_referer( 'bsc_delete_movie_' . $movie_id, '_bsc_nonce' );

$deleted = $wpdb->delete(
    $table,
    array( 'id' => $movie_id ),
    array( '%d' )
);

if ( false !== $deleted && 0 !== $deleted ) {
    wp_safe_redirect( admin_url( 'admin.php?page=bsc-movies&subtab=all&movie_deleted=true' ) );
    exit;
} else {
    wp_safe_redirect( admin_url( 'admin.php?page=bsc-movies&subtab=all&movie_error=db_failed' ) );
    exit;
}