<?php
if (!defined('ABSPATH')) exit;

$customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_customers WHERE id = %d", $customer_id));
if (!$customer) {
  echo '<div class="cinema-panel"><p>' . esc_html__('Customer record not found.', 'big-screen-cinemas') . '</p></div>';
  return;
}

// Fetch matching historical bookings by customer email or phone
$customer_history = array();
if (!empty($customer->email) || !empty($customer->phone)) {
  $customer_history = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_bookings WHERE (customer_email = %s AND customer_email != '') OR (customer_phone = %s AND customer_phone != '') ORDER BY id DESC",
    $customer->email,
    $customer->phone
  ));
}
?>
<style>
  .bsc-customer-view-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    align-items: start;
  }
  .bsc-customer-main-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    overflow: hidden;
  }
  .bsc-customer-banner-head {
    background: linear-gradient(135deg, #071115 0%, #092329 45%, #064e3b 100%);
    padding: 2.25rem 2.5rem;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    overflow: hidden;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }
  .bsc-customer-banner-head::after {
    content: '';
    position: absolute;
    right: -50px;
    bottom: -50px;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(13, 148, 136, 0.35) 0%, transparent 70%);
    pointer-events: none;
  }
  .bsc-customer-banner-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.85rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: rgba(13, 148, 136, 0.2);
    color: #5eead4;
    border: 1px solid rgba(13, 148, 136, 0.45);
  }
  .bsc-banner-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(0, 0, 0, 0.25);
    padding: 6px;
    border-radius: 10px;
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255, 255, 255, 0.1);
  }
  .bsc-banner-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    line-height: 1;
  }
  .bsc-banner-btn svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
  }
  .bsc-banner-btn.edit {
    background: rgba(254, 240, 138, 0.15);
    border-color: rgba(254, 240, 138, 0.3);
    color: #fef08a;
  }
  .bsc-banner-btn.edit:hover {
    background: #eab308;
    color: #000000;
    border-color: #eab308;
  }
  .bsc-banner-btn.delete {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #fca5a5;
  }
  .bsc-banner-btn.delete:hover {
    background: #ef4444;
    color: #ffffff;
    border-color: #ef4444;
  }
  .bsc-customer-body-wrap {
    padding: 2rem 2.25rem;
  }
  .bsc-customer-spec-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--cinema-border);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.75rem;
    background: #ffffff;
  }
  .bsc-customer-spec-table tr {
    border-bottom: 1px solid var(--cinema-border-light);
    transition: background 0.15s ease;
  }
  .bsc-customer-spec-table tr:last-child { border-bottom: none; }
  .bsc-customer-spec-table tr:hover { background: #f8fafc; }
  .bsc-customer-spec-table th {
    width: 30%;
    padding: 1.15rem 1.25rem;
    background: #fafafa;
    color: var(--cinema-text-muted);
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    vertical-align: middle;
    border-right: 1px solid var(--cinema-border-light);
  }
  .bsc-table-cell-header { display: flex; align-items: center; gap: 0.65rem; }
  .bsc-table-icon-badge {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: #f1f5f9;
    color: var(--cinema-accent-teal);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bsc-table-icon-badge svg { width: 15px; height: 15px; fill: currentColor; }
  .bsc-customer-spec-table td {
    padding: 1.15rem 1.25rem;
    color: var(--cinema-text-main);
    vertical-align: middle;
    font-size: 0.95rem;
  }
  .bsc-member-card-sidebar {
    background: linear-gradient(135deg, #09171c 0%, #064e3b 100%);
    border-radius: 16px;
    padding: 1.75rem;
    color: #ffffff;
    box-shadow: 0 10px 25px rgba(6, 78, 59, 0.15);
    position: relative;
    overflow: hidden;
  }
  .bsc-member-card-sidebar::before {
    content: '';
    position: absolute;
    top: -30px;
    right: -30px;
    width: 130px;
    height: 130px;
    background: radial-gradient(circle, rgba(13, 148, 136, 0.3) 0%, transparent 70%);
    pointer-events: none;
  }
  @media (max-width: 1024px) {
    .bsc-customer-view-grid { grid-template-columns: 1fr; }
    .bsc-customer-banner-head { flex-direction: column; gap: 1.25rem; align-items: flex-start; }
    .bsc-banner-actions { width: 100%; justify-content: flex-end; }
    .bsc-customer-spec-table th { width: 35%; }
  }
</style>

<div class="bsc-customer-view-grid">
  
  <!-- Left: Dossier & Screening History -->
  <div class="bsc-customer-main-card">
    
    <!-- Cinematic Header Banner -->
    <div class="bsc-customer-banner-head">
      <div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
          <span class="bsc-customer-banner-badge"><?php echo esc_html($customer->tier); ?></span>
          <span style="font-size:0.75rem; color:#fde68a; font-weight:800; font-family:var(--cinema-font-mono); background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.35); padding:0.25rem 0.65rem; border-radius:9999px;"><?php echo esc_html($customer->membership_id); ?></span>
        </div>
        <h2 style="font-size: 2.1rem; font-weight: 900; margin: 0 0 0.35rem 0; color: #ffffff; letter-spacing: -0.01em;">
          <?php echo esc_html($customer->name); ?>
        </h2>
        <div style="font-size: 0.82rem; color: #94a3b8; font-family: var(--cinema-font-mono);">
          Member Since: <strong style="color:#e2e8f0;"><?php echo esc_html(date_i18n('M j, Y', strtotime($customer->created_at))); ?></strong>
        </div>
      </div>

      <div class="bsc-banner-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-customers&subtab=edit&id=' . $customer->id)); ?>" class="bsc-banner-btn edit">
          <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
          <span>Edit Customer</span>
        </a>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=bsc-customers&subtab=delete&id=' . $customer->id), 'bsc_delete_customer_' . $customer->id)); ?>" class="bsc-banner-btn delete" onclick="return confirm('Permanently remove this customer record?');">
          <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
          <span>Delete</span>
        </a>
      </div>
    </div>

    <!-- Body Information -->
    <div class="bsc-customer-body-wrap">
      
      <!-- Structured Key-Value Spec Table -->
      <table class="bsc-customer-spec-table">
        <tbody>
          <tr>
            <th>
              <div class="bsc-table-cell-header">
                <div class="bsc-table-icon-badge">
                  <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <span><?php esc_html_e('Contact Information', 'big-screen-cinemas'); ?></span>
              </div>
            </th>
            <td>
              <div style="font-weight: 800; font-size: 1rem; color: var(--cinema-text-main);">
                📞 <?php echo esc_html($customer->phone ?: 'Not provided'); ?>
              </div>
              <div style="font-size: 0.85rem; color: var(--cinema-text-muted); margin-top: 3px;">
                ✉️ <?php echo esc_html($customer->email ?: 'Not provided'); ?>
              </div>
            </td>
          </tr>

          <tr>
            <th>
              <div class="bsc-table-cell-header">
                <div class="bsc-table-icon-badge">
                  <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                </div>
                <span><?php esc_html_e('Loyalty & Frequency', 'big-screen-cinemas'); ?></span>
              </div>
            </th>
            <td>
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                  <strong style="color:var(--cinema-text-main); font-size:0.95rem;">
                    <?php echo intval($customer->total_visits); ?> <?php esc_html_e('Screening Visits', 'big-screen-cinemas'); ?>
                  </strong>
                  <div style="font-size:0.8rem; color:var(--cinema-text-muted); margin-top:2px;">VIP Club Active Status</div>
                </div>
                <strong style="color:var(--cinema-accent-amber); font-family:var(--cinema-font-mono); font-size:1.1rem;">
                  <?php echo number_format($customer->reward_points); ?> PTS
                </strong>
              </div>
            </td>
          </tr>

          <tr>
            <th>
              <div class="bsc-table-cell-header">
                <div class="bsc-table-icon-badge">
                  <svg viewBox="0 0 24 24"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                </div>
                <span><?php esc_html_e('Membership ID', 'big-screen-cinemas'); ?></span>
              </div>
            </th>
            <td>
              <code style="background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-weight: 800; font-size: 0.9rem; color: #0f172a;">
                <?php echo esc_html($customer->membership_id); ?>
              </code>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Customer Ticket Purchase History -->
      <div style="border-top: 1px solid var(--cinema-border-light); padding-top: 1.5rem;">
        <h4 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 800; display:flex; align-items:center; gap:0.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
          <?php esc_html_e('Screening Passes & Ticket Log', 'big-screen-cinemas'); ?> (<?php echo count($customer_history); ?>)
        </h4>

        <div class="cinema-table-wrap">
          <table class="cinema-data-table">
            <thead>
              <tr>
                <th>TOKEN</th>
                <th>MOVIE</th>
                <th>AUDITORIUM</th>
                <th>SHOWTIME</th>
                <th>SEATS</th>
                <th>AMOUNT</th>
                <th>DATE</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($customer_history)): foreach ($customer_history as $h): ?>
                <tr>
                  <td><span class="cinema-voucher-tag"><?php echo esc_html($h->booking_token); ?></span></td>
                  <td><strong><?php echo esc_html($h->movie_title); ?></strong></td>
                  <td><?php echo esc_html($h->auditorium); ?></td>
                  <td><span class="cinema-badge-pill active" style="font-size:0.7rem;"><?php echo esc_html($h->showtime); ?></span></td>
                  <td><code><?php echo esc_html($h->seats); ?></code></td>
                  <td><strong style="color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono);"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($h->total_amount, 2); ?></strong></td>
                  <td><small style="color:var(--cinema-text-muted);"><?php echo esc_html(date_i18n('M j, Y', strtotime($h->booking_date))); ?></small></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--cinema-text-muted);"><?php esc_html_e('No ticket bookings linked to this customer yet.', 'big-screen-cinemas'); ?></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div style="margin-top:2rem; border-top:1px solid var(--cinema-border-light); padding-top:1.25rem;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-customers&subtab=all')); ?>" class="button button-secondary" style="font-weight:700;">
          ← <?php esc_html_e('Back to All Customers', 'big-screen-cinemas'); ?>
        </a>
      </div>

    </div>

  </div>

  <!-- Right: VIP Card Visual -->
  <div>
    <div class="bsc-member-card-sidebar">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
        <div style="font-size:0.8rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#99f6e4;">
          <?php echo esc_html($customer->tier); ?>
        </div>
        <svg viewBox="0 0 24 24" width="28" height="28" fill="rgba(255,255,255,0.7)"><path d="M12 2L1 21h22L12 2zm0 3.5l7.5 13H4.5L12 5.5z"/></svg>
      </div>

      <div style="font-size:1.35rem; font-weight:900; letter-spacing:0.02em; margin-bottom:0.25rem;">
        <?php echo esc_html($customer->name); ?>
      </div>
      <div style="font-size:0.85rem; color:#cbd5e1; font-family:var(--cinema-font-mono);">
        <?php echo esc_html($customer->membership_id); ?>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:2.5rem; border-top:1px solid rgba(255,255,255,0.15); padding-top:1rem;">
        <div>
          <div style="font-size:0.68rem; text-transform:uppercase; color:#99f6e4; font-weight:800;">Visits</div>
          <div style="font-size:1.1rem; font-weight:900;"><?php echo intval($customer->total_visits); ?></div>
        </div>
        <div>
          <div style="font-size:0.68rem; text-transform:uppercase; color:#99f6e4; font-weight:800; text-align:right;">Points</div>
          <div style="font-size:1.1rem; font-weight:900; font-family:var(--cinema-font-mono); color:#fde68a;">
            <?php echo number_format($customer->reward_points); ?>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>