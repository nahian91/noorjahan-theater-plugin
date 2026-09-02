<?php
if (!defined('ABSPATH')) exit;

$customer_id = intval($_GET['id'] ?? 0);

if ($customer_id > 0 && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'bsc_delete_customer_' . $customer_id)) {
  $wpdb->delete($table_customers, array('id' => $customer_id));
  wp_redirect(admin_url('admin.php?page=bsc-customers&subtab=all&customer_deleted=true'));
  exit;
} else {
  wp_die(
    esc_html__('Security check failed or invalid customer identifier.', 'big-screen-cinemas'),
    esc_html__('Error', 'big-screen-cinemas'),
    array('response' => 403)
  );
}