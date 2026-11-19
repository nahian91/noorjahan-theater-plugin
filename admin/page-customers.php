<?php
/**
 * Customer Module - Master Controller
 *
 * Path: admin/page-customers.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// 1. EARLY ACTION HANDLER (CSRF VERIFIED & SAFE REDIRECTS)
// =============================================================================
add_action( 'admin_init', 'nmbp_customer_handle_actions' );

function nmbp_customer_handle_actions() {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'nmbp-customers' !== $page ) {
		return;
	}

	global $wpdb;
	$table_customers = $wpdb->prefix . 'nmbp_customers';
	$plugin_path     = defined( 'NMBP_PLUGIN_PATH' ) ? NMBP_PLUGIN_PATH : plugin_dir_path( dirname( __FILE__ ) );

	$subtab      = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';
	$customer_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

	// Handle Delete Action with Strict CSRF Nonce Verification.
	if ( 'delete' === $subtab && $customer_id > 0 ) {
		check_admin_referer( 'nmbp_delete_customer_' . $customer_id, '_nmbp_nonce' );

		$delete_file = $plugin_path . 'admin/customers/delete-customer.php';
		if ( file_exists( $delete_file ) ) {
			require $delete_file;
			exit;
		}
	}

	// Handle POST Submissions (Add / Edit Customer).
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['nmbp_action'] ) ) {
		check_admin_referer( 'nmbp_customer_form_action', '_nmbp_nonce' );

		$action        = sanitize_key( wp_unslash( $_POST['nmbp_action'] ) );
		$valid_actions = array( 'add_customer', 'edit_customer' );

		if ( in_array( $action, $valid_actions, true ) ) {
			$action_file = $plugin_path . 'admin/customers/add-edit-customer.php';
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
function nmbp_render_customers_page() {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
	}

	global $wpdb;
	$table_customers = $wpdb->prefix . 'nmbp_customers';

	$subtab          = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
	$customer_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );

	$total_customers = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_customers}" );

	// Fetch records conditionally to preserve memory on edit/view screens.
	$customers    = array();
	$total_pages  = 1;
	$current_page = 1;

	if ( 'all' === $subtab ) {
		$per_page     = 50;
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;
		$total_pages  = max( 1, (int) ceil( $total_customers / $per_page ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_customers} ORDER BY id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		if ( is_array( $results ) ) {
			$customers = $results;
		}
	}

	// Open Layout Shell.
	if ( function_exists( 'nmbp_render_page_shell_open' ) ) {
		nmbp_render_page_shell_open( 'nmbp-customers' );
	}

	$plugin_path = defined( 'NMBP_PLUGIN_PATH' ) ? NMBP_PLUGIN_PATH : plugin_dir_path( dirname( __FILE__ ) );

	// Render Subtabs & Navigation Alerts Bar.
	$tabs_file = $plugin_path . 'admin/customers/tabs-customers.php';
	if ( file_exists( $tabs_file ) ) {
		require $tabs_file;
	}

	// Route Subviews Safely.
	$view_file = $plugin_path . 'admin/customers/all-customers.php';
	if ( 'view' === $subtab && $customer_id > 0 ) {
		$view_file = $plugin_path . 'admin/customers/view-customer.php';
	} elseif ( 'add' === $subtab || 'edit' === $subtab ) {
		$view_file = $plugin_path . 'admin/customers/add-edit-customer.php';
	}

	if ( file_exists( $view_file ) ) {
		require $view_file;
	} else {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'The requested customer view could not be loaded.', 'nahian-movie-booking-plugin' )
		);
	}

	// Close Layout Shell.
	if ( function_exists( 'nmbp_render_page_shell_close' ) ) {
		nmbp_render_page_shell_close();
	}
}