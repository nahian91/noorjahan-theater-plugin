<?php
if (!defined('ABSPATH')) exit;

$del_id = intval($_GET['id'] ?? 0);

if ($del_id > 0 && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'bsc_delete_booking_' . $del_id)) {
  $wpdb->delete($table_bookings, array('id' => $del_id));
  wp_redirect(admin_url('admin.php?page=bsc-booking&subtab=all&booking_deleted=true'));
  exit;
} else {
  wp_die(
    esc_html__('Security check failed or invalid booking record.', 'big-screen-cinemas'),
    esc_html__('Error', 'big-screen-cinemas'),
    array('response' => 403)
  );
}