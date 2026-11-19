<?php
/**
 * Movie Module - Master Controller
 *
 * Path: admin/page-movies.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// 1. EARLY ACTION HANDLER (CSRF VERIFIED & SAFE REDIRECTS)
// =============================================================================
add_action( 'admin_init', 'nmbp_movie_handle_actions' );

function nmbp_movie_handle_actions() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'nmbp-movies' !== $page ) {
		return;
	}

	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
	}

	global $wpdb;
	$table       = $wpdb->prefix . 'nmbp_control_movies';
	$plugin_path = defined( 'NMBP_PLUGIN_PATH' ) ? NMBP_PLUGIN_PATH : plugin_dir_path( dirname( __FILE__ ) );

	$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';
	$movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

	// Handle Delete Action with Strict Nonce Check.
	if ( 'delete' === $subtab && $movie_id > 0 ) {
		check_admin_referer( 'nmbp_delete_movie_' . $movie_id, '_nmbp_nonce' );

		$delete_file = $plugin_path . 'admin/movies/delete-movie.php';
		if ( file_exists( $delete_file ) ) {
			require $delete_file;
			exit;
		}
	}

	// Handle POST Submissions (Add / Edit Movie).
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['nmbp_action'] ) ) {
		check_admin_referer( 'nmbp_movie_form_action', '_nmbp_nonce' );

		$action        = sanitize_key( wp_unslash( $_POST['nmbp_action'] ) );
		$valid_actions = array( 'add_movie', 'edit_movie' );

		if ( in_array( $action, $valid_actions, true ) ) {
			$action_file = $plugin_path . 'admin/movies/add-edit-movie.php';
			if ( file_exists( $action_file ) ) {
				require $action_file;
				exit;
			}
		}
	}
}

// =============================================================================
// 2. MAIN PAGE RENDER CALLBACK
// =============================================================================
function nmbp_render_movies_page() {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'nmbp_control_movies';

	$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
	$movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

	$total_movies = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );

	// Query movies only when viewing the index listing to conserve memory.
	$movies       = array();
	$total_pages  = 1;
	$current_page = 1;

	if ( 'all' === $subtab ) {
		$per_page     = 30;
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;
		$total_pages  = max( 1, (int) ceil( $total_movies / $per_page ) );

		// Fetch columns needed for the listing table including showtimes.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, category, genre, duration, rating, age_rating, auditorium, showtimes, badge_tag, release_date, poster_url 
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

	// Open Layout Shell.
	if ( function_exists( 'nmbp_render_page_shell_open' ) ) {
		nmbp_render_page_shell_open( 'nmbp-movies' );
	}

	$plugin_path = defined( 'NMBP_PLUGIN_PATH' ) ? NMBP_PLUGIN_PATH : plugin_dir_path( dirname( __FILE__ ) );

	// Render Subtabs & Navigation Bar.
	$tabs_file = $plugin_path . 'admin/movies/tabs-movies.php';
	if ( file_exists( $tabs_file ) ) {
		require $tabs_file;
	}

	// Route Subviews Safely.
	$view_file = $plugin_path . 'admin/movies/all-movies.php';
	if ( 'view' === $subtab && $movie_id > 0 ) {
		$view_file = $plugin_path . 'admin/movies/view-movie.php';
	} elseif ( 'add' === $subtab || 'edit' === $subtab ) {
		$view_file = $plugin_path . 'admin/movies/add-edit-movie.php';
	}

	if ( file_exists( $view_file ) ) {
		require $view_file;
	} else {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'The requested movie view could not be loaded.', 'nahian-movie-booking-plugin' )
		);
	}

	// Close Layout Shell.
	if ( function_exists( 'nmbp_render_page_shell_close' ) ) {
		nmbp_render_page_shell_close();
	}
}