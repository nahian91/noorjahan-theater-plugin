<?php
if (!defined('ABSPATH')) exit;

function bsc_render_users_page() {
  global $wpdb;
  $table_staff = $wpdb->prefix . 'bsc_staff_crew';

  $subtab   = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'all';
  $staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
  $currency_symbol = get_option('bsc_currency_symbol', 'BDT');

  // Handle Delete Processing
  if ($subtab === 'delete' && $staff_id > 0) {
    require_once BSC_PLUGIN_PATH . 'admin/users/delete-user.php';
    return;
  }

  // Real-time metric computations
  $total_staff   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_staff");
  $on_duty_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_staff WHERE duty_status = 'On Duty'");
  $tech_count    = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_staff WHERE role_title LIKE '%Projection%' OR role_title LIKE '%Sound%' OR role_title LIKE '%Laser%'");

  // Fetch Staff Roster Data
  $staff_members = $wpdb->get_results("SELECT * FROM $table_staff ORDER BY id DESC");
  if (!is_array($staff_members)) { $staff_members = array(); }

  // Open Layout Shell
  bsc_render_page_shell_open('bsc-users');

  // Render Subtabs & Navigation
  require BSC_PLUGIN_PATH . 'admin/users/tabs-users.php';

  // Route Subviews Safely
  if ($subtab === 'view' && $staff_id > 0) {
    require BSC_PLUGIN_PATH . 'admin/users/view-user.php';
  } elseif ($subtab === 'add' || $subtab === 'edit') {
    require BSC_PLUGIN_PATH . 'admin/users/add-edit-user.php';
  } else {
    require BSC_PLUGIN_PATH . 'admin/users/all-users.php';
  }

  bsc_render_page_shell_close();
}