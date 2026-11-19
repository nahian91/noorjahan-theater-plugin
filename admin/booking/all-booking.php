<?php
/**
 * Booking Module - All Bookings View
 *
 * Path: admin/booking/all-booking.php
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
$table_bookings = $wpdb->prefix . 'nmbp_bookings';

$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );
$filter_movies   = $wpdb->get_col( "SELECT DISTINCT movie_title FROM {$table_bookings} WHERE movie_title != '' ORDER BY movie_title ASC" );
$filter_times    = $wpdb->get_col( "SELECT DISTINCT showtime FROM {$table_bookings} WHERE showtime != '' ORDER BY showtime ASC" );

if ( ! isset( $bookings ) || ! is_array( $bookings ) ) {
	$bookings = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC LIMIT 250" );
}
if ( ! is_array( $bookings ) ) {
	$bookings = array();
}

$today_ymd     = current_time( 'Y-m-d' );
$booking_count = count( $bookings );

// Reusable 12-Hour AM/PM Formatter.
if ( ! function_exists( 'nmbp_format_display_time' ) ) {
	function nmbp_format_display_time( $raw_time ) {
		$clean = trim( (string) $raw_time );
		if ( empty( $clean ) ) {
			return __( 'Counter', 'nahian-movie-booking-plugin' );
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
?>

<style id="nmbp-all-booking-css">
	.nmbp-bookings-panel {
		margin: 0;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
	}
	.nmbp-bookings-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1.25rem 1.5rem;
		border-bottom: 1px solid var(--nmbp-border, #e2e8f0);
	}
	.nmbp-bookings-title {
		margin: 0 0 4px 0;
		font-size: 1.15rem;
		font-weight: 800;
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-bookings-title-icon {
		vertical-align: -3px;
		margin-right: 4px;
	}
	.nmbp-bookings-subtitle {
		font-size: 0.78rem;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-filter-group {
		display: flex;
		align-items: center;
		gap: 6px;
	}
	.nmbp-filter-label {
		font-size: 0.78rem;
		font-weight: 800;
		text-transform: uppercase;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-filter-actions {
		margin-left: auto;
		display: flex;
		gap: 8px;
	}
	.nmbp-btn-filter-action {
		font-weight: 700;
	}
	.nmbp-bookings-table-wrap {
		overflow-x: auto;
	}
	.nmbp-bookings-table {
		width: 100%;
		border-collapse: collapse;
	}
	.nmbp-bookings-thead-row {
		border-bottom: 1px solid var(--nmbp-border, #e2e8f0);
		background: #f8fafc;
	}
	.nmbp-bookings-th {
		padding: 10px 12px;
		text-align: left;
	}
	.nmbp-bookings-th--right {
		padding: 10px 12px;
		text-align: right;
	}
	.nmbp-booking-row {
		border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);
	}
	.nmbp-booking-row--hidden {
		display: none;
	}
	.nmbp-bookings-td {
		padding: 12px;
	}
	.nmbp-bookings-td--right {
		padding: 12px;
		text-align: right;
		white-space: nowrap;
	}
	.nmbp-booking-phone-text {
		font-family: var(--nmbp-font-mono, monospace);
		font-size: 0.78rem;
		color: #64748b;
	}
	.nmbp-booking-showtime-badge {
		font-size: 0.74rem;
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-booking-seats-deck {
		display: flex;
		flex-wrap: wrap;
		gap: 4px;
		max-width: 140px;
	}
	.nmbp-booking-seat-chip {
		display: inline-block;
		background: #0f172a;
		color: #38bdf8;
		font-family: var(--nmbp-font-mono, monospace);
		font-size: 0.7rem;
		font-weight: 800;
		padding: 2px 5px;
		border-radius: 4px;
	}
	.nmbp-booking-payment-badge {
		font-size: 0.72rem;
	}
	.nmbp-booking-date-text {
		font-size: 0.85rem;
		font-family: var(--nmbp-font-mono, monospace);
		color: #334155;
		font-weight: 700;
	}
	.nmbp-booking-time-sub {
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-booking-total-text {
		color: var(--nmbp-accent-teal, #0d9488);
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-bookings-empty-cell {
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

<div class="nmbp-panel nmbp-bookings-panel">
	<div class="nmbp-panel-header nmbp-bookings-header">
		<div>
			<h3 class="nmbp-panel-title nmbp-bookings-title">
				<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" class="nmbp-bookings-title-icon"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
				<?php esc_html_e( 'All Customer Bookings & Passes', 'nahian-movie-booking-plugin' ); ?> (<span id="filteredCount"><?php echo esc_html( (string) $booking_count ); ?></span>)
			</h3>
			<span class="nmbp-bookings-subtitle"><?php esc_html_e( 'Showing active admissions and turnstile passes.', 'nahian-movie-booking-plugin' ); ?></span>
		</div>
	</div>

	<!-- Live Filter Toolbar -->
	<div class="nmbp-table-filter-toolbar">
		<div class="nmbp-filter-group">
			<span class="nmbp-filter-label"><?php esc_html_e( 'Filter Movie:', 'nahian-movie-booking-plugin' ); ?></span>
			<select id="filterMovie" aria-label="<?php esc_attr_e( 'Filter by Movie', 'nahian-movie-booking-plugin' ); ?>">
				<option value="">-- <?php esc_html_e( 'All Movies', 'nahian-movie-booking-plugin' ); ?> --</option>
				<?php foreach ( $filter_movies as $fm ) : ?>
					<option value="<?php echo esc_attr( $fm ); ?>"><?php echo esc_html( $fm ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="nmbp-filter-group">
			<span class="nmbp-filter-label"><?php esc_html_e( 'Showtime:', 'nahian-movie-booking-plugin' ); ?></span>
			<select id="filterTime" aria-label="<?php esc_attr_e( 'Filter by Showtime', 'nahian-movie-booking-plugin' ); ?>">
				<option value="">-- <?php esc_html_e( 'All Times', 'nahian-movie-booking-plugin' ); ?> --</option>
				<?php foreach ( $filter_times as $ft ) : ?>
					<option value="<?php echo esc_attr( $ft ); ?>"><?php echo esc_html( nmbp_format_display_time( $ft ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="nmbp-filter-group">
			<span class="nmbp-filter-label"><?php esc_html_e( 'Date:', 'nahian-movie-booking-plugin' ); ?></span>
			<input type="date" id="filterDate" value="<?php echo esc_attr( $today_ymd ); ?>" aria-label="<?php esc_attr_e( 'Filter by Date', 'nahian-movie-booking-plugin' ); ?>">
		</div>

		<div class="nmbp-filter-actions">
			<button type="button" class="button button-small nmbp-btn-filter-action" id="btnShowAllBookings"><?php esc_html_e( 'Show All', 'nahian-movie-booking-plugin' ); ?></button>
			<button type="button" class="button button-small nmbp-btn-filter-action" id="btnTodayOnlyBookings"><?php esc_html_e( 'Today Only', 'nahian-movie-booking-plugin' ); ?></button>
		</div>
	</div>

	<div class="nmbp-table-wrap nmbp-bookings-table-wrap">
		<table class="nmbp-data-table nmbp-bookings-table" id="nmbpBookingsTable">
			<thead>
				<tr class="nmbp-bookings-thead-row">
					<th class="nmbp-bookings-th"><?php esc_html_e( 'TOKEN', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-bookings-th"><?php esc_html_e( 'CUSTOMER', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-bookings-th"><?php esc_html_e( 'MOVIE', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-bookings-th"><?php esc_html_e( 'SHOWTIME', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-bookings-th"><?php esc_html_e( 'SEATS', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-bookings-th"><?php esc_html_e( 'PAYMENT', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-bookings-th"><?php esc_html_e( 'DATE', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-bookings-th"><?php esc_html_e( 'TOTAL', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-bookings-th--right"><?php esc_html_e( 'ACTIONS', 'nahian-movie-booking-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! empty( $bookings ) ) :
					foreach ( $bookings as $b ) :
						$booking_id = absint( $b->id );
						$view_url   = admin_url( 'admin.php?page=nmbp-booking&subtab=view&id=' . $booking_id );
						$edit_url   = admin_url( 'admin.php?page=nmbp-booking&subtab=edit&id=' . $booking_id );
						$delete_url = wp_nonce_url(
							admin_url( 'admin.php?page=nmbp-booking&subtab=delete&id=' . $booking_id ),
							'nmbp_delete_booking_' . $booking_id
						);

						$row_date     = ! empty( $b->booking_date ) ? wp_date( 'Y-m-d', strtotime( $b->booking_date ) ) : '';
						$human_date   = ! empty( $b->booking_date ) ? wp_date( 'd M Y', strtotime( $b->booking_date ) ) : '--';
						$human_time   = ! empty( $b->booking_date ) ? wp_date( 'h:i A', strtotime( $b->booking_date ) ) : '';
						$formatted_st = nmbp_format_display_time( $b->showtime ?? '' );
						?>
					<tr class="nmbp-booking-row" data-movie="<?php echo esc_attr( (string) $b->movie_title ); ?>" data-time="<?php echo esc_attr( (string) $b->showtime ); ?>" data-date="<?php echo esc_attr( $row_date ); ?>">
						<td class="nmbp-bookings-td">
							<span class="nmbp-voucher-tag"><?php echo esc_html( $b->booking_token ?? '' ); ?></span>
						</td>
						<td class="nmbp-bookings-td">
							<strong><?php echo esc_html( ! empty( $b->customer_name ) ? $b->customer_name : __( 'Walk-In Guest', 'nahian-movie-booking-plugin' ) ); ?></strong>
							<?php if ( ! empty( $b->customer_phone ) ) : ?>
								<br><span class="nmbp-booking-phone-text"><?php echo esc_html( $b->customer_phone ); ?></span>
							<?php endif; ?>
						</td>
						<td class="nmbp-bookings-td">
							<strong><?php echo esc_html( $b->movie_title ?? '' ); ?></strong>
						</td>
						<td class="nmbp-bookings-td">
							<span class="nmbp-badge-pill active nmbp-booking-showtime-badge"><?php echo esc_html( $formatted_st ); ?></span>
						</td>
						<td class="nmbp-bookings-td">
							<div class="nmbp-booking-seats-deck">
								<?php
								$seat_chips = array_filter( array_map( 'trim', explode( ',', (string) ( $b->seats ?? '' ) ) ) );
								foreach ( $seat_chips as $sc ) :
									?>
									<span class="nmbp-booking-seat-chip">
										<?php echo esc_html( $sc ); ?>
									</span>
								<?php endforeach; ?>
							</div>
						</td>
						<td class="nmbp-bookings-td">
							<span class="nmbp-badge-pill nmbp-booking-payment-badge"><?php echo esc_html( ! empty( $b->payment_method ) ? $b->payment_method : 'Cash' ); ?></span>
						</td>
						<td class="nmbp-bookings-td">
							<span class="nmbp-booking-date-text"><?php echo esc_html( $human_date ); ?></span>
							<?php if ( ! empty( $human_time ) ) : ?>
								<br><small class="nmbp-booking-time-sub"><?php echo esc_html( $human_time ); ?></small>
							<?php endif; ?>
						</td>
						<td class="nmbp-bookings-td">
							<strong class="nmbp-booking-total-text">
								<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $b->total_amount ?? 0.00 ), 2 ) ); ?>
							</strong>
						</td>
						<td class="nmbp-bookings-td--right">
							<div class="nmbp-table-actions">
								<a href="<?php echo esc_url( $view_url ); ?>" class="nmbp-action-btn view" title="<?php esc_attr_e( 'View', 'nahian-movie-booking-plugin' ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
									<span><?php esc_html_e( 'View', 'nahian-movie-booking-plugin' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="nmbp-action-btn edit" title="<?php esc_attr_e( 'Edit', 'nahian-movie-booking-plugin' ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
									<span><?php esc_html_e( 'Edit', 'nahian-movie-booking-plugin' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="nmbp-action-btn delete nmbp-delete-booking-link" title="<?php esc_attr_e( 'Delete', 'nahian-movie-booking-plugin' ); ?>">
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
					<tr id="noBookingsRow">
						<td colspan="9" class="nmbp-bookings-empty-cell">
							<?php esc_html_e( 'No bookings recorded yet.', 'nahian-movie-booking-plugin' ); ?>
						</td>
					</tr>
				<?php endif; ?>
				<tr id="nmbpFilterEmptyRow" class="nmbp-filter-empty-row">
					<td colspan="9" class="nmbp-bookings-empty-cell">
						<?php esc_html_e( 'No bookings match your current filter selections.', 'nahian-movie-booking-plugin' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<script id="nmbp-booking-catalog-runtime-js">
	(function() {
		document.addEventListener("DOMContentLoaded", function() {
			const filterMovie   = document.getElementById("filterMovie");
			const filterTime    = document.getElementById("filterTime");
			const filterDate    = document.getElementById("filterDate");
			const counterEl     = document.getElementById("filteredCount");
			const btnShowAll    = document.getElementById("btnShowAllBookings");
			const btnToday      = document.getElementById("btnTodayOnlyBookings");
			const emptyFilter   = document.getElementById("nmbpFilterEmptyRow");
			const todayStr      = <?php echo wp_json_encode( $today_ymd ); ?>;
			const confirmDelete = <?php echo wp_json_encode( __( 'Are you sure you want to delete this booking record?', 'nahian-movie-booking-plugin' ) ); ?>;

			function applyBookingFilters() {
				const selMovie = filterMovie ? filterMovie.value.toLowerCase().trim() : "";
				const selTime  = filterTime ? filterTime.value.toLowerCase().trim() : "";
				const selDate  = filterDate ? filterDate.value.trim() : "";
				const rows     = document.querySelectorAll("#nmbpBookingsTable tbody tr.nmbp-booking-row");
				let visibleCount = 0;

				rows.forEach(function(row) {
					const rMovie = (row.getAttribute("data-movie") || "").toLowerCase().trim();
					const rTime  = (row.getAttribute("data-time") || "").toLowerCase().trim();
					const rDate  = (row.getAttribute("data-date") || "").trim();

					const matchMovie = !selMovie || rMovie.indexOf(selMovie) !== -1;
					const matchTime  = !selTime || rTime === selTime;
					const matchDate  = !selDate || rDate === selDate;

					if (matchMovie && matchTime && matchDate) {
						row.classList.remove("nmbp-booking-row--hidden");
						visibleCount++;
					} else {
						row.classList.add("nmbp-booking-row--hidden");
					}
				});

				if (emptyFilter) {
					if (visibleCount === 0 && rows.length > 0) {
						emptyFilter.classList.add("nmbp-filter-empty-row--visible");
					} else {
						emptyFilter.classList.remove("nmbp-filter-empty-row--visible");
					}
				}

				if (counterEl) {
					counterEl.textContent = visibleCount;
				}
			}

			if (filterMovie) filterMovie.addEventListener("change", applyBookingFilters);
			if (filterTime)  filterTime.addEventListener("change", applyBookingFilters);
			if (filterDate)  filterDate.addEventListener("change", applyBookingFilters);

			if (btnShowAll) {
				btnShowAll.addEventListener("click", function() {
					if (filterMovie) filterMovie.value = "";
					if (filterTime)  filterTime.value = "";
					if (filterDate)  filterDate.value = "";
					applyBookingFilters();
				});
			}

			if (btnToday) {
				btnToday.addEventListener("click", function() {
					if (filterMovie) filterMovie.value = "";
					if (filterTime)  filterTime.value = "";
					if (filterDate)  filterDate.value = todayStr;
					applyBookingFilters();
				});
			}

			document.querySelectorAll(".nmbp-delete-booking-link").forEach(function(link) {
				link.addEventListener("click", function(e) {
					if (!window.confirm(confirmDelete)) {
						e.preventDefault();
					}
				});
			});

			// Run initial filter on page load (defaults to today).
			applyBookingFilters();
		});
	})();
</script>