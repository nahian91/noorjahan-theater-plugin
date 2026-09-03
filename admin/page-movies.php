<?php
/**
 * Movie Module - Master Controller
 *
 * Path: admin/page-movies.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Delete & Form Submissions early on admin_init to prevent header errors
add_action( 'admin_init', 'bsc_movie_handle_actions' );
function bsc_movie_handle_actions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bsc_control_movies';
    $page  = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

    if ( 'bsc-movies' !== $page ) {
        return;
    }

    $subtab   = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : '';
    $movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Handle Delete
    if ( 'delete' === $subtab && $movie_id > 0 ) {
        if ( file_exists( BSC_PLUGIN_PATH . 'admin/movies/delete-movie.php' ) ) {
            require BSC_PLUGIN_PATH . 'admin/movies/delete-movie.php';
            exit;
        }
    }

    // Handle POST Submissions (Add / Edit Movie)
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['bsc_action'] ) ) {
        if ( file_exists( BSC_PLUGIN_PATH . 'admin/movies/add-edit-movie.php' ) ) {
            require BSC_PLUGIN_PATH . 'admin/movies/add-edit-movie.php';
        }
    }
}

// 2. Main Page Render Callback
function bsc_render_movies_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bsc_control_movies';

    $subtab   = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
    $movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Fetch Movies Data for the views
    $movies = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
    if ( ! is_array( $movies ) ) {
        $movies = array();
    }

    // Open Layout Shell
    bsc_render_page_shell_open( 'bsc-movies' );

    // Render Subtabs & Navigation Alerts Bar
    if ( file_exists( BSC_PLUGIN_PATH . 'admin/movies/tabs-movies.php' ) ) {
        require BSC_PLUGIN_PATH . 'admin/movies/tabs-movies.php';
    }

    // Route Subviews Safely
    if ( 'view' === $subtab && $movie_id > 0 ) {
        require BSC_PLUGIN_PATH . 'admin/movies/view-movie.php';
    } elseif ( 'add' === $subtab || 'edit' === $subtab ) {
        require BSC_PLUGIN_PATH . 'admin/movies/add-edit-movie.php';
    } else {
        require BSC_PLUGIN_PATH . 'admin/movies/all-movies.php';
    }

    bsc_render_page_shell_close();
}