<?php
/**
 * Plugin Name: Big Screen Cinemas - Master Controller Suite
 * Plugin URI:  https://bigscreencinemas.com
 * Description: Standalone Cinema Multiplex Management Suite with 4K Media Uploads, Cast Photo Repeaters, Full CRUD Movie Management, Dynamic Scheduling, and Turnstile Booking Engine.
 * Version:     12.0.0
 * Author:      Big Screen Cinemas
 * Text Domain: big-screen-cinemas
 */

if (!defined('ABSPATH')) {
  exit;
}

define('BSC_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Load layout shell and individual page renderers
require_once BSC_PLUGIN_PATH . 'admin/layout-shell.php';
require_once BSC_PLUGIN_PATH . 'admin/page-dashboard.php';
require_once BSC_PLUGIN_PATH . 'admin/page-booking.php';
require_once BSC_PLUGIN_PATH . 'admin/page-movies.php';
require_once BSC_PLUGIN_PATH . 'admin/page-customers.php';
require_once BSC_PLUGIN_PATH . 'admin/page-accounts.php';
require_once BSC_PLUGIN_PATH . 'admin/page-users.php';
require_once BSC_PLUGIN_PATH . 'admin/page-settings.php';

// Check and include page-gallery if created, otherwise fallback gracefully
if (file_exists(BSC_PLUGIN_PATH . 'admin/page-gallery.php')) {
  require_once BSC_PLUGIN_PATH . 'admin/page-gallery.php';
} else {
  function bsc_render_gallery_page() {
    bsc_render_page_shell_open('bsc-gallery');
    ?>
    <div class="cinema-panel">
      <h3 class="cinema-panel-title">🖼️ <?php esc_html_e('Multiplex Gallery Management', 'big-screen-cinemas'); ?></h3>
      <p style="color:var(--cinema-text-secondary);"><?php esc_html_e('Manage front-end gallery photos, luxury lounge showcases, and laser screen stills.', 'big-screen-cinemas'); ?></p>
      
      <div style="background:#f8fafc; border:1px dashed var(--cinema-border); padding:2rem; text-align:center; border-radius:8px; margin-top:1.5rem;">
        <p style="font-weight:700; color:var(--cinema-text-main); margin-bottom:0.75rem;"><?php esc_html_e('Gallery Media Manager Active', 'big-screen-cinemas'); ?></p>
        <button type="button" class="button button-primary" onclick="alert('Gallery uploader active.')">📷 Upload Gallery Image</button>
      </div>
    </div>
    <?php
    bsc_render_page_shell_close();
  }
}

/* ==========================================================================
   01. DATABASE SCHEMA & ACTIVATION
   ========================================================================== */
register_activation_hook(__FILE__, 'bsc_create_custom_tables');
function bsc_create_custom_tables() {
  global $wpdb;
  $table_movies    = $wpdb->prefix . 'bsc_control_movies';
  $table_schedule  = $wpdb->prefix . 'bsc_control_schedule';
  $table_ledger    = $wpdb->prefix . 'bsc_ledger';
  $table_bookings  = $wpdb->prefix . 'bsc_bookings';
  $table_customers = $wpdb->prefix . 'bsc_customers';
  $table_staff     = $wpdb->prefix . 'bsc_staff_crew';
  $charset_collate = $wpdb->get_charset_collate();

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  dbDelta("CREATE TABLE $table_movies (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    category varchar(100) NOT NULL,
    genre varchar(100) NOT NULL,
    duration varchar(50) NOT NULL,
    rating varchar(10) NOT NULL,
    age_rating varchar(50) DEFAULT 'PG-13' NOT NULL,
    audio_language varchar(150) NOT NULL,
    director varchar(150) NOT NULL,
    writers varchar(255) NOT NULL,
    auditorium varchar(100) DEFAULT 'Auditorium 1 (23 Seats)' NOT NULL,
    showtimes longtext NOT NULL,
    poster_url text NOT NULL,
    trailer_url text NOT NULL,
    badge_tag varchar(50) NOT NULL,
    release_date date NOT NULL,
    short_description text NOT NULL,
    synopsis longtext NOT NULL,
    cast_crew longtext NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id)
  ) $charset_collate;");

  dbDelta("CREATE TABLE $table_schedule (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    movie_title varchar(255) NOT NULL,
    auditorium varchar(100) NOT NULL,
    format_type varchar(50) NOT NULL,
    showtimes varchar(255) NOT NULL,
    price decimal(10,2) NOT NULL,
    PRIMARY KEY (id)
  ) $charset_collate;");

  dbDelta("CREATE TABLE $table_ledger (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    reference_no varchar(64) NOT NULL,
    txn_type varchar(20) DEFAULT 'Income' NOT NULL,
    particulars varchar(255) NOT NULL,
    method varchar(50) DEFAULT 'Cash' NOT NULL,
    amount decimal(10,2) NOT NULL,
    transaction_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY reference_no (reference_no)
  ) $charset_collate;");

  dbDelta("CREATE TABLE $table_bookings (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    booking_token varchar(64) NOT NULL,
    movie_title varchar(255) NOT NULL,
    auditorium varchar(100) NOT NULL,
    showtime varchar(50) NOT NULL,
    ticket_type varchar(50) DEFAULT 'Standard' NOT NULL,
    seats text NOT NULL,
    customer_name varchar(150) DEFAULT 'Walk-In Guest' NOT NULL,
    customer_phone varchar(50) NOT NULL,
    customer_email varchar(150) NOT NULL,
    total_amount decimal(10,2) NOT NULL,
    payment_method varchar(50) DEFAULT 'Cash' NOT NULL,
    payment_status varchar(50) DEFAULT 'Confirmed' NOT NULL,
    booking_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY booking_token (booking_token)
  ) $charset_collate;");

  dbDelta("CREATE TABLE $table_customers (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    membership_id varchar(50) NOT NULL,
    name varchar(150) NOT NULL,
    email varchar(150) NOT NULL,
    phone varchar(50) NOT NULL,
    tier varchar(50) DEFAULT 'VIP CLUB' NOT NULL,
    total_visits int(11) DEFAULT 1 NOT NULL,
    reward_points int(11) DEFAULT 50 NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY membership_id (membership_id)
  ) $charset_collate;");

  dbDelta("CREATE TABLE $table_staff (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    staff_code varchar(50) NOT NULL,
    name varchar(150) NOT NULL,
    role_title varchar(150) NOT NULL,
    assigned_hall varchar(150) NOT NULL,
    duty_status varchar(50) DEFAULT 'On Duty' NOT NULL,
    shift_schedule varchar(100) NOT NULL,
    phone varchar(50) NOT NULL,
    email varchar(150) NOT NULL,
    salary decimal(10,2) DEFAULT '25000.00' NOT NULL,
    photo_url text NOT NULL,
    responsibilities text NOT NULL,
    joined_date date DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY staff_code (staff_code)
  ) $charset_collate;");

  // Populate default staff members on first run
  $staff_exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_staff");
  if ($staff_exists == 0) {
    $wpdb->insert($table_staff, array(
      'staff_code'       => '#STF-1001',
      'name'             => 'Abdullah Nahian',
      'role_title'       => 'Master Administrator',
      'assigned_hall'    => 'All Multiplex Facilities',
      'duty_status'      => 'On Duty',
      'shift_schedule'   => 'Full Rotational',
      'phone'            => '+880 1700-000001',
      'email'            => 'admin@bigscreencinemas.com',
      'salary'           => 60000.00,
      'responsibilities' => 'Full administrative access, box-office accounting supervision, and master show scheduler.'
    ));
    $wpdb->insert($table_staff, array(
      'staff_code'       => '#STF-1002',
      'name'             => 'Tanvir Ahmed',
      'role_title'       => 'Chief Projectionist (4K Laser)',
      'assigned_hall'    => 'Auditorium 1 (23 Seats)',
      'duty_status'      => 'On Duty',
      'shift_schedule'   => 'Evening Shift (03:00 PM - 11:30 PM)',
      'phone'            => '+880 1700-000002',
      'email'            => 'tanvir@bigscreencinemas.com',
      'salary'           => 38000.00,
      'responsibilities' => '4K dual-laser alignment, Christie projector calibration, and Dolby Atmos 128-ch sound balancing.'
    ));
    $wpdb->insert($table_staff, array(
      'staff_code'       => '#STF-1003',
      'name'             => 'Sayem Chowdhury',
      'role_title'       => 'Concierge & Box Office Supervisor',
      'assigned_hall'    => 'Reception & Gate Turnstiles',
      'duty_status'      => 'On Duty',
      'shift_schedule'   => 'Day Shift (11:00 AM - 07:00 PM)',
      'phone'            => '+880 1700-000003',
      'email'            => 'sayem@bigscreencinemas.com',
      'salary'           => 28000.00,
      'responsibilities' => 'Front-of-house ticketing desk, VIP seating concierge, and turnstile badge validation.'
    ));
  }
}

