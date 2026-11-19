<?php
/**
 * Movie Module - Single Movie Dossier & Details View
 *
 * Path: admin/movies/view-movie.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
	wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
}

global $wpdb;
$table    = $wpdb->prefix . 'nmbp_control_movies';
$movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$movie = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $movie_id ) );

if ( ! $movie ) {
	?>
	<style id="nmbp-view-movie-not-found-css">
		.nmbp-movie-not-found {
			margin: 20px;
		}
	</style>
	<div class="nmbp-panel nmbp-movie-not-found">
		<p><?php esc_html_e( 'Movie record not found.', 'nahian-movie-booking-plugin' ); ?></p>
	</div>
	<?php
	return;
}

$cast_list = ! empty( $movie->cast_crew ) ? json_decode( $movie->cast_crew, true ) : array();
if ( ! is_array( $cast_list ) ) {
	$cast_list = array();
}

// Helper date formatter: e.g. "12 Sep 2026".
if ( ! function_exists( 'nmbp_format_view_date' ) ) {
	function nmbp_format_view_date( $raw_date ) {
		if ( empty( $raw_date ) ) {
			return '--';
		}
		$ts = strtotime( $raw_date );
		return $ts ? wp_date( 'd M Y', $ts ) : esc_html( $raw_date );
	}
}

// Helper 12-Hour AM/PM Time Formatter.
if ( ! function_exists( 'nmbp_format_view_time' ) ) {
	function nmbp_format_view_time( $raw_time ) {
		$clean = trim( (string) $raw_time );
		if ( empty( $clean ) ) {
			return '';
		}
		if ( false !== stripos( $clean, 'AM' ) || false !== stripos( $clean, 'PM' ) ) {
			return $clean;
		}
		if ( false !== strpos( $clean, ':' ) ) {
			$parts = explode( ':', $clean );
			$hour  = (int) $parts[0];
			$min   = isset( $parts[1] ) ? substr( $parts[1], 0, 2 ) : '00';
			$ampm  = $hour >= 12 ? 'PM' : 'AM';
			$h12   = ( 0 === $hour % 12 ) ? 12 : ( $hour % 12 );
			return sprintf( '%02d:%s %s', $h12, $min, $ampm );
		}
		return $clean;
	}
}

// Parse official single theatrical release date.
$official_release_date_str = '--';
if ( ! empty( $movie->release_date ) ) {
	$decoded_rd                  = json_decode( $movie->release_date, true );
	$raw_rd                      = ( is_array( $decoded_rd ) && ! empty( $decoded_rd[0] ) ) ? $decoded_rd[0] : $movie->release_date;
	$official_release_date_str = nmbp_format_view_date( $raw_rd );
}

// Parse hierarchical screening schedule (Days & Time slots).
$day_schedules = array();
if ( ! empty( $movie->showtimes ) ) {
	$decoded_s = json_decode( $movie->showtimes, true );
	if ( is_array( $decoded_s ) && ! empty( $decoded_s ) ) {
		if ( isset( $decoded_s[0]['date'] ) && isset( $decoded_s[0]['times'] ) ) {
			$day_schedules = $decoded_s;
		} elseif ( isset( $decoded_s[0]['date'] ) && isset( $decoded_s[0]['time'] ) ) {
			$grouped = array();
			foreach ( $decoded_s as $item ) {
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
		} else {
			$day_schedules = array(
				array(
					'date'  => current_time( 'Y-m-d' ),
					'times' => is_array( $decoded_s ) ? $decoded_s : array( '18:00' ),
				),
			);
		}
	}
}

$delete_url = wp_nonce_url(
	admin_url( 'admin.php?page=nmbp-movies&subtab=delete&id=' . absint( $movie->id ) ),
	'nmbp_delete_movie_' . absint( $movie->id ),
	'_nmbp_nonce'
);
?>

<style id="nmbp-view-movie-css">
	.nmbp-movie-assets-box {
		margin-top: 1.25rem;
		background: #f8fafc;
		border: 1px solid var(--nmbp-border, #e2e8f0);
		padding: 1rem;
		border-radius: 8px;
	}
	.nmbp-movie-assets-label {
		font-size: 0.72rem;
		text-transform: uppercase;
		font-weight: 800;
		color: var(--nmbp-text-muted, #64748b);
		display: block;
		margin-bottom: 8px;
	}
	.nmbp-movie-asset-row {
		font-size: 0.82rem;
		margin-bottom: 8px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.nmbp-movie-asset-row--last {
		font-size: 0.82rem;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.nmbp-movie-asset-strong {
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-movie-asset-link {
		color: #0284c7;
		font-weight: 700;
		text-decoration: none;
	}
	.nmbp-movie-auditorium-row {
		font-size: 0.82rem;
		margin-top: 8px;
		padding-top: 8px;
		border-top: 1px solid var(--nmbp-border-light, #f1f5f9);
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.nmbp-movie-auditorium-val {
		color: var(--nmbp-accent-teal, #0d9488);
		font-weight: 800;
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-movie-meta-badges {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 6px;
	}
	.nmbp-movie-format-tag {
		background: rgba(56, 189, 248, 0.15);
		color: #38bdf8;
		border: 1px solid rgba(56, 189, 248, 0.3);
		padding: 2px 8px;
		border-radius: 4px;
		font-size: 0.72rem;
		font-weight: 800;
		letter-spacing: 0.04em;
	}
	.nmbp-movie-genre-text {
		font-size: 0.78rem;
		color: #94a3b8;
	}
	.nmbp-movie-hero-title {
		font-size: 1.8rem;
		font-weight: 900;
		margin: 0 0 0.35rem 0;
		color: #ffffff;
	}
	.nmbp-movie-hero-meta {
		font-size: 0.8rem;
		color: #94a3b8;
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-movie-header-actions {
		display: flex;
		gap: 8px;
	}
	.nmbp-btn-edit-movie {
		background: #f1f5f9;
		border-color: #cbd5e1;
		color: #0f172a;
		font-weight: 800;
		border-radius: 6px;
	}
	.nmbp-btn-delete-movie {
		background: #fef2f2;
		border-color: #fecaca;
		color: #ef4444;
		font-weight: 800;
		border-radius: 6px;
	}
	.nmbp-metric-val--rating {
		color: #d97706;
	}
	.nmbp-metric-val--release {
		color: #059669;
	}
	.nmbp-credits-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 1rem;
		margin-bottom: 1.5rem;
	}
	.nmbp-credit-card {
		background: #f8fafc;
		border: 1px solid var(--nmbp-border, #e2e8f0);
		border-radius: 8px;
		padding: 0.9rem 1.15rem;
	}
	.nmbp-credit-label {
		font-size: 0.7rem;
		font-weight: 800;
		text-transform: uppercase;
		color: var(--nmbp-text-muted, #64748b);
		display: block;
		margin-bottom: 3px;
	}
	.nmbp-credit-value {
		font-size: 0.98rem;
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-dossier-section {
		margin-bottom: 1.5rem;
	}
	.nmbp-dossier-section-heading {
		margin: 0 0 0.75rem 0;
		font-size: 0.82rem;
		font-weight: 800;
		text-transform: uppercase;
		color: #475569;
		letter-spacing: 0.04em;
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.nmbp-dossier-section-heading--compact {
		margin: 0 0 0.65rem 0;
		font-size: 0.82rem;
		font-weight: 800;
		text-transform: uppercase;
		color: #475569;
		letter-spacing: 0.04em;
	}
	.nmbp-schedule-col-idx {
		width: 40px;
	}
	.nmbp-schedule-col-date {
		width: 160px;
	}
	.nmbp-schedule-col-day {
		width: 100px;
	}
	.nmbp-schedule-idx-cell {
		font-weight: 700;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-schedule-date-strong {
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-schedule-times-wrap {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
	}
	.nmbp-schedule-empty-cell {
		text-align: center;
		color: var(--nmbp-text-muted, #64748b);
		padding: 1.5rem;
	}
	.nmbp-synopsis-duo-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 1rem;
		margin-bottom: 1.5rem;
	}
	.nmbp-synopsis-copy {
		font-size: 0.88rem;
		line-height: 1.6;
		color: #334155;
		margin: 0;
	}
	.nmbp-trailer-player {
		width: 100%;
		max-height: 360px;
		border-radius: 8px;
		background: #000000;
		display: block;
	}
	.nmbp-cast-name {
		font-size: 0.88rem;
		color: var(--nmbp-text-main, #0f172a);
		display: block;
	}
	.nmbp-cast-role {
		font-size: 0.75rem;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-dossier-back-footer {
		margin-top: 1.5rem;
		border-top: 1px solid var(--nmbp-border-light, #f1f5f9);
		padding-top: 1.25rem;
	}
	.nmbp-btn-back-movies {
		font-weight: 700;
		border-radius: 6px;
	}
</style>

<div class="nmbp-movie-dossier-grid">

	<!-- Left Column: Poster & Direct Media Links -->
	<div class="nmbp-poster-sidebar-card">
		<img src="<?php echo esc_url( ! empty( $movie->poster_url ) ? $movie->poster_url : 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=700&auto=format&fit=crop&q=75' ); ?>" alt="<?php echo esc_attr( $movie->title ); ?>">

		<div class="nmbp-movie-assets-box">
			<span class="nmbp-movie-assets-label"><?php esc_html_e( 'Direct Assets', 'nahian-movie-booking-plugin' ); ?></span>

			<?php if ( ! empty( $movie->poster_url ) ) : ?>
				<div class="nmbp-movie-asset-row">
					<strong class="nmbp-movie-asset-strong"><?php esc_html_e( 'Poster Image:', 'nahian-movie-booking-plugin' ); ?></strong>
					<a href="<?php echo esc_url( $movie->poster_url ); ?>" target="_blank" rel="noopener noreferrer" class="nmbp-movie-asset-link"><?php esc_html_e( 'Open ↗', 'nahian-movie-booking-plugin' ); ?></a>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $movie->trailer_url ) ) : ?>
				<div class="nmbp-movie-asset-row--last">
					<strong class="nmbp-movie-asset-strong"><?php esc_html_e( 'Trailer Stream:', 'nahian-movie-booking-plugin' ); ?></strong>
					<a href="<?php echo esc_url( $movie->trailer_url ); ?>" target="_blank" rel="noopener noreferrer" class="nmbp-movie-asset-link"><?php esc_html_e( 'Open ↗', 'nahian-movie-booking-plugin' ); ?></a>
				</div>
			<?php endif; ?>

			<div class="nmbp-movie-auditorium-row">
				<strong class="nmbp-movie-asset-strong"><?php esc_html_e( 'Auditorium:', 'nahian-movie-booking-plugin' ); ?></strong>
				<span class="nmbp-movie-auditorium-val">
					<?php echo esc_html( ! empty( $movie->auditorium ) ? $movie->auditorium : 'Auditorium 1 (30 Seats)' ); ?>
				</span>
			</div>
		</div>
	</div>

	<!-- Right Column: Content & Metadata -->
	<div class="nmbp-movie-content-main">

		<!-- Top Header Banner -->
		<div class="nmbp-movie-banner-head">
			<div>
				<div class="nmbp-movie-meta-badges">
					<span class="nmbp-movie-format-tag">
						<?php echo esc_html( ! empty( $movie->badge_tag ) ? $movie->badge_tag : '4K DUAL LASER' ); ?>
					</span>
					<span class="nmbp-movie-genre-text">
						<?php echo esc_html( $movie->genre ?? '' ); ?>
					</span>
				</div>
				<h2 class="nmbp-movie-hero-title">
					<?php echo esc_html( $movie->title ?? '' ); ?>
				</h2>
				<div class="nmbp-movie-hero-meta">
					<?php esc_html_e( 'ID:', 'nahian-movie-booking-plugin' ); ?> #MOVIE-<?php echo absint( $movie->id ); ?> &bull; <?php esc_html_e( 'Category:', 'nahian-movie-booking-plugin' ); ?> <?php echo esc_html( $movie->category ?? 'Now Showing' ); ?>
				</div>
			</div>

			<div class="nmbp-movie-header-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-movies&subtab=edit&id=' . absint( $movie->id ) ) ); ?>" class="button nmbp-btn-edit-movie">
					✏️ <?php esc_html_e( 'Edit', 'nahian-movie-booking-plugin' ); ?>
				</a>
				<a href="<?php echo esc_url( $delete_url ); ?>" class="button nmbp-btn-delete-movie nmbp-delete-movie-dossier-link">
					✕ <?php esc_html_e( 'Delete', 'nahian-movie-booking-plugin' ); ?>
				</a>
			</div>
		</div>

		<!-- Main Content Details -->
		<div class="nmbp-movie-body-wrap">

			<!-- 3 Metrics: Rating, Release Date, Runtime -->
			<div class="nmbp-metric-card-grid">
				<div class="nmbp-metric-item">
					<div class="label">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
						<?php esc_html_e( 'Rating & Censor', 'nahian-movie-booking-plugin' ); ?>
					</div>
					<div class="val nmbp-metric-val--rating">★ <?php echo esc_html( $movie->rating ?? '0.0' ); ?> / 10</div>
					<div class="sub"><?php echo esc_html( $movie->age_rating ?? 'General Admission' ); ?></div>
				</div>

				<div class="nmbp-metric-item">
					<div class="label">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
						<?php esc_html_e( 'Release Date', 'nahian-movie-booking-plugin' ); ?>
					</div>
					<div class="val nmbp-metric-val--release"><?php echo esc_html( $official_release_date_str ); ?></div>
					<div class="sub"><?php esc_html_e( 'Theatrical Premiere', 'nahian-movie-booking-plugin' ); ?></div>
				</div>

				<div class="nmbp-metric-item">
					<div class="label">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
						<?php esc_html_e( 'Runtime & Audio', 'nahian-movie-booking-plugin' ); ?>
					</div>
					<div class="val"><?php echo esc_html( ! empty( $movie->duration ) ? $movie->duration : '--' ); ?></div>
					<div class="sub"><?php echo esc_html( ! empty( $movie->audio_language ) ? $movie->audio_language : 'Standard Mix' ); ?></div>
				</div>
			</div>

			<!-- Director & Writers -->
			<div class="nmbp-credits-grid">
				<div class="nmbp-credit-card">
					<span class="nmbp-credit-label"><?php esc_html_e( 'Director', 'nahian-movie-booking-plugin' ); ?></span>
					<strong class="nmbp-credit-value"><?php echo esc_html( ! empty( $movie->director ) ? $movie->director : __( 'Not Specified', 'nahian-movie-booking-plugin' ) ); ?></strong>
				</div>
				<div class="nmbp-credit-card">
					<span class="nmbp-credit-label"><?php esc_html_e( 'Screenwriters & Story', 'nahian-movie-booking-plugin' ); ?></span>
					<strong class="nmbp-credit-value"><?php echo esc_html( ! empty( $movie->writers ) ? $movie->writers : __( 'Not Specified', 'nahian-movie-booking-plugin' ) ); ?></strong>
				</div>
			</div>

			<!-- Screening Schedule Table -->
			<div class="nmbp-dossier-section">
				<h4 class="nmbp-dossier-section-heading">
					🗓️ <?php esc_html_e( 'Screening Schedule (Multi-Day Showtimes)', 'nahian-movie-booking-plugin' ); ?>
				</h4>

				<table class="nmbp-schedule-table">
					<thead>
						<tr>
							<th class="nmbp-schedule-col-idx">#</th>
							<th class="nmbp-schedule-col-date"><?php esc_html_e( 'Date', 'nahian-movie-booking-plugin' ); ?></th>
							<th class="nmbp-schedule-col-day"><?php esc_html_e( 'Day', 'nahian-movie-booking-plugin' ); ?></th>
							<th><?php esc_html_e( 'Showtimes', 'nahian-movie-booking-plugin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( $day_schedules ) ) :
							$row_idx = 1;
							foreach ( $day_schedules as $day_slot ) :
								$d_raw       = isset( $day_slot['date'] ) ? $day_slot['date'] : current_time( 'Y-m-d' );
								$formatted_d = nmbp_format_view_date( $d_raw );
								$day_name    = wp_date( 'D', strtotime( $d_raw ) );
								$times_arr   = (array) ( $day_slot['times'] ?? array( '18:00' ) );
								?>
							<tr>
								<td class="nmbp-schedule-idx-cell"><?php echo absint( $row_idx++ ); ?></td>
								<td><strong class="nmbp-schedule-date-strong"><?php echo esc_html( $formatted_d ); ?></strong></td>
								<td><span class="nmbp-weekday-badge"><?php echo esc_html( strtoupper( $day_name ) ); ?></span></td>
								<td>
									<div class="nmbp-schedule-times-wrap">
										<?php
										foreach ( $times_arr as $t ) :
											$formatted_t = nmbp_format_view_time( $t );
											?>
											<span class="nmbp-time-pill-view">
												⏰ <?php echo esc_html( $formatted_t ); ?>
											</span>
										<?php endforeach; ?>
									</div>
								</td>
							</tr>
								<?php
							endforeach;
						else :
							?>
							<tr><td colspan="4" class="nmbp-schedule-empty-cell"><?php esc_html_e( 'No screening schedules recorded.', 'nahian-movie-booking-plugin' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Short Summary & Full Storyline -->
			<div class="nmbp-synopsis-duo-grid">
				<div class="nmbp-synopsis-box">
					<h4>📄 <?php esc_html_e( 'Short Summary', 'nahian-movie-booking-plugin' ); ?></h4>
					<p class="nmbp-synopsis-copy">
						<?php echo nl2br( esc_html( ! empty( $movie->short_description ) ? $movie->short_description : __( 'No short summary provided.', 'nahian-movie-booking-plugin' ) ) ); ?>
					</p>
				</div>

				<div class="nmbp-synopsis-box">
					<h4>📖 <?php esc_html_e( 'Full Storyline & Synopsis', 'nahian-movie-booking-plugin' ); ?></h4>
					<p class="nmbp-synopsis-copy">
						<?php echo nl2br( esc_html( ! empty( $movie->synopsis ) ? $movie->synopsis : __( 'No full synopsis provided.', 'nahian-movie-booking-plugin' ) ) ); ?>
					</p>
				</div>
			</div>

			<!-- Trailer Preview Video -->
			<?php if ( ! empty( $movie->trailer_url ) ) : ?>
				<div class="nmbp-dossier-section">
					<h4 class="nmbp-dossier-section-heading--compact">
						▶️ <?php esc_html_e( 'Trailer Preview', 'nahian-movie-booking-plugin' ); ?>
					</h4>
					<video src="<?php echo esc_url( $movie->trailer_url ); ?>" controls class="nmbp-trailer-player"></video>
				</div>
			<?php endif; ?>

			<!-- Lead Cast Roster -->
			<?php if ( ! empty( $cast_list ) ) : ?>
				<div class="nmbp-dossier-section">
					<h4 class="nmbp-dossier-section-heading--compact">
						🎭 <?php esc_html_e( 'Lead Cast & Crew', 'nahian-movie-booking-plugin' ); ?> (<?php echo esc_html( (string) count( $cast_list ) ); ?>)
					</h4>
					<div class="nmbp-cast-grid-view">
						<?php foreach ( $cast_list as $cast ) : ?>
							<div class="nmbp-cast-card-mini">
								<img src="<?php echo esc_url( ! empty( $cast['photo'] ) ? $cast['photo'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80' ); ?>" class="nmbp-cast-avatar" alt="<?php echo esc_attr( $cast['name'] ?? '' ); ?>">
								<div>
									<strong class="nmbp-cast-name"><?php echo esc_html( ! empty( $cast['name'] ) ? $cast['name'] : __( 'Unknown', 'nahian-movie-booking-plugin' ) ); ?></strong>
									<span class="nmbp-cast-role"><?php echo esc_html( ! empty( $cast['role'] ) ? $cast['role'] : __( 'Cast Member', 'nahian-movie-booking-plugin' ) ); ?></span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Back Navigation Link -->
			<div class="nmbp-dossier-back-footer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-movies&subtab=all' ) ); ?>" class="button button-secondary nmbp-btn-back-movies">
					&larr; <?php esc_html_e( 'Back to All Movies', 'nahian-movie-booking-plugin' ); ?>
				</a>
			</div>

		</div>

	</div>

</div>

<script id="nmbp-view-movie-js">
	(function() {
		document.addEventListener("DOMContentLoaded", function() {
			const confirmDelete = <?php echo wp_json_encode( __( 'Permanently delete this movie?', 'nahian-movie-booking-plugin' ) ); ?>;
			document.querySelectorAll(".nmbp-delete-movie-dossier-link").forEach(function(link) {
				link.addEventListener("click", function(e) {
					if (!window.confirm(confirmDelete)) {
						e.preventDefault();
					}
				});
			});
		});
	})();
</script>