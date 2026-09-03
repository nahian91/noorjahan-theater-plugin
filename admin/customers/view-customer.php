<?php
/**
 * Movie / Customer Module - Single Customer Dossier View
 *
 * Path: admin/customers/view-customer.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
}

global $wpdb;
$table_customers = $wpdb->prefix . 'bsc_customers';
$table_bookings  = $wpdb->prefix . 'bsc_bookings';
$customer_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $customer_id ) );
if ( ! $customer ) {
    echo '<div class="cinema-panel" style="margin:20px;"><p>' . esc_html__( 'Customer record not found.', 'bsc-cinema-manager' ) . '</p></div>';
    return;
}

// Helper 12-Hour AM/PM Time Formatter
function bsc_format_customer_time( $raw_time ) {
    $clean = trim( (string) $raw_time );
    if ( empty( $clean ) ) {
        return '';
    }
    if ( false !== stripos( $clean, 'AM' ) || false !== stripos( $clean, 'PM' ) ) {
        return $clean;
    }
    if ( false !== strpos( $clean, ':' ) ) {
        $parts = explode( ':', $clean );
        $hour  = (int) $parts[0];
        $min   = isset( $parts[1] ) ? substr( $parts[1], 0, 2 ) : '00';
        $ampm  = $hour >= 12 ? 'PM' : 'AM';
        $h12   = ( $hour % 12 === 0 ) ? 12 : ( $hour % 12 );
        return sprintf( '%02d:%s %s', $h12, $min, $ampm );
    }
    return $clean;
}

// Fetch matching historical bookings by customer email or phone
$customer_history = array();
if ( ! empty( $customer->email ) || ! empty( $customer->phone ) ) {
    $customer_history = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$table_bookings} WHERE (customer_email = %s AND customer_email != '') OR (customer_phone = %s AND customer_phone != '') ORDER BY id DESC",
        $customer->email,
        $customer->phone
    ) );
}

// Calculate total tickets from actual history or fallback
$total_tickets_count = 0;
if ( ! empty( $customer_history ) ) {
    foreach ( $customer_history as $h ) {
        $seats_arr = array_filter( array_map( 'trim', explode( ',', $h->seats ) ) );
        $total_tickets_count += count( $seats_arr );
    }
}
$total_visits_count = intval( $customer->total_visits ?? count( $customer_history ) );
?>
<style>
  .bsc-customer-view-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    align-items: start;
    max-width: 1140px;
    margin: 10px auto 60px auto;
  }
  .bsc-customer-main-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
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
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.75rem;
    background: #ffffff;
  }
  .bsc-customer-spec-table tr {
    border-bottom: 1px solid var(--cinema-border-light, #f1f5f9);
    transition: background 0.15s ease;
  }
  .bsc-customer-spec-table tr:last-child { border-bottom: none; }
  .bsc-customer-spec-table tr:hover { background: #f8fafc; }
  .bsc-customer-spec-table th {
    width: 30%;
    padding: 1.15rem 1.25rem;
    background: #fafafa;
    color: var(--cinema-text-muted, #64748b);
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    vertical-align: middle;
    border-right: 1px solid var(--cinema-border-light, #f1f5f9);
  }
  .bsc-table-cell-header { display: flex; align-items: center; gap: 0.65rem; }
  .bsc-table-icon-badge {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    background: #f1f5f9;
    color: var(--cinema-accent-teal, #0d9488);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bsc-table-icon-badge svg { width: 15px; height: 15px; fill: currentColor; }
  .bsc-customer-spec-table td {
    padding: 1.15rem 1.25rem;
    color: var(--cinema-text-main, #0f172a);
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
          <span class="bsc-customer-banner-badge"><?php esc_html_e( 'Club Member', 'bsc-cinema-manager' ); ?></span>
        </div>
        <h2 style="font-size: 2.1rem; font-weight: 900; margin: 0 0 0.35rem 0; color: #ffffff; letter-spacing: -0.01em;">
          <?php echo esc_html( $customer->name ); ?>
        </h2>
        <div style="font-size: 0.82rem; color: #94a3b8; font-family: var(--cinema-font-mono, monospace);">
          Registered: <strong style="color:#e2e8f0;"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $customer->created_at ) ) ); ?></strong>
        </div>
      </div>

      <div class="bsc-banner-actions">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-customers&subtab=edit&id=' . $customer->id ) ); ?>" class="bsc-banner-btn edit">
          <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
          <span><?php esc_html_e( 'Edit Customer', 'bsc-cinema-manager' ); ?></span>
        </a>
        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=bsc-customers&subtab=delete&id=' . $customer->id ), 'bsc_delete_customer_' . $customer->id ) ); ?>" class="bsc-banner-btn delete" onclick="return confirm('<?php echo esc_js( __( 'Permanently remove this customer record?', 'bsc-cinema-manager' ) ); ?>');">
          <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
          <span><?php esc_html_e( 'Delete', 'bsc-cinema-manager' ); ?></span>
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
                <span><?php esc_html_e( 'Contact Information', 'bsc-cinema-manager' ); ?></span>
              </div>
            </th>
            <td>
              <div style="font-weight: 800; font-size: 1rem; color: var(--cinema-text-main, #0f172a);">
                📞 <?php echo esc_html( $customer->phone ?: 'Not provided' ); ?>
              </div>
              <div style="font-size: 0.85rem; color: var(--cinema-text-muted, #64748b); margin-top: 3px;">
                ✉️ <?php echo esc_html( $customer->email ?: 'Not provided' ); ?>
              </div>
            </td>
          </tr>

          <tr>
            <th>
              <div class="bsc-table-cell-header">
                <div class="bsc-table-icon-badge">
                  <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                </div>
                <span><?php esc_html_e( 'Screening Activity', 'bsc-cinema-manager' ); ?></span>
              </div>
            </th>
            <td>
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                  <strong style="color:var(--cinema-text-main, #0f172a); font-size:0.95rem;">
                    <?php echo $total_visits_count; ?> <?php esc_html_e( 'Total Visits', 'bsc-cinema-manager' ); ?>
                  </strong>
                  <div style="font-size:0.8rem; color:var(--cinema-text-muted, #64748b); margin-top:2px;"><?php esc_html_e( 'Member Screening Record', 'bsc-cinema-manager' ); ?></div>
                </div>
                <strong style="color:var(--cinema-accent-teal, #0d9488); font-family:var(--cinema-font-mono, monospace); font-size:1.1rem;">
                  <?php echo $total_tickets_count; ?> <?php esc_html_e( 'Tickets', 'bsc-cinema-manager' ); ?>
                </strong>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Customer Ticket Purchase History -->
      <div style="border-top: 1px solid var(--cinema-border-light, #f1f5f9); padding-top: 1.5rem;">
        <h4 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 800; display:flex; align-items:center; gap:0.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V10zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
          <?php echo esc_html( sprintf( __( 'Screening Passes & Ticket Log (%d)', 'bsc-cinema-manager' ), count( $customer_history ) ) ); ?>
        </h4>

        <div class="cinema-table-wrap" style="overflow-x:auto;">
          <table class="cinema-data-table" style="width:100%; border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'TOKEN', 'bsc-cinema-manager' ); ?></th>
                <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'MOVIE', 'bsc-cinema-manager' ); ?></th>
                <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'SHOWTIME', 'bsc-cinema-manager' ); ?></th>
                <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'SEATS', 'bsc-cinema-manager' ); ?></th>
                <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'AMOUNT', 'bsc-cinema-manager' ); ?></th>
                <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'DATE', 'bsc-cinema-manager' ); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if ( ! empty( $customer_history ) ) : foreach ( $customer_history as $h ) : 
                $formatted_h_time = bsc_format_customer_time( $h->showtime );
              ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                  <td style="padding:12px;"><span class="cinema-voucher-tag" style="font-family:var(--cinema-font-mono, monospace); background:#f8fafc; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px;"><?php echo esc_html( $h->booking_token ); ?></span></td>
                  <td style="padding:12px;"><strong><?php echo esc_html( $h->movie_title ); ?></strong></td>
                  <td style="padding:12px;"><span class="cinema-badge-pill active" style="font-size:0.7rem; background:rgba(13,148,136,0.1); color:#0d9488; padding:3px 8px; border-radius:999px; font-weight:700;"><?php echo esc_html( $formatted_h_time ); ?></span></td>
                  <td style="padding:12px;"><code style="font-family:var(--cinema-font-mono, monospace);"><?php echo esc_html( $h->seats ); ?></code></td>
                  <td style="padding:12px;"><strong style="color:var(--cinema-accent-teal, #0d9488); font-family:var(--cinema-font-mono, monospace);"><?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $h->total_amount, 2 ) ); ?></strong></td>
                  <td style="padding:12px;"><small style="color:var(--cinema-text-muted, #64748b); font-weight:700;"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $h->booking_date ) ) ); ?></small></td>
                </tr>
              <?php endforeach; else : ?>
                <tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'No ticket bookings linked to this customer yet.', 'bsc-cinema-manager' ); ?></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div style="margin-top:2rem; border-top:1px solid var(--cinema-border-light, #f1f5f9); padding-top:1.25rem;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-customers&subtab=all' ) ); ?>" class="button button-secondary" style="font-weight:700; border-radius:6px;">
          &larr; <?php esc_html_e( 'Back to All Customers', 'bsc-cinema-manager' ); ?>
        </a>
      </div>

    </div>

  </div>

  <!-- Right: Member Card Visual -->
  <div>
    <div class="bsc-member-card-sidebar">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
        <div style="font-size:0.8rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#99f6e4;">
          <?php esc_html_e( 'CLUB MEMBER', 'bsc-cinema-manager' ); ?>
        </div>
        <svg viewBox="0 0 24 24" width="28" height="28" fill="rgba(255,255,255,0.7)"><path d="M12 2L1 21h22L12 2zm0 3.5l7.5 13H4.5L12 5.5z"/></svg>
      </div>

      <div style="font-size:1.35rem; font-weight:900; letter-spacing:0.02em; margin-bottom:0.25rem;">
        <?php echo esc_html( $customer->name ); ?>
      </div>
      <div style="font-size:0.85rem; color:#cbd5e1; font-family:var(--cinema-font-mono, monospace);">
        <?php echo esc_html( $customer->phone ); ?>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:2.5rem; border-top:1px solid rgba(255,255,255,0.15); padding-top:1rem;">
        <div>
          <div style="font-size:0.68rem; text-transform:uppercase; color:#99f6e4; font-weight:800;"><?php esc_html_e( 'Visits', 'bsc-cinema-manager' ); ?></div>
          <div style="font-size:1.1rem; font-weight:900;"><?php echo $total_visits_count; ?></div>
        </div>
        <div>
          <div style="font-size:0.68rem; text-transform:uppercase; color:#99f6e4; font-weight:800; text-align:right;"><?php esc_html_e( 'Tickets', 'bsc-cinema-manager' ); ?></div>
          <div style="font-size:1.1rem; font-weight:900; font-family:var(--cinema-font-mono, monospace); color:#fde68a; text-align:right;">
            <?php echo $total_tickets_count; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>