/* ==========================================================================
   02. MENU & ASSET REGISTRATIONS
   ========================================================================== */
add_action('admin_menu', 'bsc_register_master_menu');
function bsc_register_master_menu() {
  add_menu_page(
    __('Movie Theater', 'big-screen-cinemas'),
    __('Movie Theater', 'big-screen-cinemas'),
    'manage_options',
    'bsc-dashboard',
    'bsc_render_dashboard_page',
    'dashicons-video-alt3',
    6
  );

  add_submenu_page('bsc-dashboard', __('Dashboard', 'big-screen-cinemas'), __('Dashboard', 'big-screen-cinemas'), 'manage_options', 'bsc-dashboard', 'bsc_render_dashboard_page');
  add_submenu_page('bsc-dashboard', __('Booking Manager', 'big-screen-cinemas'), __('Booking', 'big-screen-cinemas'), 'manage_options', 'bsc-booking', 'bsc_render_booking_page');
  add_submenu_page('bsc-dashboard', __('Movies & Shows', 'big-screen-cinemas'), __('Movies', 'big-screen-cinemas'), 'manage_options', 'bsc-movies', 'bsc_render_movies_page');
  add_submenu_page('bsc-dashboard', __('Gallery Manager', 'big-screen-cinemas'), __('Gallery', 'big-screen-cinemas'), 'manage_options', 'bsc-gallery', 'bsc_render_gallery_page');
  add_submenu_page('bsc-dashboard', __('Customers Directory', 'big-screen-cinemas'), __('Customers', 'big-screen-cinemas'), 'manage_options', 'bsc-customers', 'bsc_render_customers_page');
  add_submenu_page('bsc-dashboard', __('Financial Accounts', 'big-screen-cinemas'), __('Accounts', 'big-screen-cinemas'), 'manage_options', 'bsc-accounts', 'bsc_render_accounts_page');
  add_submenu_page('bsc-dashboard', __('Staff & Crew', 'big-screen-cinemas'), __('Users', 'big-screen-cinemas'), 'manage_options', 'bsc-users', 'bsc_render_users_page');
  add_submenu_page('bsc-dashboard', __('Theater Settings', 'big-screen-cinemas'), __('Settings', 'big-screen-cinemas'), 'manage_options', 'bsc-settings', 'bsc_render_settings_page');
  add_submenu_page('bsc-dashboard', __('Logout', 'big-screen-cinemas'), __('Logout', 'big-screen-cinemas'), 'manage_options', 'bsc-logout', 'bsc_handle_logout_redirect');
}

