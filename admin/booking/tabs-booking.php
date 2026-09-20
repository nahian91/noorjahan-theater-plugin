<?php
/**
 * Booking Module - Subtabs Navigation & Alerts Bar
 *
 * Path: admin/booking/tabs-booking.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
}

global $wpdb;
$table = $wpdb->prefix . 'bsc_bookings';

$total_bookings = isset( $total_bookings ) ? (int) $total_bookings : (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
$subtab         = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
?>

<!-- TAB NAVIGATION TOOLBAR -->
<div class="bsc-subtabs-nav-wrapper" style="justify-content: space-between;">
    <nav class="bsc-subtabs-nav" aria-label="<?php esc_attr_e( 'Booking Subtabs Navigation', 'bcs-screen-manager' ); ?>">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=all' ) ); ?>" class="bsc-subtab-btn <?php echo ( 'all' === $subtab ) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
            <span><?php esc_html_e( 'All Bookings', 'bcs-screen-manager' ); ?></span>
            <span class="bsc-tab-badge"><?php echo esc_html( number_format( $total_bookings ) ); ?></span>
        </a>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=add' ) ); ?>" class="bsc-subtab-btn <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            <span><?php esc_html_e( 'New Counter Booking', 'bcs-screen-manager' ); ?></span>
        </a>

        <?php if ( 'view' === $subtab ) : ?>
            <span class="bsc-subtab-btn active" role="tab" aria-selected="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                <span><?php esc_html_e( 'Viewing Pass', 'bcs-screen-manager' ); ?></span>
            </span>
        <?php endif; ?>

        <?php if ( 'edit' === $subtab ) : ?>
            <span class="bsc-subtab-btn active" role="tab" aria-selected="true">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                <span><?php esc_html_e( 'Edit Booking Pass', 'bcs-screen-manager' ); ?></span>
            </span>
        <?php endif; ?>
    </nav>

    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; color: var(--bsc-text-muted, #64748b); font-weight: 700;">
        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></span>
        <span><?php esc_html_e( 'Capacity: 30 Seats / Slot', 'bcs-screen-manager' ); ?></span>
    </div>
</div>

<!-- NOTIFICATION TOAST BANNERS -->
<?php if ( isset( $_GET['booking_added'] ) ) : ?>
    <div class="bsc-notification-banner success" role="alert">
        <div class="bsc-notification-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <div><?php esc_html_e( 'Walk-in booking issued successfully! Ticket registered to system.', 'bcs-screen-manager' ); ?></div>
    </div>
<?php endif; ?>

<?php if ( isset( $_GET['booking_updated'] ) ) : ?>
    <div class="bsc-notification-banner success" role="alert">
        <div class="bsc-notification-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <div><?php esc_html_e( 'Booking details and seat assignments updated successfully!', 'bcs-screen-manager' ); ?></div>
    </div>
<?php endif; ?>

<?php if ( isset( $_GET['booking_deleted'] ) ) : ?>
    <div class="bsc-notification-banner danger" role="alert">
        <div class="bsc-notification-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 17.59 13.41 12z"/></svg>
        </div>
        <div><?php esc_html_e( 'Booking record and ticket allocation deleted successfully.', 'bcs-screen-manager' ); ?></div>
    </div>
<?php endif; ?>

<?php if ( isset( $_GET['booking_error'] ) ) : 
    $err = sanitize_key( wp_unslash( $_GET['booking_error'] ) );
?>
    <div class="bsc-notification-banner danger" role="alert">
        <div class="bsc-notification-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </div>
        <div>
            <?php 
            if ( 'seat_conflict' === $err ) {
                $conflicts = isset( $_GET['conflicts'] ) ? sanitize_text_field( wp_unslash( $_GET['conflicts'] ) ) : '';
                /* translators: %s: Comma-separated list of conflicting seat identifiers */
                printf( esc_html__( 'Seat conflict detected! Seat(s) [%s] are already booked for this screening slot.', 'bcs-screen-manager' ), esc_html( $conflicts ) );
            } elseif ( 'no_seats' === $err ) {
                esc_html_e( 'No seats selected. Please click on available seats from the 30-seat matrix before generating the ticket.', 'bcs-screen-manager' );
            } else {
                esc_html_e( 'An unexpected error occurred while processing the reservation.', 'bcs-screen-manager' );
            }
            ?>
        </div>
    </div>
<?php endif; ?>