<?php
/**
 * Customer Module - Single Customer Dossier View
 *
 * Path: admin/customers/view-customer.php
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
$table_customers = $wpdb->prefix . 'nmbp_customers';
$table_bookings  = $wpdb->prefix . 'nmbp_bookings';
$customer_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );

$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $customer_id ) );
if ( ! $customer ) {
	?>
	<style id="nmbp-view-customer-not-found-css">
		.nmbp-customer-not-found {
			margin: 20px;
		}
	</style>
	<div class="nmbp-panel nmbp-customer-not-found">
		<p><?php esc_html_e( 'Customer record not found.', 'nahian-movie-booking-plugin' ); ?></p>
	</div>
	<?php
	return;
}

// Helper 12-Hour AM/PM Time Formatter.
if ( ! function_exists( 'nmbp_format_customer_time' ) ) {
	function nmbp_format_customer_time( $raw_time ) {
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

// Fetch matching historical bookings by customer email or phone.
$customer_history = array();
$history_clauses  = array();
$history_args     = array();

if ( ! empty( $customer->email ) ) {
	$history_clauses[] = 'customer_email = %s';
	$history_args[]    = $customer->email;
}

if ( ! empty( $customer->phone ) ) {
	$history_clauses[] = 'customer_phone = %s';
	$history_args[]    = $customer->phone;
}

if ( ! empty( $history_clauses ) ) {
	$history_where    = implode( ' OR ', $history_clauses );
	$customer_history = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_bookings} WHERE ({$history_where}) ORDER BY id DESC",
			$history_args
		)
	);
}

if ( ! is_array( $customer_history ) ) {
	$customer_history = array();
}

// Calculate total tickets from actual history.
$total_tickets_count = 0;
foreach ( $customer_history as $h ) {
	if ( ! empty( $h->seats ) ) {
		$seats_arr            = array_filter( array_map( 'trim', explode( ',', $h->seats ) ) );
		$total_tickets_count += count( $seats_arr );
	}
}

$total_visits_count = intval( $customer->total_visits ?? count( $customer_history ) );
$delete_url         = wp_nonce_url(
	admin_url( 'admin.php?page=nmbp-customers&subtab=delete&id=' . absint( $customer->id ) ),
	'nmbp_delete_customer_' . absint( $customer->id ),
	'_nmbp_nonce'
);
?>

<style id="nmbp-view-customer-css">
	.nmbp-customer-badge-row {
		display: flex;
		align-items: center;
		gap: 8px;
		margin-bottom: 10px;
	}
	.nmbp-customer-hero-name {
		font-size: 2.1rem;
		font-weight: 900;
		margin: 0 0 0.35rem 0;
		color: #ffffff;
		letter-spacing: -0.01em;
	}
	.nmbp-customer-hero-meta {
		font-size: 0.82rem;
		color: #94a3b8;
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-customer-hero-date {
		color: #e2e8f0;
	}
	.nmbp-customer-contact-phone {
		font-weight: 800;
		font-size: 1rem;
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-customer-contact-email {
		font-size: 0.85rem;
		color: var(--nmbp-text-muted, #64748b);
		margin-top: 3px;
	}
	.nmbp-customer-activity-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.nmbp-customer-visits-title {
		color: var(--nmbp-text-main, #0f172a);
		font-size: 0.95rem;
	}
	.nmbp-customer-activity-sub {
		font-size: 0.8rem;
		color: var(--nmbp-text-muted, #64748b);
		margin-top: 2px;
	}
	.nmbp-customer-tickets-count {
		color: var(--nmbp-accent-teal, #0d9488);
		font-family: var(--nmbp-font-mono, monospace);
		font-size: 1.1rem;
	}
	.nmbp-customer-history-section {
		border-top: 1px solid var(--nmbp-border-light, #f1f5f9);
		padding-top: 1.5rem;
		margin-top: 1.5rem;
	}
	.nmbp-customer-history-heading {
		margin: 0 0 1rem 0;
		font-size: 1.1rem;
		font-weight: 800;
		display: flex;
		align-items: center;
		gap: 0.5rem;
	}
	.nmbp-customer-table-wrap {
		overflow-x: auto;
	}
	.nmbp-customer-history-table {
		width: 100%;
		border-collapse: collapse;
	}
	.nmbp-customer-thead-row {
		border-bottom: 1px solid var(--nmbp-border, #e2e8f0);
		background: #f8fafc;
	}
	.nmbp-customer-th {
		padding: 10px 12px;
		text-align: left;
	}
	.nmbp-customer-history-row {
		border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);
	}
	.nmbp-customer-td {
		padding: 12px;
	}
	.nmbp-showtime-pill-compact {
		font-size: 0.7rem;
	}
	.nmbp-seat-code-mono {
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-booking-amount-mono {
		color: var(--nmbp-accent-teal, #0d9488);
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-booking-date-sub {
		color: var(--nmbp-text-muted, #64748b);
		font-weight: 700;
	}
	.nmbp-history-empty-cell {
		text-align: center;
		padding: 2rem;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-customer-back-footer {
		margin-top: 2rem;
		border-top: 1px solid var(--nmbp-border-light, #f1f5f9);
		padding-top: 1.25rem;
	}
	.nmbp-btn-back-customers {
		font-weight: 700;
		border-radius: 6px;
	}
	.nmbp-club-card-top {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 2rem;
	}
	.nmbp-club-card-label {
		font-size: 0.8rem;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 1px;
		color: #99f6e4;
	}
	.nmbp-club-card-icon {
		fill: rgba(255, 255, 255, 0.7);
	}
	.nmbp-club-card-name {
		font-size: 1.35rem;
		font-weight: 900;
		letter-spacing: 0.02em;
		margin-bottom: 0.25rem;
	}
	.nmbp-club-card-phone {
		font-size: 0.85rem;
		color: #cbd5e1;
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-club-card-footer {
		display: flex;
		justify-content: space-between;
		align-items: flex-end;
		margin-top: 2.5rem;
		border-top: 1px solid rgba(255, 255, 255, 0.15);
		padding-top: 1rem;
	}
	.nmbp-club-stat-label {
		font-size: 0.68rem;
		text-transform: uppercase;
		color: #99f6e4;
		font-weight: 800;
	}
	.nmbp-club-stat-label--right {
		font-size: 0.68rem;
		text-transform: uppercase;
		color: #99f6e4;
		font-weight: 800;
		text-align: right;
	}
	.nmbp-club-stat-val {
		font-size: 1.1rem;
		font-weight: 900;
	}
	.nmbp-club-stat-val--tickets {
		font-size: 1.1rem;
		font-weight: 900;
		font-family: var(--nmbp-font-mono, monospace);
		color: #fde68a;
		text-align: right;
	}
</style>

<div class="nmbp-customer-view-grid">

	<!-- Left: Dossier & Screening History -->
	<div class="nmbp-customer-main-card">

		<!-- Cinematic Header Banner -->
		<div class="nmbp-customer-banner-head">
			<div>
				<div class="nmbp-customer-badge-row">
					<span class="nmbp-badge-pill active"><?php esc_html_e( 'Club Member', 'nahian-movie-booking-plugin' ); ?></span>
				</div>
				<h2 class="nmbp-customer-hero-name">
					<?php echo esc_html( $customer->name ?? '' ); ?>
				</h2>
				<div class="nmbp-customer-hero-meta">
					<?php esc_html_e( 'Registered:', 'nahian-movie-booking-plugin' ); ?>
					<strong class="nmbp-customer-hero-date">
						<?php echo esc_html( ! empty( $customer->created_at ) ? wp_date( 'M j, Y', strtotime( $customer->created_at ) ) : __( 'N/A', 'nahian-movie-booking-plugin' ) ); ?>
					</strong>
				</div>
			</div>

			<div class="nmbp-table-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-customers&subtab=edit&id=' . absint( $customer->id ) ) ); ?>" class="nmbp-action-btn edit" title="<?php esc_attr_e( 'Edit Customer', 'nahian-movie-booking-plugin' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
					<span><?php esc_html_e( 'Edit Customer', 'nahian-movie-booking-plugin' ); ?></span>
				</a>
				<a href="<?php echo esc_url( $delete_url ); ?>" class="nmbp-action-btn delete nmbp-delete-customer-dossier-link" title="<?php esc_attr_e( 'Delete', 'nahian-movie-booking-plugin' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
					<span><?php esc_html_e( 'Delete', 'nahian-movie-booking-plugin' ); ?></span>
				</a>
			</div>
		</div>

		<!-- Body Information -->
		<div class="nmbp-customer-body-wrap">

			<!-- Structured Key-Value Spec Table -->
			<table class="nmbp-customer-spec-table">
				<tbody>
					<tr>
						<th>
							<div class="nmbp-table-cell-header">
								<span><?php esc_html_e( 'Contact Information', 'nahian-movie-booking-plugin' ); ?></span>
							</div>
						</th>
						<td>
							<div class="nmbp-customer-contact-phone">
								📞 <?php echo esc_html( ! empty( $customer->phone ) ? $customer->phone : __( 'Not provided', 'nahian-movie-booking-plugin' ) ); ?>
							</div>
							<div class="nmbp-customer-contact-email">
								✉️ <?php echo esc_html( ! empty( $customer->email ) ? $customer->email : __( 'Not provided', 'nahian-movie-booking-plugin' ) ); ?>
							</div>
						</td>
					</tr>

					<tr>
						<th>
							<div class="nmbp-table-cell-header">
								<span><?php esc_html_e( 'Screening Activity', 'nahian-movie-booking-plugin' ); ?></span>
							</div>
						</th>
						<td>
							<div class="nmbp-customer-activity-row">
								<div>
									<strong class="nmbp-customer-visits-title">
										<?php echo esc_html( number_format_i18n( $total_visits_count ) ); ?> <?php esc_html_e( 'Total Visits', 'nahian-movie-booking-plugin' ); ?>
									</strong>
									<div class="nmbp-customer-activity-sub">
										<?php esc_html_e( 'Member Screening Record', 'nahian-movie-booking-plugin' ); ?>
									</div>
								</div>
								<strong class="nmbp-customer-tickets-count">
									<?php echo esc_html( number_format_i18n( $total_tickets_count ) ); ?> <?php esc_html_e( 'Tickets', 'nahian-movie-booking-plugin' ); ?>
								</strong>
							</div>
						</td>
					</tr>
				</tbody>
			</table>

			<!-- Customer Ticket Purchase History -->
			<div class="nmbp-customer-history-section">
				<h4 class="nmbp-customer-history-heading">
					<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V10zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
					<?php
					/* translators: %d: Historical booking count */
					printf( esc_html__( 'Screening Passes & Ticket Log (%d)', 'nahian-movie-booking-plugin' ), count( $customer_history ) );
					?>
				</h4>

				<div class="nmbp-table-wrap nmbp-customer-table-wrap">
					<table class="nmbp-data-table nmbp-customer-history-table">
						<thead>
							<tr class="nmbp-customer-thead-row">
								<th class="nmbp-customer-th"><?php esc_html_e( 'TOKEN', 'nahian-movie-booking-plugin' ); ?></th>
								<th class="nmbp-customer-th"><?php esc_html_e( 'MOVIE', 'nahian-movie-booking-plugin' ); ?></th>
								<th class="nmbp-customer-th"><?php esc_html_e( 'SHOWTIME', 'nahian-movie-booking-plugin' ); ?></th>
								<th class="nmbp-customer-th"><?php esc_html_e( 'SEATS', 'nahian-movie-booking-plugin' ); ?></th>
								<th class="nmbp-customer-th"><?php esc_html_e( 'AMOUNT', 'nahian-movie-booking-plugin' ); ?></th>
								<th class="nmbp-customer-th"><?php esc_html_e( 'DATE', 'nahian-movie-booking-plugin' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							if ( ! empty( $customer_history ) ) :
								foreach ( $customer_history as $h ) :
									$formatted_h_time = nmbp_format_customer_time( $h->showtime ?? '' );
									?>
								<tr class="nmbp-customer-history-row">
									<td class="nmbp-customer-td">
										<span class="nmbp-voucher-tag"><?php echo esc_html( $h->booking_token ?? '' ); ?></span>
									</td>
									<td class="nmbp-customer-td">
										<strong><?php echo esc_html( $h->movie_title ?? '' ); ?></strong>
									</td>
									<td class="nmbp-customer-td">
										<span class="nmbp-badge-pill active nmbp-showtime-pill-compact">
											<?php echo esc_html( ! empty( $formatted_h_time ) ? $formatted_h_time : __( 'Counter', 'nahian-movie-booking-plugin' ) ); ?>
										</span>
									</td>
									<td class="nmbp-customer-td">
										<code class="nmbp-seat-code-mono"><?php echo esc_html( $h->seats ?? '' ); ?></code>
									</td>
									<td class="nmbp-customer-td">
										<strong class="nmbp-booking-amount-mono">
											<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $h->total_amount ?? 0.00 ), 2 ) ); ?>
										</strong>
									</td>
									<td class="nmbp-customer-td">
										<small class="nmbp-booking-date-sub">
											<?php echo esc_html( ! empty( $h->booking_date ) ? wp_date( 'M j, Y', strtotime( $h->booking_date ) ) : '' ); ?>
										</small>
									</td>
								</tr>
									<?php
								endforeach;
							else :
								?>
								<tr>
									<td colspan="6" class="nmbp-history-empty-cell">
										<?php esc_html_e( 'No ticket bookings linked to this customer yet.', 'nahian-movie-booking-plugin' ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="nmbp-customer-back-footer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-customers&subtab=all' ) ); ?>" class="button button-secondary nmbp-btn-back-customers">
					&larr; <?php esc_html_e( 'Back to All Customers', 'nahian-movie-booking-plugin' ); ?>
				</a>
			</div>

		</div>

	</div>

	<!-- Right: Member Card Visual -->
	<div>
		<div class="nmbp-staff-id-card-sidebar">
			<div class="nmbp-club-card-top">
				<div class="nmbp-club-card-label">
					<?php esc_html_e( 'CLUB MEMBER', 'nahian-movie-booking-plugin' ); ?>
				</div>
				<svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true" class="nmbp-club-card-icon"><path d="M12 2L1 21h22L12 2zm0 3.5l7.5 13H4.5L12 5.5z"/></svg>
			</div>

			<div class="nmbp-club-card-name">
				<?php echo esc_html( $customer->name ?? '' ); ?>
			</div>
			<div class="nmbp-club-card-phone">
				<?php echo esc_html( $customer->phone ?? '' ); ?>
			</div>

			<div class="nmbp-club-card-footer">
				<div>
					<div class="nmbp-club-stat-label"><?php esc_html_e( 'Visits', 'nahian-movie-booking-plugin' ); ?></div>
					<div class="nmbp-club-stat-val"><?php echo esc_html( number_format_i18n( $total_visits_count ) ); ?></div>
				</div>
				<div>
					<div class="nmbp-club-stat-label--right"><?php esc_html_e( 'Tickets', 'nahian-movie-booking-plugin' ); ?></div>
					<div class="nmbp-club-stat-val--tickets">
						<?php echo esc_html( number_format_i18n( $total_tickets_count ) ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

</div>

<script id="nmbp-view-customer-js">
	(function() {
		document.addEventListener("DOMContentLoaded", function() {
			const confirmMsg = <?php echo wp_json_encode( __( 'Permanently remove this customer record?', 'nahian-movie-booking-plugin' ) ); ?>;
			document.querySelectorAll(".nmbp-delete-customer-dossier-link").forEach(function(link) {
				link.addEventListener("click", function(e) {
					if (!window.confirm(confirmMsg)) {
						e.preventDefault();
					}
				});
			});
		});
	})();
</script>