add_action('admin_enqueue_scripts', 'bsc_enqueue_admin_assets');
function bsc_enqueue_admin_assets($hook) {
  if (strpos($hook, 'bsc-') === false) {
    return;
  }
  wp_enqueue_media();
  wp_enqueue_style('bsc-admin-fonts', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@600;700;800&display=swap', array(), null);
}

// Hide native WordPress admin sidebar and top bar for a clean full-screen experience on bsc- pages
add_action('admin_head', 'bsc_hide_wp_sidebar_for_cinema_pages');
function bsc_hide_wp_sidebar_for_cinema_pages() {
  $screen = get_current_screen();
  if ($screen && strpos($screen->id, 'bsc-') !== false) {
    echo '<style>
      #adminmenumain, #wpadminbar { display: none !important; }
      #wpcontent { margin-left: 0 !important; padding-left: 0 !important; }
      #wpfooter { display: none !important; }
      body.admin-color-fresh { background: #070e11 !important; }
    </style>';
  }
}

function bsc_handle_logout_redirect() {
  wp_redirect(wp_logout_url(home_url()));
  exit;
}

/* ==========================================================================
   03. FORM POST HANDLERS & REST API
   ========================================================================== */

// --- Settings Handler (Pricing Tiers Repeater) ---
add_action('admin_post_bsc_save_settings', 'bsc_handle_save_settings');
function bsc_handle_save_settings() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_save_settings_verify');

  $subtab = sanitize_key($_POST['active_subtab'] ?? 'pricing');

  $pricing_tiers = array();
  if (!empty($_POST['pricing_titles']) && is_array($_POST['pricing_titles'])) {
    $titles       = array_map('sanitize_text_field', $_POST['pricing_titles']);
    $amounts      = array_map('floatval', $_POST['pricing_amounts'] ?? array());
    $descriptions = array_map('sanitize_text_field', $_POST['pricing_descriptions'] ?? array());

    for ($i = 0; $i < count($titles); $i++) {
      if (!empty($titles[$i])) {
        $pricing_tiers[] = array(
          'title'       => $titles[$i],
          'amount'      => $amounts[$i] ?? 0.00,
          'description' => $descriptions[$i] ?? ''
        );
      }
    }
  }
  update_option('bsc_pricing_tiers', $pricing_tiers);

  foreach ($pricing_tiers as $tier) {
    $t_lower = strtolower($tier['title']);
    if (strpos($t_lower, 'vip') !== false) {
      update_option('bsc_vip_price', $tier['amount']);
    } else {
      update_option('bsc_standard_price', $tier['amount']);
    }
  }

  if (isset($_POST['service_charge_pct'])) update_option('bsc_service_charge_pct', floatval($_POST['service_charge_pct']));
  update_option('bsc_enable_tax', isset($_POST['enable_tax']) ? 'yes' : 'no');
  if (isset($_POST['seat_hold_timeout'])) update_option('bsc_seat_hold_timeout', intval($_POST['seat_hold_timeout']));

  wp_redirect(admin_url('admin.php?page=bsc-settings&subtab=' . $subtab . '&updated=true'));
  exit;
}

// --- Gallery Save Handler ---
add_action('admin_post_bsc_save_gallery', 'bsc_handle_save_gallery');
function bsc_handle_save_gallery() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_save_gallery_verify');

  $gallery_data = array();
  if (!empty($_POST['gallery_urls']) && is_array($_POST['gallery_urls'])) {
    $urls         = array_map('esc_url_raw', $_POST['gallery_urls']);
    $categories   = array_map('sanitize_text_field', $_POST['gallery_categories'] ?? array());
    $titles       = array_map('sanitize_text_field', $_POST['gallery_titles'] ?? array());
    $descriptions = array_map('sanitize_textarea_field', $_POST['gallery_descriptions'] ?? array());

    for ($i = 0; $i < count($urls); $i++) {
      if (!empty($urls[$i])) {
        $gallery_data[] = array(
          'url'         => $urls[$i],
          'category'    => $categories[$i] ?? 'screens',
          'title'       => $titles[$i] ?? 'Multiplex Showcase',
          'description' => $descriptions[$i] ?? ''
        );
      }
    }
  }

  update_option('bsc_gallery_images', $gallery_data);
  wp_redirect(admin_url('admin.php?page=bsc-gallery&updated=true'));
  exit;
}

// --- Movie CRUD Handlers ---
add_action('admin_post_bsc_add_movie', 'bsc_handle_add_movie');
function bsc_handle_add_movie() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_add_movie_verify');
  global $wpdb;
  $table = $wpdb->prefix . 'bsc_control_movies';

  // Process Cast and Crew Repeater
  $cast_entries = array();
  if (!empty($_POST['cast_names']) && is_array($_POST['cast_names'])) {
    $names  = array_map('sanitize_text_field', $_POST['cast_names']);
    $roles  = array_map('sanitize_text_field', $_POST['cast_roles'] ?? array());
    $photos = array_map('esc_url_raw', $_POST['cast_photos'] ?? array());

    for ($i = 0; $i < count($names); $i++) {
      if (!empty($names[$i])) {
        $cast_entries[] = array(
          'name'  => $names[$i],
          'role'  => $roles[$i] ?? '',
          'photo' => $photos[$i] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300&auto=format&fit=crop&q=80'
        );
      }
    }
  }

  // Process Hierarchical Day & Showtimes Repeater
  $day_schedules = array();
  if (!empty($_POST['schedule_days']) && is_array($_POST['schedule_days'])) {
    foreach ($_POST['schedule_days'] as $day_data) {
      $clean_date = sanitize_text_field($day_data['date'] ?? current_time('Y-m-d'));
      $raw_times  = !empty($day_data['times']) && is_array($day_data['times']) ? $day_data['times'] : array('18:00');
      $clean_times = array_values(array_filter(array_map('sanitize_text_field', $raw_times)));

      if (!empty($clean_date) && !empty($clean_times)) {
        $day_schedules[] = array(
          'date'  => $clean_date,
          'times' => $clean_times
        );
      }
    }
  }
  if (empty($day_schedules)) {
    $day_schedules = array(
      array(
        'date'  => current_time('Y-m-d'),
        'times' => array('18:00')
      )
    );
  }

  // Release Date (Single official theatrical date)
  $release_date_val = sanitize_text_field($_POST['movie_release_date'] ?? current_time('Y-m-d'));

  $wpdb->insert($table, array(
    'title'             => sanitize_text_field($_POST['movie_title']),
    'category'          => sanitize_text_field($_POST['movie_category']),
    'genre'             => sanitize_text_field($_POST['movie_genre']),
    'duration'          => sanitize_text_field($_POST['movie_duration']),
    'rating'            => sanitize_text_field($_POST['movie_rating']),
    'age_rating'        => sanitize_text_field($_POST['movie_age_rating']),
    'audio_language'    => sanitize_text_field($_POST['movie_audio_lang']),
    'director'          => sanitize_text_field($_POST['movie_director']),
    'writers'           => sanitize_text_field($_POST['movie_writers']),
    'auditorium'        => sanitize_text_field($_POST['movie_auditorium']),
    'showtimes'         => wp_json_encode($day_schedules),
    'poster_url'        => esc_url_raw($_POST['movie_poster']),
    'trailer_url'       => esc_url_raw($_POST['movie_trailer']),
    'badge_tag'         => sanitize_text_field($_POST['movie_badge']),
    'release_date'      => $release_date_val,
    'short_description' => sanitize_textarea_field($_POST['movie_short_desc']),
    'synopsis'          => wp_kses_post($_POST['movie_synopsis']),
    'cast_crew'         => wp_json_encode($cast_entries)
  ));

  wp_redirect(admin_url('admin.php?page=bsc-movies&subtab=all&movie_added=true'));
  exit;
}

