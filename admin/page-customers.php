<?php
if (!defined('ABSPATH')) exit;

function bsc_render_customers_page() {
  global $wpdb;
  $table_customers = $wpdb->prefix . 'bsc_customers';
  $table_bookings  = $wpdb->prefix . 'bsc_bookings';

  $subtab      = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'all';
  $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
  $currency_symbol = get_option('bsc_currency_symbol', 'BDT');

  // Handle Delete Processing
  if ($subtab === 'delete' && $customer_id > 0) {
    require_once BSC_PLUGIN_PATH . 'admin/customers/delete-customer.php';
    return;
  }

  // Fetch Customers Data
  $customers = $wpdb->get_results("SELECT * FROM $table_customers ORDER BY id DESC");
  if (!is_array($customers)) { $customers = array(); }

  // Open Layout Shell
  bsc_render_page_shell_open('bsc-customers');

  // Render Subtabs & Navigation
  require BSC_PLUGIN_PATH . 'admin/customers/tabs-customers.php';

  // Route Subviews Safely
  if ($subtab === 'view' && $customer_id > 0) {
    require BSC_PLUGIN_PATH . 'admin/customers/view-customer.php';
  } elseif ($subtab === 'add' || $subtab === 'edit') {
    require BSC_PLUGIN_PATH . 'admin/customers/add-edit-customer.php';
  } else {
    require BSC_PLUGIN_PATH . 'admin/customers/all-customers.php';
  }

  bsc_render_page_shell_close();
}