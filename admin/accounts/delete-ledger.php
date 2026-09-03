<?php
if (!defined('ABSPATH')) exit;

$item_id = intval($_GET['id'] ?? 0);

if ($item_id > 0 && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'bsc_delete_ledger_' . $item_id)) {
  $wpdb->delete($table_ledger, array('id' => $item_id));
  wp_redirect(admin_url('admin.php?page=bsc-accounts&subtab=expense&deleted=true'));
  exit;
} else {
  wp_die(
    esc_html__('Security check failed or invalid ledger identifier.', 'bsc-cinema-manager'),
    esc_html__('Error', 'bsc-cinema-manager'),
    array('response' => 403)
  );
}