add_action('admin_post_bsc_edit_movie', 'bsc_handle_edit_movie');
function bsc_handle_edit_movie() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_edit_movie_verify');
  global $wpdb;
  $table = $wpdb->prefix . 'bsc_control_movies';
  $movie_id = intval($_POST['movie_id'] ?? 0);

  if ($movie_id <= 0) {
    wp_die('Invalid Movie Identifier');
  }

  // Process Cast and Crew Repeater
  $cast_entries = array();
  if (!empty($_POST['cast_names']) && is_array($_POST['cast_names'])) {
    $names  = array_map('sanitize_text_field', $_POST['cast_names']);
    $roles  = array_map('sanitize_text_field', $_POST['cast_roles'] ?? array());
    $photos = array_map('esc_url_raw', $_POST['cast_photos'] ?? array());

    for ($i = 0; $i < count($names); $i++) {
      if (!empty($names[$i])) {
        $cast_entries[] = array(
          'name'  => $names[$i],
          'role'  => $roles[$i] ?? '',
          'photo' => $photos[$i] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300&auto=format&fit=crop&q=80'
        );
      }
    }
  }

  // Process Hierarchical Day & Showtimes Repeater
  $day_schedules = array();
  if (!empty($_POST['schedule_days']) && is_array($_POST['schedule_days'])) {
    foreach ($_POST['schedule_days'] as $day_data) {
      $clean_date = sanitize_text_field($day_data['date'] ?? current_time('Y-m-d'));
      $raw_times  = !empty($day_data['times']) && is_array($day_data['times']) ? $day_data['times'] : array('18:00');
      $clean_times = array_values(array_filter(array_map('sanitize_text_field', $raw_times)));

      if (!empty($clean_date) && !empty($clean_times)) {
        $day_schedules[] = array(
          'date'  => $clean_date,
          'times' => $clean_times
        );
      }
    }
  }
  if (empty($day_schedules)) {
    $day_schedules = array(
      array(
        'date'  => current_time('Y-m-d'),
        'times' => array('18:00')
      )
    );
  }

  // Release Date (Single official theatrical date)
  $release_date_val = sanitize_text_field($_POST['movie_release_date'] ?? current_time('Y-m-d'));

  $wpdb->update($table, array(
    'title'             => sanitize_text_field($_POST['movie_title']),
    'category'          => sanitize_text_field($_POST['movie_category']),
    'genre'             => sanitize_text_field($_POST['movie_genre']),
    'duration'          => sanitize_text_field($_POST['movie_duration']),
    'rating'            => sanitize_text_field($_POST['movie_rating']),
    'age_rating'        => sanitize_text_field($_POST['movie_age_rating']),
    'audio_language'    => sanitize_text_field($_POST['movie_audio_lang']),
    'director'          => sanitize_text_field($_POST['movie_director']),
    'writers'           => sanitize_text_field($_POST['movie_writers']),
    'auditorium'        => sanitize_text_field($_POST['movie_auditorium']),
    'showtimes'         => wp_json_encode($day_schedules),
    'poster_url'        => esc_url_raw($_POST['movie_poster']),
    'trailer_url'       => esc_url_raw($_POST['movie_trailer']),
    'badge_tag'         => sanitize_text_field($_POST['movie_badge']),
    'release_date'      => $release_date_val,
    'short_description' => sanitize_textarea_field($_POST['movie_short_desc']),
    'synopsis'          => wp_kses_post($_POST['movie_synopsis']),
    'cast_crew'         => wp_json_encode($cast_entries)
  ), array('id' => $movie_id));

  wp_redirect(admin_url('admin.php?page=bsc-movies&subtab=all&movie_updated=true'));
  exit;
}

add_action('admin_post_bsc_delete_movie', 'bsc_handle_delete_movie');
function bsc_handle_delete_movie() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  $movie_id = intval($_GET['id'] ?? 0);
  check_admin_referer('bsc_delete_movie_' . $movie_id);

  global $wpdb;
  $table = $wpdb->prefix . 'bsc_control_movies';
  $wpdb->delete($table, array('id' => $movie_id));

  wp_redirect(admin_url('admin.php?page=bsc-movies&subtab=all&movie_deleted=true'));
  exit;
}

// --- Customer CRUD Handlers ---
add_action('admin_post_bsc_add_customer', 'bsc_handle_add_customer');
function bsc_handle_add_customer() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_add_customer_verify');
  global $wpdb;
  $table = $wpdb->prefix . 'bsc_customers';

  $wpdb->insert($table, array(
    'membership_id' => '#BSC-' . rand(10000, 99999),
    'name'          => sanitize_text_field($_POST['customer_name'] ?? ''),
    'email'         => sanitize_email($_POST['customer_email'] ?? ''),
    'phone'         => sanitize_text_field($_POST['customer_phone'] ?? ''),
    'tier'          => sanitize_text_field($_POST['customer_tier'] ?? 'VIP CLUB'),
    'total_visits'  => intval($_POST['total_visits'] ?? 1),
    'reward_points' => intval($_POST['reward_points'] ?? 100),
  ));

  wp_redirect(admin_url('admin.php?page=bsc-customers&subtab=all&customer_added=true'));
  exit;
}

