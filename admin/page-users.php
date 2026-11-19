<?php
/**
 * Users / Staff Module - Master Controller
 *
 * Path: admin/page-users.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// 1. EARLY ACTION HANDLER (CSRF VERIFIED & SAFE REDIRECTS)
// =============================================================================
add_action( 'admin_init', 'nmbp_users_handle_actions' );

function nmbp_users_handle_actions() {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'nmbp-users' !== $page ) {
		return;
	}

	global $wpdb;
	$table_staff = $wpdb->prefix . 'nmbp_staff_crew';
	$plugin_path = defined( 'NMBP_PLUGIN_PATH' ) ? NMBP_PLUGIN_PATH : plugin_dir_path( dirname( __FILE__ ) );

	$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';
	$staff_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

	// Handle Delete Action with Nonce Verification.
	if ( 'delete' === $subtab && $staff_id > 0 ) {
		check_admin_referer( 'nmbp_delete_user_' . $staff_id, '_nmbp_nonce' );

		$delete_file = $plugin_path . 'admin/users/delete-user.php';
		if ( file_exists( $delete_file ) ) {
			require $delete_file;
			exit;
		}
	}

	// Handle POST Submissions (Add / Edit Staff Member).
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['nmbp_action'] ) ) {
		check_admin_referer( 'nmbp_user_form_action', 'nmbp_user_nonce' );

		$action        = sanitize_key( wp_unslash( $_POST['nmbp_action'] ) );
		$valid_actions = array( 'add_user', 'edit_user' );

		if ( in_array( $action, $valid_actions, true ) ) {
			$form_file = $plugin_path . 'admin/users/add-edit-user.php';
			if ( file_exists( $form_file ) ) {
				require $form_file;
				exit;
			}
		}
	}
}

// =============================================================================
// 2. MAIN PAGE RENDER CALLBACK
// =============================================================================
function nmbp_render_users_page() {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
	}

	global $wpdb;
	$table_staff = $wpdb->prefix . 'nmbp_staff_crew';

	$subtab          = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
	$staff_id        = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );

	// Real-time metric computations using prepared queries.
	$total_staff   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_staff}" );
	$on_duty_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_staff} WHERE duty_status = %s",
			'On Duty'
		)
	);
	$tech_count    = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_staff} WHERE role_title LIKE %s OR role_title LIKE %s OR role_title LIKE %s",
			'%' . $wpdb->esc_like( 'Projection' ) . '%',
			'%' . $wpdb->esc_like( 'Sound' ) . '%',
			'%' . $wpdb->esc_like( 'Laser' ) . '%'
		)
	);

	// Fetch Staff Roster Data with pagination when in roster list view.
	$staff_members = array();
	$total_pages   = 1;
	$current_page  = 1;

	if ( 'all' === $subtab ) {
		$per_page     = 30;
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;
		$total_pages  = max( 1, (int) ceil( $total_staff / $per_page ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_staff} ORDER BY id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		if ( is_array( $results ) ) {
			$staff_members = $results;
		}
	}

	// Open Layout Shell.
	if ( function_exists( 'nmbp_render_page_shell_open' ) ) {
		nmbp_render_page_shell_open( 'nmbp-users' );
	}

	$plugin_path = defined( 'NMBP_PLUGIN_PATH' ) ? NMBP_PLUGIN_PATH : plugin_dir_path( dirname( __FILE__ ) );

	// Render Subtabs & Navigation Alerts Bar.
	$tabs_file = $plugin_path . 'admin/users/tabs-users.php';
	if ( file_exists( $tabs_file ) ) {
		require $tabs_file;
	}

	// Route Subviews Safely.
	if ( 'view' === $subtab && $staff_id > 0 ) {
		$view_file = $plugin_path . 'admin/users/view-user.php';
	} elseif ( 'add' === $subtab || 'edit' === $subtab ) {
		$view_file = $plugin_path . 'admin/users/add-edit-user.php';
	} else {
		$view_file = $plugin_path . 'admin/users/all-users.php';
	}

	if ( file_exists( $view_file ) ) {
		require $view_file;
	} else {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'The requested staff view could not be loaded.', 'nahian-movie-booking-plugin' )
		);
	}

	// Close Layout Shell.
	if ( function_exists( 'nmbp_render_page_shell_close' ) ) {
		nmbp_render_page_shell_close();
	}
}