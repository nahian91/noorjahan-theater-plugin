<?php
/**
 * Movie Module - Master Controller
 *
 * Path: admin/page-movies.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// 1. EARLY ACTION HANDLER (CSRF VERIFIED & SAFE REDIRECTS)
// =============================================================================
add_action( 'admin_init', 'bsc_movie_handle_actions' );

function bsc_movie_handle_actions() {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( 'bsc-movies' !== $page ) {
        return;
    }

    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
    }

    $subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';
    $movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Handle Delete Action with Strict Nonce Check
    if ( 'delete' === $subtab && $movie_id > 0 ) {
        check_admin_referer( 'bsc_delete_movie_' . $movie_id, '_bsc_nonce' );

        $delete_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/movies/delete-movie.php' : '';
        if ( ! empty( $delete_file ) && file_exists( $delete_file ) ) {
            require $delete_file;
            exit;
        }
    }

    // Handle POST Submissions (Add / Edit Movie)
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['bsc_action'] ) ) {
        check_admin_referer( 'bsc_movie_form_action', '_bsc_nonce' );

        $action        = sanitize_key( wp_unslash( $_POST['bsc_action'] ) );
        $valid_actions = array( 'add_movie', 'edit_movie' );

        if ( in_array( $action, $valid_actions, true ) ) {
            $action_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/movies/add-edit-movie.php' : '';
            if ( ! empty( $action_file ) && file_exists( $action_file ) ) {
                require $action_file;
                exit;
            }
        }
    }
}

// =============================================================================
// 2. MAIN PAGE RENDER CALLBACK
// =============================================================================
function bsc_render_movies_page() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bsc_control_movies';

    $subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
    $movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Query movies only when viewing the index listing to conserve memory
    $movies = array();
    if ( 'all' === $subtab ) {
        $per_page     = 30;
        $current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset       = ( $current_page - 1 ) * $per_page;

        // Fetch only columns needed for the listing table (omits heavy longtexts)
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, title, category, genre, duration, rating, age_rating, auditorium, badge_tag, release_date, poster_url 
                 FROM {$table} 
                 ORDER BY id DESC 
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        if ( is_array( $results ) ) {
            $movies = $results;
        }
    }

    // Open Layout Shell
    if ( function_exists( 'bsc_render_page_shell_open' ) ) {
        bsc_render_page_shell_open( 'bsc-movies' );
    }

    // Render Subtabs & Navigation Bar
    $tabs_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/movies/tabs-movies.php' : '';
    if ( ! empty( $tabs_file ) && file_exists( $tabs_file ) ) {
        require $tabs_file;
    }

    // Route Subviews Safely
    $view_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/movies/all-movies.php' : '';
    if ( 'view' === $subtab && $movie_id > 0 ) {
        $view_file = BSC_PLUGIN_PATH . 'admin/movies/view-movie.php';
    } elseif ( 'add' === $subtab || 'edit' === $subtab ) {
        $view_file = BSC_PLUGIN_PATH . 'admin/movies/add-edit-movie.php';
    }

    if ( ! empty( $view_file ) && file_exists( $view_file ) ) {
        require $view_file;
    } else {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'The requested movie view could not be loaded.', 'bcs-screen-manager' )
        );
    }

    // Close Layout Shell
    if ( function_exists( 'bsc_render_page_shell_close' ) ) {
        bsc_render_page_shell_close();
    }
}