add_action('admin_post_bsc_edit_customer', 'bsc_handle_edit_customer');
function bsc_handle_edit_customer() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_edit_customer_verify');
  global $wpdb;
  $table = $wpdb->prefix . 'bsc_customers';
  $id    = intval($_POST['customer_id'] ?? 0);

  if ($id <= 0) wp_die('Invalid customer ID');

  $wpdb->update($table, array(
    'name'          => sanitize_text_field($_POST['customer_name'] ?? ''),
    'email'         => sanitize_email($_POST['customer_email'] ?? ''),
    'phone'         => sanitize_text_field($_POST['customer_phone'] ?? ''),
    'tier'          => sanitize_text_field($_POST['customer_tier'] ?? 'VIP CLUB'),
    'total_visits'  => intval($_POST['total_visits'] ?? 1),
    'reward_points' => intval($_POST['reward_points'] ?? 100),
  ), array('id' => $id));

  wp_redirect(admin_url('admin.php?page=bsc-customers&subtab=all&customer_updated=true'));
  exit;
}

add_action('admin_post_bsc_delete_customer', 'bsc_handle_delete_customer');
function bsc_handle_delete_customer() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  $id = intval($_GET['id'] ?? 0);
  check_admin_referer('bsc_delete_customer_' . $id);

  global $wpdb;
  $table = $wpdb->prefix . 'bsc_customers';
  $wpdb->delete($table, array('id' => $id));

  wp_redirect(admin_url('admin.php?page=bsc-customers&subtab=all&customer_deleted=true'));
  exit;
}

// --- Accounts / Expense & Direct Revenue CRUD Handlers ---
add_action('admin_post_bsc_add_income', 'bsc_handle_add_income');
function bsc_handle_add_income() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_add_income_verify');
  global $wpdb;
  $table_ledger = $wpdb->prefix . 'bsc_ledger';

  $particulars = sanitize_text_field($_POST['income_particulars'] ?? 'Direct Revenue');
  $category    = sanitize_text_field($_POST['income_category'] ?? 'Concessions & F&B');
  $method      = sanitize_text_field($_POST['income_method'] ?? 'Cash');
  $amount      = floatval($_POST['income_amount'] ?? 0.00);
  $token       = '#INC-' . rand(1000, 9999) . '-TX';

  if ($amount <= 0) {
    wp_die('Invalid income amount entered.');
  }

  $wpdb->insert($table_ledger, array(
    'reference_no' => $token,
    'txn_type'     => 'Income',
    'particulars'  => '[' . $category . '] ' . $particulars,
    'method'       => $method,
    'amount'       => $amount
  ));

  wp_redirect(admin_url('admin.php?page=bsc-accounts&subtab=income&income_added=true'));
  exit;
}

add_action('admin_post_bsc_add_expense', 'bsc_handle_add_expense');
function bsc_handle_add_expense() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_add_expense_verify');
  global $wpdb;
  $table_ledger = $wpdb->prefix . 'bsc_ledger';

  $particulars = sanitize_text_field($_POST['expense_particulars'] ?? 'Operating Expense');
  $category    = sanitize_text_field($_POST['expense_category'] ?? 'General');
  $method      = sanitize_text_field($_POST['expense_method'] ?? 'Cash');
  $amount      = floatval($_POST['expense_amount'] ?? 0.00);
  $token       = '#EXP-' . rand(1000, 9999) . '-TX';

  if ($amount <= 0) {
    wp_die('Invalid expense amount entered.');
  }

  $wpdb->insert($table_ledger, array(
    'reference_no' => $token,
    'txn_type'     => 'Expense',
    'particulars'  => '[' . $category . '] ' . $particulars,
    'method'       => $method,
    'amount'       => $amount
  ));

  wp_redirect(admin_url('admin.php?page=bsc-accounts&subtab=expense&expense_added=true'));
  exit;
}

add_action('admin_post_bsc_delete_ledger_item', 'bsc_handle_delete_ledger_item');
function bsc_handle_delete_ledger_item() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  $id = intval($_GET['id'] ?? 0);
  check_admin_referer('bsc_delete_ledger_' . $id);

  global $wpdb;
  $table_ledger = $wpdb->prefix . 'bsc_ledger';
  $wpdb->delete($table_ledger, array('id' => $id));

  wp_redirect(admin_url('admin.php?page=bsc-accounts&subtab=expense&deleted=true'));
  exit;
}

// --- Staff & Crew CRUD Handlers ---
add_action('admin_post_bsc_add_staff', 'bsc_handle_add_staff');
function bsc_handle_add_staff() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_add_staff_verify');
  global $wpdb;
  $table = $wpdb->prefix . 'bsc_staff_crew';

  $wpdb->insert($table, array(
    'staff_code'       => '#STF-' . rand(1000, 9999),
    'name'             => sanitize_text_field($_POST['staff_name'] ?? ''),
    'role_title'       => sanitize_text_field($_POST['staff_role'] ?? 'Operations Crew'),
    'assigned_hall'    => sanitize_text_field($_POST['assigned_hall'] ?? 'All Multiplex Facilities'),
    'duty_status'      => sanitize_text_field($_POST['duty_status'] ?? 'On Duty'),
    'shift_schedule'   => sanitize_text_field($_POST['shift_schedule'] ?? 'Full Rotational'),
    'phone'            => sanitize_text_field($_POST['staff_phone'] ?? ''),
    'email'            => sanitize_email($_POST['staff_email'] ?? ''),
    'salary'           => floatval($_POST['staff_salary'] ?? 25000.00),
    'photo_url'        => esc_url_raw($_POST['photo_url'] ?? ''),
    'responsibilities' => sanitize_textarea_field($_POST['responsibilities'] ?? ''),
    'joined_date'      => current_time('Y-m-d')
  ));

  wp_redirect(admin_url('admin.php?page=bsc-users&subtab=all&staff_added=true'));
  exit;
}

