<?php
/**
 * Movie Module - Add & Edit Movie Views & Submission Handlers
 *
 * Path: admin/movies/add-edit-movie.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -------------------------------------------------------------
// 1. PROCESS FORM SUBMISSIONS ON admin_init (PREVENTS HEADER CONFLICTS)
// -------------------------------------------------------------
function bsc_handle_movie_form_submissions() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['bsc_action'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'bsc_control_movies';

    $action     = sanitize_text_field( wp_unslash( $_POST['bsc_action'] ?? '' ) );
    $title      = sanitize_text_field( wp_unslash( $_POST['movie_title'] ?? '' ) );
    $category   = sanitize_text_field( wp_unslash( $_POST['movie_category'] ?? 'Now Showing' ) );
    $release_dt = sanitize_text_field( wp_unslash( $_POST['movie_release_date'] ?? current_time( 'Y-m-d' ) ) );
    $genre      = sanitize_text_field( wp_unslash( $_POST['movie_genre'] ?? '' ) );
    $duration   = sanitize_text_field( wp_unslash( $_POST['movie_duration'] ?? '' ) );
    $audio      = sanitize_text_field( wp_unslash( $_POST['movie_audio_lang'] ?? '' ) );
    $badge      = sanitize_text_field( wp_unslash( $_POST['movie_badge'] ?? '' ) );
    $rating     = sanitize_text_field( wp_unslash( $_POST['movie_rating'] ?? '' ) );
    $age_rating = sanitize_text_field( wp_unslash( $_POST['movie_age_rating'] ?? '' ) );
    $director   = sanitize_text_field( wp_unslash( $_POST['movie_director'] ?? '' ) );
    $writers    = sanitize_text_field( wp_unslash( $_POST['movie_writers'] ?? '' ) );
    $poster     = esc_url_raw( wp_unslash( $_POST['movie_poster'] ?? '' ) );
    $trailer    = esc_url_raw( wp_unslash( $_POST['movie_trailer'] ?? '' ) );
    $short_desc = sanitize_textarea_field( wp_unslash( $_POST['movie_short_desc'] ?? '' ) );
    $synopsis   = sanitize_textarea_field( wp_unslash( $_POST['movie_synopsis'] ?? '' ) );
    $auditorium = sanitize_text_field( wp_unslash( $_POST['movie_auditorium'] ?? 'Auditorium 1 (23 Seats)' ) );

    // Parse and format Schedule Days
    $raw_schedule = $_POST['schedule_days'] ?? array();
    $formatted_schedule = array();
    if ( is_array( $raw_schedule ) ) {
        foreach ( $raw_schedule as $day_data ) {
            $d = sanitize_text_field( $day_data['date'] ?? '' );
            $t_arr = $day_data['times'] ?? array();
            if ( ! empty( $d ) && is_array( $t_arr ) ) {
                $clean_times = array_map( 'sanitize_text_field', $t_arr );
                $formatted_schedule[] = array(
                    'date'  => $d,
                    'times' => array_values( array_filter( $clean_times ) ),
                );
            }
        }
    }
    $showtimes_json = wp_json_encode( $formatted_schedule );
    $release_date_json = wp_json_encode( array( $release_dt ) );

    // Parse Cast & Crew repeater data
    $c_names  = $_POST['cast_names'] ?? array();
    $c_roles  = $_POST['cast_roles'] ?? array();
    $c_photos = $_POST['cast_photos'] ?? array();
    $cast_array = array();
    if ( is_array( $c_names ) ) {
        foreach ( $c_names as $i => $name ) {
            $n = sanitize_text_field( $name );
            $r = sanitize_text_field( $c_roles[$i] ?? '' );
            $p = esc_url_raw( $c_photos[$i] ?? '' );
            if ( ! empty( $n ) ) {
                $cast_array[] = array( 'name' => $n, 'role' => $r, 'photo' => $p );
            }
        }
    }
    $cast_json = wp_json_encode( $cast_array );

    $data_payload = array(
        'title'             => $title,
        'category'          => $category,
        'release_date'      => $release_date_json,
        'genre'             => $genre,
        'duration'          => $duration,
        'audio_language'    => $audio,
        'badge_tag'         => $badge,
        'rating'            => $rating,
        'age_rating'        => $age_rating,
        'director'          => $director,
        'writers'           => $writers,
        'poster_url'        => $poster,
        'trailer_url'       => $trailer,
        'short_description' => $short_desc,
        'synopsis'          => $synopsis,
        'showtimes'         => $showtimes_json,
        'cast_crew'         => $cast_json,
        'auditorium'        => $auditorium,
    );

    $data_formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

    if ( 'add_movie' === $action ) {
        check_admin_referer( 'bsc_add_movie_verify' );
        $inserted = $wpdb->insert( $table, $data_payload, $data_formats );
        if ( false !== $inserted ) {
            wp_safe_redirect( admin_url( 'admin.php?page=bsc-movies&subtab=all&movie_added=true' ) );
            exit;
        }
    } elseif ( 'edit_movie' === $action ) {
        check_admin_referer( 'bsc_edit_movie_verify' );
        $movie_id = absint( $_POST['movie_id'] ?? 0 );
        if ( $movie_id > 0 ) {
            $updated = $wpdb->update( $table, $data_payload, array( 'id' => $movie_id ), $data_formats, array( '%d' ) );
            if ( false !== $updated ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-movies&subtab=all&movie_updated=true' ) );
                exit;
            }
        }
    }
}
add_action( 'admin_init', 'bsc_handle_movie_form_submissions' );

// -------------------------------------------------------------
// 2. UI VIEW RENDERING
// -------------------------------------------------------------
global $wpdb;
$table = $wpdb->prefix . 'bsc_control_movies';

$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
$movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$movie = null;
$is_edit = ( 'edit' === $subtab && $movie_id > 0 );

if ( $is_edit ) {
    $movie = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $movie_id ) );
}

$cast_list = array();
if ( $movie && ! empty( $movie->cast_crew ) ) {
    $cast_list = json_decode( $movie->cast_crew, true );
}
if ( empty( $cast_list ) ) {
    $cast_list = array( array( 'name' => '', 'role' => '', 'photo' => '' ) );
}

// Parse official movie release date
$official_release_date = current_time( 'Y-m-d' );
if ( $movie && ! empty( $movie->release_date ) ) {
    $decoded_dates = json_decode( $movie->release_date, true );
    if ( is_array( $decoded_dates ) && ! empty( $decoded_dates ) ) {
        $official_release_date = reset( $decoded_dates );
    } else {
        $parts = array_map( 'trim', explode( ',', $movie->release_date ) );
        if ( ! empty( $parts[0] ) ) {
            $official_release_date = $parts[0];
        }
    }
}

// Parse grouped date schedules with nested times
$day_schedules = array(
    array(
        'date'  => current_time( 'Y-m-d' ),
        'times' => array( '15:00', '18:30', '21:30' ),
    ),
);

if ( $movie && ! empty( $movie->showtimes ) ) {
    $decoded_showtimes = json_decode( $movie->showtimes, true );
    if ( is_array( $decoded_showtimes ) && ! empty( $decoded_showtimes ) ) {
        if ( isset( $decoded_showtimes[0]['date'] ) && isset( $decoded_showtimes[0]['times'] ) ) {
            $day_schedules = $decoded_showtimes;
        } elseif ( isset( $decoded_showtimes[0]['date'] ) && isset( $decoded_showtimes[0]['time'] ) ) {
            $grouped = array();
            foreach ( $decoded_showtimes as $item ) {
                $d = $item['date'] ?? current_time( 'Y-m-d' );
                $t = $item['time'] ?? '18:00';
                if ( ! isset( $grouped[ $d ] ) ) {
                    $grouped[ $d ] = array( 'date' => $d, 'times' => array() );
                }
                $grouped[ $d ]['times'][] = $t;
            }
            $day_schedules = array_values( $grouped );
        } else {
            $day_schedules = array(
                array(
                    'date'  => current_time( 'Y-m-d' ),
                    'times' => is_array( $decoded_showtimes ) ? $decoded_showtimes : array( '18:00' ),
                ),
            );
        }
    }
}
?>

<style>
    .bsc-form-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .bsc-section-title {
        font-size: 0.95rem;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 16px 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #f1f5f9;
    }
    .bsc-grid-2 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 14px;
    }
    .bsc-grid-3 {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 14px;
    }
    .bsc-input-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 5px;
    }
    .bsc-input-group input,
    .bsc-input-group select,
    .bsc-input-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #ffffff;
        font-size: 0.88rem;
        color: #0f172a;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.2s;
    }
    .bsc-input-group input:focus,
    .bsc-input-group select:focus,
    .bsc-input-group textarea:focus {
        border-color: #059669;
    }
    .bsc-schedule-scrollbox {
        max-height: 480px;
        overflow-y: auto;
        padding-right: 6px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .bsc-schedule-scrollbox::-webkit-scrollbar { width: 6px; }
    .bsc-schedule-scrollbox::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .bsc-day-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px 14px;
        transition: border-color 0.15s;
    }
    .bsc-day-card:hover { border-color: #38bdf8; }
    .bsc-weekday-badge {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
        font-size: 0.72rem;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 4px;
        min-width: 40px;
        text-align: center;
    }
    .bsc-time-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        padding: 3px 8px;
        border-radius: 6px;
    }
    .bsc-time-pill input[type="time"] {
        border: none !important;
        padding: 0 !important;
        font-size: 0.82rem !important;
        font-weight: 700;
        color: #0f172a;
        background: transparent;
    }
    .bsc-btn-duplicate {
        color: #0284c7;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 5px;
        cursor: pointer;
    }
    .bsc-btn-duplicate:hover { background: #e0f2fe; }
    .bsc-btn-remove {
        color: #ef4444;
        background: #fef2f2;
        border: 1px solid #fecaca;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 5px;
        cursor: pointer;
    }
    .bsc-btn-remove:hover { background: #fee2e2; }
</style>

<div style="max-width:920px; margin:20px auto;">
  <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-movies' ) ); ?>" id="bscMovieForm">
    <?php 
    if ( $is_edit ) {
        wp_nonce_field( 'bsc_edit_movie_verify' );
        echo '<input type="hidden" name="bsc_action" value="edit_movie">';
        echo '<input type="hidden" name="movie_id" value="' . esc_attr( $movie->id ) . '">';
    } else {
        wp_nonce_field( 'bsc_add_movie_verify' );
        echo '<input type="hidden" name="bsc_action" value="add_movie">';
    }
    echo '<input type="hidden" name="movie_auditorium" value="' . esc_attr( $movie->auditorium ?? 'Auditorium 1 (23 Seats)' ) . '">';
    ?>

    <!-- 01. CORE MOVIE INFO -->
    <div class="bsc-form-card">
      <h4 class="bsc-section-title">🎬 <?php esc_html_e( 'Core Movie Information', 'bsc-cinema-manager' ); ?></h4>

      <div class="bsc-grid-2">
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Movie Title', 'bsc-cinema-manager' ); ?> *</label>
          <input type="text" name="movie_title" required value="<?php echo esc_attr( $movie->title ?? '' ); ?>" placeholder="e.g. Borbaad">
        </div>
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Movie Category', 'bsc-cinema-manager' ); ?> *</label>
          <select name="movie_category" required>
            <?php $current_category = $movie->category ?? 'Now Showing'; ?>
            <option value="Now Showing" <?php selected( $current_category, 'Now Showing' ); ?>><?php esc_html_e( 'Now Showing', 'bsc-cinema-manager' ); ?></option>
            <option value="Upcoming" <?php selected( $current_category, 'Upcoming' ); ?>><?php esc_html_e( 'Upcoming', 'bsc-cinema-manager' ); ?></option>
            <option value="Festival Special" <?php selected( $current_category, 'Festival Special' ); ?>><?php esc_html_e( 'Festival Special', 'bsc-cinema-manager' ); ?></option>
          </select>
        </div>
      </div>

      <div class="bsc-grid-2">
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Official Theatrical Release Date', 'bsc-cinema-manager' ); ?> *</label>
          <input type="date" name="movie_release_date" required value="<?php echo esc_attr( $official_release_date ); ?>">
        </div>
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Genre', 'bsc-cinema-manager' ); ?> *</label>
          <input type="text" name="movie_genre" required value="<?php echo esc_attr( $movie->genre ?? '' ); ?>" placeholder="e.g. Action / Thriller">
        </div>
      </div>

      <div class="bsc-grid-3">
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Duration', 'bsc-cinema-manager' ); ?> *</label>
          <input type="text" name="movie_duration" required value="<?php echo esc_attr( $movie->duration ?? '' ); ?>" placeholder="e.g. 02h 25m">
        </div>
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Language & Audio', 'bsc-cinema-manager' ); ?> *</label>
          <input type="text" name="movie_audio_lang" required value="<?php echo esc_attr( $movie->audio_language ?? 'Bengali (Dolby Atmos 128-Ch)' ); ?>">
        </div>
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Format Badge', 'bsc-cinema-manager' ); ?> *</label>
          <input type="text" name="movie_badge" required value="<?php echo esc_attr( $movie->badge_tag ?? '4K DUAL LASER' ); ?>">
        </div>
      </div>

      <div class="bsc-grid-3">
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Rating (IMDb)', 'bsc-cinema-manager' ); ?> *</label>
          <input type="text" name="movie_rating" required value="<?php echo esc_attr( $movie->rating ?? '9.0' ); ?>">
        </div>
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Age / Censor Rating', 'bsc-cinema-manager' ); ?> *</label>
          <input type="text" name="movie_age_rating" required value="<?php echo esc_attr( $movie->age_rating ?? 'R-Rated (18+)' ); ?>">
        </div>
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Director', 'bsc-cinema-manager' ); ?> *</label>
          <input type="text" name="movie_director" required value="<?php echo esc_attr( $movie->director ?? '' ); ?>" placeholder="e.g. Raihan Rafi">
        </div>
      </div>

      <div class="bsc-grid-1">
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Screenwriters / Story', 'bsc-cinema-manager' ); ?> *</label>
          <input type="text" name="movie_writers" required value="<?php echo esc_attr( $movie->writers ?? '' ); ?>" placeholder="e.g. Mehedi Hasan">
        </div>
      </div>
    </div>

    <!-- 02. MEDIA & SYNOPSIS -->
    <div class="bsc-form-card">
      <h4 class="bsc-section-title">🖼️ <?php esc_html_e( 'Media, Synopsis & Storyline', 'bsc-cinema-manager' ); ?></h4>

      <div class="bsc-grid-2">
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Movie Poster', 'bsc-cinema-manager' ); ?> *</label>
          <div style="background:#f8fafc; border:1px dashed #cbd5e1; padding:12px; border-radius:8px; text-align:center;">
            <input type="hidden" name="movie_poster" id="movie_poster_url" required value="<?php echo esc_url( $movie->poster_url ?? '' ); ?>">
            <img id="poster_preview" src="<?php echo esc_url( $movie->poster_url ?? '' ); ?>" style="<?php echo ! empty( $movie->poster_url ) ? 'display:block;' : 'display:none;'; ?> max-height:120px; margin:0 auto 8px auto; border-radius:6px; object-fit:cover;">
            <button type="button" class="button" id="btn_upload_poster">📷 <?php esc_html_e( 'Choose Poster', 'bsc-cinema-manager' ); ?></button>
            <button type="button" class="button" id="btn_remove_poster" style="<?php echo ! empty( $movie->poster_url ) ? 'display:inline-block;' : 'display:none;'; ?> color:#ef4444;"><?php esc_html_e( 'Remove', 'bsc-cinema-manager' ); ?></button>
          </div>
        </div>

        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Official Trailer Video', 'bsc-cinema-manager' ); ?></label>
          <div style="background:#f8fafc; border:1px dashed #cbd5e1; padding:12px; border-radius:8px; text-align:center;">
            <input type="hidden" name="movie_trailer" id="movie_trailer_url" value="<?php echo esc_url( $movie->trailer_url ?? '' ); ?>">
            <video id="trailer_preview" src="<?php echo esc_url( $movie->trailer_url ?? '' ); ?>" controls style="<?php echo ! empty( $movie->trailer_url ) ? 'display:block;' : 'display:none;'; ?> max-height:120px; width:100%; border-radius:6px; margin-bottom:8px;"></video>
            <button type="button" class="button" id="btn_upload_trailer">▶️ <?php esc_html_e( 'Choose Trailer', 'bsc-cinema-manager' ); ?></button>
            <button type="button" class="button" id="btn_remove_trailer" style="<?php echo ! empty( $movie->trailer_url ) ? 'display:inline-block;' : 'display:none;'; ?> color:#ef4444;"><?php esc_html_e( 'Remove', 'bsc-cinema-manager' ); ?></button>
          </div>
        </div>
      </div>

      <div class="bsc-grid-2">
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Short Summary (Card Excerpt)', 'bsc-cinema-manager' ); ?> *</label>
          <textarea name="movie_short_desc" rows="3" required placeholder="Quick 1-2 sentence overview..."><?php echo esc_textarea( $movie->short_description ?? '' ); ?></textarea>
        </div>
        <div class="bsc-input-group">
          <label><?php esc_html_e( 'Full Storyline & Synopsis', 'bsc-cinema-manager' ); ?> *</label>
          <textarea name="movie_synopsis" rows="3" required placeholder="Detailed movie plot and notes..."><?php echo esc_textarea( $movie->synopsis ?? '' ); ?></textarea>
        </div>
      </div>
    </div>

    <!-- 03. SCREENING SCHEDULE PLANNER -->
    <div class="bsc-form-card" style="border: 1px solid #bbf7d0;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h4 class="bsc-section-title" style="margin:0; border:none; padding:0;">
          🗓️ <?php esc_html_e( 'Screening Schedule Planner', 'bsc-cinema-manager' ); ?>
          <span id="bsc_total_days_count" style="background:#059669; color:#fff; font-size:0.7rem; font-weight:800; padding:2px 8px; border-radius:10px;">
            <?php echo count( $day_schedules ); ?> Days
          </span>
        </h4>
        <button type="button" class="button button-primary" id="btn_add_day_block" style="background:#059669; border-color:#059669; font-weight:700;">
          + <?php esc_html_e( 'Add Single Day', 'bsc-cinema-manager' ); ?>
        </button>
      </div>

      <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; background:#f8fafc; padding:8px 12px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:14px;">
        <span style="font-size:0.75rem; font-weight:800; color:#475569; text-transform:uppercase;">⚡ Quick Auto-Add:</span>
        <button type="button" class="button button-small" onclick="generateBulkSchedule(7)">+ Next 7 Days</button>
        <button type="button" class="button button-small" onclick="generateBulkSchedule(14)">+ Next 14 Days</button>
        <button type="button" class="button button-small" onclick="generateBulkSchedule(30)">+ Next 30 Days</button>

        <div style="margin-left:auto;">
          <input type="text" id="bsc_schedule_search" placeholder="Search date..." onkeyup="filterScheduleDays()" style="padding:4px 8px; font-size:0.78rem; border-radius:5px; border:1px solid #cbd5e1; width:180px;">
        </div>
      </div>

      <div id="bsc_day_schedule_container" class="bsc-schedule-scrollbox">
        <?php foreach ( $day_schedules as $day_index => $schedule_item ) : 
          $item_date = $schedule_item['date'] ?? current_time( 'Y-m-d' );
          $item_times = ! empty( $schedule_item['times'] ) ? (array) $schedule_item['times'] : array( '18:00' );
          $ts = strtotime( $item_date );
          $day_name = $ts ? date( 'D', $ts ) : 'DAY';
          $friendly_date = $ts ? date( 'd M Y', $ts ) : $item_date;
        ?>
          <div class="bsc-day-card" data-day-index="<?php echo intval( $day_index ); ?>" data-date-str="<?php echo esc_attr( $item_date . ' ' . strtolower( $day_name ) . ' ' . strtolower( $friendly_date ) ); ?>">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
              <div style="display:flex; align-items:center; gap:8px;">
                <span class="bsc-weekday-badge"><?php echo esc_html( strtoupper( $day_name ) ); ?></span>
                <span class="bsc-date-label-friendly" style="font-weight:700; font-size:0.85rem; color:#0f172a;"><?php echo esc_html( $friendly_date ); ?></span>
                <input type="date" name="schedule_days[<?php echo intval( $day_index ); ?>][date]" value="<?php echo esc_attr( $item_date ); ?>" required onchange="handleDateInputChange(this)" style="max-width:130px; font-size:0.75rem; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px;">
              </div>
              <div style="display:flex; gap:6px;">
                <button type="button" class="bsc-btn-duplicate" onclick="duplicateDayBlock(this)">📋 Duplicate</button>
                <button type="button" class="bsc-btn-remove" onclick="removeDayBlock(this)">✕ Delete</button>
              </div>
            </div>

            <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
              <span style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase;">Showtimes:</span>
              <div class="bsc-time-slots-container" style="display:flex; flex-wrap:wrap; gap:6px;">
                <?php foreach ( $item_times as $time_slot ) : ?>
                  <div class="bsc-time-pill">
                    <input type="time" name="schedule_days[<?php echo intval( $day_index ); ?>][times][]" value="<?php echo esc_attr( $time_slot ); ?>" required>
                    <button type="button" onclick="removeTimeSlotPill(this)" style="background:none; border:none; color:#94a3b8; font-weight:bold; cursor:pointer; font-size:0.85rem; padding:0 2px;">✕</button>
                  </div>
                <?php endforeach; ?>
              </div>
              <button type="button" class="button button-small" onclick="addTimeSlotToDay(this)" style="padding:1px 6px; font-size:0.72rem;">+ Time</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 04. CAST & CREW -->
    <div class="bsc-form-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h4 class="bsc-section-title" style="margin:0; border:none; padding:0;">🎭 <?php esc_html_e( 'Lead Cast & Crew', 'bsc-cinema-manager' ); ?></h4>
        <button type="button" class="button button-secondary" id="btn_add_cast_row">+ <?php esc_html_e( 'Add Cast Member', 'bsc-cinema-manager' ); ?></button>
      </div>

      <div id="bsc_cast_repeater_container" style="display:flex; flex-direction:column; gap:8px;">
        <?php foreach ( $cast_list as $cast ) : ?>
          <div class="bsc-repeater-row" style="display:flex; align-items:center; gap:8px;">
            <input type="text" name="cast_names[]" placeholder="Actor Name" value="<?php echo esc_attr( $cast['name'] ?? '' ); ?>" required style="flex:1; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px;">
            <input type="text" name="cast_roles[]" placeholder="Role / Character" value="<?php echo esc_attr( $cast['role'] ?? '' ); ?>" required style="flex:1; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px;">
            
            <div style="display:flex; align-items:center; gap:6px;">
              <input type="hidden" name="cast_photos[]" class="cast-photo-url-field" value="<?php echo esc_url( $cast['photo'] ?? '' ); ?>">
              <img src="<?php echo esc_url( $cast['photo'] ?? '' ); ?>" alt="" class="bsc-cast-photo-thumb" style="<?php echo ! empty( $cast['photo'] ) ? 'display:inline-block;' : 'display:none;'; ?> width:30px; height:30px; border-radius:50%; object-fit:cover;">
              <button type="button" class="button button-small btn-upload-cast-photo">📷 Headshot</button>
            </div>
            
            <button type="button" class="bsc-btn-remove" onclick="removeCastRow(this)">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- SUBMIT BUTTON -->
    <button type="submit" class="button button-primary" style="background:#059669; border-color:#059669; width:100%; padding:12px; font-weight:800; font-size:1rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 2px 6px rgba(5,150,105,0.25);">
      💾 <?php echo $is_edit ? esc_html__( 'Update Movie Presentation', 'bsc-cinema-manager' ) : esc_html__( 'Publish Movie Presentation', 'bsc-cinema-manager' ); ?>
    </button>
  </form>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    updateScheduleSummary();

    const btnAddDay = document.getElementById('btn_add_day_block');
    if (btnAddDay) {
      btnAddDay.addEventListener('click', function() {
        const nextDate = getNextConsecutiveDate();
        createDayBlock(nextDate, ['15:00', '18:30', '21:30']);
      });
    }

    initUploader('btn_upload_poster', 'btn_remove_poster', 'movie_poster_url', 'poster_preview', 'image');
    initUploader('btn_upload_trailer', 'btn_remove_trailer', 'movie_trailer_url', 'trailer_preview', 'video');

    document.addEventListener('click', function(e) {
      if (e.target && e.target.classList.contains('btn-upload-cast-photo')) {
        e.preventDefault();
        const btn = e.target;
        const parent = btn.parentElement;
        const urlField = parent.querySelector('.cast-photo-url-field');
        const thumb = parent.querySelector('.bsc-cast-photo-thumb');

        const frame = wp.media({
          title: 'Select Actor Headshot',
          button: { text: 'Use Photo' },
          multiple: false,
          library: { type: 'image' }
        });

        frame.on('select', function() {
          const attachment = frame.state().get('selection').first().toJSON();
          urlField.value = attachment.url;
          thumb.src = attachment.url;
          thumb.style.display = 'inline-block';
        });
        frame.open();
      }
    });

    const btnAddCast = document.getElementById('btn_add_cast_row');
    const castContainer = document.getElementById('bsc_cast_repeater_container');
    if (btnAddCast && castContainer) {
      btnAddCast.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'bsc-repeater-row';
        row.style.cssText = 'display:flex; align-items:center; gap:8px;';
        row.innerHTML = `
          <input type="text" name="cast_names[]" placeholder="Actor Name" required style="flex:1; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px;">
          <input type="text" name="cast_roles[]" placeholder="Role / Character" required style="flex:1; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px;">
          <div style="display:flex; align-items:center; gap:6px;">
            <input type="hidden" name="cast_photos[]" class="cast-photo-url-field" value="">
            <img src="" alt="" class="bsc-cast-photo-thumb" style="display:none; width:30px; height:30px; border-radius:50%; object-fit:cover;">
            <button type="button" class="button button-small btn-upload-cast-photo">📷 Headshot</button>
          </div>
          <button type="button" class="bsc-btn-remove" onclick="removeCastRow(this)">✕</button>
        `;
        castContainer.appendChild(row);
      });
    }
  });

  function formatFriendlyDate(dateStr) {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const day = String(d.getDate()).padStart(2, '0');
    return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`;
  }

  function getDayNameFromDate(dateStr) {
    if (!dateStr) return 'DAY';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return 'DAY';
    const d = new Date(parts[0], parts[1] - 1, parts[2]);
    return d.toLocaleDateString('en-US', { weekday: 'short' });
  }

  function createDayBlock(dateStr, timesArray = ['18:00']) {
    const container = document.getElementById('bsc_day_schedule_container');
    const nextIndex = container.querySelectorAll('.bsc-day-card').length;
    const dayName = getDayNameFromDate(dateStr);
    const friendlyDate = formatFriendlyDate(dateStr);

    const card = document.createElement('div');
    card.className = 'bsc-day-card';
    card.setAttribute('data-day-index', nextIndex);
    card.setAttribute('data-date-str', (dateStr + ' ' + dayName + ' ' + friendlyDate).toLowerCase());

    let timesHtml = '';
    timesArray.forEach(t => {
      timesHtml += `
        <div class="bsc-time-pill">
          <input type="time" name="schedule_days[${nextIndex}][times][]" value="${t}" required>
          <button type="button" onclick="removeTimeSlotPill(this)" style="background:none; border:none; color:#94a3b8; font-weight:bold; cursor:pointer; font-size:0.85rem; padding:0 2px;">✕</button>
        </div>
      `;
    });

    card.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div style="display:flex; align-items:center; gap:8px;">
          <span class="bsc-weekday-badge">${dayName.toUpperCase()}</span>
          <span class="bsc-date-label-friendly" style="font-weight:700; font-size:0.85rem; color:#0f172a;">${friendlyDate}</span>
          <input type="date" name="schedule_days[${nextIndex}][date]" value="${dateStr}" required onchange="handleDateInputChange(this)" style="max-width:130px; font-size:0.75rem; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px;">
        </div>
        <div style="display:flex; gap:6px;">
          <button type="button" class="bsc-btn-duplicate" onclick="duplicateDayBlock(this)">📋 Duplicate</button>
          <button type="button" class="bsc-btn-remove" onclick="removeDayBlock(this)">✕ Delete</button>
        </div>
      </div>

      <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
        <span style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase;">Showtimes:</span>
        <div class="bsc-time-slots-container" style="display:flex; flex-wrap:wrap; gap:6px;">
          ${timesHtml}
        </div>
        <button type="button" class="button button-small" onclick="addTimeSlotToDay(this)" style="padding:1px 6px; font-size:0.72rem;">+ Time</button>
      </div>
    `;

    container.appendChild(card);
    updateScheduleSummary();
    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function duplicateDayBlock(btn) {
    const card = btn.closest('.bsc-day-card');
    const currentDate = card.querySelector('input[type="date"]').value;
    
    const times = [];
    card.querySelectorAll('.bsc-time-slots-container input[type="time"]').forEach(input => {
      if (input.value) times.push(input.value);
    });

    let targetDateStr = '';
    if (currentDate) {
      const parts = currentDate.split('-');
      const d = new Date(parts[0], parts[1] - 1, parts[2]);
      d.setDate(d.getDate() + 1);
      targetDateStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    } else {
      targetDateStr = getNextConsecutiveDate();
    }

    createDayBlock(targetDateStr, times.length ? times : ['15:00', '18:30', '21:30']);
  }

  function generateBulkSchedule(numDays) {
    if (!confirm(`Add ${numDays} consecutive screening days?`)) return;

    let start = new Date();
    const defaultTemplate = ['15:00', '18:30', '21:30'];
    const container = document.getElementById('bsc_day_schedule_container');
    const existingCards = container.querySelectorAll('.bsc-day-card');
    
    if (existingCards.length > 0) {
      const lastInput = existingCards[existingCards.length - 1].querySelector('input[type="date"]');
      if (lastInput && lastInput.value) {
        const parts = lastInput.value.split('-');
        start = new Date(parts[0], parts[1] - 1, parts[2]);
        start.setDate(start.getDate() + 1);
      }
    }

    for (let i = 0; i < numDays; i++) {
      const y = start.getFullYear();
      const m = String(start.getMonth() + 1).padStart(2, '0');
      const d = String(start.getDate()).padStart(2, '0');
      createDayBlock(`${y}-${m}-${d}`, defaultTemplate);
      start.setDate(start.getDate() + 1);
    }
  }

  function addTimeSlotToDay(btn) {
    const card = btn.closest('.bsc-day-card');
    const dayIndex = card.getAttribute('data-day-index');
    const container = card.querySelector('.bsc-time-slots-container');

    const pill = document.createElement('div');
    pill.className = 'bsc-time-pill';
    pill.innerHTML = `
      <input type="time" name="schedule_days[${dayIndex}][times][]" value="18:00" required>
      <button type="button" onclick="removeTimeSlotPill(this)" style="background:none; border:none; color:#94a3b8; font-weight:bold; cursor:pointer; font-size:0.85rem; padding:0 2px;">✕</button>
    `;
    container.appendChild(pill);
  }

  function removeTimeSlotPill(btn) {
    const container = btn.closest('.bsc-time-slots-container');
    if (container.querySelectorAll('.bsc-time-pill').length > 1) {
      btn.closest('.bsc-time-pill').remove();
    } else {
      alert('Each day requires at least one showtime slot.');
    }
  }

  function removeDayBlock(btn) {
    const container = document.getElementById('bsc_day_schedule_container');
    if (container.querySelectorAll('.bsc-day-card').length > 1) {
      btn.closest('.bsc-day-card').remove();
      updateScheduleSummary();
    } else {
      alert('At least one screening day is required.');
    }
  }

  function handleDateInputChange(input) {
    const card = input.closest('.bsc-day-card');
    const badge = card.querySelector('.bsc-weekday-badge');
    const label = card.querySelector('.bsc-date-label-friendly');
    
    const dayName = getDayNameFromDate(input.value);
    const friendly = formatFriendlyDate(input.value);

    badge.innerText = dayName.toUpperCase();
    label.innerText = friendly;
    card.setAttribute('data-date-str', (input.value + ' ' + dayName + ' ' + friendly).toLowerCase());
  }

  function filterScheduleDays() {
    const query = document.getElementById('bsc_schedule_search').value.toLowerCase().trim();
    const cards = document.querySelectorAll('#bsc_day_schedule_container .bsc-day-card');

    cards.forEach(card => {
      const dateStr = card.getAttribute('data-date-str') || '';
      if (!query || dateStr.includes(query)) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  }

  function updateScheduleSummary() {
    const count = document.querySelectorAll('#bsc_day_schedule_container .bsc-day-card').length;
    const badge = document.getElementById('bsc_total_days_count');
    if (badge) badge.innerText = `${count} Days`;
  }

  function getNextConsecutiveDate() {
    const cards = document.querySelectorAll('#bsc_day_schedule_container .bsc-day-card');
    if (!cards.length) {
      const now = new Date();
      return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    }

    const lastCard = cards[cards.length - 1];
    const lastDate = lastCard.querySelector('input[type="date"]').value;
    if (lastDate) {
      const parts = lastDate.split('-');
      const d = new Date(parts[0], parts[1] - 1, parts[2]);
      d.setDate(d.getDate() + 1);
      return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  }

  function removeCastRow(btn) {
    const row = btn.closest('.bsc-repeater-row');
    if (document.querySelectorAll('#bsc_cast_repeater_container .bsc-repeater-row').length > 1) {
      row.remove();
    } else {
      alert('At least one cast entry is required.');
    }
  }

  function initUploader(btnId, removeBtnId, inputId, previewId, mediaType) {
    const btn = document.getElementById(btnId);
    const removeBtn = document.getElementById(removeBtnId);
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    if (btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const frame = wp.media({
          title: 'Select Media',
          button: { text: 'Use File' },
          multiple: false,
          library: { type: mediaType }
        });
        frame.on('select', function() {
          const attachment = frame.state().get('selection').first().toJSON();
          input.value = attachment.url;
          preview.src = attachment.url;
          preview.style.display = 'block';
          if (removeBtn) removeBtn.style.display = 'inline-block';
        });
        frame.open();
      });
    }

    if (removeBtn) {
      removeBtn.addEventListener('click', function() {
        input.value = '';
        preview.src = '';
        preview.style.display = 'none';
        removeBtn.style.display = 'none';
      });
    }
  }
</script>