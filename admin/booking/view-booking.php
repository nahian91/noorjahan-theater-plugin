<?php
/**
 * Booking Module - Single Booking Digital Pass & Thermal Voucher View
 *
 * Path: admin/booking/view-booking.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
}

global $wpdb;
$table_bookings = $wpdb->prefix . 'bsc_bookings';
$booking_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$view_booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $booking_id ) );

if ( ! $view_booking ) { 
    echo '<div class="notice notice-error" style="padding:15px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; color:#ef4444; font-weight:bold;"><p>' . esc_html__( 'Booking record not found.', 'bsc-cinema-manager' ) . '</p></div>'; 
    return; 
}

// Format friendly screening and transaction timestamps
$booking_ts            = ! empty( $view_booking->booking_date ) ? strtotime( $view_booking->booking_date ) : time();
$friendly_booking_date = date_i18n( 'd M Y - h:i A', $booking_ts );
$currency_symbol       = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

// Format Showtime cleanly to 12-Hour AM/PM format (e.g. 17:00 -> 05:00 PM)
$raw_st = trim( (string) $view_booking->showtime );
$formatted_showtime = $raw_st;
if ( ! empty( $raw_st ) ) {
    if ( false !== stripos( $raw_st, 'AM' ) || false !== stripos( $raw_st, 'PM' ) ) {
        $formatted_showtime = $raw_st;
    } elseif ( false !== strpos( $raw_st, ':' ) ) {
        $parts = explode( ':', $raw_st );
        $hour  = (int) $parts[0];
        $min   = isset( $parts[1] ) ? substr( $parts[1], 0, 2 ) : '00';
        $ampm  = $hour >= 12 ? 'PM' : 'AM';
        $h12   = $hour % 12 ?: 12;
        $formatted_showtime = sprintf( '%02d:%s %s', $h12, $min, $ampm );
    }
}

// Parse and clean seats
$seats_list = array_filter( array_map( 'trim', explode( ',', (string) $view_booking->seats ) ) );
?>

<style>
  .bsc-view-layout-shell {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    align-items: start;
    max-width: 1140px;
    margin: 10px auto 60px auto;
  }
  .bsc-pass-main-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: var(--cinema-radius-md, 14px);
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
    overflow: hidden;
  }
  .bsc-pass-banner {
    background: linear-gradient(135deg, #09171c 0%, #063d35 60%, #0d9488 100%);
    padding: 1.75rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    color: #ffffff;
  }
  .bsc-pass-banner-badge {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.4);
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
  .bsc-pass-content-body {
    padding: 1.75rem 2rem;
  }
  .bsc-detail-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
  }
  .bsc-detail-table th {
    width: 220px;
    text-align: left;
    padding: 1.1rem 0.85rem;
    border-bottom: 1px solid var(--cinema-border-light, #f1f5f9);
    vertical-align: middle;
  }
  .bsc-detail-table td {
    padding: 1.1rem 0.85rem;
    border-bottom: 1px solid var(--cinema-border-light, #f1f5f9);
    vertical-align: middle;
  }
  .bsc-table-cell-header {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--cinema-text-muted, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .bsc-table-icon-badge {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #f1f5f9;
    color: var(--cinema-accent-teal, #0d9488);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bsc-table-icon-badge svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
  }
  .bsc-seat-chip {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 6px;
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    font-size: 0.82rem;
    font-weight: 800;
    font-family: var(--cinema-font-mono, monospace);
  }
  .bsc-pass-thermal-box {
    background: #ffffff;
    border: 2px dashed #0d9488;
    border-radius: var(--cinema-radius-md, 14px);
    padding: 1.6rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
    position: relative;
  }
  .bsc-pass-thermal-box::before, .bsc-pass-thermal-box::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: var(--cinema-bg-canvas, #f0f4f8);
    border-radius: 50%;
    top: 96px;
  }
  .bsc-pass-thermal-box::before { left: -11px; }
  .bsc-pass-thermal-box::after { right: -11px; }
  .bsc-bill-table {
    width: 100%;
    border-collapse: collapse;
  }
  .bsc-bill-table td {
    padding: 8px 0;
    font-size: 0.84rem;
    border-bottom: 1px dashed var(--cinema-border-light, #f1f5f9);
  }
  .bsc-bill-table tr.grand-row td {
    border-top: 2px solid var(--cinema-border, #e2e8f0);
    border-bottom: none;
    padding-top: 12px;
    font-size: 1rem;
  }
  .bsc-pass-barcode-strip {
    height: 40px;
    background: repeating-linear-gradient(90deg, #0f172a 0px, #0f172a 2px, transparent 2px, transparent 4px, #0f172a 4px, #0f172a 6px, transparent 6px, transparent 8px);
    margin: 1.25rem 0 6px 0;
    border-radius: 4px;
    opacity: 0.85;
  }
  @media (max-width: 900px) {
    .bsc-view-layout-shell { grid-template-columns: 1fr; }
  }
  @media print {
    .bsc-sidebar, .bsc-subtabs-nav, .bsc-action-footer-bar, .bsc-pass-main-card { display: none !important; }
    .bsc-view-layout-shell { display: block !important; margin: 0 !important; width: 100% !important; }
    .bsc-pass-thermal-box { border: 1px solid #000 !important; box-shadow: none !important; max-width: 320px !important; margin: 0 auto !important; }
    .bsc-pass-thermal-box::before, .bsc-pass-thermal-box::after { display: none !important; }
  }
</style>

<div class="bsc-view-layout-shell">
  
  <!-- Left Column: Master Digital Pass Container -->
  <div class="bsc-pass-main-card">
    
    <!-- Banner Header -->
    <div class="bsc-pass-banner">
      <div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
          <span class="bsc-pass-banner-badge">
            ● <?php echo esc_html( $view_booking->payment_status ?: 'Confirmed' ); ?>
          </span>
          <span style="font-size:0.75rem; color:#38bdf8; font-family:var(--cinema-font-mono, monospace); font-weight:800;">
            <?php echo esc_html( $view_booking->booking_token ); ?>
          </span>
        </div>
        <h2 style="font-size:1.6rem; font-weight:900; color:#fff; margin:0; letter-spacing:-0.02em;">
          <?php echo esc_html( $view_booking->movie_title ); ?>
        </h2>
        <div style="font-size:0.82rem; color:#cbd5e1; margin-top:4px; font-weight:600;">
          <?php echo esc_html( $view_booking->ticket_type ?: 'Standard' ); ?> Pass Admission
        </div>
      </div>

      <div style="text-align: right;">
        <div style="font-size:0.7rem; color:#94a3b8; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Settlement</div>
        <div style="font-size:1.45rem; font-weight:900; font-family:var(--cinema-font-mono, monospace); color:#34d399; margin-top:2px;">
          <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $view_booking->total_amount, 2 ) ); ?>
        </div>
      </div>
    </div>

    <!-- Body Details -->
    <div class="bsc-pass-content-body">
      <table class="bsc-detail-table">
        <tbody>
          <!-- Customer Profile -->
          <tr>
            <th>
              <div class="bsc-table-cell-header">
                <div class="bsc-table-icon-badge">
                  <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <span><?php esc_html_e( 'Customer Profile', 'bsc-cinema-manager' ); ?></span>
              </div>
            </th>
            <td>
              <div style="font-weight:800; font-size:1rem; color:var(--cinema-text-main, #0f172a);">
                <?php echo esc_html( $view_booking->customer_name ?: 'Walk-In Guest' ); ?>
              </div>
              <div style="font-size:0.82rem; color:var(--cinema-text-muted, #64748b); margin-top:4px;">
                <span>📞 <?php echo esc_html( $view_booking->customer_phone ?: 'No phone provided' ); ?></span>
                <?php if ( ! empty( $view_booking->customer_email ) ) : ?>
                  <span style="margin-left:12px;">✉️ <?php echo esc_html( $view_booking->customer_email ); ?></span>
                <?php endif; ?>
              </div>
            </td>
          </tr>

          <!-- Screening Slot & Showtime -->
          <tr>
            <th>
              <div class="bsc-table-cell-header">
                <div class="bsc-table-icon-badge">
                  <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                </div>
                <span><?php esc_html_e( 'Screening Slot', 'bsc-cinema-manager' ); ?></span>
              </div>
            </th>
            <td>
              <div style="display:flex; align-items:center; gap:8px;">
                <span style="background:#0f172a; color:#f8fafc; padding:3px 10px; border-radius:6px; font-weight:800; font-size:0.8rem; font-family:var(--cinema-font-mono, monospace);">
                  ⏰ <?php echo esc_html( $formatted_showtime ); ?>
                </span>
              </div>
              <div style="font-size:0.82rem; color:var(--cinema-text-muted, #64748b); margin-top:5px;">
                <?php esc_html_e( 'Booked for:', 'bsc-cinema-manager' ); ?> <strong><?php echo esc_html( $friendly_booking_date ); ?></strong>
              </div>
            </td>
          </tr>

          <!-- Reserved Seats -->
          <tr>
            <th>
              <div class="bsc-table-cell-header">
                <div class="bsc-table-icon-badge">
                  <svg viewBox="0 0 24 24"><path d="M4 18v3h3v-3h10v3h3v-3h1a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H3a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h1zM5 8h14v6H5V8z"/></svg>
                </div>
                <span><?php esc_html_e( 'Reserved Seats', 'bsc-cinema-manager' ); ?></span>
              </div>
            </th>
            <td>
              <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                <?php if ( ! empty( $seats_list ) ) : ?>
                  <?php foreach ( $seats_list as $seat_item ) : ?>
                    <span class="bsc-seat-chip"><?php echo esc_html( $seat_item ); ?></span>
                  <?php endforeach; ?>
                  <span style="font-size:0.75rem; color:var(--cinema-text-muted, #64748b); margin-left:6px; font-weight:700;">
                    (<?php echo count( $seats_list ); ?> <?php esc_html_e( 'Seats Total', 'bsc-cinema-manager' ); ?>)
                  </span>
                <?php else : ?>
                  <span style="color:#94a3b8; font-size:0.85rem;"><?php esc_html_e( 'No seats assigned', 'bsc-cinema-manager' ); ?></span>
                <?php endif; ?>
              </div>
            </td>
          </tr>

          <!-- Payment Details -->
          <tr>
            <th>
              <div class="bsc-table-cell-header">
                <div class="bsc-table-icon-badge">
                  <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                </div>
                <span><?php esc_html_e( 'Payment Gateway', 'bsc-cinema-manager' ); ?></span>
              </div>
            </th>
            <td>
              <div style="display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:8px;">
                  <span style="font-weight:800; font-size:0.92rem; color:var(--cinema-text-main, #0f172a);">
                    <?php echo esc_html( $view_booking->payment_method ?: 'Cash Desk' ); ?>
                  </span>
                  <span style="font-size:0.75rem; background:#f1f5f9; padding:2px 8px; border-radius:4px; font-weight:700; color:#475569;">
                    <?php echo esc_html( $view_booking->payment_status ?: 'Confirmed' ); ?>
                  </span>
                </div>
                <div style="font-size:1.15rem; font-weight:900; font-family:var(--cinema-font-mono, monospace); color:#059669;">
                  <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $view_booking->total_amount, 2 ) ); ?>
                </div>
              </div>
            </td>
          </tr>

          <!-- Reference Token -->
          <tr>
            <th>
              <div class="bsc-table-cell-header">
                <div class="bsc-table-icon-badge">
                  <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
                </div>
                <span><?php esc_html_e( 'Voucher Token', 'bsc-cinema-manager' ); ?></span>
              </div>
            </th>
            <td>
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <code style="background:#f8fafc; border:1px solid var(--cinema-border, #cbd5e1); padding:4px 10px; border-radius:6px; font-weight:800; color:#0f172a;">
                  <?php echo esc_html( $view_booking->booking_token ); ?>
                </code>
                <span style="font-size:0.78rem; color:#059669; font-weight:800;">
                  ✓ <?php esc_html_e( 'Turnstile Authorized', 'bsc-cinema-manager' ); ?>
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Action Footer -->
      <div class="bsc-action-footer-bar" style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--cinema-border-light, #f1f5f9); padding-top:1.5rem;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=all' ) ); ?>" class="button button-secondary" style="font-weight:700; border-radius:6px;">
          &larr; <?php esc_html_e( 'Back to All Bookings', 'bsc-cinema-manager' ); ?>
        </a>
        
        <div style="display:flex; gap:10px;">
          <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=edit&id=' . $view_booking->id ) ); ?>" class="button button-primary" style="background:#0284c7; border-color:#0284c7; font-weight:800; border-radius:6px;">
            ✏️ <?php esc_html_e( 'Edit Record', 'bsc-cinema-manager' ); ?>
          </a>
          <button type="button" class="button button-secondary" onclick="window.print()" style="font-weight:800; border-radius:6px;">
            🖨️ <?php esc_html_e( 'Print Pass', 'bsc-cinema-manager' ); ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Column: Thermal Voucher Preview -->
  <div>
    <div class="bsc-pass-thermal-box">
      <div style="text-align:center; border-bottom:1px dashed #cbd5e1; padding-bottom:1rem; margin-bottom:1rem;">
        <div style="font-weight:900; font-size:1.15rem; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;">
          <?php echo esc_html( get_option( 'bsc_theater_name', 'Big Screen Cinemas' ) ); ?>
        </div>
        <span style="font-size:0.7rem; color:var(--cinema-text-muted, #64748b); font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">
          <?php esc_html_e( 'Admission Voucher', 'bsc-cinema-manager' ); ?>
        </span>
      </div>

      <table class="bsc-bill-table" style="margin-bottom:0;">
        <tbody>
          <tr>
            <td style="color:#64748b;"><?php esc_html_e( 'Token', 'bsc-cinema-manager' ); ?></td>
            <td style="text-align:right; font-family:var(--cinema-font-mono, monospace); font-weight:800; color:#0f172a;"><?php echo esc_html( $view_booking->booking_token ); ?></td>
          </tr>
          <tr>
            <td style="color:#64748b;"><?php esc_html_e( 'Movie', 'bsc-cinema-manager' ); ?></td>
            <td style="text-align:right; font-weight:800; color:#0f172a;"><?php echo esc_html( $view_booking->movie_title ); ?></td>
          </tr>
          <tr>
            <td style="color:#64748b;"><?php esc_html_e( 'Showtime', 'bsc-cinema-manager' ); ?></td>
            <td style="text-align:right; font-weight:800; color:#0284c7; font-family:var(--cinema-font-mono, monospace);"><?php echo esc_html( $formatted_showtime ); ?></td>
          </tr>
          <tr>
            <td style="color:#64748b;"><?php esc_html_e( 'Date', 'bsc-cinema-manager' ); ?></td>
            <td style="text-align:right; font-weight:700; color:#0f172a;"><?php echo esc_html( date_i18n( 'd M Y', $booking_ts ) ); ?></td>
          </tr>
          <tr>
            <td style="color:#64748b;"><?php esc_html_e( 'Seats', 'bsc-cinema-manager' ); ?></td>
            <td style="text-align:right; font-family:var(--cinema-font-mono, monospace); font-weight:800; color:#059669;"><?php echo esc_html( $view_booking->seats ); ?></td>
          </tr>
          <tr>
            <td style="color:#64748b;"><?php esc_html_e( 'Method', 'bsc-cinema-manager' ); ?></td>
            <td style="text-align:right; font-weight:700;"><?php echo esc_html( $view_booking->payment_method ?: 'Cash' ); ?></td>
          </tr>
          <tr class="grand-row">
            <td style="color:#0f172a; font-weight:900;"><?php esc_html_e( 'Total Paid', 'bsc-cinema-manager' ); ?></td>
            <td style="text-align:right; color:#059669; font-family:var(--cinema-font-mono, monospace); font-weight:900;">
              <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $view_booking->total_amount, 2 ) ); ?>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="bsc-pass-barcode-strip"></div>
      <div style="text-align:center; font-size:0.72rem; color:#64748b; font-family:var(--cinema-font-mono, monospace); font-weight:700;">
        * <?php echo esc_html( $view_booking->booking_token ); ?> *
      </div>
    </div>
  </div>

</div>