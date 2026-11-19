<?php
/**
 * Accounts Module - Comprehensive Financial & Box-Office Report View
 *
 * Path: admin/accounts/report-accounts.php
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
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'nmbp_bookings';
$table_movies   = $wpdb->prefix . 'nmbp_control_movies';
$table_ledger   = $wpdb->prefix . 'nmbp_ledger';

$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );

// Determine active sub-report tab ('ticket', 'lifetime', 'income', or 'expense').
$report_subtab = isset( $_GET['report_tab'] ) ? sanitize_key( wp_unslash( $_GET['report_tab'] ) ) : 'ticket';

// Custom Date Range Inputs from URL.
$start_date_val = isset( $_GET['report_start'] ) ? sanitize_text_field( wp_unslash( $_GET['report_start'] ) ) : '';
$end_date_val   = isset( $_GET['report_end'] ) ? sanitize_text_field( wp_unslash( $_GET['report_end'] ) ) : '';

// Build parameterized query constraints for bookings.
$booking_where_sql  = '1=1';
$booking_query_args = array();

if ( ! empty( $start_date_val ) && ! empty( $end_date_val ) ) {
	$booking_where_sql    = 'DATE(booking_date) BETWEEN %s AND %s';
	$booking_query_args[] = $start_date_val;
	$booking_query_args[] = $end_date_val;
} elseif ( ! empty( $start_date_val ) ) {
	$booking_where_sql    = 'DATE(booking_date) >= %s';
	$booking_query_args[] = $start_date_val;
} elseif ( ! empty( $end_date_val ) ) {
	$booking_where_sql    = 'DATE(booking_date) <= %s';
	$booking_query_args[] = $end_date_val;
}

// Build parameterized query constraints for ledger items.
$ledger_where_sql  = '1=1';
$ledger_query_args = array();

if ( ! empty( $start_date_val ) && ! empty( $end_date_val ) ) {
	$ledger_where_sql    = 'DATE(transaction_date) BETWEEN %s AND %s';
	$ledger_query_args[] = $start_date_val;
	$ledger_query_args[] = $end_date_val;
} elseif ( ! empty( $start_date_val ) ) {
	$ledger_where_sql    = 'DATE(transaction_date) >= %s';
	$ledger_query_args[] = $start_date_val;
} elseif ( ! empty( $end_date_val ) ) {
	$ledger_where_sql    = 'DATE(transaction_date) <= %s';
	$ledger_query_args[] = $end_date_val;
}

// Fetch filter options dynamically.
$filter_movies = $wpdb->get_col( "SELECT DISTINCT movie_title FROM {$table_bookings} WHERE movie_title != '' ORDER BY movie_title ASC" );
$filter_times  = $wpdb->get_col( "SELECT DISTINCT showtime FROM {$table_bookings} WHERE showtime != '' ORDER BY showtime ASC" );

if ( ! function_exists( 'nmbp_format_report_time' ) ) {
	/**
	 * Helper 12-Hour AM/PM Time Formatter
	 */
	function nmbp_format_report_time( $raw_time ) {
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

// Fetch detailed movie-based box-office breakdown for the ticket tab.
$ticket_breakdown_query = "
	SELECT 
		movie_title, 
		showtime,
		DATE(booking_date) as b_date,
		COUNT(id) as total_passes, 
		SUM(total_amount) as gross_revenue,
		SUM(CASE WHEN payment_method = 'Cash' THEN total_amount ELSE 0 END) as cash_revenue,
		SUM(CASE WHEN payment_method != 'Cash' THEN total_amount ELSE 0 END) as online_revenue,
		GROUP_CONCAT(seats SEPARATOR ', ') as all_booked_seats
	FROM {$table_bookings} 
	WHERE ({$booking_where_sql}) AND payment_status != 'Cancelled' 
	GROUP BY movie_title, showtime, DATE(booking_date) 
	ORDER BY gross_revenue DESC
";

if ( ! empty( $booking_query_args ) ) {
	$movie_income_breakdown = $wpdb->get_results( $wpdb->prepare( $ticket_breakdown_query, $booking_query_args ) );
} else {
	$movie_income_breakdown = $wpdb->get_results( $ticket_breakdown_query );
}

if ( ! is_array( $movie_income_breakdown ) ) {
	$movie_income_breakdown = array();
}

// Fetch lifetime movie progress statistics safely.
$lifetime_movies_stats = $wpdb->get_results( "
	SELECT 
		m.title as movie_title,
		m.poster_url,
		COALESCE(SUM(b.total_amount), 0) as lifetime_revenue,
		COUNT(b.id) as lifetime_passes
	FROM {$table_movies} m
	LEFT JOIN {$table_bookings} b ON m.title = b.movie_title AND b.payment_status != 'Cancelled'
	GROUP BY m.id, m.title, m.poster_url
	ORDER BY lifetime_revenue DESC
" );

if ( ! is_array( $lifetime_movies_stats ) ) {
	$lifetime_movies_stats = array();
}

// Fetch manual incomes for the Income tab.
$income_query = "SELECT * FROM {$table_ledger} WHERE ({$ledger_where_sql}) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') ORDER BY id DESC";
if ( ! empty( $ledger_query_args ) ) {
	$report_incomes = $wpdb->get_results( $wpdb->prepare( $income_query, $ledger_query_args ) );
} else {
	$report_incomes = $wpdb->get_results( $income_query );
}

if ( ! is_array( $report_incomes ) ) {
	$report_incomes = array();
}

// Fetch expenses for the Expense tab.
$expense_query = "SELECT * FROM {$table_ledger} WHERE ({$ledger_where_sql}) AND txn_type = 'Expense' ORDER BY id DESC";
if ( ! empty( $ledger_query_args ) ) {
	$report_expenses = $wpdb->get_results( $wpdb->prepare( $expense_query, $ledger_query_args ) );
} else {
	$report_expenses = $wpdb->get_results( $expense_query );
}

if ( ! is_array( $report_expenses ) ) {
	$report_expenses = array();
}

// Find max lifetime revenue for progress bar percentage calculation.
$max_lifetime_rev = 1.0;
foreach ( $lifetime_movies_stats as $stat ) {
	if ( (float) $stat->lifetime_revenue > $max_lifetime_rev ) {
		$max_lifetime_rev = (float) $stat->lifetime_revenue;
	}
}
?>

<div id="nmbpPrintArea">
	<!-- Internal Report Subtabs Navigation -->
	<div class="nmbp-subtabs-nav-wrapper" style="margin-bottom: 20px;">
		<nav class="nmbp-subtabs-nav" aria-label="<?php esc_attr_e( 'Report Subtabs Navigation', 'nahian-movie-booking-plugin' ); ?>">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-accounts&subtab=report&report_tab=ticket' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'ticket' === $report_subtab ) ? 'active' : ''; ?>">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
				<span><?php esc_html_e( 'Ticket', 'nahian-movie-booking-plugin' ); ?></span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-accounts&subtab=report&report_tab=lifetime' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'lifetime' === $report_subtab ) ? 'active' : ''; ?>">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
				<span><?php esc_html_e( 'Movie Performance', 'nahian-movie-booking-plugin' ); ?></span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-accounts&subtab=report&report_tab=income' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'income' === $report_subtab ) ? 'active' : ''; ?>">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
				<span><?php esc_html_e( 'Income', 'nahian-movie-booking-plugin' ); ?></span>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-accounts&subtab=report&report_tab=expense' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'expense' === $report_subtab ) ? 'active' : ''; ?>">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
				<span><?php esc_html_e( 'Expense', 'nahian-movie-booking-plugin' ); ?></span>
			</a>
		</nav>
	</div>

	<?php if ( 'ticket' === $report_subtab || 'income' === $report_subtab || 'expense' === $report_subtab ) : ?>
		<!-- Shared Date Range & Filter Bar -->
		<form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="nmbp-table-filter-toolbar" style="margin-bottom: 20px;">
			<input type="hidden" name="page" value="nmbp-accounts">
			<input type="hidden" name="subtab" value="report">
			<input type="hidden" name="report_tab" value="<?php echo esc_attr( $report_subtab ); ?>">

			<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
				<div style="display: flex; align-items: center; gap: 6px;">
					<span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--nmbp-text-muted, #64748b);"><?php esc_html_e( 'From:', 'nahian-movie-booking-plugin' ); ?></span>
					<input type="date" name="report_start" value="<?php echo esc_attr( $start_date_val ); ?>">
				</div>

				<div style="display: flex; align-items: center; gap: 6px;">
					<span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--nmbp-text-muted, #64748b);"><?php esc_html_e( 'To:', 'nahian-movie-booking-plugin' ); ?></span>
					<input type="date" name="report_end" value="<?php echo esc_attr( $end_date_val ); ?>">
				</div>

				<button type="submit" class="button button-small" style="font-weight: 800; background: #0f172a; color: #fff; border-color: #0f172a;"><?php esc_html_e( 'Apply Range', 'nahian-movie-booking-plugin' ); ?></button>
			</div>

			<?php if ( 'ticket' === $report_subtab ) : ?>
				<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-left: auto;">
					<div style="display: flex; align-items: center; gap: 6px;">
						<span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--nmbp-text-muted, #64748b);"><?php esc_html_e( 'Movie:', 'nahian-movie-booking-plugin' ); ?></span>
						<select id="reportFilterMovie">
							<option value=""><?php esc_html_e( '-- All Movies --', 'nahian-movie-booking-plugin' ); ?></option>
							<?php foreach ( $filter_movies as $fm ) : ?>
								<option value="<?php echo esc_attr( $fm ); ?>"><?php echo esc_html( $fm ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div style="display: flex; align-items: center; gap: 6px;">
						<span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--nmbp-text-muted, #64748b);"><?php esc_html_e( 'Showtime:', 'nahian-movie-booking-plugin' ); ?></span>
						<select id="reportFilterTime">
							<option value=""><?php esc_html_e( '-- All Times --', 'nahian-movie-booking-plugin' ); ?></option>
							<?php foreach ( $filter_times as $ft ) : ?>
								<option value="<?php echo esc_attr( $ft ); ?>"><?php echo esc_html( nmbp_format_report_time( $ft ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endif; ?>

			<div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-accounts&subtab=report&report_tab=' . $report_subtab ) ); ?>" class="button button-small" style="font-weight: 700;"><?php esc_html_e( 'Reset', 'nahian-movie-booking-plugin' ); ?></a>
			</div>
		</form>
	<?php endif; ?>

	<?php if ( 'ticket' === $report_subtab ) : ?>
		<!-- Ticket Tab Content -->
		<div class="nmbp-panel" style="margin: 0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
			<div class="nmbp-table-wrap" style="overflow-x: auto;">
				<table class="nmbp-data-table" id="nmbpTicketReportTable" style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="border-bottom: 1px solid var(--nmbp-border, #e2e8f0); background: #f8fafc;">
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'FEATURE MOVIE', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'SHOWTIME', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'SCREENING DATE', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: center;"><?php esc_html_e( 'PASSES', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: center;"><?php esc_html_e( 'SEATS', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'CASH', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'ONLINE / bKash', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: right;"><?php esc_html_e( 'GROSS REVENUE', 'nahian-movie-booking-plugin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( $movie_income_breakdown ) ) :
							foreach ( $movie_income_breakdown as $item ) :
								$seats_array  = array_filter( array_map( 'trim', explode( ',', (string) ( $item->all_booked_seats ?? '' ) ) ) );
								$ticket_count = count( $seats_array );
								$formatted_st = nmbp_format_report_time( $item->showtime ?? '' );
								?>
							<tr data-movie="<?php echo esc_attr( strtolower( (string) $item->movie_title ) ); ?>" data-time="<?php echo esc_attr( strtolower( trim( (string) $item->showtime ) ) ); ?>" style="border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);">
								<td style="padding: 12px;"><strong><?php echo esc_html( $item->movie_title ); ?></strong></td>
								<td style="padding: 12px;"><span class="nmbp-badge-pill active" style="font-size: 0.7rem;"><?php echo esc_html( $formatted_st ); ?></span></td>
								<td style="padding: 12px;"><small style="color: var(--nmbp-text-muted, #64748b); font-weight: 700;"><?php echo esc_html( $item->b_date ); ?></small></td>
								<td style="padding: 12px; text-align: center; font-weight: 700;"><?php echo esc_html( (string) $item->total_passes ); ?></td>
								<td style="padding: 12px; text-align: center;"><code style="font-family: var(--nmbp-font-mono, monospace);"><?php echo esc_html( (string) $ticket_count ); ?></code></td>
								<td style="padding: 12px;">
									<span style="color: #059669; font-weight: 700; font-family: var(--nmbp-font-mono, monospace);">
										<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $item->cash_revenue ?? 0.00 ), 2 ) ); ?>
									</span>
								</td>
								<td style="padding: 12px;">
									<span style="color: #d946ef; font-weight: 700; font-family: var(--nmbp-font-mono, monospace);">
										<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $item->online_revenue ?? 0.00 ), 2 ) ); ?>
									</span>
								</td>
								<td style="padding: 12px; text-align: right; font-weight: 900; color: #0d9488; font-family: var(--nmbp-font-mono, monospace); font-size: 1.05rem;">
									+<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $item->gross_revenue ?? 0.00 ), 2 ) ); ?>
								</td>
							</tr>
								<?php
							endforeach;
						else :
							?>
							<tr id="noReportRow">
								<td colspan="8" style="text-align: center; padding: 3rem; color: var(--nmbp-text-muted, #64748b);">
									<?php esc_html_e( 'No movie ticket income recorded for this date range.', 'nahian-movie-booking-plugin' ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

	<?php elseif ( 'lifetime' === $report_subtab ) : ?>
		<!-- Catalog Performance Tab -->
		<div class="nmbp-panel" style="margin: 0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
			<div class="nmbp-panel-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--nmbp-border, #e2e8f0);">
				<div>
					<h3 class="nmbp-panel-title" style="margin: 0; font-size: 1.15rem; font-weight: 900; color: var(--nmbp-text-main, #0f172a);">
						<?php esc_html_e( 'All Movies Lifetime Box-Office Performance', 'nahian-movie-booking-plugin' ); ?>
					</h3>
					<span style="font-size: 0.78rem; color: var(--nmbp-text-muted, #64748b);">
						<?php esc_html_e( 'Comprehensive historical revenue comparison across the entire movie catalog.', 'nahian-movie-booking-plugin' ); ?>
					</span>
				</div>
			</div>

			<div class="nmbp-table-wrap" style="overflow-x: auto;">
				<table class="nmbp-data-table" style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="border-bottom: 1px solid var(--nmbp-border, #e2e8f0); background: #f8fafc;">
							<th style="padding: 10px 12px; width: 60px; text-align: left;"><?php esc_html_e( 'POSTER', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'MOVIE TITLE', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: center;"><?php esc_html_e( 'LIFETIME PASSES', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; width: 30%; text-align: left;"><?php esc_html_e( 'LIFETIME REVENUE PROGRESS', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: right;"><?php esc_html_e( 'TOTAL GROSS EARNINGS', 'nahian-movie-booking-plugin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( $lifetime_movies_stats ) ) :
							foreach ( $lifetime_movies_stats as $stat ) :
								$rev = (float) ( $stat->lifetime_revenue ?? 0.00 );
								$pct = round( ( $rev / $max_lifetime_rev ) * 100, 1 );
								?>
							<tr style="border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);">
								<td style="padding: 12px;">
									<?php if ( ! empty( $stat->poster_url ) ) : ?>
										<img src="<?php echo esc_url( $stat->poster_url ); ?>" alt="<?php echo esc_attr( $stat->movie_title ); ?>" style="width: 40px; height: 55px; border-radius: 4px; object-fit: cover;">
									<?php else : ?>
										<div style="width: 40px; height: 55px; border-radius: 4px; background: #0f172a; color: #fff; font-size: 0.65rem; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: 800;">
											<?php esc_html_e( 'FILM', 'nahian-movie-booking-plugin' ); ?>
										</div>
									<?php endif; ?>
								</td>
								<td style="padding: 12px;"><strong><?php echo esc_html( $stat->movie_title ); ?></strong></td>
								<td style="padding: 12px; text-align: center;">
									<span class="nmbp-badge-pill active" style="font-size: 0.7rem;">
										<?php echo esc_html( (string) $stat->lifetime_passes ); ?> <?php esc_html_e( 'Passes', 'nahian-movie-booking-plugin' ); ?>
									</span>
								</td>
								<td style="padding: 12px;">
									<div style="display: flex; justify-content: space-between; font-size: 0.78rem; font-weight: 800; color: var(--nmbp-text-muted, #64748b); margin-bottom: 4px;">
										<span><?php echo esc_html( $currency_symbol . ' ' . number_format( $rev, 2 ) ); ?></span>
										<span><?php echo esc_html( (string) $pct ); ?>%</span>
									</div>
									<div class="nmbp-progress-bar-wrapper" style="margin-bottom: 0;">
										<div class="nmbp-progress-bar-fill" style="width: <?php echo esc_attr( (string) $pct ); ?>%;"></div>
									</div>
								</td>
								<td style="padding: 12px; text-align: right; font-weight: 900; color: #059669; font-family: var(--nmbp-font-mono, monospace); font-size: 1.1rem;">
									+<?php echo esc_html( $currency_symbol . ' ' . number_format( $rev, 2 ) ); ?>
								</td>
							</tr>
								<?php
							endforeach;
						else :
							?>
							<tr>
								<td colspan="5" style="text-align: center; padding: 3rem; color: var(--nmbp-text-muted, #64748b);">
									<?php esc_html_e( 'No movies found in catalog.', 'nahian-movie-booking-plugin' ); ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

	<?php elseif ( 'income' === $report_subtab ) : ?>
		<!-- Income Tab Report -->
		<div class="nmbp-panel" style="margin: 0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
			<div class="nmbp-panel-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--nmbp-border, #e2e8f0);">
				<h3 class="nmbp-panel-title" style="margin: 0; font-size: 1.15rem; font-weight: 900; color: var(--nmbp-text-main, #0f172a); display: flex; align-items: center; gap: 8px;">
					<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
					<?php esc_html_e( 'Direct Revenue & Concessions Report', 'nahian-movie-booking-plugin' ); ?>
				</h3>
			</div>
			<div class="nmbp-table-wrap" style="overflow-x: auto;">
				<table class="nmbp-data-table" style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="border-bottom: 1px solid var(--nmbp-border, #e2e8f0); background: #f8fafc;">
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'REF TOKEN', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'PARTICULARS', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'CHANNEL', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'DATE', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: right;"><?php esc_html_e( 'CREDIT AMOUNT', 'nahian-movie-booking-plugin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( $report_incomes ) ) :
							foreach ( $report_incomes as $inc ) :
								?>
							<tr style="border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);">
								<td style="padding: 12px;"><span class="nmbp-voucher-tag"><?php echo esc_html( $inc->reference_no ?? '' ); ?></span></td>
								<td style="padding: 12px;"><strong><?php echo esc_html( $inc->particulars ?? '' ); ?></strong></td>
								<td style="padding: 12px;"><span class="nmbp-badge-pill active"><?php echo esc_html( $inc->method ?? 'Cash' ); ?></span></td>
								<td style="padding: 12px;"><small style="color: var(--nmbp-text-muted, #64748b); font-weight: 700;"><?php echo esc_html( $inc->transaction_date ?? '' ); ?></small></td>
								<td style="padding: 12px; text-align: right; color: #059669; font-weight: 900; font-family: var(--nmbp-font-mono, monospace);">
									+<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $inc->amount ?? 0.00 ), 2 ) ); ?>
								</td>
							</tr>
								<?php
							endforeach;
						else :
							?>
							<tr><td colspan="5" style="text-align: center; padding: 3rem; color: var(--nmbp-text-muted, #64748b);"><?php esc_html_e( 'No manual income entries found for this period.', 'nahian-movie-booking-plugin' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

	<?php elseif ( 'expense' === $report_subtab ) : ?>
		<!-- Expense Tab Report -->
		<div class="nmbp-panel" style="margin: 0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
			<div class="nmbp-panel-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--nmbp-border, #e2e8f0);">
				<h3 class="nmbp-panel-title" style="margin: 0; font-size: 1.15rem; font-weight: 900; color: var(--nmbp-text-main, #0f172a); display: flex; align-items: center; gap: 8px;">
					<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
					<?php esc_html_e( 'Operating Expenses & Payroll Report', 'nahian-movie-booking-plugin' ); ?>
				</h3>
			</div>
			<div class="nmbp-table-wrap" style="overflow-x: auto;">
				<table class="nmbp-data-table" style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="border-bottom: 1px solid var(--nmbp-border, #e2e8f0); background: #f8fafc;">
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'REF TOKEN', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'PARTICULARS', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'CHANNEL', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'DATE', 'nahian-movie-booking-plugin' ); ?></th>
							<th style="padding: 10px 12px; text-align: right;"><?php esc_html_e( 'DEBIT AMOUNT', 'nahian-movie-booking-plugin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( $report_expenses ) ) :
							foreach ( $report_expenses as $exp ) :
								?>
							<tr style="border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);">
								<td style="padding: 12px;"><span class="nmbp-voucher-tag" style="background: #fef2f2; border-color: #fecaca; color: #dc2626;"><?php echo esc_html( $exp->reference_no ?? '' ); ?></span></td>
								<td style="padding: 12px;"><strong><?php echo esc_html( $exp->particulars ?? '' ); ?></strong></td>
								<td style="padding: 12px;"><span class="nmbp-badge-pill"><?php echo esc_html( $exp->method ?? 'Cash' ); ?></span></td>
								<td style="padding: 12px;"><small style="color: var(--nmbp-text-muted, #64748b); font-weight: 700;"><?php echo esc_html( $exp->transaction_date ?? '' ); ?></small></td>
								<td style="padding: 12px; text-align: right; color: #ef4444; font-weight: 900; font-family: var(--nmbp-font-mono, monospace);">
									-<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $exp->amount ?? 0.00 ), 2 ) ); ?>
								</td>
							</tr>
								<?php
							endforeach;
						else :
							?>
							<tr><td colspan="5" style="text-align: center; padding: 3rem; color: var(--nmbp-text-muted, #64748b);"><?php esc_html_e( 'No operational expenses found for this period.', 'nahian-movie-booking-plugin' ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php
$version = defined( 'NMBP_VERSION' ) ? NMBP_VERSION : '1.0';
wp_register_script( 'nmbp-report-runtime', false, array(), $version, true );
wp_enqueue_script( 'nmbp-report-runtime' );

$js_script = '
    (function() {
        document.addEventListener("DOMContentLoaded", function() {
            const filterMovie = document.getElementById("reportFilterMovie");
            const filterTime  = document.getElementById("reportFilterTime");

            function applyTableFilter() {
                const selMovie = filterMovie ? filterMovie.value.toLowerCase().trim() : "";
                const selTime  = filterTime ? filterTime.value.toLowerCase().trim() : "";
                const rows     = document.querySelectorAll("#nmbpTicketReportTable tbody tr");

                rows.forEach(function(row) {
                    if (row.id === "noReportRow") return;

                    const rMovie = (row.getAttribute("data-movie") || "").toLowerCase().trim();
                    const rTime  = (row.getAttribute("data-time") || "").toLowerCase().trim();

                    const matchMovie = !selMovie || rMovie.indexOf(selMovie) !== -1;
                    const matchTime  = !selTime || rTime === selTime;

                    if (matchMovie && matchTime) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            }

            if (filterMovie) {
                filterMovie.addEventListener("change", applyTableFilter);
            }
            if (filterTime) {
                filterTime.addEventListener("change", applyTableFilter);
            }
        });
    })();
';

wp_add_inline_script( 'nmbp-report-runtime', $js_script );