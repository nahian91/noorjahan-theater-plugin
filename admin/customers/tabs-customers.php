<?php
if (!defined('ABSPATH')) exit;
?>
<style>
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
  .bsc-customer-view-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    align-items: start;
  }
  .bsc-customer-main-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: var(--cinema-radius-md);
    padding: 1.75rem 2rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
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
  .bsc-customer-spec-table td {
    padding: 1rem 1.25rem;
    color: var(--cinema-text-main);
    vertical-align: middle;
    font-size: 0.92rem;
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
    .bsc-customer-spec-table th { width: 35%; }
  }
</style>

<div class="bsc-subtabs-nav">
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-customers&subtab=all')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'all' || $subtab === 'view' || $subtab === 'edit') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    <?php esc_html_e('All Customers', 'big-screen-cinemas'); ?>
  </a>
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-customers&subtab=add')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'add') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    <?php esc_html_e('Add Customer', 'big-screen-cinemas'); ?>
  </a>
</div>

<?php if (isset($_GET['customer_added'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Customer account created successfully!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['customer_updated'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Customer details updated successfully!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['customer_deleted'])): ?>
  <div style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    <?php esc_html_e('Customer removed from registry!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>