add_action('admin_post_bsc_edit_staff', 'bsc_handle_edit_staff');
function bsc_handle_edit_staff() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_edit_staff_verify');
  global $wpdb;
  $table = $wpdb->prefix . 'bsc_staff_crew';
  $id    = intval($_POST['staff_id'] ?? 0);

  if ($id <= 0) wp_die('Invalid staff identifier.');

  $wpdb->update($table, array(
    'name'             => sanitize_text_field($_POST['staff_name'] ?? ''),
    'role_title'       => sanitize_text_field($_POST['staff_role'] ?? 'Operations Crew'),
    'assigned_hall'    => sanitize_text_field($_POST['assigned_hall'] ?? 'All Multiplex Facilities'),
    'duty_status'      => sanitize_text_field($_POST['duty_status'] ?? 'On Duty'),
    'shift_schedule'   => sanitize_text_field($_POST['shift_schedule'] ?? 'Full Rotational'),
    'phone'            => sanitize_text_field($_POST['staff_phone'] ?? ''),
    'email'            => sanitize_email($_POST['staff_email'] ?? ''),
    'salary'           => floatval($_POST['staff_salary'] ?? 25000.00),
    'photo_url'        => esc_url_raw($_POST['photo_url'] ?? ''),
    'responsibilities' => sanitize_textarea_field($_POST['responsibilities'] ?? '')
  ), array('id' => $id));

  wp_redirect(admin_url('admin.php?page=bsc-users&subtab=all&staff_updated=true'));
  exit;
}

add_action('admin_post_bsc_delete_staff', 'bsc_handle_delete_staff');
function bsc_handle_delete_staff() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  $id = intval($_GET['id'] ?? 0);
  check_admin_referer('bsc_delete_staff_' . $id);

  global $wpdb;
  $table = $wpdb->prefix . 'bsc_staff_crew';
  $wpdb->delete($table, array('id' => $id));

  wp_redirect(admin_url('admin.php?page=bsc-users&subtab=all&staff_deleted=true'));
  exit;
}

// --- Manual Booking Handler (Cash or bKash) ---
add_action('admin_post_bsc_manual_booking', 'bsc_handle_manual_booking');
function bsc_handle_manual_booking() {
  if (!current_user_can('manage_options')) wp_die('Unauthorized');
  check_admin_referer('bsc_manual_booking_verify');
  global $wpdb;
  $table_bookings  = $wpdb->prefix . 'bsc_bookings';
  $table_ledger    = $wpdb->prefix . 'bsc_ledger';
  $table_customers = $wpdb->prefix . 'bsc_customers';

  $movie        = sanitize_text_field($_POST['manual_movie'] ?? 'Feature Presentation');
  $booking_date = sanitize_text_field($_POST['manual_booking_date'] ?? current_time('Y-m-d'));
  $showtime     = sanitize_text_field($_POST['manual_showtime'] ?? '18:00');
  $ticket_type  = sanitize_text_field($_POST['manual_ticket_type'] ?? 'Standard');
  $audit        = sanitize_text_field($_POST['manual_audit'] ?? 'Auditorium 1 (23 Seats)');
  $seats        = sanitize_text_field($_POST['manual_seats'] ?? '');
  $amount       = floatval($_POST['manual_amount'] ?? 0.00);
  $method       = sanitize_text_field($_POST['payment_method'] ?? 'Cash');
  $cust_name    = sanitize_text_field($_POST['customer_name'] ?? 'Walk-In Guest');
  $cust_phone   = sanitize_text_field($_POST['customer_phone'] ?? '');
  $cust_email   = sanitize_email($_POST['customer_email'] ?? '');
  $token        = '#BSC-' . rand(1000, 9999) . '-TX';

  if (empty($seats)) {
    wp_die('Error: Please choose at least one seat from the seat map.');
  }

  $status_label = ($method === 'bKash') ? 'bKash Paid' : 'Counter Cash';

  $wpdb->insert($table_bookings, array(
    'booking_token'  => $token,
    'movie_title'    => $movie,
    'auditorium'     => $audit,
    'showtime'       => $showtime,
    'ticket_type'    => ucfirst($ticket_type),
    'seats'          => $seats,
    'customer_name'  => $cust_name,
    'customer_phone' => $cust_phone,
    'customer_email' => $cust_email,
    'total_amount'   => $amount,
    'payment_method' => $method,
    'payment_status' => $status_label,
    'booking_date'   => $booking_date . ' ' . current_time('H:i:s')
  ));

  $wpdb->insert($table_ledger, array(
    'reference_no' => $token,
    'txn_type'     => 'Income',
    'particulars'  => 'Walk-In: ' . $movie . ' (' . $cust_name . ' - Seats: ' . $seats . ')',
    'method'       => $method,
    'amount'       => $amount
  ));

  if (!empty($cust_email) || !empty($cust_phone)) {
    $existing = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_customers WHERE (email = %s AND email != '') OR (phone = %s AND phone != '') LIMIT 1",
      $cust_email,
      $cust_phone
    ));

    if ($existing) {
      $wpdb->query($wpdb->prepare(
        "UPDATE $table_customers SET total_visits = total_visits + 1, reward_points = reward_points + 100 WHERE id = %d",
        $existing->id
      ));
    } else {
      $membership = '#BSC-' . rand(10000, 99999);
      $wpdb->insert($table_customers, array(
        'membership_id' => $membership,
        'name'          => $cust_name,
        'email'         => $cust_email,
        'phone'         => $cust_phone,
        'tier'          => ($ticket_type === 'vip') ? 'VIP CLUB' : 'STANDARD',
        'total_visits'  => 1,
        'reward_points' => 100
      ));
    }
  }

  wp_redirect(admin_url('admin.php?page=bsc-booking&subtab=all&booking_added=true'));
  exit;
}

