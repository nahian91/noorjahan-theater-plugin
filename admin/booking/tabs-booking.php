<?php
/**
 * Booking Module - Subtabs Navigation & Alerts Bar
 *
 * Path: admin/booking/tabs-booking.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$subtab = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
?>
<style>
    .bsc-subtabs-nav {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--cinema-border, #e2e8f0);
        padding-bottom: 0.75rem;
    }
    .bsc-subtab-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.88rem;
        font-weight: 700;
        text-decoration: none;
        color: var(--cinema-text-muted, #64748b);
        background: transparent;
        transition: all 0.2s ease;
    }
    .bsc-subtab-btn svg {
        width: 16px;
        height: 16px;
        fill: currentColor;
    }
    .bsc-subtab-btn:hover {
        color: var(--cinema-accent-teal, #0d9488);
        background: #f8fafc;
    }
    .bsc-subtab-btn.active {
        color: #ffffff;
        background: #0d9488;
    }
    .bsc-subtab-btn.active svg {
        fill: #ffffff;
    }
    .bsc-alert-notice {
        padding: 12px 18px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
    }
    .bsc-alert-notice svg {
        width: 18px;
        height: 18px;
        fill: currentColor;
        flex-shrink: 0;
    }
    .bsc-alert-success {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }
    .bsc-alert-danger {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
</style>

<!-- Subtabs Navigation -->
<div class="bsc-subtabs-nav">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=all' ) ); ?>" class="bsc-subtab-btn <?php echo ( 'all' === $subtab || 'view' === $subtab ) ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
        <span><?php esc_html_e( 'All Bookings', 'bsc-cinema-manager' ); ?></span>
    </a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=add' ) ); ?>" class="bsc-subtab-btn <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        <span><?php esc_html_e( 'Add Booking', 'bsc-cinema-manager' ); ?></span>
    </a>
</div>

<!-- Alert Notices -->
<?php if ( isset( $_GET['booking_added'] ) ) : ?>
    <div class="bsc-alert-notice bsc-alert-success">
        <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        <span><?php esc_html_e( 'Walk-in booking issued successfully to customer!', 'bsc-cinema-manager' ); ?></span>
    </div>
<?php endif; ?>

<?php if ( isset( $_GET['booking_updated'] ) ) : ?>
    <div class="bsc-alert-notice bsc-alert-success">
        <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        <span><?php esc_html_e( 'Booking details updated successfully!', 'bsc-cinema-manager' ); ?></span>
    </div>
<?php endif; ?>

<?php if ( isset( $_GET['booking_deleted'] ) ) : ?>
    <div class="bsc-alert-notice bsc-alert-danger">
        <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        <span><?php esc_html_e( 'Booking record deleted successfully.', 'bsc-cinema-manager' ); ?></span>
    </div>
<?php endif; ?>