<?php
/**
 * Customer Module - Subtabs Navigation & Toast Notifications
 *
 * Path: admin/customers/tabs-customers.php
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
$table = $wpdb->prefix . 'nmbp_customers';

$total_customers = isset( $total_customers ) ? (int) $total_customers : (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
$subtab          = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
?>

<style id="nmbp-tabs-customers-css">
	.nmbp-customers-tabs-wrap {
		margin-bottom: 1rem;
	}
	.nmbp-customers-toast {
		margin-bottom: 1rem;
	}
</style>

<!-- TAB NAVIGATION TOOLBAR -->
<div class="nmbp-subtabs-nav-wrapper nmbp-customers-tabs-wrap">
	<nav class="nmbp-subtabs-nav" aria-label="<?php esc_attr_e( 'Customers Subtabs Navigation', 'nahian-movie-booking-plugin' ); ?>">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-customers&subtab=all' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'all' === $subtab || 'view' === $subtab ) ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
			<span><?php esc_html_e( 'All Customers', 'nahian-movie-booking-plugin' ); ?></span>
			<span class="nmbp-tab-badge"><?php echo esc_html( number_format_i18n( $total_customers ) ); ?></span>
		</a>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-customers&subtab=add' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
			<span><?php esc_html_e( 'Add Customer', 'nahian-movie-booking-plugin' ); ?></span>
		</a>

		<?php if ( 'edit' === $subtab ) : ?>
			<span class="nmbp-subtab-btn active" role="tab" aria-selected="true">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
				<span><?php esc_html_e( 'Edit Customer', 'nahian-movie-booking-plugin' ); ?></span>
			</span>
		<?php endif; ?>
	</nav>
</div>

<!-- NOTIFICATION TOAST BANNERS -->
<?php if ( isset( $_GET['customer_added'] ) ) : ?>
	<div class="nmbp-notification-banner success nmbp-customers-toast" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
		</div>
		<div><?php esc_html_e( 'Customer account created successfully!', 'nahian-movie-booking-plugin' ); ?></div>
	</div>
<?php endif; ?>

<?php if ( isset( $_GET['customer_updated'] ) ) : ?>
	<div class="nmbp-notification-banner success nmbp-customers-toast" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
		</div>
		<div><?php esc_html_e( 'Customer details updated successfully!', 'nahian-movie-booking-plugin' ); ?></div>
	</div>
<?php endif; ?>

<?php if ( isset( $_GET['customer_deleted'] ) ) : ?>
	<div class="nmbp-notification-banner danger nmbp-customers-toast" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
		</div>
		<div><?php esc_html_e( 'Customer removed from registry!', 'nahian-movie-booking-plugin' ); ?></div>
	</div>
<?php endif; ?>