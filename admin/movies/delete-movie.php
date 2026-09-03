<?php
/**
 * Movie Module - Delete Action Handler
 *
 * Path: admin/movies/delete-movie.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
}

global $wpdb;
$table    = $wpdb->prefix . 'bsc_control_movies';
$movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$nonce    = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

if ( $movie_id > 0 && wp_verify_nonce( $nonce, 'bsc_delete_movie_' . $movie_id ) ) {
    $deleted = $wpdb->delete(
        $table,
        array( 'id' => $movie_id ),
        array( '%d' )
    );

    if ( false !== $deleted ) {
        wp_safe_redirect( admin_url( 'admin.php?page=bsc-movies&subtab=all&movie_deleted=true' ) );
        exit;
    } else {
        wp_safe_redirect( admin_url( 'admin.php?page=bsc-movies&subtab=all&movie_error=db_failed' ) );
        exit;
    }
} else {
    wp_die(
        esc_html__( 'Security check failed or invalid movie identifier.', 'bsc-cinema-manager' ),
        esc_html__( 'Error', 'bsc-cinema-manager' ),
        array( 'response' => 403 )
    );
}