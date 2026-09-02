<?php
if (!defined('ABSPATH')) exit;

$staff_id = intval($_GET['id'] ?? 0);

if ($staff_id > 0 && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'bsc_delete_staff_' . $staff_id)) {
  $wpdb->delete($table_staff, array('id' => $staff_id));
  wp_redirect(admin_url('admin.php?page=bsc-users&subtab=all&staff_deleted=true'));
  exit;
} else {
  wp_die(
    esc_html__('Security check failed or invalid staff identifier.', 'big-screen-cinemas'),
    esc_html__('Error', 'big-screen-cinemas'),
    array('response' => 403)
  );
}