// --- Frontend Public Checkout Handler (admin-post.php) ---
add_action('admin_post_nopriv_bsc_process_frontend_checkout', 'bsc_handle_frontend_checkout');
add_action('admin_post_bsc_process_frontend_checkout', 'bsc_handle_frontend_checkout');
function bsc_handle_frontend_checkout() {
    if (!isset($_POST['bsc_checkout_nonce']) || !wp_verify_nonce($_POST['bsc_checkout_nonce'], 'bsc_frontend_checkout_verify')) {
        wp_die(__('Security verification failed. Please go back and try again.', 'big-screen-cinemas'), __('Checkout Error', 'big-screen-cinemas'), array('response' => 403));
    }

    global $wpdb;
    $table_bookings  = $wpdb->prefix . 'bsc_bookings';
    $table_ledger    = $wpdb->prefix . 'bsc_ledger';
    $table_customers = $wpdb->prefix . 'bsc_customers';

    $movie        = sanitize_text_field($_POST['movie_title'] ?? 'Feature Presentation');
    $audit        = sanitize_text_field($_POST['auditorium'] ?? 'Auditorium 1 (23 Seats)');
    $seats        = sanitize_text_field($_POST['seats'] ?? 'A1');
    $amount       = floatval($_POST['total_amount'] ?? 525.00);
    $method       = sanitize_text_field($_POST['payment_method'] ?? 'bKash');
    $cust_name    = sanitize_text_field($_POST['customer_name'] ?? 'Online Guest');
    $cust_phone   = sanitize_text_field($_POST['customer_phone'] ?? '');
    $cust_email   = sanitize_email($_POST['customer_email'] ?? '');
    $booking_date = sanitize_text_field($_POST['screening_date'] ?? current_time('Y-m-d'));
    $showtime     = sanitize_text_field($_POST['screening_time'] ?? '18:00');
    $token        = '#BSC-' . rand(1000, 9999) . '-TX';

    if (empty($seats)) {
        wp_die(__('Error: No seats selected for reservation.', 'big-screen-cinemas'));
    }

    $wpdb->insert($table_bookings, array(
        'booking_token'  => $token,
        'movie_title'    => $movie,
        'auditorium'     => $audit,
        'showtime'       => $showtime,
        'ticket_type'    => 'Standard',
        'seats'          => $seats,
        'customer_name'  => $cust_name,
        'customer_phone' => $cust_phone,
        'customer_email' => $cust_email,
        'total_amount'   => $amount,
        'payment_method' => $method,
        'payment_status' => 'Confirmed Online',
        'booking_date'   => $booking_date . ' ' . current_time('H:i:s')
    ));

    $wpdb->insert($table_ledger, array(
        'reference_no' => $token,
        'txn_type'     => 'Income',
        'particulars'  => 'Online Ticket: ' . $movie . ' (Seats: ' . $seats . ')',
        'method'       => $method,
        'amount'       => $amount
    ));

    if (!empty($cust_email) || !empty($cust_phone)) {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_customers WHERE (email = %s AND email != '') OR (phone = %s AND phone != '') LIMIT 1",
            $cust_email,
            $cust_phone
        ));

        if ($existing) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_customers SET total_visits = total_visits + 1, reward_points = reward_points + 100 WHERE id = %d",
                $existing->id
            ));
        } else {
            $membership = '#BSC-' . rand(10000, 99999);
            $wpdb->insert($table_customers, array(
                'membership_id' => $membership,
                'name'          => $cust_name,
                'email'         => $cust_email,
                'phone'         => $cust_phone,
                'tier'          => 'VIP CLUB',
                'total_visits'  => 1,
                'reward_points' => 100
            ));
        }
    }

    $thank_you_url = add_query_arg(array(
        'token' => $token,
        'name'  => urlencode($cust_name),
        'movie' => urlencode($movie),
        'seats' => urlencode($seats),
        'total' => $amount
    ), home_url('/thank-you/'));

    wp_redirect($thank_you_url);
    exit;
}

/* ==========================================================================
   04. CUSTOM AUTHENTICATION, REGISTRATION & FULL-SCREEN LOGIN REDIRECT
   ========================================================================== */

// --- Handle Custom Sign In ---
add_action('admin_post_nopriv_bsc_custom_login', 'bsc_handle_custom_login');
add_action('admin_post_bsc_custom_login', 'bsc_handle_custom_login');
function bsc_handle_custom_login() {
    $creds = array(
        'user_login'    => sanitize_text_field($_POST['log'] ?? ''),
        'user_password' => $_POST['pwd'] ?? '',
        'remember'      => isset($_POST['rememberme'])
    );

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        $login_page = home_url('/login/');
        wp_redirect(add_query_arg('login', 'failed', $login_page));
        exit;
    }

    $redirect_url = !empty($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url('/account/');
    wp_redirect($redirect_url);
    exit;
}

// --- Force Full-Screen Dashboard Redirection on WP Login ---
add_filter('login_redirect', 'bsc_full_screen_login_redirect', 10, 3);
function bsc_full_screen_login_redirect($redirect_to, $request, $user) {
  if (isset($user->roles) && is_array($user->roles)) {
    if (in_array('administrator', $user->roles) || in_array('manage_options', $user->caps ?? array())) {
      return admin_url('admin.php?page=bsc-dashboard');
    }
  }
  return admin_url('admin.php?page=bsc-dashboard');
}

// --- Handle Custom Registration ---
add_action('admin_post_nopriv_bsc_custom_register', 'bsc_handle_custom_register');
add_action('admin_post_bsc_custom_register', 'bsc_handle_custom_register');
function bsc_handle_custom_register() {
    global $wpdb;
    $table_customers = $wpdb->prefix . 'bsc_customers';

    $name     = sanitize_text_field($_POST['reg_name'] ?? '');
    $email    = sanitize_email($_POST['reg_email'] ?? '');
    $password = $_POST['reg_pass'] ?? '';

    if (empty($email) || empty($password) || empty($name)) {
        wp_die('Please fill out all required registration fields.');
    }

    if (email_exists($email) || username_exists($email)) {
        wp_die('An account with this email address already exists. Please sign in.');
    }

    $user_id = wp_create_user($email, $password, $email);
    if (is_wp_error($user_id)) {
        wp_die($user_id->get_error_message());
    }

    update_user_meta($user_id, 'first_name', $name);

    $membership_id = '#BSC-' . rand(10000, 99999);
    $wpdb->insert($table_customers, array(
        'membership_id' => $membership_id,
        'name'          => $name,
        'email'         => $email,
        'phone'         => '',
        'tier'          => 'VIP CLUB',
        'total_visits'  => 1,
        'reward_points' => 150
    ));

    $creds = array('user_login' => $email, 'user_password' => $password, 'remember' => true);
    wp_signon($creds, false);

    wp_redirect(home_url('/account/'));
    exit;
}

