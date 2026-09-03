<?php
if (!defined('ABSPATH')) exit;
?>
<style>
  .bsc-report-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }
  .bsc-report-sheet {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: var(--cinema-radius-md);
    padding: 1.75rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
  }
  .bsc-report-sheet h4 {
    margin: 0 0 1.25rem 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--cinema-text-main);
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .bsc-report-sheet h4 svg { width: 18px; height: 18px; fill: currentColor; }
  .bsc-report-line-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 0;
    border-bottom: 1px dashed var(--cinema-border-light);
    font-size: 0.9rem;
  }
  .bsc-report-line-item:last-child { border-bottom: none; }
  .bsc-report-line-item strong { font-family: var(--cinema-font-mono); }
  .bsc-channel-meter {
    background: #e2e8f0;
    height: 8px;
    border-radius: 9999px;
    overflow: hidden;
    margin: 0.4rem 0 1rem 0;
    display: flex;
  }
  .bsc-channel-meter-fill { height: 100%; }
  .bsc-table-actions { display: inline-flex; align-items: center; gap: 6px; }
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
  .bsc-action-btn svg { width: 13px; height: 13px; fill: currentColor; }
  .bsc-action-btn.delete { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
  .bsc-action-btn.delete:hover { background: #b91c1c; color: #fff; }
  @media (max-width: 1024px) {
    .bsc-report-two-col { grid-template-columns: 1fr; }
  }
  @media print {
    body * { visibility: hidden; }
    #bscPrintArea, #bscPrintArea * { visibility: visible; }
    #bscPrintArea { position: absolute; left: 0; top: 0; width: 100%; }
    .bsc-subtabs-nav, .bsc-action-btn-group, .button { display: none !important; }
  }
</style>

<!-- Simplified Navigation Subtabs -->
<div class="bsc-subtabs-nav">
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=income')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'income') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M12.5 6.5H5.5V17.5H18.5V6.5H12.5ZM17 16H7V8H17V16ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z"/></svg>
    <?php esc_html_e('Income', 'bsc-cinema-manager'); ?>
  </a>
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=expense')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'expense') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
    <?php esc_html_e('Expense', 'bsc-cinema-manager'); ?>
  </a>
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=report')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'report') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
    <?php esc_html_e('Reports', 'bsc-cinema-manager'); ?>
  </a>
</div>

<?php if (isset($_GET['income_added'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Revenue credit entry logged successfully!', 'bsc-cinema-manager'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['expense_added'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Expense record posted to accounts ledger!', 'bsc-cinema-manager'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
  <div style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    <?php esc_html_e('Ledger entry removed.', 'bsc-cinema-manager'); ?>
  </div>
<?php endif; ?>