<?php
/**
 * Accounts Module - Master Controller
 *
 * Path: admin/page-accounts.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Delete & Form Submissions early on admin_init to prevent header errors
add_action( 'admin_init', 'bsc_accounts_handle_actions' );
function bsc_accounts_handle_actions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table_ledger = $wpdb->prefix . 'bsc_ledger';
    $page         = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

    if ( 'bsc-accounts' !== $page ) {
        return;
    }

    $subtab  = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : '';
    $item_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Handle Delete
    if ( 'delete' === $subtab && $item_id > 0 ) {
        if ( file_exists( BSC_PLUGIN_PATH . 'admin/accounts/delete-ledger.php' ) ) {
            require BSC_PLUGIN_PATH . 'admin/accounts/delete-ledger.php';
            exit;
        }
    }

    // Handle POST Submissions (Add Income / Expense / Edit Ledger Items)
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['bsc_action'] ) ) {
        // Handle via specific subview handlers or a general ledger processor if applicable
        $bsc_action = sanitize_text_field( wp_unslash( $_POST['bsc_action'] ) );
        if ( 'add_income' === $bsc_action || 'add_expense' === $bsc_action || 'edit_ledger' === $bsc_action ) {
            // Can be modularized or handled via dedicated submission files
            if ( file_exists( BSC_PLUGIN_PATH . 'admin/accounts/save-ledger.php' ) ) {
                require BSC_PLUGIN_PATH . 'admin/accounts/save-ledger.php';
            }
        }
    }
}

// 2. Main Page Render Callback
function bsc_render_accounts_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
    }

    global $wpdb;
    $table_ledger = $wpdb->prefix . 'bsc_ledger';

    $subtab          = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'income';
    $item_id         = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    $currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

    // Date Range Filtering Parameters
    $filter_start = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
    $filter_end   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';
    $filter_mode  = isset( $_GET['range_preset'] ) ? sanitize_key( $_GET['range_preset'] ) : 'all';

    // Build WHERE clause based on active filter
    $date_where = ' 1=1 ';
    if ( ! empty( $filter_start ) && ! empty( $filter_end ) ) {
        $date_where = $wpdb->prepare( ' DATE(transaction_date) BETWEEN %s AND %s ', $filter_start, $filter_end );
    } elseif ( 'today' === $filter_mode ) {
        $date_where = $wpdb->prepare( ' DATE(transaction_date) = %s ', current_time( 'Y-m-d' ) );
    } elseif ( 'this_month' === $filter_mode ) {
        $date_where = $wpdb->prepare( ' transaction_date LIKE %s ', current_time( 'Y-m' ) . '%' );
    } elseif ( 'this_year' === $filter_mode ) {
        $date_where = $wpdb->prepare( ' transaction_date LIKE %s ', current_time( 'Y' ) . '%' );
    }

    // Aggregate Key Performance Metrics under active filter
    $total_income  = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE ({$date_where}) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '')" );
    $total_expense = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE ({$date_where}) AND (txn_type = 'Expense')" );
    $net_profit    = $total_income - $total_expense;

    // Payment Method Breakdown
    $cash_income  = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE ({$date_where}) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') AND (method = 'Cash' OR method LIKE '%Cash%')" );
    $bkash_income = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE ({$date_where}) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') AND (method = 'bKash' OR method LIKE '%bKash%' OR method = 'Online Gateway')" );

    // Category-wise Expense Breakdown
    $distributor_exp = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE ({$date_where}) AND txn_type = 'Expense' AND particulars LIKE '%Distributor%'" );
    $payroll_exp     = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE ({$date_where}) AND txn_type = 'Expense' AND particulars LIKE '%Payroll%'" );
    $utilities_exp   = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE ({$date_where}) AND txn_type = 'Expense' AND particulars LIKE '%Utilities%'" );
    $supplies_exp    = (float) $wpdb->get_var( "SELECT SUM(amount) FROM {$table_ledger} WHERE ({$date_where}) AND txn_type = 'Expense' AND particulars LIKE '%Supplies%'" );
    $other_exp       = max( 0, $total_expense - ( $distributor_exp + $payroll_exp + $utilities_exp + $supplies_exp ) );

    // Open Layout Shell
    bsc_render_page_shell_open( 'bsc-accounts' );

    // Render Subtabs & Navigation Alerts Bar
    if ( file_exists( BSC_PLUGIN_PATH . 'admin/accounts/tabs-accounts.php' ) ) {
        require BSC_PLUGIN_PATH . 'admin/accounts/tabs-accounts.php';
    }

    // Route Subviews Safely
    if ( 'expense' === $subtab ) {
        require BSC_PLUGIN_PATH . 'admin/accounts/expense-accounts.php';
    } elseif ( 'report' === $subtab ) {
        require BSC_PLUGIN_PATH . 'admin/accounts/report-accounts.php';
    } else {
        require BSC_PLUGIN_PATH . 'admin/accounts/income-accounts.php';
    }

    // Close Layout Shell
    bsc_render_page_shell_close();
}