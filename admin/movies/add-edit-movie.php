<?php
/**
 * Movie Module - Add & Edit Movie Views & Submission Handlers
 *
 * Path: admin/movies/add-edit-movie.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -------------------------------------------------------------
// 1. PROCESS FORM SUBMISSIONS (Triggered via admin_init handler)
// -------------------------------------------------------------
$error_message = '';

if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['bsc_action'] ) ) {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
    }

    check_admin_referer( 'bsc_movie_form_action', '_bsc_nonce' );

    global $wpdb;
    $table = $wpdb->prefix . 'bsc_control_movies';

    $action     = sanitize_key( wp_unslash( $_POST['bsc_action'] ) );
    $title      = isset( $_POST['movie_title'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_title'] ) ) : '';
    $category   = isset( $_POST['movie_category'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_category'] ) ) : 'Now Showing';
    $release_dt = isset( $_POST['movie_release_date'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_release_date'] ) ) : current_time( 'Y-m-d' );
    $genre      = isset( $_POST['movie_genre'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_genre'] ) ) : '';
    $duration   = isset( $_POST['movie_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_duration'] ) ) : '';
    $audio      = isset( $_POST['movie_audio_lang'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_audio_lang'] ) ) : '';
    $badge      = isset( $_POST['movie_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_badge'] ) ) : '';
    $rating     = isset( $_POST['movie_rating'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_rating'] ) ) : '';
    $age_rating = isset( $_POST['movie_age_rating'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_age_rating'] ) ) : '';
    $director   = isset( $_POST['movie_director'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_director'] ) ) : '';
    $writers    = isset( $_POST['movie_writers'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_writers'] ) ) : '';
    $poster     = isset( $_POST['movie_poster'] ) ? esc_url_raw( wp_unslash( $_POST['movie_poster'] ) ) : '';
    $trailer    = isset( $_POST['movie_trailer'] ) ? esc_url_raw( wp_unslash( $_POST['movie_trailer'] ) ) : '';
    $short_desc = isset( $_POST['movie_short_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['movie_short_desc'] ) ) : '';
    $synopsis   = isset( $_POST['movie_synopsis'] ) ? sanitize_textarea_field( wp_unslash( $_POST['movie_synopsis'] ) ) : '';
    $auditorium = ! empty( $_POST['movie_auditorium'] ) ? sanitize_text_field( wp_unslash( $_POST['movie_auditorium'] ) ) : 'Auditorium 1 (30 Seats)';

    // Format Schedule Days into clean JSON
    $raw_schedule       = isset( $_POST['schedule_days'] ) && is_array( $_POST['schedule_days'] ) ? wp_unslash( $_POST['schedule_days'] ) : array();
    $formatted_schedule = array();
    foreach ( $raw_schedule as $day_data ) {
        $d     = isset( $day_data['date'] ) ? sanitize_text_field( $day_data['date'] ) : '';
        $t_arr = isset( $day_data['times'] ) && is_array( $day_data['times'] ) ? $day_data['times'] : array();
        if ( ! empty( $d ) ) {
            $clean_times          = array_map( 'sanitize_text_field', $t_arr );
            $formatted_schedule[] = array(
                'date'  => $d,
                'times' => array_values( array_filter( $clean_times ) ),
            );
        }
    }
    $showtimes_json = wp_json_encode( $formatted_schedule );

    // Format Cast & Crew repeater data into JSON
    $c_names    = isset( $_POST['cast_names'] ) && is_array( $_POST['cast_names'] ) ? wp_unslash( $_POST['cast_names'] ) : array();
    $c_roles    = isset( $_POST['cast_roles'] ) && is_array( $_POST['cast_roles'] ) ? wp_unslash( $_POST['cast_roles'] ) : array();
    $c_photos   = isset( $_POST['cast_photos'] ) && is_array( $_POST['cast_photos'] ) ? wp_unslash( $_POST['cast_photos'] ) : array();
    $cast_array = array();
    foreach ( $c_names as $i => $name ) {
        $n = sanitize_text_field( $name );
        $r = isset( $c_roles[ $i ] ) ? sanitize_text_field( $c_roles[ $i ] ) : '';
        $p = isset( $c_photos[ $i ] ) ? esc_url_raw( $c_photos[ $i ] ) : '';
        if ( ! empty( $n ) ) {
            $cast_array[] = array( 'name' => $n, 'role' => $r, 'photo' => $p );
        }
    }
    $cast_json = wp_json_encode( $cast_array );

    $data_payload = array(
        'title'             => $title,
        'category'          => $category,
        'genre'             => $genre,
        'duration'          => $duration,
        'rating'            => $rating,
        'age_rating'        => $age_rating,
        'audio_language'    => $audio,
        'director'          => $director,
        'writers'           => $writers,
        'auditorium'        => $auditorium,
        'showtimes'         => $showtimes_json,
        'poster_url'        => $poster,
        'trailer_url'       => $trailer,
        'badge_tag'         => $badge,
        'release_date'      => $release_dt,
        'short_description' => $short_desc,
        'synopsis'          => $synopsis,
        'cast_crew'         => $cast_json,
    );

    $data_formats = array(
        '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
        '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
    );

    if ( 'add_movie' === $action ) {
        $inserted = $wpdb->insert( $table, $data_payload, $data_formats );

        if ( false !== $inserted ) {
            wp_safe_redirect( admin_url( 'admin.php?page=bsc-movies&subtab=all&movie_added=true' ) );
            exit;
        } else {
            $error_message = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to save movie to database.', 'bcs-screen-manager' );
        }
    } elseif ( 'edit_movie' === $action ) {
        $movie_id = isset( $_POST['movie_id'] ) ? absint( $_POST['movie_id'] ) : 0;
        if ( $movie_id > 0 ) {
            $updated = $wpdb->update( $table, $data_payload, array( 'id' => $movie_id ), $data_formats, array( '%d' ) );
            if ( false !== $updated ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-movies&subtab=all&movie_updated=true' ) );
                exit;
            } else {
                $error_message = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to update movie.', 'bcs-screen-manager' );
            }
        }
    }
}

// -------------------------------------------------------------
// 2. UI VIEW DATA PREPARATION
// -------------------------------------------------------------
$admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
}

global $wpdb;
$table = $wpdb->prefix . 'bsc_control_movies';

$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
$movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$movie   = null;
$is_edit = ( 'edit' === $subtab && $movie_id > 0 );

if ( $is_edit ) {
    $movie = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $movie_id ) );
}

$cast_list = array();
if ( $movie && ! empty( $movie->cast_crew ) ) {
    $cast_list = json_decode( $movie->cast_crew, true );
}
if ( empty( $cast_list ) || ! is_array( $cast_list ) ) {
    $cast_list = array( array( 'name' => '', 'role' => '', 'photo' => '' ) );
}

$official_release_date = ( $movie && ! empty( $movie->release_date ) ) ? $movie->release_date : current_time( 'Y-m-d' );

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
                $d = isset( $item['date'] ) ? $item['date'] : current_time( 'Y-m-d' );
                $t = isset( $item['time'] ) ? $item['time'] : '18:00';
                if ( ! isset( $grouped[ $d ] ) ) {
                    $grouped[ $d ] = array( 'date' => $d, 'times' => array() );
                }
                $grouped[ $d ]['times'][] = $t;
            }
            $day_schedules = array_values( $grouped );
        }
    }
}

wp_enqueue_media();
?>

<div style="max-width:920px; margin:20px auto;">

    <?php if ( ! empty( $error_message ) ) : ?>
        <div style="background:#fef2f2; border:1px solid #f87171; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:0.9rem;" role="alert">
            <strong><?php esc_html_e( 'Database Error:', 'bcs-screen-manager' ); ?></strong> <?php echo esc_html( $error_message ); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-movies' ) ); ?>" id="bscMovieForm">
        <?php 
        wp_nonce_field( 'bsc_movie_form_action', '_bsc_nonce' );

        if ( $is_edit && $movie ) {
            echo '<input type="hidden" name="bsc_action" value="edit_movie">';
            echo '<input type="hidden" name="movie_id" value="' . esc_attr( (string) $movie->id ) . '">';
        } else {
            echo '<input type="hidden" name="bsc_action" value="add_movie">';
        }
        echo '<input type="hidden" name="movie_auditorium" value="' . esc_attr( ! empty( $movie->auditorium ) ? $movie->auditorium : 'Auditorium 1 (30 Seats)' ) . '">';
        ?>

        <!-- 01. CORE MOVIE INFO -->
        <div class="bsc-form-card">
            <h4 class="bsc-section-title">🎬 <?php esc_html_e( 'Core Movie Information', 'bcs-screen-manager' ); ?></h4>

            <div class="bsc-grid-2">
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Movie Title', 'bcs-screen-manager' ); ?> *</label>
                    <input type="text" name="movie_title" required value="<?php echo esc_attr( $movie->title ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Borbaad', 'bcs-screen-manager' ); ?>">
                </div>
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Movie Category', 'bcs-screen-manager' ); ?> *</label>
                    <select name="movie_category" required>
                        <?php $current_category = $movie->category ?? 'Now Showing'; ?>
                        <option value="Now Showing" <?php selected( $current_category, 'Now Showing' ); ?>><?php esc_html_e( 'Now Showing', 'bcs-screen-manager' ); ?></option>
                        <option value="Upcoming" <?php selected( $current_category, 'Upcoming' ); ?>><?php esc_html_e( 'Upcoming', 'bcs-screen-manager' ); ?></option>
                        <option value="Festival Special" <?php selected( $current_category, 'Festival Special' ); ?>><?php esc_html_e( 'Festival Special', 'bcs-screen-manager' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="bsc-grid-2">
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Official Theatrical Release Date', 'bcs-screen-manager' ); ?> *</label>
                    <input type="date" name="movie_release_date" required value="<?php echo esc_attr( $official_release_date ); ?>">
                </div>
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Genre', 'bcs-screen-manager' ); ?> *</label>
                    <input type="text" name="movie_genre" required value="<?php echo esc_attr( $movie->genre ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Action / Thriller', 'bcs-screen-manager' ); ?>">
                </div>
            </div>

            <div class="bsc-grid-3">
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Duration', 'bcs-screen-manager' ); ?> *</label>
                    <input type="text" name="movie_duration" required value="<?php echo esc_attr( $movie->duration ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. 02h 25m', 'bcs-screen-manager' ); ?>">
                </div>
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Language & Audio', 'bcs-screen-manager' ); ?> *</label>
                    <input type="text" name="movie_audio_lang" required value="<?php echo esc_attr( $movie->audio_language ?? 'Bengali (Dolby Atmos 128-Ch)' ); ?>">
                </div>
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Format Badge', 'bcs-screen-manager' ); ?> *</label>
                    <input type="text" name="movie_badge" required value="<?php echo esc_attr( $movie->badge_tag ?? '4K DUAL LASER' ); ?>">
                </div>
            </div>

            <div class="bsc-grid-3">
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Rating (IMDb)', 'bcs-screen-manager' ); ?> *</label>
                    <input type="text" name="movie_rating" required value="<?php echo esc_attr( $movie->rating ?? '9.0' ); ?>">
                </div>
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Age / Censor Rating', 'bcs-screen-manager' ); ?> *</label>
                    <input type="text" name="movie_age_rating" required value="<?php echo esc_attr( $movie->age_rating ?? 'R-Rated (18+)' ); ?>">
                </div>
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Director', 'bcs-screen-manager' ); ?> *</label>
                    <input type="text" name="movie_director" required value="<?php echo esc_attr( $movie->director ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Raihan Rafi', 'bcs-screen-manager' ); ?>">
                </div>
            </div>

            <div class="bsc-grid-1">
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Screenwriters / Story', 'bcs-screen-manager' ); ?> *</label>
                    <input type="text" name="movie_writers" required value="<?php echo esc_attr( $movie->writers ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Mehedi Hasan', 'bcs-screen-manager' ); ?>">
                </div>
            </div>
        </div>

        <!-- 02. MEDIA & SYNOPSIS -->
        <div class="bsc-form-card">
            <h4 class="bsc-section-title">🖼️ <?php esc_html_e( 'Media, Synopsis & Storyline', 'bcs-screen-manager' ); ?></h4>

            <div class="bsc-grid-2">
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Movie Poster', 'bcs-screen-manager' ); ?> *</label>
                    <div style="background:#f8fafc; border:1px dashed #cbd5e1; padding:12px; border-radius:8px; text-align:center;">
                        <input type="hidden" name="movie_poster" id="movie_poster_url" required value="<?php echo esc_url( $movie->poster_url ?? '' ); ?>">
                        <img id="poster_preview" src="<?php echo esc_url( $movie->poster_url ?? '' ); ?>" alt="<?php esc_attr_e( 'Movie Poster Preview', 'bcs-screen-manager' ); ?>" style="<?php echo ! empty( $movie->poster_url ) ? 'display:block;' : 'display:none;'; ?> max-height:120px; margin:0 auto 8px auto; border-radius:6px; object-fit:cover;">
                        <button type="button" class="button" id="btn_upload_poster">📷 <?php esc_html_e( 'Choose Poster', 'bcs-screen-manager' ); ?></button>
                        <button type="button" class="button" id="btn_remove_poster" style="<?php echo ! empty( $movie->poster_url ) ? 'display:inline-block;' : 'display:none;'; ?> color:#ef4444;"><?php esc_html_e( 'Remove', 'bcs-screen-manager' ); ?></button>
                    </div>
                </div>

                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Official Trailer Video', 'bcs-screen-manager' ); ?></label>
                    <div style="background:#f8fafc; border:1px dashed #cbd5e1; padding:12px; border-radius:8px; text-align:center;">
                        <input type="hidden" name="movie_trailer" id="movie_trailer_url" value="<?php echo esc_url( $movie->trailer_url ?? '' ); ?>">
                        <video id="trailer_preview" src="<?php echo esc_url( $movie->trailer_url ?? '' ); ?>" controls style="<?php echo ! empty( $movie->trailer_url ) ? 'display:block;' : 'display:none;'; ?> max-height:120px; width:100%; border-radius:6px; margin-bottom:8px;"></video>
                        <button type="button" class="button" id="btn_upload_trailer">▶️ <?php esc_html_e( 'Choose Trailer', 'bcs-screen-manager' ); ?></button>
                        <button type="button" class="button" id="btn_remove_trailer" style="<?php echo ! empty( $movie->trailer_url ) ? 'display:inline-block;' : 'display:none;'; ?> color:#ef4444;"><?php esc_html_e( 'Remove', 'bcs-screen-manager' ); ?></button>
                    </div>
                </div>
            </div>

            <div class="bsc-grid-2">
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Short Summary (Card Excerpt)', 'bcs-screen-manager' ); ?> *</label>
                    <textarea name="movie_short_desc" rows="3" required placeholder="<?php esc_attr_e( 'Quick 1-2 sentence overview...', 'bcs-screen-manager' ); ?>"><?php echo esc_textarea( $movie->short_description ?? '' ); ?></textarea>
                </div>
                <div class="bsc-input-group">
                    <label><?php esc_html_e( 'Full Storyline & Synopsis', 'bcs-screen-manager' ); ?> *</label>
                    <textarea name="movie_synopsis" rows="3" required placeholder="<?php esc_attr_e( 'Detailed movie plot and notes...', 'bcs-screen-manager' ); ?>"><?php echo esc_textarea( $movie->synopsis ?? '' ); ?></textarea>
                </div>
            </div>
        </div>

        <!-- 03. SCREENING SCHEDULE PLANNER -->
        <div class="bsc-form-card" style="border: 1px solid #bbf7d0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h4 class="bsc-section-title" style="margin:0; border:none; padding:0;">
                    🗓️ <?php esc_html_e( 'Screening Schedule Planner', 'bcs-screen-manager' ); ?>
                    <span id="bsc_total_days_count" style="background:#059669; color:#fff; font-size:0.7rem; font-weight:800; padding:2px 8px; border-radius:10px;">
                        <?php echo esc_html( (string) count( $day_schedules ) ); ?> <?php esc_html_e( 'Days', 'bcs-screen-manager' ); ?>
                    </span>
                </h4>
                <button type="button" class="button button-primary" id="btn_add_day_block" style="background:#059669; border-color:#059669; font-weight:700;">
                    + <?php esc_html_e( 'Add Single Day', 'bcs-screen-manager' ); ?>
                </button>
            </div>

            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; background:#f8fafc; padding:8px 12px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:14px;">
                <span style="font-size:0.75rem; font-weight:800; color:#475569; text-transform:uppercase;"><?php esc_html_e( '⚡ Quick Auto-Add:', 'bcs-screen-manager' ); ?></span>
                <button type="button" class="button button-small btn-bulk-sched" data-days="7"><?php esc_html_e( '+ Next 7 Days', 'bcs-screen-manager' ); ?></button>
                <button type="button" class="button button-small btn-bulk-sched" data-days="14"><?php esc_html_e( '+ Next 14 Days', 'bcs-screen-manager' ); ?></button>
                <button type="button" class="button button-small btn-bulk-sched" data-days="30"><?php esc_html_e( '+ Next 30 Days', 'bcs-screen-manager' ); ?></button>

                <div style="margin-left:auto;">
                    <input type="text" id="bsc_schedule_search" placeholder="<?php esc_attr_e( 'Search date...', 'bcs-screen-manager' ); ?>" style="padding:4px 8px; font-size:0.78rem; border-radius:5px; border:1px solid #cbd5e1; width:180px;" aria-label="<?php esc_attr_e( 'Search schedule date', 'bcs-screen-manager' ); ?>">
                </div>
            </div>

            <div id="bsc_day_schedule_container" class="bsc-schedule-scrollbox">
                <?php foreach ( $day_schedules as $day_index => $schedule_item ) : 
                    $item_date     = isset( $schedule_item['date'] ) ? $schedule_item['date'] : current_time( 'Y-m-d' );
                    $item_times    = ! empty( $schedule_item['times'] ) ? (array) $schedule_item['times'] : array( '18:00' );
                    $ts            = strtotime( $item_date );
                    $day_name      = $ts ? gmdate( 'D', $ts ) : 'DAY';
                    $friendly_date = $ts ? wp_date( 'd M Y', $ts ) : $item_date;
                ?>
                    <div class="bsc-day-card" data-day-index="<?php echo absint( $day_index ); ?>" data-date-str="<?php echo esc_attr( strtolower( $item_date . ' ' . $day_name . ' ' . $friendly_date ) ); ?>">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="bsc-weekday-badge"><?php echo esc_html( strtoupper( $day_name ) ); ?></span>
                                <span class="bsc-date-label-friendly" style="font-weight:700; font-size:0.85rem; color:#0f172a;"><?php echo esc_html( $friendly_date ); ?></span>
                                <input type="date" class="bsc-date-picker-input" name="schedule_days[<?php echo absint( $day_index ); ?>][date]" value="<?php echo esc_attr( $item_date ); ?>" required style="max-width:130px; font-size:0.75rem; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px;" aria-label="<?php esc_attr_e( 'Schedule date', 'bcs-screen-manager' ); ?>">
                            </div>
                            <div style="display:flex; gap:6px;">
                                <button type="button" class="bsc-btn-duplicate bsc-btn-dup-day">📋 <?php esc_html_e( 'Duplicate', 'bcs-screen-manager' ); ?></button>
                                <button type="button" class="bsc-btn-remove bsc-btn-del-day">✕ <?php esc_html_e( 'Delete', 'bcs-screen-manager' ); ?></button>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
                            <span style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase;"><?php esc_html_e( 'Showtimes:', 'bcs-screen-manager' ); ?></span>
                            <div class="bsc-time-slots-container" style="display:flex; flex-wrap:wrap; gap:6px;">
                                <?php foreach ( $item_times as $time_slot ) : ?>
                                    <div class="bsc-time-pill">
                                        <input type="time" name="schedule_days[<?php echo absint( $day_index ); ?>][times][]" value="<?php echo esc_attr( $time_slot ); ?>" required aria-label="<?php esc_attr_e( 'Showtime slot', 'bcs-screen-manager' ); ?>">
                                        <button type="button" class="bsc-btn-del-time" style="background:none; border:none; color:#94a3b8; font-weight:bold; cursor:pointer; font-size:0.85rem; padding:0 2px;">✕</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="button button-small bsc-btn-add-time" style="padding:1px 6px; font-size:0.72rem;">+ <?php esc_html_e( 'Time', 'bcs-screen-manager' ); ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 04. CAST & CREW -->
        <div class="bsc-form-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h4 class="bsc-section-title" style="margin:0; border:none; padding:0;">🎭 <?php esc_html_e( 'Lead Cast & Crew', 'bcs-screen-manager' ); ?></h4>
                <button type="button" class="button button-secondary" id="btn_add_cast_row">+ <?php esc_html_e( 'Add Cast Member', 'bcs-screen-manager' ); ?></button>
            </div>

            <div id="bsc_cast_repeater_container" style="display:flex; flex-direction:column; gap:8px;">
                <?php foreach ( $cast_list as $cast ) : ?>
                    <div class="bsc-repeater-row" style="display:flex; align-items:center; gap:8px;">
                        <input type="text" name="cast_names[]" placeholder="<?php esc_attr_e( 'Actor Name', 'bcs-screen-manager' ); ?>" value="<?php echo esc_attr( $cast['name'] ?? '' ); ?>" required style="flex:1; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px;" aria-label="<?php esc_attr_e( 'Actor Name', 'bcs-screen-manager' ); ?>">
                        <input type="text" name="cast_roles[]" placeholder="<?php esc_attr_e( 'Role / Character', 'bcs-screen-manager' ); ?>" value="<?php echo esc_attr( $cast['role'] ?? '' ); ?>" required style="flex:1; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px;" aria-label="<?php esc_attr_e( 'Role / Character', 'bcs-screen-manager' ); ?>">
                        
                        <div style="display:flex; align-items:center; gap:6px;">
                            <input type="hidden" name="cast_photos[]" class="cast-photo-url-field" value="<?php echo esc_url( $cast['photo'] ?? '' ); ?>">
                            <img src="<?php echo esc_url( $cast['photo'] ?? '' ); ?>" alt="<?php esc_attr_e( 'Actor Headshot', 'bcs-screen-manager' ); ?>" class="bsc-cast-photo-thumb" style="<?php echo ! empty( $cast['photo'] ) ? 'display:inline-block;' : 'display:none;'; ?> width:30px; height:30px; border-radius:50%; object-fit:cover;">
                            <button type="button" class="button button-small btn-upload-cast-photo">📷 <?php esc_html_e( 'Headshot', 'bcs-screen-manager' ); ?></button>
                        </div>
                        
                        <button type="button" class="bsc-btn-remove bsc-btn-del-cast">✕</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <button type="submit" class="button button-primary" style="background:#059669; border-color:#059669; width:100%; padding:12px; font-weight:800; font-size:1rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 2px 6px rgba(5,150,105,0.25); cursor:pointer;">
            💾 <?php echo $is_edit ? esc_html__( 'Update Movie Presentation', 'bcs-screen-manager' ) : esc_html__( 'Publish Movie Presentation', 'bcs-screen-manager' ); ?>
        </button>
    </form>
</div>

<?php
$version = defined( 'BSC_VERSION' ) ? BSC_VERSION : '1.0.0';
wp_register_script( 'bsc-movie-builder-runtime', false, array(), $version, true );
wp_enqueue_script( 'bsc-movie-builder-runtime' );

$i18n_strings = array(
    'selectPoster'     => __( 'Select Movie Poster', 'bcs-screen-manager' ),
    'selectTrailer'    => __( 'Select Trailer Video', 'bcs-screen-manager' ),
    'selectHeadshot'   => __( 'Select Actor Headshot', 'bcs-screen-manager' ),
    'useFile'          => __( 'Use File', 'bcs-screen-manager' ),
    'confirmBulk'      => __( 'Add {N} consecutive screening days?', 'bcs-screen-manager' ),
    'atLeastOneDay'    => __( 'At least one screening day is required.', 'bcs-screen-manager' ),
    'atLeastOneSlot'   => __( 'Each day requires at least one showtime slot.', 'bcs-screen-manager' ),
    'atLeastOneCast'   => __( 'At least one cast entry is required.', 'bcs-screen-manager' ),
    'actorPlaceholder' => __( 'Actor Name', 'bcs-screen-manager' ),
    'rolePlaceholder'  => __( 'Role / Character', 'bcs-screen-manager' ),
    'duplicate'        => __( 'Duplicate', 'bcs-screen-manager' ),
    'delete'           => __( 'Delete', 'bcs-screen-manager' ),
    'time'             => __( 'Time', 'bcs-screen-manager' ),
    'headshot'         => __( 'Headshot', 'bcs-screen-manager' ),
);

$js_script = '
    (function() {
        const i18n = ' . wp_json_encode( $i18n_strings ) . ';

        function formatFriendlyDate(dateStr) {
            if (!dateStr) return "";
            const parts = dateStr.split("-");
            if (parts.length !== 3) return dateStr;
            const d = new Date(parts[0], parts[1] - 1, parts[2]);
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const day = String(d.getDate()).padStart(2, "0");
            return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`;
        }

        function getDayNameFromDate(dateStr) {
            if (!dateStr) return "DAY";
            const parts = dateStr.split("-");
            if (parts.length !== 3) return "DAY";
            const d = new Date(parts[0], parts[1] - 1, parts[2]);
            return d.toLocaleDateString("en-US", { weekday: "short" });
        }

        function updateScheduleSummary() {
            const count = document.querySelectorAll("#bsc_day_schedule_container .bsc-day-card").length;
            const badge = document.getElementById("bsc_total_days_count");
            if (badge) badge.innerText = `${count} Days`;
        }

        function getNextConsecutiveDate() {
            const cards = document.querySelectorAll("#bsc_day_schedule_container .bsc-day-card");
            if (!cards.length) {
                const now = new Date();
                return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
            }

            const lastCard = cards[cards.length - 1];
            const lastDate = lastCard.querySelector("input[type=\"date\"]").value;
            if (lastDate) {
                const parts = lastDate.split("-");
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                d.setDate(d.getDate() + 1);
                return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
            }
            const now = new Date();
            return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
        }

        function createDayBlock(dateStr, timesArray = ["18:00"]) {
            const container = document.getElementById("bsc_day_schedule_container");
            const nextIndex = container.querySelectorAll(".bsc-day-card").length;
            const dayName = getDayNameFromDate(dateStr);
            const friendlyDate = formatFriendlyDate(dateStr);

            const card = document.createElement("div");
            card.className = "bsc-day-card";
            card.setAttribute("data-day-index", nextIndex);
            card.setAttribute("data-date-str", (dateStr + " " + dayName + " " + friendlyDate).toLowerCase());

            let timesHtml = "";
            timesArray.forEach(t => {
                timesHtml += `
                    <div class="bsc-time-pill">
                        <input type="time" name="schedule_days[${nextIndex}][times][]" value="${t}" required>
                        <button type="button" class="bsc-btn-del-time" style="background:none; border:none; color:#94a3b8; font-weight:bold; cursor:pointer; font-size:0.85rem; padding:0 2px;">✕</button>
                    </div>
                `;
            });

            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="bsc-weekday-badge">${dayName.toUpperCase()}</span>
                        <span class="bsc-date-label-friendly" style="font-weight:700; font-size:0.85rem; color:#0f172a;">${friendlyDate}</span>
                        <input type="date" class="bsc-date-picker-input" name="schedule_days[${nextIndex}][date]" value="${dateStr}" required style="max-width:130px; font-size:0.75rem; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div style="display:flex; gap:6px;">
                        <button type="button" class="bsc-btn-duplicate bsc-btn-dup-day">📋 ${i18n.duplicate}</button>
                        <button type="button" class="bsc-btn-remove bsc-btn-del-day">✕ ${i18n.delete}</button>
                    </div>
                </div>

                <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px;">
                    <span style="font-size:0.72rem; font-weight:700; color:#64748b; text-transform:uppercase;">Showtimes:</span>
                    <div class="bsc-time-slots-container" style="display:flex; flex-wrap:wrap; gap:6px;">
                        ${timesHtml}
                    </div>
                    <button type="button" class="button button-small bsc-btn-add-time" style="padding:1px 6px; font-size:0.72rem;">+ ${i18n.time}</button>
                </div>
            `;

            container.appendChild(card);
            updateScheduleSummary();
            card.scrollIntoView({ behavior: "smooth", block: "nearest" });
        }

        function initUploader(btnId, removeBtnId, inputId, previewId, mediaType, modalTitle) {
            const btn = document.getElementById(btnId);
            const removeBtn = document.getElementById(removeBtnId);
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);

            if (btn) {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    if (typeof wp === "undefined" || !wp.media) return;

                    const frame = wp.media({
                        title: modalTitle,
                        button: { text: i18n.useFile },
                        multiple: false,
                        library: { type: mediaType }
                    });
                    frame.on("select", function() {
                        const attachment = frame.state().get("selection").first().toJSON();
                        input.value = attachment.url;
                        preview.src = attachment.url;
                        preview.style.display = "block";
                        if (removeBtn) removeBtn.style.display = "inline-block";
                    });
                    frame.open();
                });
            }

            if (removeBtn) {
                removeBtn.addEventListener("click", function() {
                    input.value = "";
                    preview.src = "";
                    preview.style.display = "none";
                    removeBtn.style.display = "none";
                });
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateScheduleSummary();

            const btnAddDay = document.getElementById("btn_add_day_block");
            if (btnAddDay) {
                btnAddDay.addEventListener("click", function() {
                    createDayBlock(getNextConsecutiveDate(), ["15:00", "18:30", "21:30"]);
                });
            }

            initUploader("btn_upload_poster", "btn_remove_poster", "movie_poster_url", "poster_preview", "image", i18n.selectPoster);
            initUploader("btn_upload_trailer", "btn_remove_trailer", "movie_trailer_url", "trailer_preview", "video", i18n.selectTrailer);

            // Delegated Event Listeners
            document.addEventListener("click", function(e) {
                // Upload cast photo
                if (e.target && e.target.classList.contains("btn-upload-cast-photo")) {
                    e.preventDefault();
                    if (typeof wp === "undefined" || !wp.media) return;

                    const btn = e.target;
                    const parent = btn.parentElement;
                    const urlField = parent.querySelector(".cast-photo-url-field");
                    const thumb = parent.querySelector(".bsc-cast-photo-thumb");

                    const frame = wp.media({
                        title: i18n.selectHeadshot,
                        button: { text: i18n.useFile },
                        multiple: false,
                        library: { type: "image" }
                    });

                    frame.on("select", function() {
                        const attachment = frame.state().get("selection").first().toJSON();
                        urlField.value = attachment.url;
                        thumb.src = attachment.url;
                        thumb.style.display = "inline-block";
                    });
                    frame.open();
                }

                // Delete cast row
                if (e.target && e.target.classList.contains("bsc-btn-del-cast")) {
                    const row = e.target.closest(".bsc-repeater-row");
                    if (document.querySelectorAll("#bsc_cast_repeater_container .bsc-repeater-row").length > 1) {
                        row.remove();
                    } else {
                        alert(i18n.atLeastOneCast);
                    }
                }

                // Delete day block
                if (e.target && e.target.classList.contains("bsc-btn-del-day")) {
                    const container = document.getElementById("bsc_day_schedule_container");
                    if (container.querySelectorAll(".bsc-day-card").length > 1) {
                        e.target.closest(".bsc-day-card").remove();
                        updateScheduleSummary();
                    } else {
                        alert(i18n.atLeastOneDay);
                    }
                }

                // Duplicate day block
                if (e.target && e.target.classList.contains("bsc-btn-dup-day")) {
                    const card = e.target.closest(".bsc-day-card");
                    const currentDate = card.querySelector("input[type=\"date\"]").value;
                    const times = [];
                    card.querySelectorAll(".bsc-time-slots-container input[type=\"time\"]").forEach(input => {
                        if (input.value) times.push(input.value);
                    });

                    let targetDateStr = "";
                    if (currentDate) {
                        const parts = currentDate.split("-");
                        const d = new Date(parts[0], parts[1] - 1, parts[2]);
                        d.setDate(d.getDate() + 1);
                        targetDateStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
                    } else {
                        targetDateStr = getNextConsecutiveDate();
                    }
                    createDayBlock(targetDateStr, times.length ? times : ["15:00", "18:30", "21:30"]);
                }

                // Add time slot
                if (e.target && e.target.classList.contains("bsc-btn-add-time")) {
                    const card = e.target.closest(".bsc-day-card");
                    const dayIndex = card.getAttribute("data-day-index");
                    const container = card.querySelector(".bsc-time-slots-container");
                    const pill = document.createElement("div");
                    pill.className = "bsc-time-pill";
                    pill.innerHTML = `
                        <input type="time" name="schedule_days[${dayIndex}][times][]" value="18:00" required>
                        <button type="button" class="bsc-btn-del-time" style="background:none; border:none; color:#94a3b8; font-weight:bold; cursor:pointer; font-size:0.85rem; padding:0 2px;">✕</button>
                    `;
                    container.appendChild(pill);
                }

                // Remove time slot
                if (e.target && e.target.classList.contains("bsc-btn-del-time")) {
                    const container = e.target.closest(".bsc-time-slots-container");
                    if (container.querySelectorAll(".bsc-time-pill").length > 1) {
                        e.target.closest(".bsc-time-pill").remove();
                    } else {
                        alert(i18n.atLeastOneSlot);
                    }
                }

                // Bulk auto-add days
                if (e.target && e.target.classList.contains("btn-bulk-sched")) {
                    const numDays = parseInt(e.target.getAttribute("data-days"), 10);
                    if (!confirm(i18n.confirmBulk.replace("{N}", numDays))) return;

                    let start = new Date();
                    const defaultTemplate = ["15:00", "18:30", "21:30"];
                    const container = document.getElementById("bsc_day_schedule_container");
                    const existingCards = container.querySelectorAll(".bsc-day-card");

                    if (existingCards.length > 0) {
                        const lastInput = existingCards[existingCards.length - 1].querySelector("input[type=\"date\"]");
                        if (lastInput && lastInput.value) {
                            const parts = lastInput.value.split("-");
                            start = new Date(parts[0], parts[1] - 1, parts[2]);
                            start.setDate(start.getDate() + 1);
                        }
                    }

                    for (let i = 0; i < numDays; i++) {
                        const y = start.getFullYear();
                        const m = String(start.getMonth() + 1).padStart(2, "0");
                        const d = String(start.getDate()).padStart(2, "0");
                        createDayBlock(`${y}-${m}-${d}`, defaultTemplate);
                        start.setDate(start.getDate() + 1);
                    }
                }
            });

            // Date picker change handling
            document.addEventListener("change", function(e) {
                if (e.target && e.target.classList.contains("bsc-date-picker-input")) {
                    const card = e.target.closest(".bsc-day-card");
                    const badge = card.querySelector(".bsc-weekday-badge");
                    const label = card.querySelector(".bsc-date-label-friendly");
                    const dayName = getDayNameFromDate(e.target.value);
                    const friendly = formatFriendlyDate(e.target.value);

                    badge.innerText = dayName.toUpperCase();
                    label.innerText = friendly;
                    card.setAttribute("data-date-str", (e.target.value + " " + dayName + " " + friendly).toLowerCase());
                }
            });

            // Filter schedule days
            const schedSearch = document.getElementById("bsc_schedule_search");
            if (schedSearch) {
                schedSearch.addEventListener("input", function() {
                    const query = this.value.toLowerCase().trim();
                    const cards = document.querySelectorAll("#bsc_day_schedule_container .bsc-day-card");
                    cards.forEach(card => {
                        const dateStr = card.getAttribute("data-date-str") || "";
                        card.style.display = (!query || dateStr.includes(query)) ? "" : "none";
                    });
                });
            }

            // Add Cast Member Row
            const btnAddCast = document.getElementById("btn_add_cast_row");
            const castContainer = document.getElementById("bsc_cast_repeater_container");
            if (btnAddCast && castContainer) {
                btnAddCast.addEventListener("click", function() {
                    const row = document.createElement("div");
                    row.className = "bsc-repeater-row";
                    row.style.cssText = "display:flex; align-items:center; gap:8px;";
                    row.innerHTML = `
                        <input type="text" name="cast_names[]" placeholder="${i18n.actorPlaceholder}" required style="flex:1; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px;">
                        <input type="text" name="cast_roles[]" placeholder="${i18n.rolePlaceholder}" required style="flex:1; padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <input type="hidden" name="cast_photos[]" class="cast-photo-url-field" value="">
                            <img src="" alt="" class="bsc-cast-photo-thumb" style="display:none; width:30px; height:30px; border-radius:50%; object-fit:cover;">
                            <button type="button" class="button button-small btn-upload-cast-photo">📷 ${i18n.headshot}</button>
                        </div>
                        <button type="button" class="bsc-btn-remove bsc-btn-del-cast">✕</button>
                    `;
                    castContainer.appendChild(row);
                });
            }
        });
    })();
';

wp_add_inline_script( 'bsc-movie-builder-runtime', $js_script );