/* ==========================================================================
   05. PURE-CODE GOOGLE OAUTH 2.0 LOGIN LISTENER
   ========================================================================== */
add_action('init', 'bsc_handle_google_oauth_callback');
function bsc_handle_google_oauth_callback() {
    if (isset($_GET['bsc_auth']) && $_GET['bsc_auth'] === 'google_callback') {
        $client_id     = get_option('bsc_google_client_id', 'YOUR_GOOGLE_CLIENT_ID');
        $client_secret = get_option('bsc_google_client_secret', 'YOUR_GOOGLE_CLIENT_SECRET');
        $redirect_uri  = home_url('/account/?bsc_auth=google_callback');

        if (isset($_GET['code'])) {
            $code = sanitize_text_field($_GET['code']);

            $token_response = wp_remote_post('https://oauth2.googleapis.com/token', array(
                'body' => array(
                    'code'          => $code,
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'redirect_uri'  => $redirect_uri,
                    'grant_type'    => 'authorization_code',
                )
            ));

            if (!is_wp_error($token_response)) {
                $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
                if (isset($token_body['access_token'])) {
                    $access_token = $token_body['access_token'];

                    $profile_response = wp_remote_get('https://www.googleapis.com/oauth2/v3/userinfo', array(
                        'headers' => array('Authorization' => 'Bearer ' . $access_token)
                    ));

                    if (!is_wp_error($profile_response)) {
                        $profile = json_decode(wp_remote_retrieve_body($profile_response), true);
                        
                        if (!empty($profile['email'])) {
                            $email = sanitize_email($profile['email']);
                            $name  = sanitize_text_field($profile['name'] ?? 'Google User');

                            global $wpdb;
                            $table_customers = $wpdb->prefix . 'bsc_customers';
                            $user = get_user_by('email', $email);

                            if (!$user) {
                                $username = sanitize_user(current(explode('@', $email)) . '_' . rand(100, 999), true);
                                $password = wp_generate_password(16, false);
                                $user_id  = wp_create_user($username, $password, $email);

                                if (!is_wp_error($user_id)) {
                                    update_user_meta($user_id, 'first_name', $name);
                                    $user = get_user_by('id', $user_id);

                                    $wpdb->insert($table_customers, array(
                                        'membership_id' => '#BSC-' . rand(10000, 99999),
                                        'name'          => $name,
                                        'email'         => $email,
                                        'phone'         => '',
                                        'tier'          => 'VIP CLUB',
                                        'total_visits'  => 1,
                                        'reward_points' => 150
                                    ));
                                }
                            }

                            if ($user && !is_wp_error($user)) {
                                wp_set_current_user($user->ID, $user->user_login);
                                wp_set_auth_cookie($user->ID, true);
                                do_action('wp_login', $user->user_login, $user);

                                wp_safe_redirect(home_url('/account/'));
                                exit;
                            }
                        }
                    }
                }
            }
        }
        wp_safe_redirect(home_url('/login/?login=failed'));
        exit;
    }
}

// --- REST API Endpoints ---
add_action('rest_api_init', 'bsc_register_rest_routes');
function bsc_register_rest_routes() {
  register_rest_route('bsc/v1', '/book-ticket', array(
    'methods'             => 'POST',
    'callback'            => 'bsc_api_book_ticket',
    'permission_callback' => '__return_true',
  ));
}

function bsc_api_book_ticket($request) {
  global $wpdb;
  $table_bookings = $wpdb->prefix . 'bsc_bookings';
  $table_ledger   = $wpdb->prefix . 'bsc_ledger';
  $params         = $request->get_json_params();

  $movie_title  = sanitize_text_field($params['movie_title'] ?? 'Feature Presentation');
  $auditorium   = sanitize_text_field($params['auditorium'] ?? 'Auditorium 1 (23 Seats)');
  $seats        = sanitize_text_field(implode(', ', (array)($params['seats'] ?? array())));
  $total_amount = floatval($params['total_amount'] ?? 525.00);
  $token        = sanitize_text_field($params['token'] ?? ('#BSC-' . rand(1000, 9999) . '-TX'));
  $method       = sanitize_text_field($params['method'] ?? 'Online Gateway');
  $booking_date = sanitize_text_field($params['screening_date'] ?? current_time('Y-m-d'));
  $showtime     = sanitize_text_field($params['screening_time'] ?? '18:00');

  $wpdb->insert($table_bookings, array(
    'booking_token'  => $token,
    'movie_title'    => $movie_title,
    'auditorium'     => $auditorium,
    'showtime'       => $showtime,
    'ticket_type'    => 'Standard',
    'seats'          => $seats,
    'customer_name'  => sanitize_text_field($params['customer_name'] ?? 'Online Customer'),
    'customer_phone' => sanitize_text_field($params['customer_phone'] ?? ''),
    'customer_email' => sanitize_email($params['customer_email'] ?? ''),
    'total_amount'   => $total_amount,
    'payment_method' => $method,
    'payment_status' => 'Confirmed Online',
    'booking_date'   => $booking_date . ' ' . current_time('H:i:s')
  ));

  $wpdb->insert($table_ledger, array(
    'reference_no' => $token,
    'txn_type'     => 'Income',
    'particulars'  => 'Ticket Booking: ' . $movie_title . ' (Seats: ' . $seats . ')',
    'method'       => $method,
    'amount'       => $total_amount
  ));

  return rest_ensure_response(array('success' => true, 'token' => $token));
}

/* ==========================================================================
   06. CUSTOM LOGIN URL REDIRECTION GATEWAY (FRONTEND ONLY)
   ========================================================================== */
add_filter('login_url', 'bsc_redirect_to_custom_login', 10, 3);
function bsc_redirect_to_custom_login($login_url, $redirect, $force_reauth) {
    if (is_admin() || strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
        if (empty($redirect) && !isset($_GET['reauth'])) {
            return $login_url;
        }
    }

    $custom_login = home_url('/login/');

    if (!empty($redirect)) {
        $custom_login = add_query_arg('redirect_to', urlencode($redirect), $custom_login);
    }

    return $custom_login;
}