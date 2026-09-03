<?php
/**
 * Users / Staff Module - Single Staff Dossier View
 *
 * Path: admin/users/view-user.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
}

global $wpdb;
$table_staff = $wpdb->prefix . 'bsc_staff_crew';
$staff_id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

$staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id ) );
if ( ! $staff ) {
    echo '<div class="cinema-panel" style="margin:20px;"><p>' . esc_html__( 'Staff record not found.', 'bsc-cinema-manager' ) . '</p></div>';
    return;
}

$is_on_duty = ( 'On Duty' === $staff->duty_status );
?>
<style>
  .bsc-staff-view-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    align-items: start;
    max-width: 1140px;
    margin: 10px auto 60px auto;
  }
  .bsc-staff-main-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    padding: 2rem 2.25rem;
  }
  .bsc-staff-spec-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.75rem;
    background: #ffffff;
  }
  .bsc-staff-spec-table tr {
    border-bottom: 1px solid var(--cinema-border-light, #f1f5f9);
    transition: background 0.15s ease;
  }
  .bsc-staff-spec-table tr:last-child { border-bottom: none; }
  .bsc-staff-spec-table tr:hover { background: #f8fafc; }
  .bsc-staff-spec-table th {
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
  .bsc-staff-spec-table td {
    padding: 1.15rem 1.25rem;
    color: var(--cinema-text-main, #0f172a);
    vertical-align: middle;
    font-size: 0.95rem;
  }
  .bsc-staff-id-card-sidebar {
    background: linear-gradient(135deg, #09171c 0%, #064e3b 100%);
    border-radius: 16px;
    padding: 1.75rem;
    color: #ffffff;
    box-shadow: 0 10px 25px rgba(6, 78, 59, 0.15);
    position: relative;
    overflow: hidden;
    text-align: center;
  }
  .bsc-staff-id-card-sidebar::before {
    content: '';
    position: absolute;
    top: -30px;
    right: -30px;
    width: 130px;
    height: 130px;
    background: radial-gradient(circle, rgba(13, 148, 136, 0.3) 0%, transparent 70%);
    pointer-events: none;
  }
  .bsc-staff-badge-photo {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem auto;
    border: 3px solid rgba(255, 255, 255, 0.2);
    display: block;
    background: #0d9488;
  }
  @media (max-width: 1024px) {
    .bsc-staff-view-grid { grid-template-columns: 1fr; }
    .bsc-staff-spec-table th { width: 35%; }
  }
</style>

<div class="bsc-staff-view-grid">
  
  <!-- Left: Master Dossier Table -->
  <div class="bsc-staff-main-card">
    
    <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid var(--cinema-border-light, #f1f5f9); padding-bottom:1.25rem; margin-bottom:1.5rem;">
      <div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
          <span class="cinema-badge-pill active" style="<?php echo ! $is_on_duty ? 'background:rgba(239,68,68,0.15); color:#ef4444; border-color:rgba(239,68,68,0.3);' : ''; ?>">
            ● <?php echo esc_html( $staff->duty_status ); ?>
          </span>
          <span class="cinema-voucher-tag" style="font-family:var(--cinema-font-mono, monospace); background:#f8fafc; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px; font-size:0.75rem;"><?php echo esc_html( $staff->staff_code ?? '#STAFF-' . $staff->id ); ?></span>
        </div>
        <h2 style="font-size: 1.75rem; font-weight: 900; margin: 0; color: var(--cinema-text-main, #0f172a);">
          <?php echo esc_html( $staff->name ); ?>
        </h2>
        <span style="font-size: 0.82rem; color: var(--cinema-text-muted, #64748b); font-family: var(--cinema-font-mono, monospace);">
          Role: <strong><?php echo esc_html( $staff->role_title ); ?></strong> &bull; Joined: <strong><?php echo esc_html( ! empty( $staff->created_at ) ? date_i18n( 'M j, Y', strtotime( $staff->created_at ) ) : 'N/A' ); ?></strong>
        </span>
      </div>

      <div class="bsc-table-actions">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-users&subtab=edit&id=' . $staff->id ) ); ?>" class="bsc-action-btn edit" style="padding:7px 12px; font-size:0.82rem;">
          <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
          <span><?php esc_html_e( 'Edit Staff', 'bsc-cinema-manager' ); ?></span>
        </a>
        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=bsc-users&subtab=delete&id=' . $staff->id ), 'bsc_delete_staff_' . $staff->id ) ); ?>" class="bsc-action-btn delete" style="padding:7px 12px; font-size:0.82rem;" onclick="return confirm('<?php echo esc_js( __( 'Permanently remove this staff record?', 'bsc-cinema-manager' ) ); ?>');">
          <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
          <span><?php esc_html_e( 'Delete', 'bsc-cinema-manager' ); ?></span>
        </a>
      </div>
    </div>

    <!-- Structured Key-Value Table -->
    <table class="bsc-staff-spec-table">
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
              📞 <?php echo esc_html( $staff->phone ?: 'Not provided' ); ?>
            </div>
            <div style="font-size: 0.85rem; color: var(--cinema-text-muted, #64748b); margin-top: 3px;">
              ✉️ <?php echo esc_html( $staff->email ?: 'Not provided' ); ?>
            </div>
          </td>
        </tr>

        <tr>
          <th>
            <div class="bsc-table-cell-header">
              <div class="bsc-table-icon-badge">
                <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
              </div>
              <span><?php esc_html_e( 'Shift & Duty Status', 'bsc-cinema-manager' ); ?></span>
            </div>
          </th>
          <td>
            <div style="display: flex; align-items: center; justify-content: space-between;">
              <div>
                <strong style="color: var(--cinema-accent-teal, #0d9488); font-size: 0.95rem;">
                  <?php echo esc_html( $staff->shift_schedule ?: 'Rotational Shift' ); ?>
                </strong>
              </div>
              <span class="cinema-badge-pill active" style="<?php echo ! $is_on_duty ? 'background:rgba(239,68,68,0.15); color:#ef4444; border-color:rgba(239,68,68,0.3);' : ''; ?>">
                ● <?php echo esc_html( $staff->duty_status ); ?>
              </span>
            </div>
          </td>
        </tr>

        <tr>
          <th>
            <div class="bsc-table-cell-header">
              <div class="bsc-table-icon-badge">
                <svg viewBox="0 0 24 24"><path d="M12.5 6.5H5.5V17.5H18.5V6.5H12.5ZM17 16H7V8H17V16ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z"/></svg>
              </div>
              <span><?php esc_html_e( 'Monthly Salary', 'bsc-cinema-manager' ); ?></span>
            </div>
          </th>
          <td>
            <div style="font-size: 1.15rem; font-weight: 900; font-family: var(--cinema-font-mono, monospace); color: #059669;">
              <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $staff->salary, 2 ) ); ?>
            </div>
          </td>
        </tr>

        <tr>
          <th>
            <div class="bsc-table-cell-header">
              <div class="bsc-table-icon-badge">
                <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
              </div>
              <span><?php esc_html_e( 'Notes / Responsibilities', 'bsc-cinema-manager' ); ?></span>
            </div>
          </th>
          <td>
            <p style="font-size:0.92rem; line-height:1.75; color:var(--cinema-text-secondary, #475569); margin:0;">
              <?php echo nl2br( esc_html( $staff->responsibilities ?: __( 'Standard cinema floor operations, guest welcoming, equipment readiness, and ticket turnstile check-in duties.', 'bsc-cinema-manager' ) ) ); ?>
            </p>
          </td>
        </tr>
      </tbody>
    </table>

    <div style="margin-top:2rem; border-top:1px solid var(--cinema-border-light, #f1f5f9); padding-top:1.25rem;">
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-users&subtab=all' ) ); ?>" class="button button-secondary" style="font-weight:700; border-radius:6px;">
        &larr; <?php esc_html_e( 'Back to Staff Roster', 'bsc-cinema-manager' ); ?>
      </a>
    </div>

  </div>

  <!-- Right: ID Badge Visual -->
  <div>
    <div class="bsc-staff-id-card-sidebar">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <div style="font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#99f6e4;">
          <?php esc_html_e( 'MULTIPLEX CREW ID', 'bsc-cinema-manager' ); ?>
        </div>
        <svg viewBox="0 0 24 24" width="22" height="22" fill="rgba(255,255,255,0.7)"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
      </div>

      <?php if ( ! empty( $staff->photo_url ) ) : ?>
        <img src="<?php echo esc_url( $staff->photo_url ); ?>" alt="<?php echo esc_attr( $staff->name ); ?>" class="bsc-staff-badge-photo">
      <?php else : ?>
        <div class="bsc-staff-badge-photo" style="display:flex; align-items:center; justify-content:center; color:#fff; font-size:2.2rem; font-weight:900;">
          <?php echo esc_html( strtoupper( substr( $staff->name, 0, 2 ) ) ); ?>
        </div>
      <?php endif; ?>

      <div style="font-size:1.35rem; font-weight:900; letter-spacing:0.02em; margin-bottom:0.25rem;">
        <?php echo esc_html( $staff->name ); ?>
      </div>
      <div style="font-size:0.85rem; color:#99f6e4; font-weight:700;">
        <?php echo esc_html( $staff->role_title ); ?>
      </div>
      <div style="font-size:0.8rem; color:#cbd5e1; font-family:var(--cinema-font-mono, monospace); margin-top:2px;">
        <?php echo esc_html( $staff->staff_code ?? '#STAFF-' . $staff->id ); ?>
      </div>

      <div style="display:flex; justify-content:flex-end; align-items:center; margin-top:1.75rem; border-top:1px solid rgba(255,255,255,0.15); padding-top:1rem;">
        <span class="cinema-badge-pill active" style="font-size:0.68rem; <?php echo ! $is_on_duty ? 'background:rgba(239,68,68,0.2); color:#fca5a5; border-color:rgba(239,68,68,0.4);' : ''; ?>">
          ● <?php echo esc_html( $staff->duty_status ); ?>
        </span>
      </div>
    </div>
  </div>

</div>