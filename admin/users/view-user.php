<?php
/**
 * Users / Staff Module - Single Staff Dossier View
 *
 * Path: admin/users/view-user.php
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
$table_staff     = $wpdb->prefix . 'nmbp_staff_crew';
$staff_id        = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );

$staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id ) );
if ( ! $staff ) {
	?>
	<style id="nmbp-view-user-not-found-css">
		.nmbp-staff-not-found {
			margin: 20px;
		}
	</style>
	<div class="nmbp-panel nmbp-staff-not-found">
		<p><?php esc_html_e( 'Staff record not found.', 'nahian-movie-booking-plugin' ); ?></p>
	</div>
	<?php
	return;
}

$is_on_duty    = ( 'On Duty' === ( $staff->duty_status ?? '' ) );
$staff_item_id = absint( $staff->id );
$staff_code    = ! empty( $staff->staff_code ) ? $staff->staff_code : '#STAFF-' . $staff_item_id;
$delete_url    = wp_nonce_url(
	admin_url( 'admin.php?page=nmbp-users&subtab=delete&id=' . $staff_item_id ),
	'nmbp_delete_user_' . $staff_item_id,
	'_nmbp_nonce'
);
?>

<style id="nmbp-view-user-css">
	.nmbp-staff-head-bar {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);
		padding-bottom: 1.25rem;
		margin-bottom: 1.5rem;
	}
	.nmbp-staff-status-row {
		display: flex;
		align-items: center;
		gap: 0.5rem;
		margin-bottom: 0.4rem;
	}
	.nmbp-badge-pill--off-duty {
		background: rgba(239, 68, 68, 0.15);
		color: #ef4444;
		border: 1px solid rgba(239, 68, 68, 0.3);
	}
	.nmbp-badge-pill--badge-off-duty {
		background: rgba(239, 68, 68, 0.2);
		color: #fca5a5;
		border: 1px solid rgba(239, 68, 68, 0.4);
	}
	.nmbp-badge-pill--compact {
		font-size: 0.68rem;
	}
	.nmbp-staff-code-tag {
		font-family: var(--nmbp-font-mono, monospace);
		background: #f8fafc;
		padding: 2px 6px;
		border: 1px solid #cbd5e1;
		border-radius: 4px;
		font-size: 0.75rem;
	}
	.nmbp-staff-name-heading {
		font-size: 1.75rem;
		font-weight: 900;
		margin: 0;
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-staff-meta-line {
		font-size: 0.82rem;
		color: var(--nmbp-text-muted, #64748b);
		font-family: var(--nmbp-font-mono, monospace);
	}
	.nmbp-action-btn--lg {
		padding: 7px 12px;
		font-size: 0.82rem;
	}
	.nmbp-staff-contact-phone {
		font-weight: 800;
		font-size: 1rem;
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-staff-contact-email {
		font-size: 0.85rem;
		color: var(--nmbp-text-muted, #64748b);
		margin-top: 3px;
	}
	.nmbp-staff-shift-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	.nmbp-staff-shift-text {
		color: var(--nmbp-accent-teal, #0d9488);
		font-size: 0.95rem;
	}
	.nmbp-staff-salary-val {
		font-size: 1.15rem;
		font-weight: 900;
		font-family: var(--nmbp-font-mono, monospace);
		color: #059669;
	}
	.nmbp-staff-notes-copy {
		font-size: 0.92rem;
		line-height: 1.75;
		color: var(--nmbp-text-muted, #475569);
		margin: 0;
	}
	.nmbp-staff-back-bar {
		margin-top: 2rem;
		border-top: 1px solid var(--nmbp-border-light, #f1f5f9);
		padding-top: 1.25rem;
	}
	.nmbp-btn-back-roster {
		font-weight: 700;
		border-radius: 6px;
	}
	.nmbp-id-card-top {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1rem;
	}
	.nmbp-id-card-label {
		font-size: 0.75rem;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 1px;
		color: #99f6e4;
	}
	.nmbp-id-card-icon {
		fill: rgba(255, 255, 255, 0.7);
	}
	.nmbp-staff-badge-photo--fallback {
		display: flex;
		align-items: center;
		justify-content: center;
		color: #ffffff;
		font-size: 2.2rem;
		font-weight: 900;
	}
	.nmbp-id-card-name {
		font-size: 1.35rem;
		font-weight: 900;
		letter-spacing: 0.02em;
		margin-bottom: 0.25rem;
	}
	.nmbp-id-card-role {
		font-size: 0.85rem;
		color: #99f6e4;
		font-weight: 700;
	}
	.nmbp-id-card-code {
		font-size: 0.8rem;
		color: #cbd5e1;
		font-family: var(--nmbp-font-mono, monospace);
		margin-top: 2px;
	}
	.nmbp-id-card-footer {
		display: flex;
		justify-content: flex-end;
		align-items: center;
		margin-top: 1.75rem;
		border-top: 1px solid rgba(255, 255, 255, 0.15);
		padding-top: 1rem;
	}
</style>

<div class="nmbp-staff-view-grid">

	<!-- Left: Master Dossier Table -->
	<div class="nmbp-staff-main-card">

		<div class="nmbp-staff-head-bar">
			<div>
				<div class="nmbp-staff-status-row">
					<span class="nmbp-badge-pill <?php echo $is_on_duty ? 'active' : 'nmbp-badge-pill--off-duty'; ?>">
						● <?php echo esc_html( $staff->duty_status ?? __( 'Off Duty', 'nahian-movie-booking-plugin' ) ); ?>
					</span>
					<span class="nmbp-voucher-tag nmbp-staff-code-tag">
						<?php echo esc_html( $staff_code ); ?>
					</span>
				</div>
				<h2 class="nmbp-staff-name-heading">
					<?php echo esc_html( $staff->name ?? '' ); ?>
				</h2>
				<span class="nmbp-staff-meta-line">
					<?php esc_html_e( 'Role:', 'nahian-movie-booking-plugin' ); ?> <strong><?php echo esc_html( $staff->role_title ?? '' ); ?></strong> &bull; <?php esc_html_e( 'Joined:', 'nahian-movie-booking-plugin' ); ?> <strong><?php echo esc_html( ! empty( $staff->created_at ) ? wp_date( 'M j, Y', strtotime( $staff->created_at ) ) : __( 'N/A', 'nahian-movie-booking-plugin' ) ); ?></strong>
				</span>
			</div>

			<div class="nmbp-table-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-users&subtab=edit&id=' . $staff_item_id ) ); ?>" class="nmbp-action-btn edit nmbp-action-btn--lg" title="<?php esc_attr_e( 'Edit Staff', 'nahian-movie-booking-plugin' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
					<span><?php esc_html_e( 'Edit Staff', 'nahian-movie-booking-plugin' ); ?></span>
				</a>
				<a href="<?php echo esc_url( $delete_url ); ?>" class="nmbp-action-btn delete nmbp-action-btn--lg nmbp-delete-staff-dossier-link" title="<?php esc_attr_e( 'Delete', 'nahian-movie-booking-plugin' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
					<span><?php esc_html_e( 'Delete', 'nahian-movie-booking-plugin' ); ?></span>
				</a>
			</div>
		</div>

		<!-- Structured Key-Value Table -->
		<table class="nmbp-staff-spec-table">
			<tbody>
				<tr>
					<th>
						<div class="nmbp-table-cell-header">
							<span><?php esc_html_e( 'Contact Information', 'nahian-movie-booking-plugin' ); ?></span>
						</div>
					</th>
					<td>
						<div class="nmbp-staff-contact-phone">
							📞 <?php echo esc_html( ! empty( $staff->phone ) ? $staff->phone : __( 'Not provided', 'nahian-movie-booking-plugin' ) ); ?>
						</div>
						<div class="nmbp-staff-contact-email">
							✉️ <?php echo esc_html( ! empty( $staff->email ) ? $staff->email : __( 'Not provided', 'nahian-movie-booking-plugin' ) ); ?>
						</div>
					</td>
				</tr>

				<tr>
					<th>
						<div class="nmbp-table-cell-header">
							<span><?php esc_html_e( 'Shift & Duty Status', 'nahian-movie-booking-plugin' ); ?></span>
						</div>
					</th>
					<td>
						<div class="nmbp-staff-shift-row">
							<div>
								<strong class="nmbp-staff-shift-text">
									<?php echo esc_html( ! empty( $staff->shift_schedule ) ? $staff->shift_schedule : __( 'Rotational Shift', 'nahian-movie-booking-plugin' ) ); ?>
								</strong>
							</div>
							<span class="nmbp-badge-pill <?php echo $is_on_duty ? 'active' : 'nmbp-badge-pill--off-duty'; ?>">
								● <?php echo esc_html( $staff->duty_status ?? '' ); ?>
							</span>
						</div>
					</td>
				</tr>

				<tr>
					<th>
						<div class="nmbp-table-cell-header">
							<span><?php esc_html_e( 'Monthly Salary', 'nahian-movie-booking-plugin' ); ?></span>
						</div>
					</th>
					<td>
						<div class="nmbp-staff-salary-val">
							<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $staff->salary ?? 0.00 ), 2 ) ); ?>
						</div>
					</td>
				</tr>

				<tr>
					<th>
						<div class="nmbp-table-cell-header">
							<span><?php esc_html_e( 'Notes / Responsibilities', 'nahian-movie-booking-plugin' ); ?></span>
						</div>
					</th>
					<td>
						<p class="nmbp-staff-notes-copy">
							<?php
							$notes = ! empty( $staff->responsibilities )
								? $staff->responsibilities
								: __( 'Standard cinema floor operations, guest welcoming, equipment readiness, and ticket turnstile check-in duties.', 'nahian-movie-booking-plugin' );
							echo nl2br( esc_html( $notes ) );
							?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<div class="nmbp-staff-back-bar">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-users&subtab=all' ) ); ?>" class="button button-secondary nmbp-btn-back-roster">
				&larr; <?php esc_html_e( 'Back to Staff Roster', 'nahian-movie-booking-plugin' ); ?>
			</a>
		</div>

	</div>

	<!-- Right: ID Badge Visual -->
	<div>
		<div class="nmbp-staff-id-card-sidebar">
			<div class="nmbp-id-card-top">
				<div class="nmbp-id-card-label">
					<?php esc_html_e( 'MULTIPLEX CREW ID', 'nahian-movie-booking-plugin' ); ?>
				</div>
				<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true" class="nmbp-id-card-icon"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
			</div>

			<?php if ( ! empty( $staff->photo_url ) ) : ?>
				<img src="<?php echo esc_url( $staff->photo_url ); ?>" alt="<?php echo esc_attr( $staff->name ?? '' ); ?>" class="nmbp-staff-badge-photo">
			<?php else : ?>
				<div class="nmbp-staff-badge-photo nmbp-staff-badge-photo--fallback">
					<?php echo esc_html( strtoupper( substr( (string) ( $staff->name ?? 'SC' ), 0, 2 ) ) ); ?>
				</div>
			<?php endif; ?>

			<div class="nmbp-id-card-name">
				<?php echo esc_html( $staff->name ?? '' ); ?>
			</div>
			<div class="nmbp-id-card-role">
				<?php echo esc_html( $staff->role_title ?? '' ); ?>
			</div>
			<div class="nmbp-id-card-code">
				<?php echo esc_html( $staff_code ); ?>
			</div>

			<div class="nmbp-id-card-footer">
				<span class="nmbp-badge-pill nmbp-badge-pill--compact <?php echo $is_on_duty ? 'active' : 'nmbp-badge-pill--badge-off-duty'; ?>">
					● <?php echo esc_html( $staff->duty_status ?? '' ); ?>
				</span>
			</div>
		</div>
	</div>

</div>

<script id="nmbp-view-user-js">
	(function() {
		document.addEventListener("DOMContentLoaded", function() {
			const confirmMsg = <?php echo wp_json_encode( __( 'Permanently remove this staff record?', 'nahian-movie-booking-plugin' ) ); ?>;
			document.querySelectorAll(".nmbp-delete-staff-dossier-link").forEach(function(link) {
				link.addEventListener("click", function(e) {
					if (!window.confirm(confirmMsg)) {
						e.preventDefault();
					}
				});
			});
		});
	})();
</script>