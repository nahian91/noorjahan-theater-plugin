<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_staff = $wpdb->prefix . 'bsc_staff_crew';

$total_staff = isset($total_staff) ? (int) $total_staff : (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_staff}");
$on_duty_count = isset($on_duty_count) ? (int) $on_duty_count : (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_staff} WHERE duty_status = 'On Duty'");
$tech_count = isset($tech_count) ? (int) $tech_count : (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_staff} WHERE role_title LIKE '%Projection%' OR role_title LIKE '%Tech%'");

$subtab = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'all';
?>
<style>
  /* ==========================================================================
     MODERN STAFF TABS & HEADER TOOLBAR
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

  /* ==========================================================================
     STAFF KPI DECK
     ========================================================================== */
  .bsc-staff-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    margin-bottom: 2rem;
  }

  .bsc-staff-kpi-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: var(--cinema-radius-md, 14px);
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .bsc-staff-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
  }

  .bsc-staff-kpi-card .label {
    font-size: 0.72rem;
    text-transform: uppercase;
    font-weight: 800;
    color: var(--cinema-text-muted, #64748b);
    letter-spacing: 0.05em;
  }

  .bsc-staff-kpi-card .val {
    font-size: 1.65rem;
    font-weight: 900;
    font-family: var(--cinema-font-mono, monospace);
    margin-top: 0.25rem;
    line-height: 1.1;
    color: var(--cinema-text-main, #0f172a);
  }

  .bsc-staff-icon-bubble {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .bsc-staff-icon-bubble svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
  }

  /* Table Action Buttons */
  .bsc-table-actions {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .bsc-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    line-height: 1;
  }
  .bsc-action-btn svg {
    width: 13px;
    height: 13px;
    fill: currentColor;
  }
  .bsc-action-btn.view {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
  }
  .bsc-action-btn.view:hover {
    background: #1d4ed8;
    color: #fff;
  }
  .bsc-action-btn.edit {
    background: #fefce8;
    border-color: #fef08a;
    color: #a16207;
  }
  .bsc-action-btn.edit:hover {
    background: #a16207;
    color: #fff;
  }
  .bsc-action-btn.delete {
    background: #fef2f2;
    border-color: #fecaca;
    color: #b91c1c;
  }
  .bsc-action-btn.delete:hover {
    background: #b91c1c;
    color: #fff;
  }

  /* Staff Dossier Table View */
  .bsc-staff-view-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    align-items: start;
  }
  .bsc-staff-main-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: var(--cinema-radius-md, 14px);
    padding: 1.75rem 2rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
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
    padding: 1rem 1.25rem;
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
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #f1f5f9;
    color: var(--cinema-accent-teal, #0d9488);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bsc-table-icon-badge svg { width: 14px; height: 14px; fill: currentColor; }
  .bsc-staff-spec-table td {
    padding: 1rem 1.25rem;
    color: var(--cinema-text-main, #0f172a);
    vertical-align: middle;
    font-size: 0.92rem;
  }
  .bsc-staff-avatar-cell {
    display: flex;
    align-items: center;
    gap: 0.85rem;
  }
  .bsc-staff-avatar-thumb {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--cinema-accent-teal, #0d9488);
    background: #0f1e24;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 800;
    font-size: 0.85rem;
  }

  /* Staff Employee Badge Card */
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
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #0d9488;
    margin: 0.5rem auto 1rem auto;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    display: block;
    background: #0f1e24;
  }

  @media (max-width: 1024px) {
    .bsc-staff-kpi-grid { grid-template-columns: 1fr; }
    .bsc-staff-view-grid { grid-template-columns: 1fr; }
    .bsc-staff-spec-table th { width: 35%; }
  }
</style>

<!-- TAB NAVIGATION TOOLBAR -->
<div class="bsc-subtabs-nav-wrapper">
  <nav class="bsc-subtabs-nav">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-users&subtab=all')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'all' || $subtab === 'view') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
      <span><?php esc_html_e('All Staff & Crew', 'bcs-screen-manager'); ?></span>
      <span class="bsc-tab-badge"><?php echo esc_html($total_staff); ?></span>
    </a>

    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-users&subtab=add')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'add') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      <span><?php esc_html_e('Add Staff Member', 'bcs-screen-manager'); ?></span>
    </a>

    <?php if ($subtab === 'edit'): ?>
      <span class="bsc-subtab-btn active">
        <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        <span><?php esc_html_e('Edit Staff Member', 'bcs-screen-manager'); ?></span>
      </span>
    <?php endif; ?>
  </nav>
</div>

<!-- NOTIFICATION TOAST BANNERS -->
<?php if (isset($_GET['staff_added'])): ?>
  <div class="bsc-notification-banner success">
    <div class="bsc-notification-icon">
      <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    </div>
    <div><?php esc_html_e('Staff member added to multiplex personnel records!', 'bcs-screen-manager'); ?></div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['staff_updated'])): ?>
  <div class="bsc-notification-banner success">
    <div class="bsc-notification-icon">
      <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    </div>
    <div><?php esc_html_e('Staff profile & operational role updated!', 'bcs-screen-manager'); ?></div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['staff_deleted'])): ?>
  <div class="bsc-notification-banner danger">
    <div class="bsc-notification-icon">
      <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    </div>
    <div><?php esc_html_e('Staff record removed from roster.', 'bcs-screen-manager'); ?></div>
  </div>
<?php endif; ?>

<!-- STAFF DUTY OVERVIEW KPI DECK -->
<div class="bsc-staff-kpi-grid">
  <div class="bsc-staff-kpi-card">
    <div>
      <div class="label"><?php esc_html_e('Total Registered Staff', 'bcs-screen-manager'); ?></div>
      <div class="val"><?php echo number_format($total_staff); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted, #64748b);">Crew</span></div>
    </div>
    <div class="bsc-staff-icon-bubble" style="background:#eff6ff; color:#3b82f6;">
      <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    </div>
  </div>

  <div class="bsc-staff-kpi-card">
    <div>
      <div class="label"><?php esc_html_e('Currently Active On Duty', 'bcs-screen-manager'); ?></div>
      <div class="val" style="color:#059669;"><?php echo number_format($on_duty_count); ?> <span style="font-size:0.85rem; color:#10b981;">Present</span></div>
    </div>
    <div class="bsc-staff-icon-bubble" style="background:#ecfdf5; color:#10b981;">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
    </div>
  </div>

  <div class="bsc-staff-kpi-card">
    <div>
      <div class="label"><?php esc_html_e('Projection & Technical Crew', 'bcs-screen-manager'); ?></div>
      <div class="val" style="color:#d97706;"><?php echo number_format($tech_count); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted, #64748b);">Techs</span></div>
    </div>
    <div class="bsc-staff-icon-bubble" style="background:#fef3c7; color:#d97706;">
      <svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
    </div>
  </div>
</div>