<?php
/**
 * Users / Staff Module - All Staff Roster View
 *
 * Path: admin/users/all-users.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$staff_count = ( isset( $staff_members ) && is_array( $staff_members ) ) ? count( $staff_members ) : 0;
?>

<style id="nmbp-all-users-css">
	.nmbp-users-panel {
		margin: 0;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
	}
	.nmbp-users-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 1.25rem 1.5rem;
		border-bottom: 1px solid var(--nmbp-border, #e2e8f0);
	}
	.nmbp-users-title {
		margin: 0 0 4px 0;
		font-size: 1.15rem;
		font-weight: 800;
		color: var(--nmbp-text-main, #0f172a);
	}
	.nmbp-users-icon {
		vertical-align: -4px;
		margin-right: 4px;
	}
	.nmbp-users-subtitle {
		font-size: 0.78rem;
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-btn-add-staff {
		background: #064e3b;
		border-color: #064e3b;
		font-weight: 800;
		display: inline-flex;
		align-items: center;
		gap: 6px;
	}
	.nmbp-users-table-wrap {
		overflow-x: auto;
	}
	.nmbp-users-table {
		width: 100%;
		border-collapse: collapse;
	}
	.nmbp-users-thead-row {
		border-bottom: 1px solid var(--nmbp-border, #e2e8f0);
		background: #f8fafc;
	}
	.nmbp-users-th {
		padding: 10px 12px;
		text-align: left;
	}
	.nmbp-users-th--right {
		padding: 10px 12px;
		text-align: right;
	}
	.nmbp-users-row {
		border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);
	}
	.nmbp-users-td {
		padding: 12px;
	}
	.nmbp-users-td--right {
		padding: 12px;
		text-align: right;
		white-space: nowrap;
	}
	.nmbp-users-muted-sub {
		color: var(--nmbp-text-muted, #64748b);
	}
	.nmbp-users-phone {
		font-family: var(--nmbp-font-mono, monospace);
		font-size: 0.85rem;
		color: #334155;
		font-weight: 700;
	}
	.nmbp-badge-pill--off-duty {
		background: rgba(239, 68, 68, 0.15);
		color: #ef4444;
		border: 1px solid rgba(239, 68, 68, 0.3);
	}
	.nmbp-users-empty-cell {
		text-align: center;
		padding: 2.5rem;
		color: var(--nmbp-text-muted, #64748b);
	}
</style>

<div class="nmbp-panel nmbp-users-panel">
	<div class="nmbp-panel-header nmbp-users-header">
		<div>
			<h3 class="nmbp-panel-title nmbp-users-title">
				<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" class="nmbp-users-icon">
					<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
				</svg>
				<?php
				/* translators: %d: number of staff members */
				printf( esc_html__( 'Staff & Operational Crew (%d)', 'nahian-movie-booking-plugin' ), absint( $staff_count ) );
				?>
			</h3>
			<span class="nmbp-users-subtitle">
				<?php esc_html_e( 'Manage multiplex staff roster, duty status, and payroll.', 'nahian-movie-booking-plugin' ); ?>
			</span>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-users&subtab=add' ) ); ?>" class="button button-primary nmbp-btn-add-staff">
			<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true">
				<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
			</svg>
			<?php esc_html_e( 'Add Staff Member', 'nahian-movie-booking-plugin' ); ?>
		</a>
	</div>

	<div class="nmbp-table-wrap nmbp-users-table-wrap">
		<table class="nmbp-data-table nmbp-users-table">
			<thead>
				<tr class="nmbp-users-thead-row">
					<th class="nmbp-users-th"><?php esc_html_e( 'STAFF MEMBER', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-users-th"><?php esc_html_e( 'ROLE / TITLE', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-users-th"><?php esc_html_e( 'CONTACT', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-users-th"><?php esc_html_e( 'STATUS', 'nahian-movie-booking-plugin' ); ?></th>
					<th class="nmbp-users-th--right"><?php esc_html_e( 'ACTIONS', 'nahian-movie-booking-plugin' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! empty( $staff_members ) && is_array( $staff_members ) ) :
					foreach ( $staff_members as $s ) :
						$item_id    = absint( $s->id );
						$view_url   = admin_url( 'admin.php?page=nmbp-users&subtab=view&id=' . $item_id );
						$edit_url   = admin_url( 'admin.php?page=nmbp-users&subtab=edit&id=' . $item_id );
						$delete_url = wp_nonce_url(
							admin_url( 'admin.php?page=nmbp-users&subtab=delete&id=' . $item_id ),
							'nmbp_delete_user_' . $item_id,
							'_nmbp_nonce'
						);
						$is_on_duty = ( 'On Duty' === ( $s->duty_status ?? '' ) );
						?>
					<tr class="nmbp-users-row">
						<td class="nmbp-users-td">
							<div class="nmbp-staff-avatar-cell">
								<?php if ( ! empty( $s->photo_url ) ) : ?>
									<img src="<?php echo esc_url( $s->photo_url ); ?>" alt="<?php echo esc_attr( $s->name ?? '' ); ?>" class="nmbp-staff-avatar-thumb">
								<?php else : ?>
									<div class="nmbp-staff-avatar-thumb">
										<?php echo esc_html( strtoupper( substr( (string) ( $s->name ?? 'SC' ), 0, 2 ) ) ); ?>
									</div>
								<?php endif; ?>
								<div>
									<strong><?php echo esc_html( $s->name ?? '' ); ?></strong><br>
									<small class="nmbp-users-muted-sub">
										<?php echo esc_html( ! empty( $s->shift_schedule ) ? $s->shift_schedule : __( 'General Shift', 'nahian-movie-booking-plugin' ) ); ?>
									</small>
								</div>
							</div>
						</td>
						<td class="nmbp-users-td">
							<strong><?php echo esc_html( $s->role_title ?? '' ); ?></strong>
						</td>
						<td class="nmbp-users-td">
							<span class="nmbp-users-phone">
								<?php echo esc_html( $s->phone ?? '' ); ?>
							</span><br>
							<small class="nmbp-users-muted-sub">
								<?php echo esc_html( ! empty( $s->email ) ? $s->email : __( 'No email provided', 'nahian-movie-booking-plugin' ) ); ?>
							</small>
						</td>
						<td class="nmbp-users-td">
							<span class="nmbp-badge-pill <?php echo $is_on_duty ? 'active' : 'nmbp-badge-pill--off-duty'; ?>">
								● <?php echo esc_html( $s->duty_status ?? __( 'Off Duty', 'nahian-movie-booking-plugin' ) ); ?>
							</span>
						</td>
						<td class="nmbp-users-td--right">
							<div class="nmbp-table-actions">
								<a href="<?php echo esc_url( $view_url ); ?>" class="nmbp-action-btn view" title="<?php esc_attr_e( 'View', 'nahian-movie-booking-plugin' ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
									<span><?php esc_html_e( 'View', 'nahian-movie-booking-plugin' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="nmbp-action-btn edit" title="<?php esc_attr_e( 'Edit', 'nahian-movie-booking-plugin' ); ?>">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
									<span><?php esc_html_e( 'Edit', 'nahian-movie-booking-plugin' ); ?></span>
								</a>
								<a href="<?php echo esc_url( $delete_url ); ?>" class="nmbp-action-btn delete nmbp-delete-staff-link" title="<?php esc_attr_e( 'Delete', 'nahian-movie-booking-plugin' ); ?>">
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
					<tr>
						<td colspan="5" class="nmbp-users-empty-cell">
							<?php esc_html_e( 'No staff members registered in the multiplex roster.', 'nahian-movie-booking-plugin' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<script id="nmbp-all-users-js">
	(function() {
		document.addEventListener("DOMContentLoaded", function() {
			const confirmMsg = <?php echo wp_json_encode( __( 'Permanently remove this staff member from the system?', 'nahian-movie-booking-plugin' ) ); ?>;
			document.querySelectorAll(".nmbp-delete-staff-link").forEach(function(link) {
				link.addEventListener("click", function(e) {
					if (!window.confirm(confirmMsg)) {
						e.preventDefault();
					}
				});
			});
		});
	})();
</script>