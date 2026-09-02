<?php
if (!defined('ABSPATH')) exit;

$view_booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_bookings WHERE id = %d", $booking_id));

if (!$view_booking) { 
  echo '<div class="notice notice-error" style="padding:15px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; color:#ef4444; font-weight:bold;"><p>' . esc_html__('Booking record not found.', 'big-screen-cinemas') . '</p></div>'; 
  return; 
}
?>
<div class="bsc-view-layout-shell">
  
  <!-- Left Column: Master Digital Pass Container -->
  <div class="bsc-pass-main-card">
    
    <!-- Banner Header -->
    <div class="bsc-pass-banner">
      <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
          <span class="bsc-pass-banner-badge">
            ● <?php echo esc_html($view_booking->payment_status); ?>
          </span>
          <span style="font-size:0.75rem; color:#99f6e4; font-family:var(--cinema-font-mono); font-weight:800;">
            <?php echo esc_html($view_booking->booking_token); ?>
          </span>
        </div>
        <h2 style="font-size:1.65rem; font-weight:900; color:#fff; margin:0;">
          <?php echo esc_html($view_booking->movie_title); ?>
        </h2>
        <div style="font-size:0.85rem; color:#cbd5e1; margin-top:3px;">
          <?php echo esc_html($view_booking->auditorium); ?> • <?php echo esc_html($view_booking->ticket_type); ?> Experience
        </div>
      </div>

      <div style="text-align: right;">
        <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">Settlement</div>
        <div style="font-size: 1.65rem; font-weight: 900; font-family: var(--cinema-font-mono); color: #f59e0b;">
          <?php echo esc_html($currency_symbol); ?> <?php echo number_format($view_booking->total_amount, 2); ?>
        </div>
      </div>
    </div>

    <!-- Body Details -->
    <div class="bsc-pass-content-body">
      
      <!-- Structured Information Table with SVG Icons -->
      <div class="bsc-view-table-wrapper">
        <table class="bsc-detail-table">
          <tbody>
            <tr>
              <th>
                <div class="bsc-table-cell-header">
                  <div class="bsc-table-icon-badge">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                  </div>
                  <span><?php esc_html_e('Customer Profile', 'big-screen-cinemas'); ?></span>
                </div>
              </th>
              <td>
                <div style="font-weight: 800; font-size: 1.05rem; color: var(--cinema-text-main);">
                  <?php echo esc_html($view_booking->customer_name ?: 'Walk-In Guest'); ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--cinema-text-muted); margin-top: 3px;">
                  <span>📞 <?php echo esc_html($view_booking->customer_phone ?: 'No phone provided'); ?></span>
                  <?php if (!empty($view_booking->customer_email)): ?>
                    <span style="margin-left: 10px;">✉️ <?php echo esc_html($view_booking->customer_email); ?></span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>

            <tr>
              <th>
                <div class="bsc-table-cell-header">
                  <div class="bsc-table-icon-badge">
                    <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                  </div>
                  <span><?php esc_html_e('Screening & Slot', 'big-screen-cinemas'); ?></span>
                </div>
              </th>
              <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span class="cinema-badge-pill active" style="font-size: 0.8rem; font-weight: 800;">
                    <?php echo esc_html($view_booking->showtime ?: 'Counter Open Slot'); ?>
                  </span>
                  <strong style="color: var(--cinema-text-main); font-size: 0.95rem;">
                    <?php echo esc_html($view_booking->auditorium); ?>
                  </strong>
                </div>
                <div style="font-size: 0.82rem; color: var(--cinema-text-muted); margin-top: 4px;">
                  <?php esc_html_e('Booked on:', 'big-screen-cinemas'); ?> <?php echo esc_html(date_i18n('l, jS F Y - h:i A', strtotime($view_booking->booking_date))); ?>
                </div>
              </td>
            </tr>

            <tr>
              <th>
                <div class="bsc-table-cell-header">
                  <div class="bsc-table-icon-badge">
                    <svg viewBox="0 0 24 24"><path d="M4 18v3h3v-3h10v3h3v-3h1a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H3a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h1zM5 8h14v6H5V8z"/></svg>
                  </div>
                  <span><?php esc_html_e('Reserved Seats', 'big-screen-cinemas'); ?></span>
                </div>
              </th>
              <td>
                <div style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                  <?php 
                    $s_list = explode(',', $view_booking->seats);
                    foreach ($s_list as $seat_item):
                      $trimmed = trim($seat_item);
                      if (!empty($trimmed)):
                  ?>
                    <span class="bsc-seat-chip" style="font-size: 0.88rem; padding: 0.35rem 0.75rem; background: #0d9488; color: #fff;">
                      <?php echo esc_html($trimmed); ?>
                    </span>
                  <?php endif; endforeach; ?>
                  <span style="font-size: 0.8rem; color: var(--cinema-text-muted); margin-left: 6px;">
                    (<?php echo count(array_filter(array_map('trim', $s_list))); ?> <?php esc_html_e('Seats Total', 'big-screen-cinemas'); ?>)
                  </span>
                </div>
              </td>
            </tr>

            <tr>
              <th>
                <div class="bsc-table-cell-header">
                  <div class="bsc-table-icon-badge">
                    <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                  </div>
                  <span><?php esc_html_e('Payment Gateway', 'big-screen-cinemas'); ?></span>
                </div>
              </th>
              <td>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-weight: 800; font-size: 0.95rem; color: var(--cinema-text-main);">
                      <?php echo esc_html($view_booking->payment_method ?: 'Cash Desk'); ?>
                    </span>
                    <span class="cinema-badge-payment" style="font-size: 0.75rem;">
                      <?php echo esc_html($view_booking->payment_status); ?>
                    </span>
                  </div>
                  <div style="font-size: 1.15rem; font-weight: 900; font-family: var(--cinema-font-mono); color: var(--cinema-accent-teal);">
                    <?php echo esc_html($currency_symbol); ?> <?php echo number_format($view_booking->total_amount, 2); ?>
                  </div>
                </div>
              </td>
            </tr>

            <tr>
              <th>
                <div class="bsc-table-cell-header">
                  <div class="bsc-table-icon-badge">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
                  </div>
                  <span><?php esc_html_e('Transaction Ledger', 'big-screen-cinemas'); ?></span>
                </div>
              </th>
              <td>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <div>
                    <span style="font-size: 0.75rem; color: var(--cinema-text-muted); font-weight: 700; text-transform: uppercase;"><?php esc_html_e('Reference Voucher Token', 'big-screen-cinemas'); ?>:</span>
                    <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 800; color: #0f172a; margin-left: 4px;">
                      <?php echo esc_html($view_booking->booking_token); ?>
                    </code>
                  </div>
                  <span style="font-size: 0.8rem; color: #10b981; font-weight: 800;">
                    ✓ <?php esc_html_e('Turnstile Authorized', 'big-screen-cinemas'); ?>
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Action Footer -->
      <div class="bsc-pass-actions-footer">
        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=all')); ?>" class="button button-secondary" style="font-weight:700; display:inline-flex; align-items:center; gap:6px;">
          ← <?php esc_html_e('Back to All Bookings', 'big-screen-cinemas'); ?>
        </a>
        
        <div style="display: flex; gap: 8px;">
          <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=edit&id=' . $view_booking->id)); ?>" class="button button-primary" style="background:#0d9488; border-color:#0d9488; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            <?php esc_html_e('Edit Record', 'big-screen-cinemas'); ?>
          </a>
          <button type="button" class="button button-secondary" onclick="window.print()" style="font-weight:800; display:inline-flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
            <?php esc_html_e('Print Admission Pass', 'big-screen-cinemas'); ?>
          </button>
        </div>
      </div>

    </div>

  </div>

  <!-- Right Column: Thermal Voucher Preview -->
  <div>
    <div class="bsc-pass-thermal-box">
      <div style="text-align: center; border-bottom: 1px dashed var(--cinema-border); padding-bottom: 1rem; margin-bottom: 1rem;">
        <div style="font-weight: 900; font-size: 1.15rem; color: #0f172a; text-transform: uppercase; letter-spacing: 1px;">
          <?php echo esc_html(get_option('bsc_theater_name', 'Big Screen Cinemas')); ?>
        </div>
        <span style="font-size: 0.72rem; color: var(--cinema-text-muted); font-weight: 700; text-transform: uppercase;">
          <?php esc_html_e('Gate Admission Ticket', 'big-screen-cinemas'); ?>
        </span>
      </div>

      <table class="bsc-bill-table" style="margin-bottom:0;">
        <tbody>
          <tr>
            <td style="color:var(--cinema-text-muted);"><?php esc_html_e('Token', 'big-screen-cinemas'); ?></td>
            <td style="text-align:right; font-family:var(--cinema-font-mono); font-weight:800; color:var(--cinema-text-main);"><?php echo esc_html($view_booking->booking_token); ?></td>
          </tr>
          <tr>
            <td style="color:var(--cinema-text-muted);"><?php esc_html_e('Auditorium', 'big-screen-cinemas'); ?></td>
            <td style="text-align:right; font-weight:700;"><?php echo esc_html($view_booking->auditorium); ?></td>
          </tr>
          <tr>
            <td style="color:var(--cinema-text-muted);"><?php esc_html_e('Showtime', 'big-screen-cinemas'); ?></td>
            <td style="text-align:right; font-weight:800; color:var(--cinema-accent-teal);"><?php echo esc_html($view_booking->showtime); ?></td>
          </tr>
          <tr>
            <td style="color:var(--cinema-text-muted);"><?php esc_html_e('Seats', 'big-screen-cinemas'); ?></td>
            <td style="text-align:right; font-family:var(--cinema-font-mono); font-weight:900; color:#d97706;"><?php echo esc_html($view_booking->seats); ?></td>
          </tr>
          <tr>
            <td style="color:var(--cinema-text-muted);"><?php esc_html_e('Method', 'big-screen-cinemas'); ?></td>
            <td style="text-align:right; font-weight:700;"><?php echo esc_html($view_booking->payment_method); ?></td>
          </tr>
          <tr class="grand-row">
            <td style="color:var(--cinema-text-main); font-weight:900;"><?php esc_html_e('Total Paid', 'big-screen-cinemas'); ?></td>
            <td style="text-align:right; color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono); font-weight:900;">
              <?php echo esc_html($currency_symbol); ?> <?php echo number_format($view_booking->total_amount, 2); ?>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="bsc-pass-barcode-strip"></div>
      <div style="text-align:center; font-size:0.7rem; color:var(--cinema-text-muted); font-family:var(--cinema-font-mono);">
        * <?php echo esc_html($view_booking->booking_token); ?> *
      </div>
    </div>
  </div>

</div>