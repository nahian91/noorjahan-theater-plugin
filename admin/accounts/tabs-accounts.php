<?php
if (!defined('ABSPATH')) exit;
?>
<style>
  .bsc-acc-filter-bar {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: var(--cinema-radius-md);
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
  }
  .bsc-filter-chips-group { display: flex; gap: 6px; }
  .bsc-filter-chip {
    padding: 6px 12px;
    font-size: 0.78rem;
    font-weight: 700;
    border-radius: 6px;
    text-decoration: none;
    background: #f1f5f9;
    color: var(--cinema-text-muted);
    border: 1px solid var(--cinema-border);
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
  .bsc-filter-chip svg { width: 12px; height: 12px; fill: currentColor; }
  .bsc-filter-chip.active, .bsc-filter-chip:hover {
    background: var(--cinema-accent-teal);
    color: #ffffff;
    border-color: var(--cinema-accent-teal);
  }
  .bsc-acc-metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.75rem;
  }
  .bsc-acc-card {
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
  .bsc-acc-card:hover { transform: translateY(-2px); }
  .bsc-acc-card .label {
    font-size: 0.72rem;
    text-transform: uppercase;
    font-weight: 800;
    color: var(--cinema-text-muted);
    letter-spacing: 0.05em;
  }
  .bsc-acc-card .val {
    font-size: 1.55rem;
    font-weight: 900;
    font-family: var(--cinema-font-mono);
    margin-top: 0.25rem;
    line-height: 1.1;
  }
  .bsc-acc-icon-bubble {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .bsc-acc-icon-bubble svg { width: 20px; height: 20px; fill: currentColor; }
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
    .bsc-acc-metrics-grid { grid-template-columns: repeat(2, 1fr); }
    .bsc-report-two-col { grid-template-columns: 1fr; }
  }
  @media print {
    body * { visibility: hidden; }
    #bscPrintArea, #bscPrintArea * { visibility: visible; }
    #bscPrintArea { position: absolute; left: 0; top: 0; width: 100%; }
    .bsc-subtabs-nav, .bsc-acc-filter-bar, .bsc-action-btn-group, .button { display: none !important; }
  }
</style>

<!-- Navigation Subtabs with SVGs -->
<div class="bsc-subtabs-nav">
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=income')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'income') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M12.5 6.5H5.5V17.5H18.5V6.5H12.5ZM17 16H7V8H17V16ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z"/></svg>
    <?php esc_html_e('Income & Box Office', 'big-screen-cinemas'); ?>
  </a>
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=expense')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'expense') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
    <?php esc_html_e('Expenses & Royalties', 'big-screen-cinemas'); ?>
  </a>
  <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=report')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'report') ? 'active' : ''; ?>">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
    <?php esc_html_e('Financial Reports & P&L', 'big-screen-cinemas'); ?>
  </a>
</div>

<?php if (isset($_GET['income_added'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Revenue credit entry logged successfully!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['expense_added'])): ?>
  <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?php esc_html_e('Expense record posted to accounts ledger!', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
  <div style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    <?php esc_html_e('Ledger entry removed.', 'big-screen-cinemas'); ?>
  </div>
<?php endif; ?>

<!-- Date Filtering Control Bar -->
<div class="bsc-acc-filter-bar">
  <div class="bsc-filter-chips-group">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=' . $subtab . '&range_preset=all')); ?>" class="bsc-filter-chip <?php echo ($filter_mode === 'all') ? 'active' : ''; ?>">
      <?php esc_html_e('All Time', 'big-screen-cinemas'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=' . $subtab . '&range_preset=today')); ?>" class="bsc-filter-chip <?php echo ($filter_mode === 'today') ? 'active' : ''; ?>">
      <?php esc_html_e('Today', 'big-screen-cinemas'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=' . $subtab . '&range_preset=this_month')); ?>" class="bsc-filter-chip <?php echo ($filter_mode === 'this_month') ? 'active' : ''; ?>">
      <?php esc_html_e('This Month', 'big-screen-cinemas'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=' . $subtab . '&range_preset=this_year')); ?>" class="bsc-filter-chip <?php echo ($filter_mode === 'this_year') ? 'active' : ''; ?>">
      <?php esc_html_e('This Year', 'big-screen-cinemas'); ?>
    </a>
  </div>

  <form method="GET" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="display:flex; align-items:center; gap:8px;">
    <input type="hidden" name="page" value="bsc-accounts">
    <input type="hidden" name="subtab" value="<?php echo esc_attr($subtab); ?>">
    <input type="date" name="start_date" value="<?php echo esc_attr($filter_start); ?>" style="padding:5px 8px; font-size:0.8rem; border:1px solid var(--cinema-border); border-radius:6px; background:#fff;">
    <span style="color:var(--cinema-text-muted); font-size:0.8rem; font-weight:700;">to</span>
    <input type="date" name="end_date" value="<?php echo esc_attr($filter_end); ?>" style="padding:5px 8px; font-size:0.8rem; border:1px solid var(--cinema-border); border-radius:6px; background:#fff;">
    <button type="submit" class="button button-small" style="font-weight:800; display:inline-flex; align-items:center; gap:4px;">
      <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/></svg>
      <?php esc_html_e('Filter', 'big-screen-cinemas'); ?>
    </button>
  </form>
</div>

<!-- Real-Time Metrics Overview Deck -->
<div class="bsc-acc-metrics-grid">
  <div class="bsc-acc-card">
    <div>
      <div class="label"><?php esc_html_e('Total Gross Income', 'big-screen-cinemas'); ?></div>
      <div class="val" style="color:#059669;">+<?php echo esc_html($currency_symbol); ?> <?php echo number_format($total_income, 2); ?></div>
    </div>
    <div class="bsc-acc-icon-bubble" style="background:#ecfdf5; color:#059669;">
      <svg viewBox="0 0 24 24"><path d="M12.5 6.5H5.5V17.5H18.5V6.5H12.5ZM17 16H7V8H17V16ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z"/></svg>
    </div>
  </div>

  <div class="bsc-acc-card">
    <div>
      <div class="label"><?php esc_html_e('Total Operating Costs', 'big-screen-cinemas'); ?></div>
      <div class="val" style="color:#ef4444;">-<?php echo esc_html($currency_symbol); ?> <?php echo number_format($total_expense, 2); ?></div>
    </div>
    <div class="bsc-acc-icon-bubble" style="background:#fef2f2; color:#ef4444;">
      <svg viewBox="0 0 24 24"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
    </div>
  </div>

  <div class="bsc-acc-card">
    <div>
      <div class="label"><?php esc_html_e('Net Operating Profit', 'big-screen-cinemas'); ?></div>
      <div class="val" style="color: <?php echo ($net_profit >= 0) ? '#0d9488' : '#dc2626'; ?>;">
        <?php echo ($net_profit >= 0) ? '+' : '-'; ?><?php echo esc_html($currency_symbol); ?> <?php echo number_format(abs($net_profit), 2); ?>
      </div>
    </div>
    <div class="bsc-acc-icon-bubble" style="background:#f0fdfa; color:#0d9488;">
      <svg viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>
    </div>
  </div>

  <div class="bsc-acc-card">
    <div>
      <div class="label"><?php esc_html_e('Cash / bKash Split', 'big-screen-cinemas'); ?></div>
      <div class="val" style="font-size:1.15rem; margin-top:0.35rem;">
        <span style="color:#059669;"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($cash_income, 0); ?></span>
        <span style="color:var(--cinema-text-muted); font-size:0.85rem;">/</span>
        <span style="color:#d946ef;"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($bkash_income, 0); ?></span>
      </div>
    </div>
    <div class="bsc-acc-icon-bubble" style="background:#fdf2f8; color:#db2777;">
      <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
    </div>
  </div>
</div>