<?php
if (!defined('ABSPATH')) exit;

function bsc_render_movies_page() {
  global $wpdb;
  $table = $wpdb->prefix . 'bsc_control_movies';

  $subtab   = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'all';
  $movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

  // Handle Delete Processing
  if ($subtab === 'delete' && $movie_id > 0) {
    require_once BSC_PLUGIN_PATH . 'admin/movies/delete-movie.php';
    return;
  }

  // Fetch Movies Data
  $movies = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
  if (!is_array($movies)) { $movies = array(); }

  // Open Layout Shell
  bsc_render_page_shell_open('bsc-movies');

  // Render Subtabs & Navigation
  require BSC_PLUGIN_PATH . 'admin/movies/tabs-movies.php';

  // Route Subviews Safely
  if ($subtab === 'view' && $movie_id > 0) {
    require BSC_PLUGIN_PATH . 'admin/movies/view-movie.php';
  } elseif ($subtab === 'add' || $subtab === 'edit') {
    require BSC_PLUGIN_PATH . 'admin/movies/add-edit-movie.php';
  } else {
    require BSC_PLUGIN_PATH . 'admin/movies/all-movies.php';
  }

  bsc_render_page_shell_close();
}