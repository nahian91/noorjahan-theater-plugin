<?php
if (!defined('ABSPATH')) exit;
?>
<style>
  /* Table Action Buttons with SVGs */
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

  /* Staff KPI Metrics */
  .bsc-staff-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.75rem;
  }
  .bsc-staff-kpi-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: var(--cinema-radius-md);
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
    transition: transform 0.2s ease;
  }
  .bsc-staff-kpi-card:hover { transform: translateY(-2px); }
  .bsc-staff-kpi-card .label {
    font-size: 0.72rem;
    text-transform: uppercase;
    font-weight: 800;
    color: var(--cinema-text-muted);
    letter-spacing: 0.05em;
  }
  .bsc-staff-kpi-card .val {
    font-size: 1.65rem;
    font-weight: 900;
    font-family: var(--cinema-font-mono);
    margin-top: 0.25rem;
    line-height: 1.1;
    color: var(--cinema-text-main);
  }
  .bsc-staff-icon-bubble {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bsc-staff-icon-bubble svg { width: 20px; height: 20px; fill: currentColor; }

  /* Staff Dossier Table View */
  .bsc-staff-view-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    align-items: start;
  }
  .bsc-staff-main-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: var(--cinema-radius-md);
    padding: 1.75rem 2rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
  }
  .bsc-staff-spec-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--cinema-border);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1.75rem;
    background: #ffffff;
  }
  .bsc-staff-spec-table tr {
    border-bottom: 1px solid var(--cinema-border-light);
    transition: background 0.15s ease;
  }
  .bsc-staff-spec-table tr:last-child { border-bottom: none; }
  .bsc-staff-spec-table tr:hover { background: #f8fafc; }
  .bsc-staff-spec-table th {
    width: 30%;
    padding: 1rem 1.25rem;
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
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #f1f5f9;
    color: var(--cinema-accent-teal);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bsc-table-icon-badge svg { width: 14px; height: 14px; fill: currentColor; }
  .bsc-staff-spec-table td {
    padding: 1rem 1.25rem;
    color: var(--cinema-text-main);
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
    border: 2px solid var(--cinema-accent-teal);
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

<!-- Clean Subtab Navigation -->
<div class="bsc-subtabs-nav">
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-users&subtab=all')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'all' || $subtab === 'view' || $subtab === 'edit') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    <?php esc_html_e('All Staff & Crew', 'bsc-cinema-manager'); ?>
  </a>
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-users&subtab=add')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'add') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    <?php esc_html_e('Add Staff Member', 'bsc-cinema-manager'); ?>
  </a>
</div>

<?php if (isset($_GET['staff_added'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Staff member added to multiplex personnel records!', 'bsc-cinema-manager'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['staff_updated'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Staff profile & operational role updated!', 'bsc-cinema-manager'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['staff_deleted'])): ?>
  <div style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    <?php esc_html_e('Staff record removed from roster.', 'bsc-cinema-manager'); ?>
  </div>
<?php endif; ?>

<!-- Staff Duty Overview KPI Deck -->
<div class="bsc-staff-kpi-grid">
  <div class="bsc-staff-kpi-card">
    <div>
      <div class="label"><?php esc_html_e('Total Registered Staff', 'bsc-cinema-manager'); ?></div>
      <div class="val"><?php echo number_format($total_staff); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted);">Crew</span></div>
    </div>
    <div class="bsc-staff-icon-bubble" style="background:#eff6ff; color:#3b82f6;">
      <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    </div>
  </div>

  <div class="bsc-staff-kpi-card">
    <div>
      <div class="label"><?php esc_html_e('Currently Active On Duty', 'bsc-cinema-manager'); ?></div>
      <div class="val" style="color:#059669;"><?php echo number_format($on_duty_count); ?> <span style="font-size:0.85rem; color:#10b981;">Present</span></div>
    </div>
    <div class="bsc-staff-icon-bubble" style="background:#ecfdf5; color:#10b981;">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
    </div>
  </div>

  <div class="bsc-staff-kpi-card">
    <div>
      <div class="label"><?php esc_html_e('Projection & Technical Crew', 'bsc-cinema-manager'); ?></div>
      <div class="val" style="color:#d97706;"><?php echo number_format($tech_count); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted);">Techs</span></div>
    </div>
    <div class="bsc-staff-icon-bubble" style="background:#fef3c7; color:#d97706;">
      <svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
    </div>
  </div>
</div>