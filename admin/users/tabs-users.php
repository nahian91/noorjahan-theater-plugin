<?php
/**
 * Users / Staff Module - Subtabs Navigation, Toast Notifications & KPI Deck
 *
 * Path: admin/users/tabs-users.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_staff = $wpdb->prefix . 'nmbp_staff_crew';

$total_staff   = isset( $total_staff ) ? (int) $total_staff : (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_staff}" );
$on_duty_count = isset( $on_duty_count ) ? (int) $on_duty_count : (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(id) FROM {$table_staff} WHERE duty_status = %s",
		'On Duty'
	)
);
$tech_count    = isset( $tech_count ) ? (int) $tech_count : (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(id) FROM {$table_staff} WHERE role_title LIKE %s OR role_title LIKE %s",
		'%' . $wpdb->esc_like( 'Projection' ) . '%',
		'%' . $wpdb->esc_like( 'Tech' ) . '%'
	)
);

$subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
?>

<style id="nmbp-users-tabs-css">
	.nmbp-staff-kpi-unit {
		font-size: 0.85rem;
		color: var(--nmbp-text-muted, #64748b);
		font-weight: 700;
	}
	.nmbp-staff-kpi-unit--active {
		font-size: 0.85rem;
		color: #10b981;
		font-weight: 700;
	}
	.nmbp-staff-kpi-val--active {
		color: #059669;
	}
	.nmbp-staff-kpi-val--tech {
		color: #d97706;
	}
	.nmbp-staff-icon-bubble--crew {
		background: #eff6ff;
		color: #3b82f6;
	}
	.nmbp-staff-icon-bubble--active {
		background: #ecfdf5;
		color: #10b981;
	}
	.nmbp-staff-icon-bubble--tech {
		background: #fef3c7;
		color: #d97706;
	}
</style>

<!-- TAB NAVIGATION TOOLBAR -->
<div class="nmbp-subtabs-nav-wrapper">
	<nav class="nmbp-subtabs-nav" aria-label="<?php esc_attr_e( 'Staff Subtabs Navigation', 'nahian-movie-booking-plugin' ); ?>">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-users&subtab=all' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'all' === $subtab || 'view' === $subtab ) ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
			<span><?php esc_html_e( 'All Staff & Crew', 'nahian-movie-booking-plugin' ); ?></span>
			<span class="nmbp-tab-badge"><?php echo esc_html( (string) $total_staff ); ?></span>
		</a>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-users&subtab=add' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
			<span><?php esc_html_e( 'Add Staff Member', 'nahian-movie-booking-plugin' ); ?></span>
		</a>

		<?php if ( 'edit' === $subtab ) : ?>
			<span class="nmbp-subtab-btn active" role="tab" aria-selected="true">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
				<span><?php esc_html_e( 'Edit Staff Member', 'nahian-movie-booking-plugin' ); ?></span>
			</span>
		<?php endif; ?>
	</nav>
</div>

<!-- NOTIFICATION TOAST BANNERS -->
<?php if ( isset( $_GET['staff_added'] ) ) : ?>
	<div class="nmbp-notification-banner success" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
		</div>
		<div><?php esc_html_e( 'Staff member added to multiplex personnel records!', 'nahian-movie-booking-plugin' ); ?></div>
	</div>
<?php endif; ?>

<?php if ( isset( $_GET['staff_updated'] ) ) : ?>
	<div class="nmbp-notification-banner success" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
		</div>
		<div><?php esc_html_e( 'Staff profile & operational role updated!', 'nahian-movie-booking-plugin' ); ?></div>
	</div>
<?php endif; ?>

<?php if ( isset( $_GET['staff_deleted'] ) ) : ?>
	<div class="nmbp-notification-banner danger" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
		</div>
		<div><?php esc_html_e( 'Staff record removed from roster.', 'nahian-movie-booking-plugin' ); ?></div>
	</div>
<?php endif; ?>

<!-- STAFF DUTY OVERVIEW KPI DECK -->
<div class="nmbp-staff-kpi-grid">
	<div class="nmbp-staff-kpi-card">
		<div>
			<div class="label"><?php esc_html_e( 'Total Registered Staff', 'nahian-movie-booking-plugin' ); ?></div>
			<div class="val">
				<?php echo esc_html( number_format( (float) $total_staff ) ); ?>
				<span class="nmbp-staff-kpi-unit">
					<?php esc_html_e( 'Crew', 'nahian-movie-booking-plugin' ); ?>
				</span>
			</div>
		</div>
		<div class="nmbp-staff-icon-bubble nmbp-staff-icon-bubble--crew">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
		</div>
	</div>

	<div class="nmbp-staff-kpi-card">
		<div>
			<div class="label"><?php esc_html_e( 'Currently Active On Duty', 'nahian-movie-booking-plugin' ); ?></div>
			<div class="val nmbp-staff-kpi-val--active">
				<?php echo esc_html( number_format( (float) $on_duty_count ) ); ?>
				<span class="nmbp-staff-kpi-unit--active">
					<?php esc_html_e( 'Present', 'nahian-movie-booking-plugin' ); ?>
				</span>
			</div>
		</div>
		<div class="nmbp-staff-icon-bubble nmbp-staff-icon-bubble--active">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
		</div>
	</div>

	<div class="nmbp-staff-kpi-card">
		<div>
			<div class="label"><?php esc_html_e( 'Projection & Technical Crew', 'nahian-movie-booking-plugin' ); ?></div>
			<div class="val nmbp-staff-kpi-val--tech">
				<?php echo esc_html( number_format( (float) $tech_count ) ); ?>
				<span class="nmbp-staff-kpi-unit">
					<?php esc_html_e( 'Techs', 'nahian-movie-booking-plugin' ); ?>
				</span>
			</div>
		</div>
		<div class="nmbp-staff-icon-bubble nmbp-staff-icon-bubble--tech">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
		</div>
	</div>
</div>