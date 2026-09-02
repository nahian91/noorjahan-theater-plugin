<?php
if (!defined('ABSPATH')) exit;

$movie_id = intval($_GET['id'] ?? 0);

if ($movie_id > 0 && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'bsc_delete_movie_' . $movie_id)) {
  $wpdb->delete($table, array('id' => $movie_id));
  wp_redirect(admin_url('admin.php?page=bsc-movies&subtab=all&movie_deleted=true'));
  exit;
} else {
  wp_die(
    esc_html__('Security check failed or invalid movie identifier.', 'big-screen-cinemas'),
    esc_html__('Error', 'big-screen-cinemas'),
    array('response' => 403)
  );
}