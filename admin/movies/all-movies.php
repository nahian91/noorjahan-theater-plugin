<?php
/**
 * Movie Module - All Movies Catalog View
 *
 * Path: admin/movies/all-movies.php
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
$table_movies        = $wpdb->prefix . 'nmbp_control_movies';
$table_bookings      = $wpdb->prefix . 'nmbp_bookings';
$today_ymd           = current_time( 'Y-m-d' );
$total_hall_capacity = 30; // Synchronized 30-seat matrix capacity (6 rows x 5 seats).

// Always fetch complete movie columns.
$movies      = $wpdb->get_results( "SELECT * FROM {$table_movies} ORDER BY id DESC" );
$movie_count = ( is_array( $movies ) ) ? count( $movies ) : 0;

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

// PRE-FETCH TODAY'S BOOKINGS MAP.
$today_bookings_map  = array();
$prefetched_bookings = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT movie_title, showtime, GROUP_CONCAT(seats SEPARATOR ',') as booked_seats 
		 FROM {$table_bookings} 
		 WHERE DATE(booking_date) = %s AND payment_status != 'Cancelled' 
		 GROUP BY movie_title, showtime",
		$today_ymd
	)
);

if ( ! empty( $prefetched_bookings ) ) {
	foreach ( $prefetched_bookings as $pb ) {
		$clean_title = strtolower( trim( (string) $pb->movie_title ) );
		$clean_time  = trim( (string) $pb->showtime );
		$seat_count  = count( array_filter( array_map( 'trim', explode( ',', (string) $pb->booked_seats ) ) ) );

		$today_bookings_map[ $clean_title . '|' . strtolower( $clean_time ) ] = $seat_count;
		$today_bookings_map[ $clean_title . '|' . strtolower( nmbp_format_view_time( $clean_time ) ) ] = $seat_count;

		$ts = strtotime( $today_ymd . ' ' . $clean_time );
		if ( $ts ) {
			$today_bookings_map[ $clean_title . '|' . gmdate( 'H:i', $ts ) ] = $seat_count;
		}
	}
}
?>

<style id="nmbp-all-movies-css">
	.nmbp-panel-container {
		background: #ffffff;
		border: 1px solid var(--nmbp-border, #e2e8f0);
		border-radius: 10px;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
		margin-top: 1rem;
	}
	.nmbp-movies-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1.25rem 1.5rem;
		border-bottom: 1px solid var(--nmbp-border, #e2e8f0);
	}
	.nmbp-movies-title {
		margin: 0 0 4px 0;
		font-size: 1.15rem;
		font-weight: 800;
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-movies-title-icon {
		vertical-align: -3px;
		margin-right: 4px;
	}
	.nmbp-movies-subtitle {
		font-size: 0.78rem;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-btn-add-movie {
		background: #0d9488;
		border-color: #0d9488;
		font-weight: 700;
	}
	.nmbp-table-filter-toolbar {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 12px 20px;
		background: #f8fafc;
		border-bottom: 1px solid var(--nmbp-border, #e2e8f0);
		gap: 12px;
	}
	.nmbp-filter-search-group {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.nmbp-filter-search-label {
		font-size: 0.78rem;
		font-weight: 800;
		text-transform: uppercase;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-table-filter-toolbar input[type="text"] {
		padding: 6px 12px;
		border: 1px solid var(--nmbp-border, #cbd5e1);
		border-radius: 6px;
		font-size: 0.88rem;
		outline: none;
		min-width: 240px;
	}
	.nmbp-table-filter-toolbar input[type="text"]:focus {
		border-color: #0d9488;
		box-shadow: 0 0 0 1px #0d9488;
	}
	.nmbp-btn-reset-filter {
		font-weight: 700;
	}
	.nmbp-movies-table-wrap {
		overflow-x: auto;
	}
	.nmbp-movies-table {
		width: 100%;
		border-collapse: collapse;
	}
	.nmbp-movies-thead-row {
		border-bottom: 1px solid var(--nmbp-border, #e2e8f0);
		background: #f8fafc;
	}
	.nmbp-movies-th {
		padding: 10px 14px;
		text-align: left;
	}
	.nmbp-movies-th--poster {
		padding: 10px 14px;
		text-align: left;
		width: 60px;
	}
	.nmbp-movies-th--category {
		padding: 10px 14px;
		text-align: left;
		width: 140px;
	}
	.nmbp-movies-th--actions {
		padding: 10px 14px;
		text-align: right;
		width: 170px;
	}
	.nmbp-movie-row {
		border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);
	}
	.nmbp-movie-row--hidden {
		display: none;
	}
	.nmbp-movies-td {
		padding: 12px 14px;
	}
	.nmbp-movies-td--actions {
		padding: 12px 14px;
		text-align: right;
		white-space: nowrap;
	}
	.nmbp-poster-thumb {
		width: 44px;
		height: 60px;
		border-radius: 6px;
		object-fit: cover;
		box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
		border: 1px solid var(--nmbp-border, #e2e8f0);
		display: block;
	}
	.nmbp-poster-placeholder {
		width: 44px;
		height: 60px;
		border-radius: 6px;
		background: #0f172a;
		color: #94a3b8;
		font-size: 0.65rem;
		display: flex;
		align-items: center;
		justify-content: center;
		text-align: center;
		font-weight: 800;
		border: 1px solid #1e293b;
	}
	.nmbp-movie-title-text {
		color: var(--nmbp-text-main, #0f172a);
		font-size: 0.95rem;
	}
	.nmbp-movie-badge-pill {
		font-size: 0.68rem;
		margin-top: 4px;
		display: inline-block;
	}
	.nmbp-showtime-chip {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		background: #f1f5f9;
		border: 1px solid #cbd5e1;
		padding: 4px 10px;
		border-radius: 6px;
		font-size: 0.8rem;
		font-weight: 700;
		color: #334155;
	}
	.nmbp-showtime-chip .seats-left {
		font-size: 0.72rem;
		padding: 2px 6px;
		border-radius: 4px;
		background: #ecfdf5;
		color: #059669;
		font-family: var(--nmbp-font-mono, monospace);
		font-weight: 800;
	}
	.nmbp-showtime-chip .seats-left.sold-out {
		background: #fef2f2;
		color: #ef4444;
	}
	.nmbp-showtimes-list {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
		align-items: center;
	}
	.nmbp-no-shows-text {
		color: var(--nmbp-text-muted, #64748b);
		font-size: 0.78rem;
		font-style: italic;
	}
	.nmbp-category-badge {
		display: inline-block;
		padding: 3px 8px;
		border-radius: 6px;
		font-size: 0.75rem;
		font-weight: 800;
		letter-spacing: 0.02em;
		background: #f1f5f9;
		color: #334155;
		border: 1px solid #cbd5e1;
	}
	.nmbp-category-badge.now-showing {
		background: #ecfdf5;
		color: #065f46;
		border-color: #a7f3d0;
	}
	.nmbp-category-badge.upcoming {
		background: #fef3c7;
		color: #92400e;
		border-color: #fde68a;
	}
	.nmbp-table-actions {
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}
	.nmbp-action-btn {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		padding: 4px 8px;
		border-radius: 6px;
		font-size: 0.75rem;
		font-weight: 700;
		text-decoration: none;
		border: 1px solid transparent;
		transition: background 0.15s ease, border-color 0.15s ease;
	}
	.nmbp-action-btn svg {
		width: 14px;
		height: 14px;
		fill: currentColor;
	}
	.nmbp-action-btn.view {
		background: #f0fdf4;
		border-color: #bbf7d0;
		color: #166534;
	}
	.nmbp-action-btn.view:hover {
		background: #dcfce7;
	}
	.nmbp-action-btn.edit {
		background: #f0f9ff;
		border-color: #bae6fd;
		color: #0369a1;
	}
	.nmbp-action-btn.edit:hover {
		background: #e0f2fe;
	}
	.nmbp-action-btn.delete {
		background: #fef2f2;
		border-color: #fecaca;
		color: #991b1b;
	}
	.nmbp-action-btn.delete:hover {
		background: #fee2e2;
	}
	.nmbp-empty-row-cell {
		text-align: center;
		padding: 2.5rem;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-filter-empty-row {
		display: none;
	}
	.nmbp-filter-empty-row--visible {
		display: table-row;
	}
</style>

<div class="nmbp-panel nmbp-panel-container">
	<div class="nmbp-panel-header nmbp-movies-header">
		<div>
			<h3 class="nmbp-panel-title nmbp-movies-title">
				<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" class="nmbp-movies-title-icon"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
				<?php esc_html_e( 'All Feature Movies', 'nahian-movie-booking-plugin' ); ?> (<span id="filteredMovieCount"><?php echo esc_html( (string) $movie_count ); ?></span>)
			</h3>
			<span class="nmbp-movies-subtitle">
				<?php esc_html_e( "Manage multiplex film catalog, categories, and today's live seat availability (30-seat matrix capacity).", 'nahian-movie-booking-plugin' ); ?>
			</span>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-movies&subtab=add' ) ); ?>" class="button button-primary nmbp-btn-add-movie">
			+ <?php esc_html_e( 'Add New Movie', 'nahian-movie-booking-plugin' ); ?>
		</a>
	</div>

	<!-- Live Filter Toolbar -->
	<div class="nmbp-table-filter-toolbar">
		<div class="nmbp-filter-search-group">
			<span class="nmbp-filter-search-label">
				<?php esc_html_e( 'Search Title:', 'nahian-movie-booking-plugin' ); ?>
			</span>
			<input type="text" id="filterMovieTitle" placeholder="<?php esc_attr_e( 'Type title to filter...', 'nahian-movie-booking-plugin' ); ?>" aria-label="<?php esc_attr_e( 'Search movie title', 'nahian-movie-booking-plugin' ); ?>">
		</div>

		<div>
			<button type="button" class="button button-secondary button-small nmbp-btn-reset-filter" id="btnResetMovieFilters">
				<?php esc_html_e( 'Reset Filter', 'nahian-movie-booking-plugin' ); ?>
			</button>
		</div>
	</div>

	<div class="nmbp-table-wrap nmbp-movies-table-wrap">
		<table class="nmbp-data-table nmbp-movies-table" id="nmbpMoviesTable">
			<thead>
				<tr class="nmbp-movies-thead-row">
					<th class="nmbp-movies-th--poster"><?php esc_html_e( 'POSTER', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-movies-th"><?php esc_html_e( 'TITLE', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-movies-th--category"><?php esc_html_e( 'CATEGORY', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-movies-th"><?php esc_html_e( "TODAY'S SHOWTIMES & TICKETS (30 SEATS MAX)", 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-movies-th--actions"><?php esc_html_e( 'ACTIONS', 'nahian-movie-booking-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! empty( $movies ) && is_array( $movies ) ) :
					foreach ( $movies as $m ) :
						$m_id           = absint( $m->id );
						$view_admin_url = admin_url( 'admin.php?page=nmbp-movies&subtab=view&id=' . $m_id );
						$edit_url       = admin_url( 'admin.php?page=nmbp-movies&subtab=edit&id=' . $m_id );
						$delete_url     = wp_nonce_url(
							admin_url( 'admin.php?page=nmbp-movies&subtab=delete&id=' . $m_id ),
							'nmbp_delete_movie_' . $m_id,
							'_nmbp_nonce'
						);

						// Category formatting.
						$category_val = ! empty( $m->category ) ? trim( (string) $m->category ) : __( 'Now Showing', 'nahian-movie-booking-plugin' );
						$cat_slug     = sanitize_title( $category_val );

						// Parse showtimes.
						$today_matched_times = array();
						$all_movie_times     = array();

						if ( ! empty( $m->showtimes ) ) {
							$raw_st  = wp_unslash( trim( (string) $m->showtimes ) );
							$decoded = json_decode( $raw_st, true );

							if ( ! is_array( $decoded ) && ! empty( $raw_st ) ) {
								$decoded = json_decode( stripslashes( $raw_st ), true );
							}

							if ( is_array( $decoded ) ) {
								foreach ( $decoded as $entry ) {
									if ( is_array( $entry ) ) {
										$entry_date_raw = ! empty( $entry['date'] ) ? trim( (string) $entry['date'] ) : '';
										$parsed_ts      = $entry_date_raw ? strtotime( $entry_date_raw ) : false;
										$entry_date_ymd = $parsed_ts ? gmdate( 'Y-m-d', $parsed_ts ) : $entry_date_raw;

										$times_bundle = array();
										if ( ! empty( $entry['times'] ) && is_array( $entry['times'] ) ) {
											$times_bundle = $entry['times'];
										} elseif ( ! empty( $entry['time'] ) ) {
											$times_bundle = array( $entry['time'] );
										}

										foreach ( $times_bundle as $t ) {
											$t_clean = trim( (string) $t );
											if ( ! empty( $t_clean ) ) {
												$all_movie_times[] = $t_clean;
												if ( $entry_date_ymd === $today_ymd ) {
													$today_matched_times[] = $t_clean;
												}
											}
										}
									} elseif ( is_string( $entry ) && ! empty( trim( $entry ) ) ) {
										$all_movie_times[] = trim( $entry );
									}
								}
							} elseif ( is_string( $raw_st ) && ! empty( $raw_st ) ) {
								$exploded = explode( ',', $raw_st );
								foreach ( $exploded as $exp_t ) {
									$t_clean = trim( $exp_t );
									if ( ! empty( $t_clean ) ) {
										$all_movie_times[] = $t_clean;
									}
								}
							}
						}

						// Prioritize exact today matches, then fallback to recurring times.
						$display_times = ! empty( $today_matched_times ) ? $today_matched_times : $all_movie_times;

						$today_show_data = array();
						if ( ! empty( $display_times ) ) {
							$unique_times = array_unique( array_filter( $display_times ) );
							foreach ( $unique_times as $clean_t ) {
								$formatted_time = nmbp_format_view_time( $clean_t );
								$movie_key      = strtolower( trim( (string) $m->title ) );

								$booked_count = 0;
								if ( isset( $today_bookings_map[ $movie_key . '|' . strtolower( $clean_t ) ] ) ) {
									$booked_count = $today_bookings_map[ $movie_key . '|' . strtolower( $clean_t ) ];
								} elseif ( isset( $today_bookings_map[ $movie_key . '|' . strtolower( $formatted_time ) ] ) ) {
									$booked_count = $today_bookings_map[ $movie_key . '|' . strtolower( $formatted_time ) ];
								}

								$remaining_seats = max( 0, $total_hall_capacity - $booked_count );

								$today_show_data[] = array(
									'time'      => $formatted_time,
									'remaining' => $remaining_seats,
								);
							}
						}
						?>
					<tr class="nmbp-movie-row" data-title="<?php echo esc_attr( strtolower( (string) $m->title ) ); ?>">
						<td class="nmbp-movies-td">
							<?php if ( ! empty( $m->poster_url ) ) : ?>
								<img src="<?php echo esc_url( $m->poster_url ); ?>" alt="<?php echo esc_attr( $m->title ); ?>" class="nmbp-poster-thumb">
							<?php else : ?>
								<div class="nmbp-poster-placeholder">
									<?php esc_html_e( 'FILM', 'nahian-movie-booking-plugin' ); ?>
								</div>
							<?php endif; ?>
						</td>
						<td class="nmbp-movies-td">
							<strong class="nmbp-movie-title-text"><?php echo esc_html( $m->title ); ?></strong>
							<?php if ( ! empty( $m->badge_tag ) ) : ?>
								<br><span class="nmbp-badge-pill active nmbp-movie-badge-pill"><?php echo esc_html( $m->badge_tag ); ?></span>
							<?php endif; ?>
						</td>
						<td class="nmbp-movies-td">
							<span class="nmbp-category-badge <?php echo esc_attr( $cat_slug ); ?>">
								<?php echo esc_html( $category_val ); ?>
							</span>
						</td>
						<td class="nmbp-movies-td">
							<?php if ( ! empty( $today_show_data ) ) : ?>
								<div class="nmbp-showtimes-list">
									<?php
									foreach ( $today_show_data as $show ) :
										$is_sold_out = ( 0 === $show['remaining'] );
										?>
										<span class="nmbp-showtime-chip">
											<span><?php echo esc_html( $show['time'] ); ?></span>
											<span class="seats-left <?php echo $is_sold_out ? 'sold-out' : ''; ?>">
												<?php echo $is_sold_out ? esc_html__( 'Sold Out', 'nahian-movie-booking-plugin' ) : esc_html( $show['remaining'] . ' ' . __( 'left', 'nahian-movie-booking-plugin' ) ); ?>
											</span>
										</span>
									<?php endforeach; ?>
								</div>
							<?php else : ?>
								<span class="nmbp-no-shows-text">
									<?php esc_html_e( 'No shows scheduled today', 'nahian-movie-booking-plugin' ); ?>
								</span>
							<?php endif; ?>
						</td>
						<td class="nmbp-movies-td--actions">
							<div class="nmbp-table-actions">
								<a href="<?php echo esc_url( $view_admin_url ); ?>" class="nmbp-action-btn view" title="<?php esc_attr_e( 'View', 'nahian-movie-booking-plugin' ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
									<span><?php esc_html_e( 'View', 'nahian-movie-booking-plugin' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="nmbp-action-btn edit" title="<?php esc_attr_e( 'Edit', 'nahian-movie-booking-plugin' ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
									<span><?php esc_html_e( 'Edit', 'nahian-movie-booking-plugin' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="nmbp-action-btn delete nmbp-delete-movie-link" title="<?php esc_attr_e( 'Delete', 'nahian-movie-booking-plugin' ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
									<span><?php esc_html_e( 'Delete', 'nahian-movie-booking-plugin' ); ?></span>
								</a>
							</div>
						</td>
					</tr>
						<?php
					endforeach;
				else :
					?>
					<tr id="noMoviesRow">
						<td colspan="5" class="nmbp-empty-row-cell">
							<?php esc_html_e( 'No movies published in the catalog.', 'nahian-movie-booking-plugin' ); ?>
						</td>
					</tr>
				<?php endif; ?>
				<tr id="nmbpMovieFilterEmptyRow" class="nmbp-filter-empty-row">
					<td colspan="5" class="nmbp-empty-row-cell">
						<?php esc_html_e( 'No movies found matching your search title.', 'nahian-movie-booking-plugin' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<script id="nmbp-movies-catalog-runtime-js">
	(function() {
		document.addEventListener("DOMContentLoaded", function() {
			const filterInput    = document.getElementById("filterMovieTitle");
			const btnReset       = document.getElementById("btnResetMovieFilters");
			const counterEl      = document.getElementById("filteredMovieCount");
			const emptyFilterRow = document.getElementById("nmbpMovieFilterEmptyRow");
			const rows           = document.querySelectorAll("#nmbpMoviesTable tbody tr.nmbp-movie-row");
			const confirmDelete  = <?php echo wp_json_encode( __( 'Are you sure you want to permanently delete this movie from the database?', 'nahian-movie-booking-plugin' ) ); ?>;

			function applyFilter() {
				const searchTitle = filterInput ? filterInput.value.toLowerCase().trim() : "";
				let visibleCount = 0;

				rows.forEach(function(row) {
					const rTitle = row.getAttribute("data-title") || "";
					const match = !searchTitle || rTitle.indexOf(searchTitle) !== -1;

					if (match) {
						row.classList.remove("nmbp-movie-row--hidden");
						visibleCount++;
					} else {
						row.classList.add("nmbp-movie-row--hidden");
					}
				});

				if (emptyFilterRow) {
					if (visibleCount === 0 && rows.length > 0) {
						emptyFilterRow.classList.add("nmbp-filter-empty-row--visible");
					} else {
						emptyFilterRow.classList.remove("nmbp-filter-empty-row--visible");
					}
				}

				if (counterEl) {
					counterEl.textContent = visibleCount;
				}
			}

			if (filterInput) {
				filterInput.addEventListener("input", applyFilter);
			}

			if (btnReset) {
				btnReset.addEventListener("click", function() {
					if (filterInput) filterInput.value = "";
					applyFilter();
				});
			}

			document.querySelectorAll(".nmbp-delete-movie-link").forEach(function(link) {
				link.addEventListener("click", function(e) {
					if (!window.confirm(confirmDelete)) {
						e.preventDefault();
					}
				});
			});
		});
	})();
</script>