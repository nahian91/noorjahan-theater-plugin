<?php
/**
 * Dashboard Module - Master Controller
 *
 * Path: admin/page-dashboard.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Cinema Master Dashboard.
 */
function nmbp_render_dashboard_page() {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized user action.', 'nahian-movie-booking-plugin' ), 403 );
	}

	global $wpdb;
	$table_bookings = $wpdb->prefix . 'nmbp_bookings';
	$table_movies   = $wpdb->prefix . 'nmbp_control_movies';
	$table_ledger   = $wpdb->prefix . 'nmbp_ledger';

	$today_start = current_time( 'Y-m-d 00:00:00' );
	$today_end   = current_time( 'Y-m-d 23:59:59' );

	$std_base_price  = (float) get_option( 'nmbp_standard_price', 400 );
	$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );

	// =========================================================================
	// 1. CONSOLIDATED LEDGER METRICS (Single Query)
	// =========================================================================
	$cash_like   = '%' . $wpdb->esc_like( 'Cash' ) . '%';
	$bkash_like  = '%' . $wpdb->esc_like( 'bKash' ) . '%';
	$online_like = '%' . $wpdb->esc_like( 'Online' ) . '%';
	$portal_like = '%' . $wpdb->esc_like( 'Portal' ) . '%';

	$ledger_stats = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT 
                COALESCE(SUM(amount), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN (method = 'Cash' OR method LIKE %s) THEN amount ELSE 0 END), 0) AS cash_revenue,
                COALESCE(SUM(CASE WHEN (method = 'bKash' OR method LIKE %s OR method LIKE %s OR method LIKE %s) THEN amount ELSE 0 END), 0) AS bkash_revenue
            FROM {$table_ledger}
            WHERE txn_type = 'Income' 
              AND transaction_date >= %s 
              AND transaction_date <= %s",
			$cash_like,
			$bkash_like,
			$online_like,
			$portal_like,
			$today_start,
			$today_end
		)
	);

	$today_revenue       = (float) ( $ledger_stats->total_revenue ?? 0.00 );
	$today_cash_revenue  = (float) ( $ledger_stats->cash_revenue ?? 0.00 );
	$today_bkash_revenue = (float) ( $ledger_stats->bkash_revenue ?? 0.00 );

	// =========================================================================
	// 2. UNIFIED TODAY'S BOOKINGS (Single Query for Table, Metrics & JS Matrix)
	// =========================================================================
	$today_bookings = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, booking_token, customer_name, customer_phone, movie_title, auditorium, showtime, seats, payment_method, payment_status, total_amount, booking_date 
             FROM {$table_bookings} 
             WHERE booking_date >= %s AND booking_date <= %s 
             ORDER BY id DESC",
			$today_start,
			$today_end
		)
	);

	if ( ! is_array( $today_bookings ) ) {
		$today_bookings = array();
	}

	$today_bookings_count  = count( $today_bookings );
	$today_tickets_sold    = 0;
	$today_bookings_for_js = array();

	foreach ( $today_bookings as $b ) {
		if ( ! empty( $b->seats ) ) {
			$seat_parts         = explode( ',', $b->seats );
			$clean_seats        = array_filter( array_map( 'trim', $seat_parts ) );
			$today_tickets_sold += count( $clean_seats );
		}

		$today_bookings_for_js[] = array(
			'movie_title'    => $b->movie_title,
			'showtime'       => $b->showtime,
			'seats'          => $b->seats,
			'customer_name'  => $b->customer_name,
			'customer_phone' => $b->customer_phone,
			'booking_token'  => $b->booking_token,
			'payment_method' => $b->payment_method,
			'payment_status' => $b->payment_status,
			'total_amount'   => (float) $b->total_amount,
			'booking_date'   => $b->booking_date,
		);
	}

	// =========================================================================
	// 3. MOVIE CATALOG & SHOWTIMES
	// =========================================================================
	$movies_raw  = $wpdb->get_results( "SELECT id, title, showtimes, badge_tag FROM {$table_movies} ORDER BY id DESC" );
	$movies_list = array();

	if ( ! empty( $movies_raw ) && is_array( $movies_raw ) ) {
		foreach ( $movies_raw as $m ) {
			$parsed_showtimes = array();
			$decoded          = json_decode( $m->showtimes, true );

			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $day_entry ) {
					if ( is_array( $day_entry ) && ! empty( $day_entry['times'] ) && is_array( $day_entry['times'] ) ) {
						foreach ( $day_entry['times'] as $t ) {
							$parsed_showtimes[] = sanitize_text_field( $t );
						}
					} elseif ( is_string( $day_entry ) ) {
						$parsed_showtimes[] = sanitize_text_field( $day_entry );
					}
				}
			}

			if ( empty( $parsed_showtimes ) ) {
				$parsed_showtimes = array( '18:00' );
			}

			$m->showtimes_json = wp_json_encode( array_values( array_unique( $parsed_showtimes ) ) );
			$movies_list[]     = $m;
		}
	}

	$current_user = wp_get_current_user();
	$user_name    = $current_user->exists() ? $current_user->display_name : __( 'Master Administrator', 'nahian-movie-booking-plugin' );
	$avatar_url   = get_avatar_url( $current_user->ID, array( 'size' => 140 ) );

	$hour = (int) current_time( 'G' );
	if ( $hour < 12 ) {
		$greeting = __( 'Good Morning', 'nahian-movie-booking-plugin' );
	} elseif ( $hour < 17 ) {
		$greeting = __( 'Good Afternoon', 'nahian-movie-booking-plugin' );
	} else {
		$greeting = __( 'Good Evening', 'nahian-movie-booking-plugin' );
	}

	if ( function_exists( 'nmbp_render_page_shell_open' ) ) {
		nmbp_render_page_shell_open( 'nmbp-dashboard' );
	}
	?>
	<style id="nmbp-dashboard-page-css">
		.nmbp-hero-pill {
			font-size: 0.7rem;
			letter-spacing: 0.06em;
		}
		.nmbp-hero-status-online {
			color: #34d399;
			font-weight: 800;
		}
		.nmbp-kpi-value--revenue {
			color: #059669;
		}
		.nmbp-kpi-value--tickets {
			color: #0284c7;
		}
		.nmbp-kpi-value--orders {
			color: #d97706;
		}
		.nmbp-kpi-value--split {
			font-size: 1.15rem;
			margin-top: 0.35rem;
		}
		.nmbp-kpi-unit-muted {
			font-size: 0.85rem;
			color: var(--nmbp-text-muted, #64748b);
			font-weight: 700;
		}
		.nmbp-split-cash {
			color: #059669;
		}
		.nmbp-split-sep {
			color: var(--nmbp-text-muted, #64748b);
			font-size: 0.85rem;
		}
		.nmbp-split-online {
			color: #d946ef;
		}
		.nmbp-kpi-icon--revenue {
			background: #ecfdf5;
			color: #059669;
		}
		.nmbp-kpi-icon--tickets {
			background: #f0f9ff;
			color: #0284c7;
		}
		.nmbp-kpi-icon--orders {
			background: #fef3c7;
			color: #d97706;
		}
		.nmbp-kpi-icon--split {
			background: #fdf2f8;
			color: #db2777;
		}
		.nmbp-panel-title--spaced {
			margin-bottom: 1rem;
		}
		.nmbp-panel-title--telemetry {
			margin-bottom: 1.25rem;
		}
		.nmbp-form-group--no-margin {
			margin-bottom: 0;
		}
		.nmbp-monitor-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 1rem;
		}
		.nmbp-monitor-pill {
			font-size: 0.68rem;
		}
		.nmbp-monitor-heading {
			font-size: 1.15rem;
			font-weight: 900;
			margin: 0.35rem 0 0 0;
			color: #ffffff;
		}
		.nmbp-booked-counter {
			font-family: var(--nmbp-font-mono, monospace);
			font-size: 0.88rem;
			color: #ef4444;
			font-weight: 800;
			background: rgba(239, 68, 68, 0.15);
			padding: 0.25rem 0.65rem;
			border-radius: 6px;
			border: 1px solid rgba(239, 68, 68, 0.3);
		}
		.nmbp-col-label-top {
			color: #94a3b8;
			font-weight: 900;
		}
		.nmbp-col-label-bottom {
			color: #94a3b8;
			font-weight: 900;
			margin-top: 8px;
		}
		.nmbp-screen-arrow {
			margin-top: 8px;
		}
		.nmbp-legend-dot--available {
			background: #0d9488;
		}
		.nmbp-legend-dot--booked {
			background: #ef4444;
		}
		.nmbp-occupancy-meta {
			display: flex;
			justify-content: space-between;
			font-size: 0.82rem;
			font-weight: 800;
			margin-bottom: 0.35rem;
		}
		.nmbp-occupancy-label {
			color: var(--nmbp-text-muted, #64748b);
		}
		.nmbp-occupancy-percent {
			color: var(--nmbp-accent-teal, #0d9488);
		}
		.nmbp-progress-bar-fill--zero {
			width: 0%;
		}
		.nmbp-roster-label {
			font-weight: 800;
			font-size: 0.75rem;
			text-transform: uppercase;
			color: var(--nmbp-text-muted, #64748b);
			display: block;
			margin-bottom: 0.35rem;
		}
		.nmbp-roster-empty {
			font-size: 0.82rem;
			color: var(--nmbp-text-muted, #64748b);
		}
		.nmbp-metric-cell-muted {
			color: var(--nmbp-text-muted, #64748b);
		}
		.nmbp-metric-cell-booked {
			text-align: right;
			font-weight: 800;
			color: #ef4444;
		}
		.nmbp-metric-cell-free {
			text-align: right;
			font-weight: 800;
			color: #10b981;
		}
		.nmbp-metric-cell-main {
			color: var(--nmbp-text-main, #0f172a);
		}
		.nmbp-metric-cell-revenue {
			text-align: right;
			color: var(--nmbp-accent-teal, #0d9488);
			font-family: var(--nmbp-font-mono, monospace);
		}
		.nmbp-btn-counter-booking {
			background: #064e3b;
			border-color: #064e3b;
			width: 100%;
			text-align: center;
			padding: 11px;
			font-weight: 900;
			font-size: 0.95rem;
			text-decoration: none;
			display: block;
			border-radius: 8px;
			box-shadow: 0 4px 14px rgba(6, 78, 59, 0.25);
			box-sizing: border-box;
		}
		.nmbp-bookings-panel {
			margin: 0;
			background: #ffffff;
			border: 1px solid var(--nmbp-border, #e2e8f0);
			border-radius: var(--nmbp-radius-md, 8px);
			box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
		}
		.nmbp-bookings-heading {
			margin: 0 0 4px 0;
		}
		.nmbp-bookings-subtext {
			font-size: 0.78rem;
			color: var(--nmbp-text-muted, #64748b);
		}
		.nmbp-btn-archives {
			font-weight: 700;
		}
		.nmbp-table-wrap--scroll {
			overflow-x: auto;
		}
		.nmbp-subline-muted {
			color: var(--nmbp-text-muted, #64748b);
		}
		.nmbp-showtime-badge {
			font-size: 0.68rem;
		}
		.nmbp-amount-text {
			color: var(--nmbp-accent-teal, #0d9488);
			font-family: var(--nmbp-font-mono, monospace);
		}
		.nmbp-empty-bookings-cell {
			text-align: center;
			padding: 2.5rem;
			color: var(--nmbp-text-muted, #64748b);
		}
	</style>

	<header class="nmbp-hero-glass-card">
		<div class="nmbp-hero-profile-unit">
			<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php esc_attr_e( 'Admin Avatar', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-hero-avatar-ring">
			<div class="nmbp-hero-headings">
				<span class="nmbp-badge-pill active nmbp-hero-pill">
					⚡ <?php esc_html_e( 'LIVE MULTIPLEX CONTROLLER', 'nahian-movie-booking-plugin' ); ?>
				</span>
				<h1><?php echo esc_html( sprintf( '%s, %s', $greeting, $user_name ) ); ?></h1>
				<div class="nmbp-hero-tags-row">
					<span><?php esc_html_e( "Today's Passes:", 'nahian-movie-booking-plugin' ); ?> <strong><?php echo esc_html( (string) $today_bookings_count ); ?></strong></span>
					<span>•</span>
					<span><?php esc_html_e( 'Tickets Issued:', 'nahian-movie-booking-plugin' ); ?> <strong><?php echo esc_html( (string) $today_tickets_sold ); ?></strong></span>
					<span>•</span>
					<span class="nmbp-hero-status-online">● <?php esc_html_e( 'BOX OFFICE ONLINE', 'nahian-movie-booking-plugin' ); ?></span>
				</div>
			</div>
		</div>

		<div class="nmbp-live-clock-deck">
			<div class="date-label"><?php echo esc_html( wp_date( 'l, jS F Y' ) ); ?></div>
			<div class="time-readout" id="nmbpLiveClockMaster"><?php echo esc_html( wp_date( 'H:i:s' ) ); ?></div>
		</div>
	</header>

	<div class="nmbp-kpi-deck-4">
		<div class="nmbp-kpi-tile">
			<div>
				<div class="nmbp-kpi-title"><?php esc_html_e( "Today's Revenue", 'nahian-movie-booking-plugin' ); ?></div>
				<div class="nmbp-kpi-value nmbp-kpi-value--revenue">
					<?php echo esc_html( $currency_symbol . ' ' . number_format( $today_revenue, 2 ) ); ?>
				</div>
			</div>
			<div class="nmbp-kpi-icon-bubble nmbp-kpi-icon--revenue"><?php echo esc_html( $currency_symbol ); ?></div>
		</div>

		<div class="nmbp-kpi-tile">
			<div>
				<div class="nmbp-kpi-title"><?php esc_html_e( 'Tickets Issued', 'nahian-movie-booking-plugin' ); ?></div>
				<div class="nmbp-kpi-value nmbp-kpi-value--tickets">
					<?php echo esc_html( number_format( $today_tickets_sold ) ); ?> <span class="nmbp-kpi-unit-muted"><?php esc_html_e( 'Seats', 'nahian-movie-booking-plugin' ); ?></span>
				</div>
			</div>
			<div class="nmbp-kpi-icon-bubble nmbp-kpi-icon--tickets">🎟️</div>
		</div>

		<div class="nmbp-kpi-tile">
			<div>
				<div class="nmbp-kpi-title"><?php esc_html_e( 'Total Orders', 'nahian-movie-booking-plugin' ); ?></div>
				<div class="nmbp-kpi-value nmbp-kpi-value--orders">
					<?php echo esc_html( number_format( $today_bookings_count ) ); ?> <span class="nmbp-kpi-unit-muted"><?php esc_html_e( 'Passes', 'nahian-movie-booking-plugin' ); ?></span>
				</div>
			</div>
			<div class="nmbp-kpi-icon-bubble nmbp-kpi-icon--orders">🧾</div>
		</div>

		<div class="nmbp-kpi-tile">
			<div>
				<div class="nmbp-kpi-title"><?php esc_html_e( 'Counter / Online Split', 'nahian-movie-booking-plugin' ); ?></div>
				<div class="nmbp-kpi-value nmbp-kpi-value--split">
					<span class="nmbp-split-cash"><?php echo esc_html( $currency_symbol . number_format( $today_cash_revenue, 0 ) ); ?></span>
					<span class="nmbp-split-sep">/</span>
					<span class="nmbp-split-online"><?php echo esc_html( $currency_symbol . number_format( $today_bkash_revenue, 0 ) ); ?></span>
				</div>
			</div>
			<div class="nmbp-kpi-icon-bubble nmbp-kpi-icon--split">💳</div>
		</div>
	</div>

	<div class="nmbp-theater-arena-grid">
		<div>
			<div class="nmbp-screen-filter-bar">
				<h3 class="nmbp-panel-title nmbp-panel-title--spaced">🎬 <?php esc_html_e( 'Select Active Screening Slot', 'nahian-movie-booking-plugin' ); ?></h3>
				<div class="nmbp-form-grid-2">
					<div class="nmbp-form-group nmbp-form-group--no-margin">
						<label for="nmbpMonitorMovieSelect"><?php esc_html_e( 'Feature Movie Presentation', 'nahian-movie-booking-plugin' ); ?></label>
						<select id="nmbpMonitorMovieSelect" required>
							<?php
							if ( ! empty( $movies_list ) ) :
								foreach ( $movies_list as $m ) :
									?>
								<option value="<?php echo esc_attr( $m->title ); ?>" data-showtimes="<?php echo esc_attr( $m->showtimes_json ); ?>">
									<?php echo esc_html( $m->title ); ?>
								</option>
									<?php
								endforeach;
							else :
								?>
								<option value="Feature Film" data-showtimes='["14:30","18:00","21:30"]'><?php esc_html_e( 'Standard Schedule', 'nahian-movie-booking-plugin' ); ?></option>
							<?php endif; ?>
						</select>
					</div>

					<div class="nmbp-form-group nmbp-form-group--no-margin">
						<label for="nmbpMonitorShowtimeSelect"><?php esc_html_e( 'Screening Time Slot', 'nahian-movie-booking-plugin' ); ?></label>
						<select id="nmbpMonitorShowtimeSelect" required>
							<option value="18:00">06:00 PM</option>
						</select>
					</div>
				</div>
			</div>

			<div class="nmbp-monitor-cinema-box">
				<div class="nmbp-monitor-header">
					<div>
						<span class="nmbp-badge-pill active nmbp-monitor-pill"><?php esc_html_e( 'AUDITORIUM 30-SEAT MATRIX', 'nahian-movie-booking-plugin' ); ?></span>
						<h4 class="nmbp-monitor-heading"><?php esc_html_e( 'Live Auditorium Monitor', 'nahian-movie-booking-plugin' ); ?></h4>
					</div>
					<span id="nmbpSelectedSeatsCounter" class="nmbp-booked-counter">
						0 <?php esc_html_e( 'Seats Booked', 'nahian-movie-booking-plugin' ); ?>
					</span>
				</div>

				<div class="nmbp-monitor-matrix-canvas">
					<?php
					$matrix_rows = array( 'F', 'E', 'D', 'C', 'B', 'A' );

					foreach ( $matrix_rows as $row_letter ) :
						?>
						<div class="nmbp-seat-matrix-col">
							<div class="col-label nmbp-col-label-top"><?php echo esc_html( $row_letter ); ?></div>
							<?php
							for ( $seat_num = 1; $seat_num <= 5; $seat_num++ ) :
								$s_id = $row_letter . $seat_num;
								?>
								<div class="nmbp-matrix-seat standard" id="nmbp-matrix-seat-<?php echo esc_attr( $s_id ); ?>">
									<?php echo esc_html( $s_id ); ?>
								</div>
							<?php endfor; ?>
							<div class="col-label nmbp-col-label-bottom"><?php echo esc_html( $row_letter ); ?></div>
						</div>
					<?php endforeach; ?>

					<div class="nmbp-matrix-screen-beacon">
						<span>S</span><span>C</span><span>R</span><span>E</span><span>E</span><span>N</span>
						<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" class="nmbp-screen-arrow" aria-hidden="true">
							<path d="M5 12h14M13 5l7 7-7 7"/>
						</svg>
					</div>
				</div>

				<div class="nmbp-matrix-legend-row">
					<div class="nmbp-legend-pill">
						<div class="dot nmbp-legend-dot--available"></div>
						<span><?php esc_html_e( 'Available', 'nahian-movie-booking-plugin' ); ?> (<?php echo esc_html( $currency_symbol . ' ' . number_format( $std_base_price, 0 ) ); ?>)</span>
					</div>
					<div class="nmbp-legend-pill">
						<div class="dot nmbp-legend-dot--booked"></div>
						<span><?php esc_html_e( 'Booked / Reserved for Slot', 'nahian-movie-booking-plugin' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<div>
			<div class="nmbp-telemetry-panel">
				<h3 class="nmbp-panel-title nmbp-panel-title--telemetry">📊 <?php esc_html_e( 'Slot Telemetry', 'nahian-movie-booking-plugin' ); ?></h3>

				<div class="nmbp-occupancy-meta">
					<span class="nmbp-occupancy-label"><?php esc_html_e( 'Occupancy Percentage', 'nahian-movie-booking-plugin' ); ?></span>
					<span class="nmbp-occupancy-percent" id="nmbpLblOccupancyPercent">0%</span>
				</div>
				<div class="nmbp-progress-bar-wrapper">
					<div class="nmbp-progress-bar-fill nmbp-progress-bar-fill--zero" id="nmbpOccupancyProgressBar"></div>
				</div>

				<label class="nmbp-roster-label">
					<?php esc_html_e( 'Reserved Seats Roster', 'nahian-movie-booking-plugin' ); ?>
				</label>
				<div class="nmbp-chips-roster-box" id="nmbpSelectedChipsDeck">
					<span class="nmbp-roster-empty"><?php esc_html_e( 'No seats booked for this slot yet.', 'nahian-movie-booking-plugin' ); ?></span>
				</div>

				<table class="nmbp-slot-metric-table">
					<tbody>
						<tr>
							<td class="nmbp-metric-cell-muted"><?php esc_html_e( 'Slot Booked Seats', 'nahian-movie-booking-plugin' ); ?></td>
							<td class="nmbp-metric-cell-booked" id="nmbpLblSlotBooked">0</td>
						</tr>
						<tr>
							<td class="nmbp-metric-cell-muted"><?php esc_html_e( 'Available Seats', 'nahian-movie-booking-plugin' ); ?></td>
							<td class="nmbp-metric-cell-free" id="nmbpLblSlotFree">30</td>
						</tr>
						<tr class="total-row">
							<td class="nmbp-metric-cell-main"><?php esc_html_e( 'Slot Gross Revenue', 'nahian-movie-booking-plugin' ); ?></td>
							<td class="nmbp-metric-cell-revenue" id="nmbpLblSlotRevenue">
								<?php echo esc_html( $currency_symbol ); ?> 0.00
							</td>
						</tr>
					</tbody>
				</table>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-booking&subtab=add' ) ); ?>" class="button button-primary nmbp-btn-counter-booking">
					🎟️ <?php esc_html_e( 'Manual Counter Booking', 'nahian-movie-booking-plugin' ); ?>
				</a>
			</div>
		</div>
	</div>

	<div class="nmbp-panel nmbp-bookings-panel">
		<div class="nmbp-panel-header">
			<div>
				<h3 class="nmbp-panel-title nmbp-bookings-heading">🎟️ <?php esc_html_e( "Today's Admission Passes & Orders", 'nahian-movie-booking-plugin' ); ?> (<?php echo esc_html( (string) $today_bookings_count ); ?>)</h3>
				<span class="nmbp-bookings-subtext">
					<?php
					/* translators: %s: formatted localized current date */
					printf( esc_html__( 'Showing transactions logged on %s', 'nahian-movie-booking-plugin' ), esc_html( wp_date( 'l, jS F Y' ) ) );
					?>
				</span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-booking&subtab=all' ) ); ?>" class="button button-secondary nmbp-btn-archives">
				<?php esc_html_e( 'View Complete Booking Archives', 'nahian-movie-booking-plugin' ); ?> →
			</a>
		</div>

		<div class="nmbp-table-wrap nmbp-table-wrap--scroll">
			<table class="nmbp-data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'TOKEN', 'nahian-movie-booking-plugin' ); ?></th>
						<th><?php esc_html_e( 'CUSTOMER', 'nahian-movie-booking-plugin' ); ?></th>
						<th><?php esc_html_e( 'MOVIE', 'nahian-movie-booking-plugin' ); ?></th>
						<th><?php esc_html_e( 'SHOWTIME', 'nahian-movie-booking-plugin' ); ?></th>
						<th><?php esc_html_e( 'SEATS', 'nahian-movie-booking-plugin' ); ?></th>
						<th><?php esc_html_e( 'METHOD', 'nahian-movie-booking-plugin' ); ?></th>
						<th><?php esc_html_e( 'TOTAL AMOUNT', 'nahian-movie-booking-plugin' ); ?></th>
						<th><?php esc_html_e( 'TIME', 'nahian-movie-booking-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( ! empty( $today_bookings ) ) :
						foreach ( $today_bookings as $b ) :
							?>
						<tr>
							<td><span class="nmbp-voucher-tag"><?php echo esc_html( $b->booking_token ); ?></span></td>
							<td>
								<strong><?php echo esc_html( ! empty( $b->customer_name ) ? $b->customer_name : __( 'Walk-In Guest', 'nahian-movie-booking-plugin' ) ); ?></strong><br>
								<small class="nmbp-subline-muted"><?php echo esc_html( ! empty( $b->customer_phone ) ? $b->customer_phone : __( 'No phone', 'nahian-movie-booking-plugin' ) ); ?></small>
							</td>
							<td>
								<strong><?php echo esc_html( $b->movie_title ); ?></strong>
							</td>
							<td><span class="nmbp-badge-pill active nmbp-showtime-badge"><?php echo esc_html( ! empty( $b->showtime ) ? $b->showtime : __( 'Counter', 'nahian-movie-booking-plugin' ) ); ?></span></td>
							<td><code><?php echo esc_html( $b->seats ); ?></code></td>
							<td><span class="nmbp-badge-pill"><?php echo esc_html( ! empty( $b->payment_method ) ? $b->payment_method : 'Cash' ); ?></span></td>
							<td><strong class="nmbp-amount-text"><?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $b->total_amount, 2 ) ); ?></strong></td>
							<td><small class="nmbp-subline-muted"><?php echo esc_html( wp_date( 'h:i A', strtotime( $b->booking_date ) ) ); ?></small></td>
						</tr>
							<?php
						endforeach;
					else :
						?>
						<tr>
							<td colspan="8" class="nmbp-empty-bookings-cell">
								<?php esc_html_e( 'No ticket bookings recorded yet for today.', 'nahian-movie-booking-plugin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<script id="nmbp-dashboard-runtime-js">
		(function() {
			const nmbpTodayBookings = <?php echo wp_json_encode( $today_bookings_for_js ); ?>;
			const NMBP_TOTAL_CAPACITY = 30;
			const NMBP_CURRENCY_TAG   = <?php echo wp_json_encode( $currency_symbol ); ?>;
			const NMBP_EMPTY_ROSTER   = <?php echo wp_json_encode( __( 'No seats booked for this slot yet.', 'nahian-movie-booking-plugin' ) ); ?>;
			const nmbpAllSeatIds = [
				'A1', 'A2', 'A3', 'A4', 'A5',
				'B1', 'B2', 'B3', 'B4', 'B5',
				'C1', 'C2', 'C3', 'C4', 'C5',
				'D1', 'D2', 'D3', 'D4', 'D5',
				'E1', 'E2', 'E3', 'E4', 'E5',
				'F1', 'F2', 'F3', 'F4', 'F5'
			];

			function nmbpUpdateLiveClockMaster() {
				const now = new Date();
				const hrs = String(now.getHours()).padStart(2, '0');
				const mins = String(now.getMinutes()).padStart(2, '0');
				const secs = String(now.getSeconds()).padStart(2, '0');
				const el = document.getElementById('nmbpLiveClockMaster');
				if (el) el.textContent = hrs + ':' + mins + ':' + secs;
			}
			setInterval(nmbpUpdateLiveClockMaster, 1000);
			nmbpUpdateLiveClockMaster();

			function nmbpFormatSlotTime(timeStr) {
				if (!timeStr) return '06:00 PM';
				if (timeStr.indexOf(':') !== -1 && timeStr.length <= 5) {
					const parts = timeStr.split(':');
					const hour = parseInt(parts[0], 10);
					const ampm = hour >= 12 ? 'PM' : 'AM';
					const formattedHour = hour % 12 || 12;
					return String(formattedHour).padStart(2, '0') + ':' + parts[1] + ' ' + ampm;
				}
				return timeStr;
			}

			function nmbpOnMonitorMovieChange(selectEl) {
				if (!selectEl) return;
				const opt = selectEl.options[selectEl.selectedIndex];
				if (!opt) return;

				const showtimesRaw = opt.getAttribute('data-showtimes') || '["18:00"]';
				const showtimeSelect = document.getElementById('nmbpMonitorShowtimeSelect');
				if (!showtimeSelect) return;
				showtimeSelect.innerHTML = '';

				let slots = [];
				try {
					const parsed = JSON.parse(showtimesRaw);
					if (Array.isArray(parsed)) slots = parsed;
					else slots = [showtimesRaw];
				} catch (e) {
					slots = showtimesRaw.split(',');
				}

				slots.forEach(function(slot) {
					const trimmed = String(slot).trim();
					if (trimmed) {
						const o = document.createElement('option');
						o.value = trimmed;
						o.textContent = nmbpFormatSlotTime(trimmed);
						showtimeSelect.appendChild(o);
					}
				});

				nmbpRenderLiveMatrix();
			}

			function nmbpRenderLiveMatrix() {
				const movieSelect = document.getElementById('nmbpMonitorMovieSelect');
				const showtimeSelect = document.getElementById('nmbpMonitorShowtimeSelect');

				const selectedMovie = movieSelect ? movieSelect.value.trim().toLowerCase() : '';
				const selectedTime = showtimeSelect ? showtimeSelect.value.trim() : '';

				const occupiedSeats = [];
				let slotRevenue = 0;

				nmbpTodayBookings.forEach(function(b) {
					const bMovie = (b.movie_title || '').trim().toLowerCase();
					const bTime = (b.showtime || '').trim();

					if (bMovie === selectedMovie && bTime === selectedTime) {
						slotRevenue += parseFloat(b.total_amount || 0);
						if (b.seats) {
							const parts = b.seats.split(',');
							parts.forEach(function(p) {
								const trimmed = p.trim().toUpperCase();
								if (trimmed && occupiedSeats.indexOf(trimmed) === -1) {
									occupiedSeats.push(trimmed);
								}
							});
						}
					}
				});

				nmbpAllSeatIds.forEach(function(seatId) {
					const seatEl = document.getElementById('nmbp-matrix-seat-' + seatId);
					if (!seatEl) return;

					if (occupiedSeats.indexOf(seatId) !== -1) {
						seatEl.className = 'nmbp-matrix-seat occupied';
						seatEl.title = 'Seat ' + seatId + ' [OCCUPIED FOR SLOT]';
					} else {
						seatEl.className = 'nmbp-matrix-seat standard';
						seatEl.title = 'Seat ' + seatId + ' [AVAILABLE]';
					}
				});

				const bookedCount = occupiedSeats.length;
				const freeCount   = Math.max(0, NMBP_TOTAL_CAPACITY - bookedCount);
				const percent     = Math.round((bookedCount / NMBP_TOTAL_CAPACITY) * 100);

				const counter       = document.getElementById('nmbpSelectedSeatsCounter');
				const chipsDeck     = document.getElementById('nmbpSelectedChipsDeck');
				const elBooked      = document.getElementById('nmbpLblSlotBooked');
				const elFree        = document.getElementById('nmbpLblSlotFree');
				const elPct         = document.getElementById('nmbpLblOccupancyPercent');
				const elBar         = document.getElementById('nmbpOccupancyProgressBar');
				const elSlotRevenue = document.getElementById('nmbpLblSlotRevenue');

				if (counter) counter.textContent = bookedCount + ' Seats Booked';
				if (elBooked) elBooked.textContent = bookedCount;
				if (elFree) elFree.textContent = freeCount;
				if (elPct) elPct.textContent = percent + '%';
				if (elBar) elBar.style.width = percent + '%';
				if (elSlotRevenue) elSlotRevenue.textContent = NMBP_CURRENCY_TAG + ' ' + slotRevenue.toFixed(2);

				if (chipsDeck) {
					chipsDeck.innerHTML = '';
					if (occupiedSeats.length === 0) {
						const emptySpan = document.createElement('span');
						emptySpan.className = 'nmbp-roster-empty';
						emptySpan.textContent = NMBP_EMPTY_ROSTER;
						chipsDeck.appendChild(emptySpan);
					} else {
						occupiedSeats.forEach(function(s) {
							const badge = document.createElement('span');
							badge.className = 'nmbp-roster-badge';
							badge.textContent = s;
							chipsDeck.appendChild(badge);
						});
					}
				}
			}

			document.addEventListener('DOMContentLoaded', function() {
				const movieSelect = document.getElementById('nmbpMonitorMovieSelect');
				const showtimeSelect = document.getElementById('nmbpMonitorShowtimeSelect');

				if (movieSelect) {
					movieSelect.addEventListener('change', function() {
						nmbpOnMonitorMovieChange(this);
					});
					nmbpOnMonitorMovieChange(movieSelect);
				}

				if (showtimeSelect) {
					showtimeSelect.addEventListener('change', nmbpRenderLiveMatrix);
				}

				nmbpRenderLiveMatrix();
			});
		})();
	</script>
	<?php
	if ( function_exists( 'nmbp_render_page_shell_close' ) ) {
		nmbp_render_page_shell_close();
	}
}