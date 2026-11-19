<?php
/**
 * Booking Module - Single Booking Digital Pass & Thermal Voucher View
 *
 * Path: admin/booking/view-booking.php
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
$booking_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$view_booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $booking_id ) );

if ( ! $view_booking ) {
	?>
	<style id="nmbp-view-booking-not-found-css">
		.nmbp-booking-not-found {
			padding: 15px;
			background: #fef2f2;
			border: 1px solid #fecaca;
			border-radius: 8px;
			color: #ef4444;
			font-weight: bold;
		}
	</style>
	<div class="notice notice-error nmbp-booking-not-found">
		<p><?php esc_html_e( 'Booking record not found.', 'nahian-movie-booking-plugin' ); ?></p>
	</div>
	<?php
	return;
}

// Format friendly screening and transaction timestamps.
$booking_ts            = ! empty( $view_booking->booking_date ) ? strtotime( $view_booking->booking_date ) : time();
$friendly_booking_date = wp_date( 'd M Y - h:i A', $booking_ts );
$currency_symbol       = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );

// Format Showtime cleanly to 12-Hour AM/PM format (e.g. 17:00 -> 05:00 PM).
if ( ! function_exists( 'nmbp_format_view_booking_time' ) ) {
	function nmbp_format_view_booking_time( $raw_time ) {
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

$formatted_showtime = nmbp_format_view_booking_time( $view_booking->showtime ?? '' );

// Parse and clean seats.
$seats_list = array_filter( array_map( 'trim', explode( ',', (string) ( $view_booking->seats ?? '' ) ) ) );
?>

<style id="nmbp-view-booking-css">
	.nmbp-pass-badge-row {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 6px;
	}
	.nmbp-pass-status-pill {
		font-size: 0.7rem;
		font-weight: 800;
		text-transform: uppercase;
	}
	.nmbp-pass-token-code {
		font-size: 0.8rem;
		color: #38bdf8;
		font-family: var(--nmbp-font-mono, monospace);
		font-weight: 800;
	}
	.nmbp-pass-movie-title {
		font-size: 1.6rem;
		font-weight: 900;
		color: #ffffff;
		margin: 0;
		letter-spacing: -0.02em;
	}
	.nmbp-pass-admission-sub {
		font-size: 0.82rem;
		color: #cbd5e1;
		margin-top: 4px;
		font-weight: 600;
	}
	.nmbp-pass-settlement-box {
		text-align: right;
	}
	.nmbp-pass-settlement-label {
		font-size: 0.7rem;
		color: #94a3b8;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.05em;
	}
	.nmbp-pass-settlement-amount {
		font-size: 1.5rem;
		font-weight: 900;
		font-family: var(--nmbp-font-mono, monospace);
		color: #34d399;
		margin-top: 2px;
	}
	.nmbp-spec-th-flex {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.nmbp-spec-customer-name {
		font-weight: 800;
		font-size: 1rem;
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-spec-contact-meta {
		font-size: 0.82rem;
		color: var(--nmbp-text-muted, #64748b);
		margin-top: 4px;
	}
	.nmbp-spec-email-sep {
		margin-left: 12px;
	}
	.nmbp-spec-slot-row {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.nmbp-spec-auditorium-label {
		font-size: 0.8rem;
		font-weight: 700;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-spec-registered-meta {
		font-size: 0.82rem;
		color: var(--nmbp-text-muted, #64748b);
		margin-top: 5px;
	}
	.nmbp-spec-seats-row {
		display: flex;
		flex-wrap: wrap;
		gap: 6px;
		align-items: center;
	}
	.nmbp-roster-badge--standard {
		background: #0f172a;
		color: #38bdf8;
		border-color: #1e293b;
	}
	.nmbp-roster-badge--vip {
		background: #fef3c7;
		color: #b45309;
		border-color: #fde68a;
	}
	.nmbp-spec-seats-total {
		font-size: 0.75rem;
		color: var(--nmbp-text-muted, #64748b);
		margin-left: 6px;
		font-weight: 700;
	}
	.nmbp-spec-no-seats {
		color: #94a3b8;
		font-size: 0.85rem;
	}
	.nmbp-spec-payment-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	.nmbp-spec-payment-left {
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.nmbp-spec-payment-method {
		font-weight: 800;
		font-size: 0.92rem;
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-badge-pill--status-compact {
		font-size: 0.75rem;
	}
	.nmbp-spec-payment-amount {
		font-size: 1.15rem;
		font-weight: 900;
		font-family: var(--nmbp-font-mono, monospace);
		color: #059669;
	}
	.nmbp-spec-token-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.nmbp-voucher-tag--lg {
		font-size: 0.9rem;
		padding: 4px 10px;
	}
	.nmbp-turnstile-verified {
		font-size: 0.78rem;
		color: #059669;
		font-weight: 800;
	}
	.nmbp-action-footer-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 1.5rem;
		padding-top: 1.25rem;
		border-top: 1px solid var(--nmbp-border-light, #f1f5f9);
	}
	.nmbp-btn-back-bookings {
		font-weight: 700;
		border-radius: 6px;
	}
	.nmbp-pass-footer-actions {
		display: flex;
		gap: 10px;
	}
	.nmbp-btn-edit-pass {
		background: #0284c7;
		border-color: #0284c7;
		font-weight: 800;
		border-radius: 6px;
	}
	.nmbp-btn-print-pass {
		font-weight: 800;
		border-radius: 6px;
	}
	.nmbp-voucher-card {
		background: #ffffff;
		border: 1px dashed #cbd5e1;
		box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
		padding: 1.5rem;
	}
	.nmbp-voucher-head {
		text-align: center;
		border-bottom: 1px dashed #cbd5e1;
		padding-bottom: 1rem;
		margin-bottom: 1rem;
	}
	.nmbp-voucher-brand {
		font-weight: 900;
		font-size: 1.15rem;
		color: #0f172a;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}
	.nmbp-voucher-sublabel {
		font-size: 0.7rem;
		color: var(--nmbp-text-muted, #64748b);
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.05em;
	}
	.nmbp-voucher-table {
		margin-bottom: 1rem;
	}
	.nmbp-voucher-cell-label {
		color: #64748b;
	}
	.nmbp-voucher-cell-token {
		text-align: right;
		font-family: var(--nmbp-font-mono, monospace);
		font-weight: 800;
		color: #0f172a;
	}
	.nmbp-voucher-cell-movie {
		text-align: right;
		font-weight: 800;
		color: #0f172a;
	}
	.nmbp-voucher-cell-showtime {
		text-align: right;
		font-weight: 800;
		color: #0284c7;
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-voucher-cell-date {
		text-align: right;
		font-weight: 700;
		color: #0f172a;
	}
	.nmbp-voucher-cell-seats {
		text-align: right;
		font-family: var(--nmbp-font-mono, monospace);
		font-weight: 800;
		color: #059669;
	}
	.nmbp-voucher-cell-method {
		text-align: right;
		font-weight: 700;
	}
	.nmbp-voucher-total-label {
		color: #0f172a;
		font-weight: 900;
	}
	.nmbp-voucher-total-val {
		text-align: right;
		color: #059669;
		font-family: var(--nmbp-font-mono, monospace);
		font-weight: 900;
	}
	.nmbp-voucher-barcode {
		height: 36px;
		background: repeating-linear-gradient(
			90deg,
			#0f172a 0px,
			#0f172a 2px,
			transparent 2px,
			transparent 4px,
			#0f172a 4px,
			#0f172a 7px,
			transparent 7px,
			transparent 9px
		);
		margin: 1.25rem 0 0.5rem 0;
		border-radius: 3px;
	}
	.nmbp-voucher-barcode-caption {
		text-align: center;
		font-size: 0.75rem;
		color: #64748b;
		font-family: var(--nmbp-font-mono, monospace);
		font-weight: 800;
	}

	@media print {
		body * {
			visibility: hidden;
		}
		#adminmenumain,
		#wpadminbar,
		.nmbp-subtabs-nav-wrapper,
		.nmbp-action-footer-bar,
		.notice {
			display: none !important;
		}
		#nmbpPrintArea,
		#nmbpPrintArea * {
			visibility: visible;
		}
		#nmbpPrintArea {
			position: absolute;
			left: 0;
			top: 0;
			width: 100%;
			margin: 0;
			padding: 0;
			display: block !important;
		}
		#nmbpThermalVoucher {
			border: 1px solid #000000 !important;
			box-shadow: none !important;
			max-width: 380px;
			margin: 0 auto;
		}
	}
</style>

<div class="nmbp-staff-view-grid" id="nmbpPrintArea">

	<!-- LEFT COLUMN: Master Digital Pass Container -->
	<div class="nmbp-movie-content-main">

		<!-- Banner Header -->
		<div class="nmbp-movie-banner-head">
			<div>
				<div class="nmbp-pass-badge-row">
					<span class="nmbp-badge-pill active nmbp-pass-status-pill">
						● <?php echo esc_html( ! empty( $view_booking->payment_status ) ? $view_booking->payment_status : __( 'Confirmed', 'nahian-movie-booking-plugin' ) ); ?>
					</span>
					<span class="nmbp-pass-token-code">
						<?php echo esc_html( $view_booking->booking_token ?? '' ); ?>
					</span>
				</div>
				<h2 class="nmbp-pass-movie-title">
					<?php echo esc_html( $view_booking->movie_title ?? '' ); ?>
				</h2>
				<div class="nmbp-pass-admission-sub">
					<?php echo esc_html( ! empty( $view_booking->ticket_type ) ? $view_booking->ticket_type : __( 'Standard', 'nahian-movie-booking-plugin' ) ); ?> <?php esc_html_e( 'Pass Admission', 'nahian-movie-booking-plugin' ); ?>
				</div>
			</div>

			<div class="nmbp-pass-settlement-box">
				<div class="nmbp-pass-settlement-label"><?php esc_html_e( 'Settlement', 'nahian-movie-booking-plugin' ); ?></div>
				<div class="nmbp-pass-settlement-amount">
					<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $view_booking->total_amount ?? 0.00 ), 2 ) ); ?>
				</div>
			</div>
		</div>

		<!-- Body Details -->
		<div class="nmbp-movie-body-wrap">
			<table class="nmbp-staff-spec-table">
				<tbody>
					<!-- Customer Profile -->
					<tr>
						<th>
							<div class="nmbp-spec-th-flex">
								<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
								<span><?php esc_html_e( 'Customer Profile', 'nahian-movie-booking-plugin' ); ?></span>
							</div>
						</th>
						<td>
							<div class="nmbp-spec-customer-name">
								<?php echo esc_html( ! empty( $view_booking->customer_name ) ? $view_booking->customer_name : __( 'Walk-In Guest', 'nahian-movie-booking-plugin' ) ); ?>
							</div>
							<div class="nmbp-spec-contact-meta">
								<span>📞 <?php echo esc_html( ! empty( $view_booking->customer_phone ) ? $view_booking->customer_phone : __( 'No phone provided', 'nahian-movie-booking-plugin' ) ); ?></span>
								<?php if ( ! empty( $view_booking->customer_email ) ) : ?>
									<span class="nmbp-spec-email-sep">✉️ <?php echo esc_html( $view_booking->customer_email ); ?></span>
								<?php endif; ?>
							</div>
						</td>
					</tr>

					<!-- Screening Slot & Showtime -->
					<tr>
						<th>
							<div class="nmbp-spec-th-flex">
								<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
								<span><?php esc_html_e( 'Screening Slot', 'nahian-movie-booking-plugin' ); ?></span>
							</div>
						</th>
						<td>
							<div class="nmbp-spec-slot-row">
								<span class="nmbp-time-pill-view">
									⏰ <?php echo esc_html( $formatted_showtime ); ?>
								</span>
								<span class="nmbp-spec-auditorium-label">
									(<?php echo esc_html( ! empty( $view_booking->auditorium ) ? $view_booking->auditorium : 'Auditorium 1' ); ?>)
								</span>
							</div>
							<div class="nmbp-spec-registered-meta">
								<?php esc_html_e( 'Registered On:', 'nahian-movie-booking-plugin' ); ?> <strong><?php echo esc_html( $friendly_booking_date ); ?></strong>
							</div>
						</td>
					</tr>

					<!-- Reserved Seats -->
					<tr>
						<th>
							<div class="nmbp-spec-th-flex">
								<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M4 18v3h3v-3h10v3h3v-3h1a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H3a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h1zM5 8h14v6H5V8z"/></svg>
								<span><?php esc_html_e( 'Reserved Seats', 'nahian-movie-booking-plugin' ); ?></span>
							</div>
						</th>
						<td>
							<div class="nmbp-spec-seats-row">
								<?php if ( ! empty( $seats_list ) ) : ?>
									<?php
									foreach ( $seats_list as $seat_item ) :
										$is_vip = ( 0 === strpos( strtoupper( $seat_item ), 'A' ) );
										?>
										<span class="nmbp-roster-badge <?php echo $is_vip ? 'nmbp-roster-badge--vip' : 'nmbp-roster-badge--standard'; ?>">
											<?php echo esc_html( $seat_item . ( $is_vip ? ' [VIP]' : '' ) ); ?>
										</span>
									<?php endforeach; ?>
									<span class="nmbp-spec-seats-total">
										(<?php echo esc_html( (string) count( $seats_list ) ); ?> <?php esc_html_e( 'Seats Total', 'nahian-movie-booking-plugin' ); ?>)
									</span>
								<?php else : ?>
									<span class="nmbp-spec-no-seats"><?php esc_html_e( 'No seats assigned', 'nahian-movie-booking-plugin' ); ?></span>
								<?php endif; ?>
							</div>
						</td>
					</tr>

					<!-- Payment Details -->
					<tr>
						<th>
							<div class="nmbp-spec-th-flex">
								<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
								<span><?php esc_html_e( 'Payment Gateway', 'nahian-movie-booking-plugin' ); ?></span>
							</div>
						</th>
						<td>
							<div class="nmbp-spec-payment-row">
								<div class="nmbp-spec-payment-left">
									<span class="nmbp-spec-payment-method">
										<?php echo esc_html( ! empty( $view_booking->payment_method ) ? $view_booking->payment_method : __( 'Cash Desk', 'nahian-movie-booking-plugin' ) ); ?>
									</span>
									<span class="nmbp-badge-pill nmbp-badge-pill--status-compact">
										<?php echo esc_html( ! empty( $view_booking->payment_status ) ? $view_booking->payment_status : __( 'Confirmed', 'nahian-movie-booking-plugin' ) ); ?>
									</span>
								</div>
								<div class="nmbp-spec-payment-amount">
									<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $view_booking->total_amount ?? 0.00 ), 2 ) ); ?>
								</div>
							</div>
						</td>
					</tr>

					<!-- Reference Token -->
					<tr>
						<th>
							<div class="nmbp-spec-th-flex">
								<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
								<span><?php esc_html_e( 'Voucher Token', 'nahian-movie-booking-plugin' ); ?></span>
							</div>
						</th>
						<td>
							<div class="nmbp-spec-token-row">
								<span class="nmbp-voucher-tag nmbp-voucher-tag--lg">
									<?php echo esc_html( $view_booking->booking_token ?? '' ); ?>
								</span>
								<span class="nmbp-turnstile-verified">
									✓ <?php esc_html_e( 'Turnstile Authorized', 'nahian-movie-booking-plugin' ); ?>
								</span>
							</div>
						</td>
					</tr>
				</tbody>
			</table>

			<!-- Action Footer -->
			<div class="nmbp-action-footer-bar">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-booking&subtab=all' ) ); ?>" class="button button-secondary nmbp-btn-back-bookings">
					&larr; <?php esc_html_e( 'Back to All Bookings', 'nahian-movie-booking-plugin' ); ?>
				</a>

				<div class="nmbp-pass-footer-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-booking&subtab=edit&id=' . absint( $view_booking->id ) ) ); ?>" class="button button-primary nmbp-btn-edit-pass">
						✏️ <?php esc_html_e( 'Edit Record', 'nahian-movie-booking-plugin' ); ?>
					</a>
					<button type="button" class="button button-secondary nmbp-btn-print-pass" id="btnPrintBookingPass">
						🖨️ <?php esc_html_e( 'Print Pass', 'nahian-movie-booking-plugin' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- RIGHT COLUMN: Thermal Voucher Preview -->
	<div>
		<div class="nmbp-poster-sidebar-card nmbp-voucher-card" id="nmbpThermalVoucher">
			<div class="nmbp-voucher-head">
				<div class="nmbp-voucher-brand">
					<?php echo esc_html( get_option( 'nmbp_theater_name', 'Nahian Movie Booking' ) ); ?>
				</div>
				<span class="nmbp-voucher-sublabel">
					<?php esc_html_e( 'Admission Voucher', 'nahian-movie-booking-plugin' ); ?>
				</span>
			</div>

			<table class="nmbp-slot-metric-table nmbp-voucher-table">
				<tbody>
					<tr>
						<td class="nmbp-voucher-cell-label"><?php esc_html_e( 'Token', 'nahian-movie-booking-plugin' ); ?></td>
						<td class="nmbp-voucher-cell-token"><?php echo esc_html( $view_booking->booking_token ?? '' ); ?></td>
					</tr>
					<tr>
						<td class="nmbp-voucher-cell-label"><?php esc_html_e( 'Movie', 'nahian-movie-booking-plugin' ); ?></td>
						<td class="nmbp-voucher-cell-movie"><?php echo esc_html( $view_booking->movie_title ?? '' ); ?></td>
					</tr>
					<tr>
						<td class="nmbp-voucher-cell-label"><?php esc_html_e( 'Showtime', 'nahian-movie-booking-plugin' ); ?></td>
						<td class="nmbp-voucher-cell-showtime"><?php echo esc_html( $formatted_showtime ); ?></td>
					</tr>
					<tr>
						<td class="nmbp-voucher-cell-label"><?php esc_html_e( 'Date', 'nahian-movie-booking-plugin' ); ?></td>
						<td class="nmbp-voucher-cell-date"><?php echo esc_html( wp_date( 'd M Y', $booking_ts ) ); ?></td>
					</tr>
					<tr>
						<td class="nmbp-voucher-cell-label"><?php esc_html_e( 'Seats', 'nahian-movie-booking-plugin' ); ?></td>
						<td class="nmbp-voucher-cell-seats"><?php echo esc_html( $view_booking->seats ?? '' ); ?></td>
					</tr>
					<tr>
						<td class="nmbp-voucher-cell-label"><?php esc_html_e( 'Method', 'nahian-movie-booking-plugin' ); ?></td>
						<td class="nmbp-voucher-cell-method"><?php echo esc_html( ! empty( $view_booking->payment_method ) ? $view_booking->payment_method : __( 'Cash', 'nahian-movie-booking-plugin' ) ); ?></td>
					</tr>
					<tr class="total-row">
						<td class="nmbp-voucher-total-label"><?php esc_html_e( 'Total Paid', 'nahian-movie-booking-plugin' ); ?></td>
						<td class="nmbp-voucher-total-val">
							<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $view_booking->total_amount ?? 0.00 ), 2 ) ); ?>
						</td>
					</tr>
				</tbody>
			</table>

			<!-- Barcode Graphic -->
			<div class="nmbp-voucher-barcode" aria-hidden="true"></div>

			<div class="nmbp-voucher-barcode-caption">
				* <?php echo esc_html( $view_booking->booking_token ?? '' ); ?> *
			</div>
		</div>
	</div>
</div>

<script id="nmbp-view-booking-runtime-js">
	(function() {
		document.addEventListener("DOMContentLoaded", function() {
			const btnPrint = document.getElementById("btnPrintBookingPass");
			if (btnPrint) {
				btnPrint.addEventListener("click", function() {
					window.print();
				});
			}
		});
	})();
</script>