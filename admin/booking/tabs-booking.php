<?php
/**
 * Booking Module - Subtabs Navigation & Alerts Bar
 *
 * Path: admin/booking/tabs-booking.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'bsc_bookings';
$total_bookings = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );

$subtab = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
?>
<style>
  /* ==========================================================================
     MODERN BOOKING TABS & HEADER TOOLBAR
     ========================================================================== */
  .bsc-subtabs-nav-wrapper {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 14px;
    padding: 10px 14px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.03);
  }

  .bsc-subtabs-nav {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    padding: 5px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
  }

  .bsc-subtab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    color: #64748b;
    font-size: 0.84rem;
    font-weight: 700;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
  }

  .bsc-subtab-btn svg {
    width: 15px;
    height: 15px;
    fill: currentColor;
    transition: transform 0.2s ease;
  }

  .bsc-subtab-btn:hover {
    color: #0f172a;
    background: rgba(255, 255, 255, 0.6);
  }

  .bsc-subtab-btn.active {
    background: #ffffff;
    color: #0d9488;
    border-color: #e2e8f0;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
  }

  .bsc-subtab-btn.active svg {
    transform: scale(1.08);
  }

  /* Counter Badge inside tab */
  .bsc-tab-badge {
    background: #e2e8f0;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 9999px;
    line-height: 1;
    letter-spacing: 0.02em;
    transition: all 0.2s ease;
  }

  .bsc-subtab-btn.active .bsc-tab-badge {
    background: rgba(13, 148, 136, 0.12);
    color: #0d9488;
  }

  /* ==========================================================================
     ALERT / NOTIFICATION TOAST BANNERS
     ========================================================================== */
  .bsc-notification-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 22px;
    font-size: 0.88rem;
    font-weight: 600;
    animation: bscSlideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .bsc-notification-banner.success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
  }

  .bsc-notification-banner.danger {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
  }

  .bsc-notification-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .bsc-notification-banner.success .bsc-notification-icon {
    background: #d1fae5;
    color: #059669;
  }

  .bsc-notification-banner.danger .bsc-notification-icon {
    background: #fee2e2;
    color: #dc2626;
  }

  .bsc-notification-icon svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
  }

  @keyframes bscSlideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>

<!-- TAB NAVIGATION TOOLBAR -->
<div class="bsc-subtabs-nav-wrapper">
  <nav class="bsc-subtabs-nav">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=all' ) ); ?>" class="bsc-subtab-btn <?php echo ( 'all' === $subtab || 'view' === $subtab ) ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
      <span><?php esc_html_e( 'All Bookings', 'bcs-screen-manager' ); ?></span>
      <span class="bsc-tab-badge"><?php echo esc_html( $total_bookings ); ?></span>
    </a>

    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=add' ) ); ?>" class="bsc-subtab-btn <?php echo ( 'add' === $subtab ) ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      <span><?php esc_html_e( 'Add Booking', 'bcs-screen-manager' ); ?></span>
    </a>

    <?php if ( 'edit' === $subtab ) : ?>
      <span class="bsc-subtab-btn active">
        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        <span><?php esc_html_e( 'Edit Booking', 'bcs-screen-manager' ); ?></span>
      </span>
    <?php endif; ?>
  </nav>
</div>

<!-- NOTIFICATION TOAST BANNERS -->
<?php if ( isset( $_GET['booking_added'] ) ) : ?>
  <div class="bsc-notification-banner success">
    <div class="bsc-notification-icon">
      <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    </div>
    <div><?php esc_html_e( 'Walk-in booking issued successfully to customer!', 'bcs-screen-manager' ); ?></div>
  </div>
<?php endif; ?>

<?php if ( isset( $_GET['booking_updated'] ) ) : ?>
  <div class="bsc-notification-banner success">
    <div class="bsc-notification-icon">
      <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    </div>
    <div><?php esc_html_e( 'Booking details updated successfully!', 'bcs-screen-manager' ); ?></div>
  </div>
<?php endif; ?>

<?php if ( isset( $_GET['booking_deleted'] ) ) : ?>
  <div class="bsc-notification-banner danger">
    <div class="bsc-notification-icon">
      <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    </div>
    <div><?php esc_html_e( 'Booking record deleted successfully.', 'bcs-screen-manager' ); ?></div>
  </div>
<?php endif; ?>