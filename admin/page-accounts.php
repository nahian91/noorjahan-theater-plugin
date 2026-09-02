<?php
if (!defined('ABSPATH')) exit;

function bsc_render_accounts_page() {
  global $wpdb;
  $table_ledger = $wpdb->prefix . 'bsc_ledger';

  $subtab = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'income';
  $item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

  // Handle Delete Processing
  if ($subtab === 'delete' && $item_id > 0) {
    require_once BSC_PLUGIN_PATH . 'admin/accounts/delete-ledger.php';
    return;
  }

  // Date Range Filtering Parameters
  $filter_start = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
  $filter_end   = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
  $filter_mode  = isset($_GET['range_preset']) ? sanitize_key($_GET['range_preset']) : 'all';
  $currency_symbol = get_option('bsc_currency_symbol', 'BDT');

  // Build WHERE clause based on active filter
  $date_where = " 1=1 ";
  if (!empty($filter_start) && !empty($filter_end)) {
    $date_where = $wpdb->prepare(" DATE(transaction_date) BETWEEN %s AND %s ", $filter_start, $filter_end);
  } elseif ($filter_mode === 'today') {
    $date_where = $wpdb->prepare(" DATE(transaction_date) = %s ", current_time('Y-m-d'));
  } elseif ($filter_mode === 'this_month') {
    $date_where = $wpdb->prepare(" transaction_date LIKE %s ", current_time('Y-m') . '%');
  } elseif ($filter_mode === 'this_year') {
    $date_where = $wpdb->prepare(" transaction_date LIKE %s ", current_time('Y') . '%');
  }

  // Aggregate Key Performance Metrics under active filter
  $total_income  = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '')");
  $total_expense = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Expense')");
  $net_profit    = $total_income - $total_expense;

  // Payment Method Breakdown
  $cash_income  = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') AND (method = 'Cash' OR method LIKE '%%Cash%%')");
  $bkash_income = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') AND (method = 'bKash' OR method LIKE '%%bKash%%' OR method = 'Online Gateway')");

  // Category-wise Expense Breakdown
  $distributor_exp = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' AND particulars LIKE '%%Distributor%%'");
  $payroll_exp     = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' AND particulars LIKE '%%Payroll%%'");
  $utilities_exp   = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' AND particulars LIKE '%%Utilities%%'");
  $supplies_exp    = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' AND particulars LIKE '%%Supplies%%'");
  $other_exp       = max(0, $total_expense - ($distributor_exp + $payroll_exp + $utilities_exp + $supplies_exp));

  // Open Layout Shell
  bsc_render_page_shell_open('bsc-accounts');

  // Render Subtabs & Navigation
  require BSC_PLUGIN_PATH . 'admin/accounts/tabs-accounts.php';

  // Route Subviews Safely
  if ($subtab === 'expense') {
    require BSC_PLUGIN_PATH . 'admin/accounts/expense-accounts.php';
  } elseif ($subtab === 'report') {
    require BSC_PLUGIN_PATH . 'admin/accounts/report-accounts.php';
  } else {
    require BSC_PLUGIN_PATH . 'admin/accounts/income-accounts.php';
  }

  bsc_render_page_shell_close();
}