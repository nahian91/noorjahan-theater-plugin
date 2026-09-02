<?php
if (!defined('ABSPATH')) exit;

function bsc_render_booking_page() {
  global $wpdb;
  $table_bookings = $wpdb->prefix . 'bsc_bookings';
  $table_movies   = $wpdb->prefix . 'bsc_control_movies';

  $subtab     = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'all';
  $booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

  // Handle Edit Form Submission
  if (isset($_POST['bsc_action']) && $_POST['bsc_action'] === 'edit_booking') {
    if (check_admin_referer('bsc_edit_booking_verify')) {
      $edit_id = intval($_POST['booking_id']);
      $wpdb->update($table_bookings, array(
        'customer_name'  => sanitize_text_field($_POST['customer_name']),
        'customer_phone' => sanitize_text_field($_POST['customer_phone']),
        'customer_email' => sanitize_email($_POST['customer_email']),
        'seats'          => sanitize_text_field($_POST['seats']),
        'total_amount'   => floatval($_POST['total_amount']),
        'payment_method' => sanitize_text_field($_POST['payment_method']),
        'payment_status' => sanitize_text_field($_POST['payment_status'])
      ), array('id' => $edit_id));

      wp_redirect(admin_url('admin.php?page=bsc-booking&subtab=all&booking_updated=true'));
      exit;
    }
  }

  // Handle Delete Processing
  if ($subtab === 'delete' && $booking_id > 0) {
    require_once BSC_PLUGIN_PATH . 'admin/booking/delete-booking.php';
    return;
  }

  // Fetch Bookings & Movies Data for the views
  $bookings = $wpdb->get_results("SELECT * FROM $table_bookings ORDER BY id DESC LIMIT 50");
  if (!is_array($bookings)) {
    $bookings = array();
  }

  $movies = $wpdb->get_results("SELECT * FROM $table_movies ORDER BY id DESC");
  if (!is_array($movies)) {
    $movies = array();
  }

  // Configuration Variables
  $vip_base_price  = floatval(get_option('bsc_vip_price', 500));
  $std_base_price  = floatval(get_option('bsc_standard_price', 400));
  $currency_symbol = get_option('bsc_currency_symbol', 'BDT');

  // Query occupied seats
  $occupied_seats_raw = $wpdb->get_col("SELECT seats FROM $table_bookings");
  $all_occupied = array();
  if (!empty($occupied_seats_raw)) {
    foreach ($occupied_seats_raw as $s_str) {
      $parts = explode(',', $s_str);
      foreach ($parts as $p) {
        $trimmed = trim($p);
        if (!empty($trimmed)) {
          $all_occupied[] = $trimmed;
        }
      }
    }
  }
  $all_occupied = array_unique($all_occupied);

  // Open Layout Shell
  bsc_render_page_shell_open('bsc-booking');

  // Render Subtabs
  require BSC_PLUGIN_PATH . 'admin/booking/tabs-booking.php';

  // Route Subviews safely
  if ($subtab === 'view' && $booking_id > 0) {
    require BSC_PLUGIN_PATH . 'admin/booking/view-booking.php';
  } elseif ($subtab === 'add' || $subtab === 'edit') {
    require BSC_PLUGIN_PATH . 'admin/booking/add-edit-booking.php';
  } else {
    require BSC_PLUGIN_PATH . 'admin/booking/all-booking.php';
  }

  bsc_render_page_shell_close();
}