<?php
/**
 * Accounts Module - Master Controller
 *
 * Path: admin/page-accounts.php
 *
 * @package NahianMovieBookingPlugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// 1. EARLY ACTION HANDLER (CSRF VERIFIED & SAFE REDIRECTS)
// =============================================================================
add_action( 'admin_init', 'nmbp_accounts_handle_actions' );

function nmbp_accounts_handle_actions() {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'nmbp-accounts' !== $page ) {
		return;
	}

	global $wpdb;
	$table_ledger    = $wpdb->prefix . 'nmbp_ledger';
	$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );
	$subtab          = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';
	$item_id         = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$plugin_path     = defined( 'NMBP_PLUGIN_PATH' ) ? NMBP_PLUGIN_PATH : plugin_dir_path( dirname( __FILE__ ) );

	// CSRF-Protected Delete Action.
	if ( 'delete' === $subtab && $item_id > 0 ) {
		check_admin_referer( 'nmbp_delete_ledger_' . $item_id, '_nmbp_nonce' );

		$delete_file = $plugin_path . 'admin/accounts/delete-ledger.php';
		if ( file_exists( $delete_file ) ) {
			require $delete_file;
			exit;
		}
	}

	// CSRF-Protected Form Submissions (Add / Edit Ledger Items).
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['nmbp_action'] ) ) {
		check_admin_referer( 'nmbp_ledger_form_action', '_nmbp_nonce' );

		$nmbp_action = sanitize_key( wp_unslash( $_POST['nmbp_action'] ) );
		if ( in_array( $nmbp_action, array( 'add_income', 'add_expense', 'edit_ledger' ), true ) ) {
			$save_file = $plugin_path . 'admin/accounts/save-ledger.php';
			if ( file_exists( $save_file ) ) {
				require $save_file;
				exit;
			}
		}
	}
}

// =============================================================================
// 2. MAIN PAGE RENDER CALLBACK
// =============================================================================
function nmbp_render_accounts_page() {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
	}

	global $wpdb;
	$table_ledger = $wpdb->prefix . 'nmbp_ledger';

	$subtab          = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'income';
	$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );

	// Sanitize and normalize filter parameters.
	$filter_mode  = isset( $_GET['range_preset'] ) ? sanitize_key( wp_unslash( $_GET['range_preset'] ) ) : 'all';
	$filter_start = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
	$filter_end   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

	// Consolidated Aggregate Metrics in a Single Prepared Query (WPCS Compliant).
	$cash_like        = '%' . $wpdb->esc_like( 'Cash' ) . '%';
	$bkash_like       = '%' . $wpdb->esc_like( 'bKash' ) . '%';
	$distributor_like = '%' . $wpdb->esc_like( 'Distributor' ) . '%';
	$payroll_like     = '%' . $wpdb->esc_like( 'Payroll' ) . '%';
	$utilities_like   = '%' . $wpdb->esc_like( 'Utilities' ) . '%';
	$supplies_like    = '%' . $wpdb->esc_like( 'Supplies' ) . '%';

	// Base SELECT arguments.
	$query_args = array(
		$cash_like,
		$bkash_like,
		$distributor_like,
		$payroll_like,
		$utilities_like,
		$supplies_like,
	);

	// Build index-friendly range constraints.
	$where_clauses = array( '1=1' );

	if ( ! empty( $filter_start ) && ! empty( $filter_end ) ) {
		$where_clauses[] = 'transaction_date >= %s AND transaction_date <= %s';
		$query_args[]    = $filter_start . ' 00:00:00';
		$query_args[]    = $filter_end . ' 23:59:59';
	} elseif ( 'today' === $filter_mode ) {
		$today           = current_time( 'Y-m-d' );
		$where_clauses[] = 'transaction_date >= %s AND transaction_date <= %s';
		$query_args[]    = $today . ' 00:00:00';
		$query_args[]    = $today . ' 23:59:59';
	} elseif ( 'this_month' === $filter_mode ) {
		$where_clauses[] = 'transaction_date >= %s AND transaction_date <= %s';
		$query_args[]    = current_time( 'Y-m-01 00:00:00' );
		$query_args[]    = current_time( 'Y-m-t 23:59:59' );
	} elseif ( 'this_year' === $filter_mode ) {
		$where_clauses[] = 'transaction_date >= %s AND transaction_date <= %s';
		$query_args[]    = current_time( 'Y-01-01 00:00:00' );
		$query_args[]    = current_time( 'Y-12-31 23:59:59' );
	}

	$where_sql = implode( ' AND ', $where_clauses );

	// Prepare the unified query.
	$metrics_query = $wpdb->prepare(
		"SELECT 
			COALESCE(SUM(CASE WHEN (txn_type = 'Income' OR txn_type = '' OR txn_type IS NULL) THEN amount ELSE 0 END), 0) AS total_income,
			COALESCE(SUM(CASE WHEN txn_type = 'Expense' THEN amount ELSE 0 END), 0) AS total_expense,
			COALESCE(SUM(CASE WHEN (txn_type = 'Income' OR txn_type = '' OR txn_type IS NULL) AND (method LIKE %s) THEN amount ELSE 0 END), 0) AS cash_income,
			COALESCE(SUM(CASE WHEN (txn_type = 'Income' OR txn_type = '' OR txn_type IS NULL) AND (method LIKE %s OR method = 'Online Gateway') THEN amount ELSE 0 END), 0) AS bkash_income,
			COALESCE(SUM(CASE WHEN txn_type = 'Expense' AND particulars LIKE %s THEN amount ELSE 0 END), 0) AS distributor_exp,
			COALESCE(SUM(CASE WHEN txn_type = 'Expense' AND particulars LIKE %s THEN amount ELSE 0 END), 0) AS payroll_exp,
			COALESCE(SUM(CASE WHEN txn_type = 'Expense' AND particulars LIKE %s THEN amount ELSE 0 END), 0) AS utilities_exp,
			COALESCE(SUM(CASE WHEN txn_type = 'Expense' AND particulars LIKE %s THEN amount ELSE 0 END), 0) AS supplies_exp
		FROM {$table_ledger}
		WHERE {$where_sql}",
		$query_args
	);

	$metrics = $wpdb->get_row( $metrics_query );

	$total_income    = (float) ( $metrics->total_income ?? 0 );
	$total_expense   = (float) ( $metrics->total_expense ?? 0 );
	$net_profit      = $total_income - $total_expense;
	$cash_income     = (float) ( $metrics->cash_income ?? 0 );
	$bkash_income    = (float) ( $metrics->bkash_income ?? 0 );
	$distributor_exp = (float) ( $metrics->distributor_exp ?? 0 );
	$payroll_exp     = (float) ( $metrics->payroll_exp ?? 0 );
	$utilities_exp   = (float) ( $metrics->utilities_exp ?? 0 );
	$supplies_exp    = (float) ( $metrics->supplies_exp ?? 0 );
	$other_exp       = max( 0, $total_expense - ( $distributor_exp + $payroll_exp + $utilities_exp + $supplies_exp ) );

	// Open Layout Shell.
	if ( function_exists( 'nmbp_render_page_shell_open' ) ) {
		nmbp_render_page_shell_open( 'nmbp-accounts' );
	}

	$plugin_path = defined( 'NMBP_PLUGIN_PATH' ) ? NMBP_PLUGIN_PATH : plugin_dir_path( dirname( __FILE__ ) );

	// Render Subtabs & Navigation Alerts Bar.
	$tabs_file = $plugin_path . 'admin/accounts/tabs-accounts.php';
	if ( file_exists( $tabs_file ) ) {
		require $tabs_file;
	}

	// Route Subviews Safely.
	$view_file = $plugin_path . 'admin/accounts/income-accounts.php';
	if ( 'expense' === $subtab ) {
		$view_file = $plugin_path . 'admin/accounts/expense-accounts.php';
	} elseif ( 'report' === $subtab ) {
		$view_file = $plugin_path . 'admin/accounts/report-accounts.php';
	}

	if ( file_exists( $view_file ) ) {
		require $view_file;
	} else {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'The requested view file could not be found.', 'nahian-movie-booking-plugin' )
		);
	}

	// Close Layout Shell.
	if ( function_exists( 'nmbp_render_page_shell_close' ) ) {
		nmbp_render_page_shell_close();
	}
}