<?php
/**
 * Movie Module - Add & Edit Movie Views & Submission Handlers
 *
 * Path: admin/movies/add-edit-movie.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------
// 1. PROCESS FORM SUBMISSIONS (Triggered via admin_init handler)
// -------------------------------------------------------------
$error_message = '';

if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['nmbp_action'] ) ) {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
	}

	check_admin_referer( 'nmbp_movie_form_action', '_nmbp_nonce' );

	global $wpdb;
	$table = $wpdb->prefix . 'nmbp_control_movies';

	$action     = sanitize_key( wp_unslash( $_POST['nmbp_action'] ) );
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

	// Format Schedule Days into clean JSON.
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

	// Format Cast & Crew repeater data into JSON.
	$c_names    = isset( $_POST['cast_names'] ) && is_array( $_POST['cast_names'] ) ? wp_unslash( $_POST['cast_names'] ) : array();
	$c_roles    = isset( $_POST['cast_roles'] ) && is_array( $_POST['cast_roles'] ) ? wp_unslash( $_POST['cast_roles'] ) : array();
	$c_photos   = isset( $_POST['cast_photos'] ) && is_array( $_POST['cast_photos'] ) ? wp_unslash( $_POST['cast_photos'] ) : array();
	$cast_array = array();
	foreach ( $c_names as $i => $name ) {
		$n = sanitize_text_field( $name );
		$r = isset( $c_roles[ $i ] ) ? sanitize_text_field( $c_roles[ $i ] ) : '';
		$p = isset( $c_photos[ $i ] ) ? esc_url_raw( $c_photos[ $i ] ) : '';
		if ( ! empty( $n ) ) {
			$cast_array[] = array(
				'name'  => $n,
				'role'  => $r,
				'photo' => $p,
			);
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
		'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
	);

	if ( 'add_movie' === $action ) {
		$inserted = $wpdb->insert( $table, $data_payload, $data_formats );

		if ( false !== $inserted ) {
			wp_safe_redirect( admin_url( 'admin.php?page=nmbp-movies&subtab=all&movie_added=true' ) );
			exit;
		} else {
			$error_message = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to save movie to database.', 'nahian-movie-booking-plugin' );
		}
	} elseif ( 'edit_movie' === $action ) {
		$movie_id = isset( $_POST['movie_id'] ) ? absint( $_POST['movie_id'] ) : 0;
		if ( $movie_id > 0 ) {
			$updated = $wpdb->update( $table, $data_payload, array( 'id' => $movie_id ), $data_formats, array( '%d' ) );
			if ( false !== $updated ) {
				wp_safe_redirect( admin_url( 'admin.php?page=nmbp-movies&subtab=all&movie_updated=true' ) );
				exit;
			} else {
				$error_message = $wpdb->last_error ? $wpdb->last_error : __( 'Failed to update movie.', 'nahian-movie-booking-plugin' );
			}
		}
	}
}

// -------------------------------------------------------------
// 2. UI VIEW DATA PREPARATION
// -------------------------------------------------------------
$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
	wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
}

global $wpdb;
$table = $wpdb->prefix . 'nmbp_control_movies';

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
	$cast_list = array(
		array(
			'name'  => '',
			'role'  => '',
			'photo' => '',
		),
	);
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
					$grouped[ $d ] = array(
						'date'  => $d,
						'times' => array(),
					);
				}
				$grouped[ $d ]['times'][] = $t;
			}
			$day_schedules = array_values( $grouped );
		}
	}
}

wp_enqueue_media();
?>

<style id="nmbp-add-edit-movie-css">
	.nmbp-movie-builder-wrap {
		max-width: 920px;
		margin: 20px auto;
	}
	.nmbp-movie-db-error {
		background: #fef2f2;
		border: 1px solid #f87171;
		color: #991b1b;
		padding: 12px 16px;
		border-radius: 8px;
		margin-bottom: 16px;
		font-size: 0.9rem;
	}
	.nmbp-media-dropzone {
		background: #f8fafc;
		border: 1px dashed #cbd5e1;
		padding: 12px;
		border-radius: 8px;
		text-align: center;
	}
	.nmbp-poster-preview-img {
		max-height: 120px;
		margin: 0 auto 8px auto;
		border-radius: 6px;
		object-fit: cover;
	}
	.nmbp-poster-preview-img--visible {
		display: block;
	}
	.nmbp-poster-preview-img--hidden {
		display: none;
	}
	.nmbp-trailer-preview-video {
		max-height: 120px;
		width: 100%;
		border-radius: 6px;
		margin-bottom: 8px;
	}
	.nmbp-trailer-preview-video--visible {
		display: block;
	}
	.nmbp-trailer-preview-video--hidden {
		display: none;
	}
	.nmbp-btn-remove-media {
		color: #ef4444;
	}
	.nmbp-btn-remove-media--visible {
		display: inline-block;
	}
	.nmbp-btn-remove-media--hidden {
		display: none;
	}
	.nmbp-form-card--schedule {
		border: 1px solid #bbf7d0;
	}
	.nmbp-section-header-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 12px;
	}
	.nmbp-section-title--inline {
		margin: 0;
		border: none;
		padding: 0;
	}
	.nmbp-days-count-badge {
		background: #059669;
		color: #ffffff;
		font-size: 0.7rem;
		font-weight: 800;
		padding: 2px 8px;
		border-radius: 10px;
	}
	.nmbp-btn-add-day {
		background: #059669;
		border-color: #059669;
		font-weight: 700;
	}
	.nmbp-bulk-toolbar {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 8px;
		background: #f8fafc;
		padding: 8px 12px;
		border-radius: 8px;
		border: 1px solid #e2e8f0;
		margin-bottom: 14px;
	}
	.nmbp-bulk-label {
		font-size: 0.75rem;
		font-weight: 800;
		color: #475569;
		text-transform: uppercase;
	}
	.nmbp-schedule-search-wrap {
		margin-left: auto;
	}
	.nmbp-schedule-search-input {
		padding: 4px 8px;
		font-size: 0.78rem;
		border-radius: 5px;
		border: 1px solid #cbd5e1;
		width: 180px;
	}
	.nmbp-day-card-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 10px;
	}
	.nmbp-day-card-meta {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.nmbp-date-label-friendly {
		font-weight: 700;
		font-size: 0.85rem;
		color: #0f172a;
	}
	.nmbp-date-picker-input {
		max-width: 130px;
		font-size: 0.75rem;
		padding: 2px 6px;
		border: 1px solid #cbd5e1;
		border-radius: 4px;
	}
	.nmbp-day-card-actions {
		display: flex;
		gap: 6px;
	}
	.nmbp-showtimes-row {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: 8px;
	}
	.nmbp-showtimes-label {
		font-size: 0.72rem;
		font-weight: 700;
		color: #64748b;
		text-transform: uppercase;
	}
	.nmbp-time-slots-container {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
	}
	.nmbp-btn-add-time {
		padding: 1px 6px;
		font-size: 0.72rem;
	}
	.nmbp-btn-del-time {
		background: none;
		border: none;
		color: #94a3b8;
		font-weight: bold;
		cursor: pointer;
		font-size: 0.85rem;
		padding: 0 2px;
	}
	.nmbp-cast-repeater-container {
		display: flex;
		flex-direction: column;
		gap: 8px;
	}
	.nmbp-repeater-row {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.nmbp-cast-text-input {
		flex: 1;
		padding: 6px 10px;
		border: 1px solid #cbd5e1;
		border-radius: 6px;
	}
	.nmbp-cast-photo-group {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.nmbp-cast-photo-thumb {
		width: 30px;
		height: 30px;
		border-radius: 50%;
		object-fit: cover;
	}
	.nmbp-cast-photo-thumb--visible {
		display: inline-block;
	}
	.nmbp-cast-photo-thumb--hidden {
		display: none;
	}
	.nmbp-btn-publish-movie {
		background: #059669;
		border-color: #059669;
		width: 100%;
		padding: 12px;
		font-weight: 800;
		font-size: 1rem;
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);
		cursor: pointer;
	}
</style>

<div class="nmbp-movie-builder-wrap">

	<?php if ( ! empty( $error_message ) ) : ?>
		<div class="nmbp-movie-db-error" role="alert">
			<strong><?php esc_html_e( 'Database Error:', 'nahian-movie-booking-plugin' ); ?></strong> <?php echo esc_html( $error_message ); ?>
		</div>
	<?php endif; ?>

	<form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-movies' ) ); ?>" id="nmbpMovieForm">
		<?php
		wp_nonce_field( 'nmbp_movie_form_action', '_nmbp_nonce' );

		if ( $is_edit && $movie ) {
			echo '<input type="hidden" name="nmbp_action" value="edit_movie">';
			echo '<input type="hidden" name="movie_id" value="' . esc_attr( (string) $movie->id ) . '">';
		} else {
			echo '<input type="hidden" name="nmbp_action" value="add_movie">';
		}
		echo '<input type="hidden" name="movie_auditorium" value="' . esc_attr( ! empty( $movie->auditorium ) ? $movie->auditorium : 'Auditorium 1 (30 Seats)' ) . '">';
		?>

		<!-- 01. CORE MOVIE INFO -->
		<div class="nmbp-form-card">
			<h4 class="nmbp-section-title">🎬 <?php esc_html_e( 'Core Movie Information', 'nahian-movie-booking-plugin' ); ?></h4>

			<div class="nmbp-grid-2">
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Movie Title', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="text" name="movie_title" required value="<?php echo esc_attr( $movie->title ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Borbaad', 'nahian-movie-booking-plugin' ); ?>">
				</div>
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Movie Category', 'nahian-movie-booking-plugin' ); ?> *</label>
					<select name="movie_category" required>
						<?php $current_category = $movie->category ?? 'Now Showing'; ?>
						<option value="Now Showing" <?php selected( $current_category, 'Now Showing' ); ?>><?php esc_html_e( 'Now Showing', 'nahian-movie-booking-plugin' ); ?></option>
						<option value="Upcoming" <?php selected( $current_category, 'Upcoming' ); ?>><?php esc_html_e( 'Upcoming', 'nahian-movie-booking-plugin' ); ?></option>
						<option value="Festival Special" <?php selected( $current_category, 'Festival Special' ); ?>><?php esc_html_e( 'Festival Special', 'nahian-movie-booking-plugin' ); ?></option>
					</select>
				</div>
			</div>

			<div class="nmbp-grid-2">
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Official Theatrical Release Date', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="date" name="movie_release_date" required value="<?php echo esc_attr( $official_release_date ); ?>">
				</div>
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Genre', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="text" name="movie_genre" required value="<?php echo esc_attr( $movie->genre ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Action / Thriller', 'nahian-movie-booking-plugin' ); ?>">
				</div>
			</div>

			<div class="nmbp-grid-3">
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Duration', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="text" name="movie_duration" required value="<?php echo esc_attr( $movie->duration ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. 02h 25m', 'nahian-movie-booking-plugin' ); ?>">
				</div>
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Language & Audio', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="text" name="movie_audio_lang" required value="<?php echo esc_attr( $movie->audio_language ?? 'Bengali (Dolby Atmos 128-Ch)' ); ?>">
				</div>
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Format Badge', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="text" name="movie_badge" required value="<?php echo esc_attr( $movie->badge_tag ?? '4K DUAL LASER' ); ?>">
				</div>
			</div>

			<div class="nmbp-grid-3">
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Rating (IMDb)', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="text" name="movie_rating" required value="<?php echo esc_attr( $movie->rating ?? '9.0' ); ?>">
				</div>
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Age / Censor Rating', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="text" name="movie_age_rating" required value="<?php echo esc_attr( $movie->age_rating ?? 'R-Rated (18+)' ); ?>">
				</div>
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Director', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="text" name="movie_director" required value="<?php echo esc_attr( $movie->director ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Raihan Rafi', 'nahian-movie-booking-plugin' ); ?>">
				</div>
			</div>

			<div class="nmbp-grid-1">
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Screenwriters / Story', 'nahian-movie-booking-plugin' ); ?> *</label>
					<input type="text" name="movie_writers" required value="<?php echo esc_attr( $movie->writers ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Mehedi Hasan', 'nahian-movie-booking-plugin' ); ?>">
				</div>
			</div>
		</div>

		<!-- 02. MEDIA & SYNOPSIS -->
		<div class="nmbp-form-card">
			<h4 class="nmbp-section-title">🖼️ <?php esc_html_e( 'Media, Synopsis & Storyline', 'nahian-movie-booking-plugin' ); ?></h4>

			<div class="nmbp-grid-2">
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Movie Poster', 'nahian-movie-booking-plugin' ); ?> *</label>
					<div class="nmbp-media-dropzone">
						<input type="hidden" name="movie_poster" id="movie_poster_url" required value="<?php echo esc_url( $movie->poster_url ?? '' ); ?>">
						<img id="poster_preview" src="<?php echo esc_url( $movie->poster_url ?? '' ); ?>" alt="<?php esc_attr_e( 'Movie Poster Preview', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-poster-preview-img <?php echo ! empty( $movie->poster_url ) ? 'nmbp-poster-preview-img--visible' : 'nmbp-poster-preview-img--hidden'; ?>">
						<button type="button" class="button" id="btn_upload_poster">📷 <?php esc_html_e( 'Choose Poster', 'nahian-movie-booking-plugin' ); ?></button>
						<button type="button" class="button nmbp-btn-remove-media <?php echo ! empty( $movie->poster_url ) ? 'nmbp-btn-remove-media--visible' : 'nmbp-btn-remove-media--hidden'; ?>" id="btn_remove_poster"><?php esc_html_e( 'Remove', 'nahian-movie-booking-plugin' ); ?></button>
					</div>
				</div>

				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Official Trailer Video', 'nahian-movie-booking-plugin' ); ?></label>
					<div class="nmbp-media-dropzone">
						<input type="hidden" name="movie_trailer" id="movie_trailer_url" value="<?php echo esc_url( $movie->trailer_url ?? '' ); ?>">
						<video id="trailer_preview" src="<?php echo esc_url( $movie->trailer_url ?? '' ); ?>" controls class="nmbp-trailer-preview-video <?php echo ! empty( $movie->trailer_url ) ? 'nmbp-trailer-preview-video--visible' : 'nmbp-trailer-preview-video--hidden'; ?>"></video>
						<button type="button" class="button" id="btn_upload_trailer">▶️ <?php esc_html_e( 'Choose Trailer', 'nahian-movie-booking-plugin' ); ?></button>
						<button type="button" class="button nmbp-btn-remove-media <?php echo ! empty( $movie->trailer_url ) ? 'nmbp-btn-remove-media--visible' : 'nmbp-btn-remove-media--hidden'; ?>" id="btn_remove_trailer"><?php esc_html_e( 'Remove', 'nahian-movie-booking-plugin' ); ?></button>
					</div>
				</div>
			</div>

			<div class="nmbp-grid-2">
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Short Summary (Card Excerpt)', 'nahian-movie-booking-plugin' ); ?> *</label>
					<textarea name="movie_short_desc" rows="3" required placeholder="<?php esc_attr_e( 'Quick 1-2 sentence overview...', 'nahian-movie-booking-plugin' ); ?>"><?php echo esc_textarea( $movie->short_description ?? '' ); ?></textarea>
				</div>
				<div class="nmbp-input-group">
					<label><?php esc_html_e( 'Full Storyline & Synopsis', 'nahian-movie-booking-plugin' ); ?> *</label>
					<textarea name="movie_synopsis" rows="3" required placeholder="<?php esc_attr_e( 'Detailed movie plot and notes...', 'nahian-movie-booking-plugin' ); ?>"><?php echo esc_textarea( $movie->synopsis ?? '' ); ?></textarea>
				</div>
			</div>
		</div>

		<!-- 03. SCREENING SCHEDULE PLANNER -->
		<div class="nmbp-form-card nmbp-form-card--schedule">
			<div class="nmbp-section-header-row">
				<h4 class="nmbp-section-title nmbp-section-title--inline">
					🗓️ <?php esc_html_e( 'Screening Schedule Planner', 'nahian-movie-booking-plugin' ); ?>
					<span id="nmbp_total_days_count" class="nmbp-days-count-badge">
						<?php echo esc_html( (string) count( $day_schedules ) ); ?> <?php esc_html_e( 'Days', 'nahian-movie-booking-plugin' ); ?>
					</span>
				</h4>
				<button type="button" class="button button-primary nmbp-btn-add-day" id="btn_add_day_block">
					+ <?php esc_html_e( 'Add Single Day', 'nahian-movie-booking-plugin' ); ?>
				</button>
			</div>

			<div class="nmbp-bulk-toolbar">
				<span class="nmbp-bulk-label"><?php esc_html_e( '⚡ Quick Auto-Add:', 'nahian-movie-booking-plugin' ); ?></span>
				<button type="button" class="button button-small btn-bulk-sched" data-days="7"><?php esc_html_e( '+ Next 7 Days', 'nahian-movie-booking-plugin' ); ?></button>
				<button type="button" class="button button-small btn-bulk-sched" data-days="14"><?php esc_html_e( '+ Next 14 Days', 'nahian-movie-booking-plugin' ); ?></button>
				<button type="button" class="button button-small btn-bulk-sched" data-days="30"><?php esc_html_e( '+ Next 30 Days', 'nahian-movie-booking-plugin' ); ?></button>

				<div class="nmbp-schedule-search-wrap">
					<input type="text" id="nmbp_schedule_search" placeholder="<?php esc_attr_e( 'Search date...', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-schedule-search-input" aria-label="<?php esc_attr_e( 'Search schedule date', 'nahian-movie-booking-plugin' ); ?>">
				</div>
			</div>

			<div id="nmbp_day_schedule_container" class="nmbp-schedule-scrollbox">
				<?php
				foreach ( $day_schedules as $day_index => $schedule_item ) :
					$item_date     = isset( $schedule_item['date'] ) ? $schedule_item['date'] : current_time( 'Y-m-d' );
					$item_times    = ! empty( $schedule_item['times'] ) ? (array) $schedule_item['times'] : array( '18:00' );
					$ts            = strtotime( $item_date );
					$day_name      = $ts ? gmdate( 'D', $ts ) : 'DAY';
					$friendly_date = $ts ? wp_date( 'd M Y', $ts ) : $item_date;
					?>
					<div class="nmbp-day-card" data-day-index="<?php echo absint( $day_index ); ?>" data-date-str="<?php echo esc_attr( strtolower( $item_date . ' ' . $day_name . ' ' . $friendly_date ) ); ?>">
						<div class="nmbp-day-card-header">
							<div class="nmbp-day-card-meta">
								<span class="nmbp-weekday-badge"><?php echo esc_html( strtoupper( $day_name ) ); ?></span>
								<span class="nmbp-date-label-friendly"><?php echo esc_html( $friendly_date ); ?></span>
								<input type="date" class="nmbp-date-picker-input" name="schedule_days[<?php echo absint( $day_index ); ?>][date]" value="<?php echo esc_attr( $item_date ); ?>" required aria-label="<?php esc_attr_e( 'Schedule date', 'nahian-movie-booking-plugin' ); ?>">
							</div>
							<div class="nmbp-day-card-actions">
								<button type="button" class="nmbp-btn-duplicate nmbp-btn-dup-day">📋 <?php esc_html_e( 'Duplicate', 'nahian-movie-booking-plugin' ); ?></button>
								<button type="button" class="nmbp-btn-remove nmbp-btn-del-day">✕ <?php esc_html_e( 'Delete', 'nahian-movie-booking-plugin' ); ?></button>
							</div>
						</div>

						<div class="nmbp-showtimes-row">
							<span class="nmbp-showtimes-label"><?php esc_html_e( 'Showtimes:', 'nahian-movie-booking-plugin' ); ?></span>
							<div class="nmbp-time-slots-container">
								<?php foreach ( $item_times as $time_slot ) : ?>
									<div class="nmbp-time-pill">
										<input type="time" name="schedule_days[<?php echo absint( $day_index ); ?>][times][]" value="<?php echo esc_attr( $time_slot ); ?>" required aria-label="<?php esc_attr_e( 'Showtime slot', 'nahian-movie-booking-plugin' ); ?>">
										<button type="button" class="nmbp-btn-del-time">✕</button>
									</div>
								<?php endforeach; ?>
							</div>
							<button type="button" class="button button-small nmbp-btn-add-time">+ <?php esc_html_e( 'Time', 'nahian-movie-booking-plugin' ); ?></button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- 04. CAST & CREW -->
		<div class="nmbp-form-card">
			<div class="nmbp-section-header-row">
				<h4 class="nmbp-section-title nmbp-section-title--inline">🎭 <?php esc_html_e( 'Lead Cast & Crew', 'nahian-movie-booking-plugin' ); ?></h4>
				<button type="button" class="button button-secondary" id="btn_add_cast_row">+ <?php esc_html_e( 'Add Cast Member', 'nahian-movie-booking-plugin' ); ?></button>
			</div>

			<div id="nmbp_cast_repeater_container" class="nmbp-cast-repeater-container">
				<?php foreach ( $cast_list as $cast ) : ?>
					<div class="nmbp-repeater-row">
						<input type="text" name="cast_names[]" placeholder="<?php esc_attr_e( 'Actor Name', 'nahian-movie-booking-plugin' ); ?>" value="<?php echo esc_attr( $cast['name'] ?? '' ); ?>" required class="nmbp-cast-text-input" aria-label="<?php esc_attr_e( 'Actor Name', 'nahian-movie-booking-plugin' ); ?>">
						<input type="text" name="cast_roles[]" placeholder="<?php esc_attr_e( 'Role / Character', 'nahian-movie-booking-plugin' ); ?>" value="<?php echo esc_attr( $cast['role'] ?? '' ); ?>" required class="nmbp-cast-text-input" aria-label="<?php esc_attr_e( 'Role / Character', 'nahian-movie-booking-plugin' ); ?>">

						<div class="nmbp-cast-photo-group">
							<input type="hidden" name="cast_photos[]" class="cast-photo-url-field" value="<?php echo esc_url( $cast['photo'] ?? '' ); ?>">
							<img src="<?php echo esc_url( $cast['photo'] ?? '' ); ?>" alt="<?php esc_attr_e( 'Actor Headshot', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-cast-photo-thumb <?php echo ! empty( $cast['photo'] ) ? 'nmbp-cast-photo-thumb--visible' : 'nmbp-cast-photo-thumb--hidden'; ?>">
							<button type="button" class="button button-small btn-upload-cast-photo">📷 <?php esc_html_e( 'Headshot', 'nahian-movie-booking-plugin' ); ?></button>
						</div>

						<button type="button" class="nmbp-btn-remove nmbp-btn-del-cast">✕</button>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- SUBMIT BUTTON -->
		<button type="submit" class="button button-primary nmbp-btn-publish-movie">
			💾 <?php echo $is_edit ? esc_html__( 'Update Movie Presentation', 'nahian-movie-booking-plugin' ) : esc_html__( 'Publish Movie Presentation', 'nahian-movie-booking-plugin' ); ?>
		</button>
	</form>
</div>

<script id="nmbp-movie-builder-runtime-js">
	(function() {
		const i18n = {
			selectPoster: <?php echo wp_json_encode( __( 'Select Movie Poster', 'nahian-movie-booking-plugin' ) ); ?>,
			selectTrailer: <?php echo wp_json_encode( __( 'Select Trailer Video', 'nahian-movie-booking-plugin' ) ); ?>,
			selectHeadshot: <?php echo wp_json_encode( __( 'Select Actor Headshot', 'nahian-movie-booking-plugin' ) ); ?>,
			useFile: <?php echo wp_json_encode( __( 'Use File', 'nahian-movie-booking-plugin' ) ); ?>,
			confirmBulk: <?php echo wp_json_encode( __( 'Add {N} consecutive screening days?', 'nahian-movie-booking-plugin' ) ); ?>,
			atLeastOneDay: <?php echo wp_json_encode( __( 'At least one screening day is required.', 'nahian-movie-booking-plugin' ) ); ?>,
			atLeastOneSlot: <?php echo wp_json_encode( __( 'Each day requires at least one showtime slot.', 'nahian-movie-booking-plugin' ) ); ?>,
			atLeastOneCast: <?php echo wp_json_encode( __( 'At least one cast entry is required.', 'nahian-movie-booking-plugin' ) ); ?>,
			actorPlaceholder: <?php echo wp_json_encode( __( 'Actor Name', 'nahian-movie-booking-plugin' ) ); ?>,
			rolePlaceholder: <?php echo wp_json_encode( __( 'Role / Character', 'nahian-movie-booking-plugin' ) ); ?>,
			showtimesLabel: <?php echo wp_json_encode( __( 'Showtimes:', 'nahian-movie-booking-plugin' ) ); ?>,
			duplicate: <?php echo wp_json_encode( __( 'Duplicate', 'nahian-movie-booking-plugin' ) ); ?>,
			delete: <?php echo wp_json_encode( __( 'Delete', 'nahian-movie-booking-plugin' ) ); ?>,
			time: <?php echo wp_json_encode( __( 'Time', 'nahian-movie-booking-plugin' ) ); ?>,
			headshot: <?php echo wp_json_encode( __( 'Headshot', 'nahian-movie-booking-plugin' ) ); ?>,
			daysSuffix: <?php echo wp_json_encode( __( 'Days', 'nahian-movie-booking-plugin' ) ); ?>
		};

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
			const count = document.querySelectorAll("#nmbp_day_schedule_container .nmbp-day-card").length;
			const badge = document.getElementById("nmbp_total_days_count");
			if (badge) badge.textContent = `${count} ${i18n.daysSuffix}`;
		}

		function getNextConsecutiveDate() {
			const cards = document.querySelectorAll("#nmbp_day_schedule_container .nmbp-day-card");
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
			const container = document.getElementById("nmbp_day_schedule_container");
			const nextIndex = container.querySelectorAll(".nmbp-day-card").length;
			const dayName = getDayNameFromDate(dateStr);
			const friendlyDate = formatFriendlyDate(dateStr);

			const card = document.createElement("div");
			card.className = "nmbp-day-card";
			card.setAttribute("data-day-index", nextIndex);
			card.setAttribute("data-date-str", (dateStr + " " + dayName + " " + friendlyDate).toLowerCase());

			let timesHtml = "";
			timesArray.forEach(t => {
				timesHtml += `
					<div class="nmbp-time-pill">
						<input type="time" name="schedule_days[${nextIndex}][times][]" value="${t}" required>
						<button type="button" class="nmbp-btn-del-time">✕</button>
					</div>
				`;
			});

			card.innerHTML = `
				<div class="nmbp-day-card-header">
					<div class="nmbp-day-card-meta">
						<span class="nmbp-weekday-badge">${dayName.toUpperCase()}</span>
						<span class="nmbp-date-label-friendly">${friendlyDate}</span>
						<input type="date" class="nmbp-date-picker-input" name="schedule_days[${nextIndex}][date]" value="${dateStr}" required>
					</div>
					<div class="nmbp-day-card-actions">
						<button type="button" class="nmbp-btn-duplicate nmbp-btn-dup-day">📋 ${i18n.duplicate}</button>
						<button type="button" class="nmbp-btn-remove nmbp-btn-del-day">✕ ${i18n.delete}</button>
					</div>
				</div>

				<div class="nmbp-showtimes-row">
					<span class="nmbp-showtimes-label">${i18n.showtimesLabel}</span>
					<div class="nmbp-time-slots-container">
						${timesHtml}
					</div>
					<button type="button" class="button button-small nmbp-btn-add-time">+ ${i18n.time}</button>
				</div>
			`;

			container.appendChild(card);
			updateScheduleSummary();
			card.scrollIntoView({ behavior: "smooth", block: "nearest" });
		}

		function setElementVisibility(el, isVisible, visibleClass, hiddenClass) {
			if (!el) return;
			el.classList.remove(visibleClass, hiddenClass);
			el.classList.add(isVisible ? visibleClass : hiddenClass);
		}

		function initUploader(btnId, removeBtnId, inputId, previewId, mediaType, modalTitle, visibleClass, hiddenClass, btnVisibleClass, btnHiddenClass) {
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
						setElementVisibility(preview, true, visibleClass, hiddenClass);
						setElementVisibility(removeBtn, true, btnVisibleClass, btnHiddenClass);
					});
					frame.open();
				});
			}

			if (removeBtn) {
				removeBtn.addEventListener("click", function() {
					input.value = "";
					preview.src = "";
					setElementVisibility(preview, false, visibleClass, hiddenClass);
					setElementVisibility(removeBtn, false, btnVisibleClass, btnHiddenClass);
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

			initUploader(
				"btn_upload_poster",
				"btn_remove_poster",
				"movie_poster_url",
				"poster_preview",
				"image",
				i18n.selectPoster,
				"nmbp-poster-preview-img--visible",
				"nmbp-poster-preview-img--hidden",
				"nmbp-btn-remove-media--visible",
				"nmbp-btn-remove-media--hidden"
			);

			initUploader(
				"btn_upload_trailer",
				"btn_remove_trailer",
				"movie_trailer_url",
				"trailer_preview",
				"video",
				i18n.selectTrailer,
				"nmbp-trailer-preview-video--visible",
				"nmbp-trailer-preview-video--hidden",
				"nmbp-btn-remove-media--visible",
				"nmbp-btn-remove-media--hidden"
			);

			// Delegated Event Listeners.
			document.addEventListener("click", function(e) {
				// Upload cast photo.
				if (e.target && e.target.classList.contains("btn-upload-cast-photo")) {
					e.preventDefault();
					if (typeof wp === "undefined" || !wp.media) return;

					const btn = e.target;
					const parent = btn.parentElement;
					const urlField = parent.querySelector(".cast-photo-url-field");
					const thumb = parent.querySelector(".nmbp-cast-photo-thumb");

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
						setElementVisibility(thumb, true, "nmbp-cast-photo-thumb--visible", "nmbp-cast-photo-thumb--hidden");
					});
					frame.open();
				}

				// Delete cast row.
				if (e.target && e.target.classList.contains("nmbp-btn-del-cast")) {
					const row = e.target.closest(".nmbp-repeater-row");
					if (document.querySelectorAll("#nmbp_cast_repeater_container .nmbp-repeater-row").length > 1) {
						row.remove();
					} else {
						alert(i18n.atLeastOneCast);
					}
				}

				// Delete day block.
				if (e.target && e.target.classList.contains("nmbp-btn-del-day")) {
					const container = document.getElementById("nmbp_day_schedule_container");
					if (container.querySelectorAll(".nmbp-day-card").length > 1) {
						e.target.closest(".nmbp-day-card").remove();
						updateScheduleSummary();
					} else {
						alert(i18n.atLeastOneDay);
					}
				}

				// Duplicate day block.
				if (e.target && e.target.classList.contains("nmbp-btn-dup-day")) {
					const card = e.target.closest(".nmbp-day-card");
					const currentDate = card.querySelector("input[type=\"date\"]").value;
					const times = [];
					card.querySelectorAll(".nmbp-time-slots-container input[type=\"time\"]").forEach(input => {
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

				// Add time slot.
				if (e.target && e.target.classList.contains("nmbp-btn-add-time")) {
					const card = e.target.closest(".nmbp-day-card");
					const dayIndex = card.getAttribute("data-day-index");
					const container = card.querySelector(".nmbp-time-slots-container");
					const pill = document.createElement("div");
					pill.className = "nmbp-time-pill";
					pill.innerHTML = `
						<input type="time" name="schedule_days[${dayIndex}][times][]" value="18:00" required>
						<button type="button" class="nmbp-btn-del-time">✕</button>
					`;
					container.appendChild(pill);
				}

				// Remove time slot.
				if (e.target && e.target.classList.contains("nmbp-btn-del-time")) {
					const container = e.target.closest(".nmbp-time-slots-container");
					if (container.querySelectorAll(".nmbp-time-pill").length > 1) {
						e.target.closest(".nmbp-time-pill").remove();
					} else {
						alert(i18n.atLeastOneSlot);
					}
				}

				// Bulk auto-add days.
				if (e.target && e.target.classList.contains("btn-bulk-sched")) {
					const numDays = parseInt(e.target.getAttribute("data-days"), 10);
					if (!confirm(i18n.confirmBulk.replace("{N}", numDays))) return;

					let start = new Date();
					const defaultTemplate = ["15:00", "18:30", "21:30"];
					const container = document.getElementById("nmbp_day_schedule_container");
					const existingCards = container.querySelectorAll(".nmbp-day-card");

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

			// Date picker change handling.
			document.addEventListener("change", function(e) {
				if (e.target && e.target.classList.contains("nmbp-date-picker-input")) {
					const card = e.target.closest(".nmbp-day-card");
					const badge = card.querySelector(".nmbp-weekday-badge");
					const label = card.querySelector(".nmbp-date-label-friendly");
					const dayName = getDayNameFromDate(e.target.value);
					const friendly = formatFriendlyDate(e.target.value);

					badge.textContent = dayName.toUpperCase();
					label.textContent = friendly;
					card.setAttribute("data-date-str", (e.target.value + " " + dayName + " " + friendly).toLowerCase());
				}
			});

			// Filter schedule days.
			const schedSearch = document.getElementById("nmbp_schedule_search");
			if (schedSearch) {
				schedSearch.addEventListener("input", function() {
					const query = this.value.toLowerCase().trim();
					const cards = document.querySelectorAll("#nmbp_day_schedule_container .nmbp-day-card");
					cards.forEach(card => {
						const dateStr = card.getAttribute("data-date-str") || "";
						card.style.display = (!query || dateStr.includes(query)) ? "" : "none";
					});
				});
			}

			// Add Cast Member Row.
			const btnAddCast = document.getElementById("btn_add_cast_row");
			const castContainer = document.getElementById("nmbp_cast_repeater_container");
			if (btnAddCast && castContainer) {
				btnAddCast.addEventListener("click", function() {
					const row = document.createElement("div");
					row.className = "nmbp-repeater-row";
					row.innerHTML = `
						<input type="text" name="cast_names[]" placeholder="${i18n.actorPlaceholder}" required class="nmbp-cast-text-input">
						<input type="text" name="cast_roles[]" placeholder="${i18n.rolePlaceholder}" required class="nmbp-cast-text-input">
						<div class="nmbp-cast-photo-group">
							<input type="hidden" name="cast_photos[]" class="cast-photo-url-field" value="">
							<img src="" alt="" class="nmbp-cast-photo-thumb nmbp-cast-photo-thumb--hidden">
							<button type="button" class="button button-small btn-upload-cast-photo">📷 ${i18n.headshot}</button>
						</div>
						<button type="button" class="nmbp-btn-remove nmbp-btn-del-cast">✕</button>
					`;
					castContainer.appendChild(row);
				});
			}
		});
	})();
</script>