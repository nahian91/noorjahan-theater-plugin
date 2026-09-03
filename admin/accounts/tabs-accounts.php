<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_ledger = $wpdb->prefix . 'bsc_ledger';

$income_count  = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_ledger} WHERE txn_type = 'Income'");
$expense_count = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_ledger} WHERE txn_type = 'Expense'");
?>
<style>
  /* ==========================================================================
     MODERN ACCOUNTS TABS & HEADER TOOLBAR
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

  /* ==========================================================================
     REPORT SHEETS & TABLES
     ========================================================================== */
  .bsc-report-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }
  .bsc-report-sheet {
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: var(--cinema-radius-md, 14px);
    padding: 1.75rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
  }
  .bsc-report-sheet h4 {
    margin: 0 0 1.25rem 0;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--cinema-text-main, #0f172a);
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
    border-bottom: 1px dashed var(--cinema-border-light, #f1f5f9);
    font-size: 0.9rem;
  }
  .bsc-report-line-item:last-child { border-bottom: none; }
  .bsc-report-line-item strong { font-family: var(--cinema-font-mono, monospace); }
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
    .bsc-subtabs-nav-wrapper, .bsc-action-btn-group, .button { display: none !important; }
  }
</style>

<!-- TAB NAVIGATION TOOLBAR -->
<div class="bsc-subtabs-nav-wrapper">
  <nav class="bsc-subtabs-nav">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=income')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'income') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24"><path d="M12.5 6.5H5.5V17.5H18.5V6.5H12.5ZM17 16H7V8H17V16ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z"/></svg>
      <span><?php esc_html_e('Income', 'bcs-screen-manager'); ?></span>
      <span class="bsc-tab-badge"><?php echo esc_html($income_count); ?></span>
    </a>

    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=expense')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'expense') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
      <span><?php esc_html_e('Expense', 'bcs-screen-manager'); ?></span>
      <span class="bsc-tab-badge"><?php echo esc_html($expense_count); ?></span>
    </a>

    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=report')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'report') ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
      <span><?php esc_html_e('Reports', 'bcs-screen-manager'); ?></span>
    </a>
  </nav>
</div>

<!-- NOTIFICATION TOAST BANNERS -->
<?php if (isset($_GET['income_added'])): ?>
  <div class="bsc-notification-banner success">
    <div class="bsc-notification-icon">
      <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    </div>
    <div><?php esc_html_e('Revenue credit entry logged successfully!', 'bcs-screen-manager'); ?></div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['expense_added'])): ?>
  <div class="bsc-notification-banner success">
    <div class="bsc-notification-icon">
      <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    </div>
    <div><?php esc_html_e('Expense record posted to accounts ledger!', 'bcs-screen-manager'); ?></div>
  </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
  <div class="bsc-notification-banner danger">
    <div class="bsc-notification-icon">
      <svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    </div>
    <div><?php esc_html_e('Ledger entry removed.', 'bcs-screen-manager'); ?></div>
  </div>
<?php endif; ?>