<?php
/**
 * Movie Module - Subtabs Navigation & Toast Notifications
 *
 * Path: admin/movies/tabs-movies.php
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
$table = $wpdb->prefix . 'nmbp_control_movies';

$total_movies = isset( $total_movies ) ? (int) $total_movies : (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
$subtab       = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
?>

<style id="nmbp-tabs-movies-css">
	.nmbp-subtabs-nav-wrapper--space-between {
		justify-content: space-between;
	}
	.nmbp-catalog-engine-status {
		display: flex;
		align-items: center;
		gap: 8px;
		font-size: 0.78rem;
		color: var(--nmbp-text-muted, #64748b);
		font-weight: 700;
	}
	.nmbp-catalog-status-dot {
		display: inline-block;
		width: 8px;
		height: 8px;
		border-radius: 50%;
		background: #059669;
		box-shadow: 0 0 8px #059669;
	}
</style>

<!-- TAB NAVIGATION TOOLBAR -->
<div class="nmbp-subtabs-nav-wrapper nmbp-subtabs-nav-wrapper--space-between">
	<nav class="nmbp-subtabs-nav" aria-label="<?php esc_attr_e( 'Movies Subtabs Navigation', 'nahian-movie-booking-plugin' ); ?>">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-movies&subtab=all' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'all' === $subtab ) ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
			<span><?php esc_html_e( 'All Movies', 'nahian-movie-booking-plugin' ); ?></span>
			<span class="nmbp-tab-badge"><?php echo esc_html( number_format( $total_movies ) ); ?></span>
		</a>

		<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-movies&subtab=add' ) ); ?>" class="nmbp-subtab-btn <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
			<span><?php esc_html_e( 'Add Movie', 'nahian-movie-booking-plugin' ); ?></span>
		</a>

		<?php if ( 'view' === $subtab ) : ?>
			<span class="nmbp-subtab-btn active" role="tab" aria-selected="true">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
				<span><?php esc_html_e( 'Viewing Feature Dossier', 'nahian-movie-booking-plugin' ); ?></span>
			</span>
		<?php endif; ?>

		<?php if ( 'edit' === $subtab ) : ?>
			<span class="nmbp-subtab-btn active" role="tab" aria-selected="true">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
				<span><?php esc_html_e( 'Edit Movie', 'nahian-movie-booking-plugin' ); ?></span>
			</span>
		<?php endif; ?>
	</nav>

	<div class="nmbp-catalog-engine-status">
		<span class="nmbp-catalog-status-dot" aria-hidden="true"></span>
		<span><?php esc_html_e( 'Catalog Engine Online', 'nahian-movie-booking-plugin' ); ?></span>
	</div>
</div>

<!-- NOTIFICATION TOAST BANNERS -->
<?php if ( isset( $_GET['movie_added'] ) ) : ?>
	<div class="nmbp-notification-banner success" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
		</div>
		<div><?php esc_html_e( 'Feature presentation published successfully to catalog!', 'nahian-movie-booking-plugin' ); ?></div>
	</div>
<?php endif; ?>

<?php if ( isset( $_GET['movie_updated'] ) ) : ?>
	<div class="nmbp-notification-banner success" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
		</div>
		<div><?php esc_html_e( 'Movie details and screening timetable updated successfully!', 'nahian-movie-booking-plugin' ); ?></div>
	</div>
<?php endif; ?>

<?php if ( isset( $_GET['movie_deleted'] ) ) : ?>
	<div class="nmbp-notification-banner danger" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
		</div>
		<div><?php esc_html_e( 'Movie record removed from multiplex database!', 'nahian-movie-booking-plugin' ); ?></div>
	</div>
<?php endif; ?>

<?php if ( isset( $_GET['movie_error'] ) ) : ?>
	<div class="nmbp-notification-banner danger" role="alert">
		<div class="nmbp-notification-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
		</div>
		<div>
			<?php
			$err = sanitize_key( wp_unslash( $_GET['movie_error'] ) );
			if ( 'db_failed' === $err ) {
				esc_html_e( 'Database operation failed. Please try again.', 'nahian-movie-booking-plugin' );
			} else {
				esc_html_e( 'An unexpected error occurred while updating the movie catalog.', 'nahian-movie-booking-plugin' );
			}
			?>
		</div>
	</div>
<?php endif; ?>