<?php
if (!defined('ABSPATH')) exit;

function bsc_render_accounts_page() {
  global $wpdb;
  $table_ledger   = $wpdb->prefix . 'bsc_ledger';
  $table_bookings = $wpdb->prefix . 'bsc_bookings';

  $subtab = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'income';

  // Date Range Filtering Parameters
  $filter_start = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
  $filter_end   = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
  $filter_mode  = isset($_GET['range_preset']) ? sanitize_key($_GET['range_preset']) : 'all';

  // Build WHERE clause based on active filter
  $date_where = " 1=1 ";
  if (!empty($filter_start) && !empty($filter_end)) {
    $date_where = $wpdb->prepare(" DATE(transaction_date) BETWEEN %s AND %s ", $filter_start, $filter_end);
  } elseif ($filter_mode === 'today') {
    $date_where = $wpdb->prepare(" DATE(transaction_date) = %s ", current_time('Y-m-d'));
  } elseif ($filter_mode === 'this_month') {
    $date_where = $wpdb->prepare(" transaction_date LIKE %s ", current_time('Y-m') . '%');
  } elseif ($filter_mode === 'this_year') {
    $date_where = $wpdb->prepare(" transaction_date LIKE %s ", current_time('Y') . '%');
  }

  // Aggregate Key Performance Metrics under active filter
  $total_income = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '')");
  $total_expense = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Expense')");
  $net_profit = $total_income - $total_expense;

  // Payment Method Breakdown
  $cash_income = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') AND (method = 'Cash' OR method LIKE '%%Cash%%')");
  $bkash_income = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') AND (method = 'bKash' OR method LIKE '%%bKash%%' OR method = 'Online Gateway')");

  // Category-wise Expense Breakdown
  $distributor_exp = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' AND particulars LIKE '%%Distributor%%'");
  $payroll_exp     = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' AND particulars LIKE '%%Payroll%%'");
  $utilities_exp   = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' AND particulars LIKE '%%Utilities%%'");
  $supplies_exp    = (float) $wpdb->get_var("SELECT SUM(amount) FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' AND particulars LIKE '%%Supplies%%'");
  $other_exp       = max(0, $total_expense - ($distributor_exp + $payroll_exp + $utilities_exp + $supplies_exp));

  bsc_render_page_shell_open('bsc-accounts');
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
    }

    .bsc-filter-chips-group {
      display: flex;
      gap: 6px;
    }

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
    }

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
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    }

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
      padding: 1.5rem;
    }

    .bsc-report-sheet h4 {
      margin: 0 0 1rem 0;
      font-size: 1rem;
      font-weight: 800;
      color: var(--cinema-text-main);
      display: flex;
      align-items: center;
      gap: 0.45rem;
    }

    .bsc-report-line-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px dashed var(--cinema-border-light);
      font-size: 0.9rem;
    }

    .bsc-report-line-item:last-child {
      border-bottom: none;
    }

    .bsc-report-line-item strong {
      font-family: var(--cinema-font-mono);
    }

    .bsc-channel-meter {
      background: #e2e8f0;
      height: 8px;
      border-radius: 9999px;
      overflow: hidden;
      margin: 0.4rem 0 1rem 0;
      display: flex;
    }

    .bsc-channel-meter-fill {
      height: 100%;
    }

    @media print {
      body * { visibility: hidden; }
      #bscPrintArea, #bscPrintArea * { visibility: visible; }
      #bscPrintArea { position: absolute; left: 0; top: 0; width: 100%; }
      .bsc-subtabs-nav, .bsc-acc-filter-bar, .bsc-action-btn-group, .button { display: none !important; }
    }
  </style>

  <!-- Navigation Subtabs -->
  <div class="bsc-subtabs-nav">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=income')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'income') ? 'active' : ''; ?>">
      💵 <?php esc_html_e('Income & Box Office', 'big-screen-cinemas'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=expense')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'expense') ? 'active' : ''; ?>">
      💸 <?php esc_html_e('Expenses & Royalties', 'big-screen-cinemas'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=report')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'report') ? 'active' : ''; ?>">
      📊 <?php esc_html_e('Financial Reports & P&L', 'big-screen-cinemas'); ?>
    </a>
  </div>

  <?php if (isset($_GET['income_added'])): ?>
    <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✓ <?php esc_html_e('Revenue credit entry logged successfully!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['expense_added'])): ?>
    <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✓ <?php esc_html_e('Expense record posted to accounts ledger!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['deleted'])): ?>
    <div style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✕ <?php esc_html_e('Ledger entry removed.', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <!-- Date Filtering Control Bar -->
  <div class="bsc-acc-filter-bar">
    <div class="bsc-filter-chips-group">
      <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=' . $subtab . '&range_preset=all')); ?>" class="bsc-filter-chip <?php echo ($filter_mode === 'all') ? 'active' : ''; ?>"><?php esc_html_e('All Time', 'big-screen-cinemas'); ?></a>
      <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=' . $subtab . '&range_preset=today')); ?>" class="bsc-filter-chip <?php echo ($filter_mode === 'today') ? 'active' : ''; ?>"><?php esc_html_e('Today', 'big-screen-cinemas'); ?></a>
      <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=' . $subtab . '&range_preset=this_month')); ?>" class="bsc-filter-chip <?php echo ($filter_mode === 'this_month') ? 'active' : ''; ?>"><?php esc_html_e('This Month', 'big-screen-cinemas'); ?></a>
      <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-accounts&subtab=' . $subtab . '&range_preset=this_year')); ?>" class="bsc-filter-chip <?php echo ($filter_mode === 'this_year') ? 'active' : ''; ?>"><?php esc_html_e('This Year', 'big-screen-cinemas'); ?></a>
    </div>

    <form method="GET" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="display:flex; align-items:center; gap:8px;">
      <input type="hidden" name="page" value="bsc-accounts">
      <input type="hidden" name="subtab" value="<?php echo esc_attr($subtab); ?>">
      <input type="date" name="start_date" value="<?php echo esc_attr($filter_start); ?>" style="padding:4px 8px; font-size:0.8rem; border:1px solid var(--cinema-border); border-radius:6px;">
      <span style="color:var(--cinema-text-muted); font-size:0.8rem;">to</span>
      <input type="date" name="end_date" value="<?php echo esc_attr($filter_end); ?>" style="padding:4px 8px; font-size:0.8rem; border:1px solid var(--cinema-border); border-radius:6px;">
      <button type="submit" class="button button-small" style="font-weight:700;"><?php esc_html_e('Filter', 'big-screen-cinemas'); ?></button>
    </form>
  </div>

  <!-- Real-Time Metrics Overview Deck -->
  <div class="bsc-acc-metrics-grid">
    <div class="bsc-acc-card">
      <div>
        <div class="label"><?php esc_html_e('Total Gross Income', 'big-screen-cinemas'); ?></div>
        <div class="val" style="color:#059669;">+৳<?php echo number_format($total_income, 2); ?></div>
      </div>
      <div class="bsc-metric-icon" style="background:#ecfdf5; color:#10b981;">💵</div>
    </div>

    <div class="bsc-acc-card">
      <div>
        <div class="label"><?php esc_html_e('Total Operating Costs', 'big-screen-cinemas'); ?></div>
        <div class="val" style="color:#ef4444;">-৳<?php echo number_format($total_expense, 2); ?></div>
      </div>
      <div class="bsc-metric-icon" style="background:#fef2f2; color:#ef4444;">💸</div>
    </div>

    <div class="bsc-acc-card">
      <div>
        <div class="label"><?php esc_html_e('Net Operating Profit', 'big-screen-cinemas'); ?></div>
        <div class="val" style="color: <?php echo ($net_profit >= 0) ? '#0d9488' : '#dc2626'; ?>;">
          <?php echo ($net_profit >= 0) ? '+৳' : '-৳'; ?><?php echo number_format(abs($net_profit), 2); ?>
        </div>
      </div>
      <div class="bsc-metric-icon" style="background:#f0fdfa; color:#0d9488;">📈</div>
    </div>

    <div class="bsc-acc-card">
      <div>
        <div class="label"><?php esc_html_e('Cash / bKash Split', 'big-screen-cinemas'); ?></div>
        <div class="val" style="font-size:1.15rem; margin-top:0.35rem;">
          <span style="color:#059669;">৳<?php echo number_format($cash_income, 0); ?></span>
          <span style="color:var(--cinema-text-muted); font-size:0.85rem;">/</span>
          <span style="color:#d946ef;">৳<?php echo number_format($bkash_income, 0); ?></span>
        </div>
      </div>
      <div class="bsc-metric-icon" style="background:#fdf2f8; color:#db2777;">💳</div>
    </div>
  </div>

  <div id="bscPrintArea">
    <?php
    /* ==========================================================================
       TAB 1: INCOME & BOX OFFICE
       ========================================================================== */
    if ($subtab === 'income'):
      $incomes = $wpdb->get_results("SELECT * FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') ORDER BY id DESC");
    ?>
      <div class="bsc-report-two-col" style="align-items:start;">
        <!-- Manual Revenue / Concession Entry Form -->
        <div class="cinema-panel" style="margin:0;">
          <h3 class="cinema-panel-title" style="margin-bottom:1.25rem;">➕ <?php esc_html_e('Log Direct Revenue / Concessions', 'big-screen-cinemas'); ?></h3>
          
          <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bsc_add_income_verify'); ?>
            <input type="hidden" name="action" value="bsc_add_income">

            <div class="bsc-form-group">
              <label><?php esc_html_e('Particulars / Revenue Item', 'big-screen-cinemas'); ?> *</label>
              <input type="text" name="income_particulars" required placeholder="e.g. Gourmet Popcorn Combo Sales, Hall Private Rental">
            </div>

            <div class="bsc-form-grid-2">
              <div class="bsc-form-group">
                <label><?php esc_html_e('Revenue Stream', 'big-screen-cinemas'); ?> *</label>
                <select name="income_category" required>
                  <option value="Concessions & F&B"><?php esc_html_e('Concessions & Snacks F&B', 'big-screen-cinemas'); ?></option>
                  <option value="Private Hall Booking"><?php esc_html_e('Private Hall Booking', 'big-screen-cinemas'); ?></option>
                  <option value="On-Screen Sponsorship"><?php esc_html_e('On-Screen Brand Sponsorship', 'big-screen-cinemas'); ?></option>
                  <option value="VIP Lounge Upgrades"><?php esc_html_e('VIP Lounge Upgrades', 'big-screen-cinemas'); ?></option>
                  <option value="Merchandise Sales"><?php esc_html_e('Merchandise Sales', 'big-screen-cinemas'); ?></option>
                </select>
              </div>

              <div class="bsc-form-group">
                <label><?php esc_html_e('Payment Channel', 'big-screen-cinemas'); ?> *</label>
                <select name="income_method" required>
                  <option value="Cash"><?php esc_html_e('Counter Cash', 'big-screen-cinemas'); ?></option>
                  <option value="bKash"><?php esc_html_e('bKash Merchant', 'big-screen-cinemas'); ?></option>
                  <option value="Bank Transfer"><?php esc_html_e('Bank Transfer', 'big-screen-cinemas'); ?></option>
                </select>
              </div>
            </div>

            <div class="bsc-form-group">
              <label><?php esc_html_e('Credit Amount (BDT)', 'big-screen-cinemas'); ?> *</label>
              <input type="number" step="0.01" name="income_amount" required placeholder="0.00">
            </div>

            <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:9px; font-weight:800;">
              💵 <?php esc_html_e('Record Revenue Credit', 'big-screen-cinemas'); ?>
            </button>
          </form>
        </div>

        <!-- Income Stream Breakdown -->
        <div class="cinema-panel" style="margin:0;">
          <div class="cinema-panel-header">
            <h3 class="cinema-panel-title">💳 <?php esc_html_e('Revenue Channel Analytics', 'big-screen-cinemas'); ?></h3>
          </div>

          <?php
            $total_inc_calc = max(1, $total_income);
            $cash_pct  = round(($cash_income / $total_inc_calc) * 100);
            $bkash_pct = round(($bkash_income / $total_inc_calc) * 100);
          ?>
          <div style="font-size:0.85rem; margin-bottom:1rem;">
            <div style="display:flex; justify-content:space-between; font-weight:700;">
              <span>Cash (<?php echo esc_html($cash_pct); ?>%)</span>
              <span>bKash / Gateway (<?php echo esc_html($bkash_pct); ?>%)</span>
            </div>
            <div class="bsc-channel-meter">
              <div class="bsc-channel-meter-fill" style="width:<?php echo esc_attr($cash_pct); ?>%; background:#10b981;"></div>
              <div class="bsc-channel-meter-fill" style="width:<?php echo esc_attr($bkash_pct); ?>%; background:#d946ef;"></div>
            </div>
          </div>

          <div class="cinema-table-wrap">
            <table class="cinema-data-table">
              <thead>
                <tr>
                  <th>STREAM</th>
                  <th>CHANNEL</th>
                  <th style="text-align:right;">TOTAL VALUE</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>Box Office Ticket Sales</strong></td>
                  <td><span class="cinema-badge-payment">Automated</span></td>
                  <td style="text-align:right; font-weight:800; color:#059669; font-family:var(--cinema-font-mono);">৳<?php echo number_format($total_income, 2); ?></td>
                </tr>
                <tr>
                  <td><strong>Physical Cash Desk</strong></td>
                  <td><span class="cinema-badge-payment">Cash Desk</span></td>
                  <td style="text-align:right; font-weight:800; color:#059669; font-family:var(--cinema-font-mono);">৳<?php echo number_format($cash_income, 2); ?></td>
                </tr>
                <tr>
                  <td><strong>bKash Merchant Pay</strong></td>
                  <td><span class="cinema-badge-payment" style="color:#d946ef;">MFS Pay</span></td>
                  <td style="text-align:right; font-weight:800; color:#d946ef; font-family:var(--cinema-font-mono);">৳<?php echo number_format($bkash_income, 2); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Income Ledger Table -->
      <div class="cinema-panel" style="margin-top:1.5rem;">
        <div class="cinema-panel-header">
          <h3 class="cinema-panel-title">📋 <?php echo esc_html(sprintf(__('Income Transactions (%d)', 'big-screen-cinemas'), count($incomes))); ?></h3>
          <button type="button" class="button button-secondary" onclick="exportTableToCSV('bsc-income-report.csv')">📥 <?php esc_html_e('Export CSV', 'big-screen-cinemas'); ?></button>
        </div>

        <div class="cinema-table-wrap">
          <table class="cinema-data-table" id="bscDataTable">
            <thead>
              <tr>
                <th>REF TOKEN</th>
                <th>PARTICULARS</th>
                <th>PAYMENT METHOD</th>
                <th>DATE &amp; TIME</th>
                <th style="text-align:right;">CREDIT AMOUNT</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($incomes)): foreach ($incomes as $l): ?>
                <tr>
                  <td><span class="cinema-voucher-tag"><?php echo esc_html($l->reference_no); ?></span></td>
                  <td><strong><?php echo esc_html($l->particulars); ?></strong></td>
                  <td><span class="cinema-badge-payment"><?php echo esc_html($l->method); ?></span></td>
                  <td><small style="color:var(--cinema-text-muted);"><?php echo esc_html($l->transaction_date); ?></small></td>
                  <td style="text-align:right; color:#059669; font-weight:900; font-family:var(--cinema-font-mono);">
                    +৳<?php echo number_format($l->amount, 2); ?>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center; padding:2rem;"><?php esc_html_e('No income records match this filter period.', 'big-screen-cinemas'); ?></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php
    /* ==========================================================================
       TAB 2: EXPENSES & ROYALTIES
       ========================================================================== */
    elseif ($subtab === 'expense'):
      $expenses = $wpdb->get_results("SELECT * FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' ORDER BY id DESC");
    ?>
      <div class="bsc-report-two-col" style="align-items:start;">
        <!-- Post Operating Expense Form -->
        <div class="cinema-panel" style="margin:0;">
          <h3 class="cinema-panel-title" style="margin-bottom:1.25rem;">💸 <?php esc_html_e('Post Operating Cost / Royalty', 'big-screen-cinemas'); ?></h3>
          
          <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bsc_add_expense_verify'); ?>
            <input type="hidden" name="action" value="bsc_add_expense">

            <div class="bsc-form-group">
              <label><?php esc_html_e('Expense Particulars / Description', 'big-screen-cinemas'); ?> *</label>
              <input type="text" name="expense_particulars" required placeholder="e.g. Disney 50% Box-Office Share, Projection Laser Lamp">
            </div>

            <div class="bsc-form-grid-2">
              <div class="bsc-form-group">
                <label><?php esc_html_e('Cost Category', 'big-screen-cinemas'); ?> *</label>
                <select name="expense_category" required>
                  <option value="Film Distributor Share"><?php esc_html_e('Film Distributor Share / Royalty', 'big-screen-cinemas'); ?></option>
                  <option value="Staff & Crew Payroll"><?php esc_html_e('Staff & Crew Payroll', 'big-screen-cinemas'); ?></option>
                  <option value="Electricity & Utilities"><?php esc_html_e('Electricity & AC Utilities', 'big-screen-cinemas'); ?></option>
                  <option value="Equipment & Projection"><?php esc_html_e('Equipment & Laser Maintenance', 'big-screen-cinemas'); ?></option>
                  <option value="Concession Supplies"><?php esc_html_e('Popcorn & Concession Supplies', 'big-screen-cinemas'); ?></option>
                  <option value="Facility Cleaning"><?php esc_html_e('Facility Sanitization & Cleaning', 'big-screen-cinemas'); ?></option>
                </select>
              </div>

              <div class="bsc-form-group">
                <label><?php esc_html_e('Payment Channel', 'big-screen-cinemas'); ?> *</label>
                <select name="expense_method" required>
                  <option value="Cash"><?php esc_html_e('Petty Cash Desk', 'big-screen-cinemas'); ?></option>
                  <option value="Bank Transfer"><?php esc_html_e('Company Bank Account', 'big-screen-cinemas'); ?></option>
                  <option value="bKash"><?php esc_html_e('bKash Corporate', 'big-screen-cinemas'); ?></option>
                </select>
              </div>
            </div>

            <div class="bsc-form-group">
              <label><?php esc_html_e('Debit Amount (BDT)', 'big-screen-cinemas'); ?> *</label>
              <input type="number" step="0.01" name="expense_amount" required placeholder="0.00">
            </div>

            <button type="submit" class="button button-primary" style="background:#ef4444; border-color:#ef4444; width:100%; padding:9px; font-weight:800;">
              💸 <?php esc_html_e('Record Outflow Expense', 'big-screen-cinemas'); ?>
            </button>
          </form>
        </div>

        <!-- Cost Center Breakdown -->
        <div class="cinema-panel" style="margin:0;">
          <div class="cinema-panel-header">
            <h3 class="cinema-panel-title">🏷️ <?php esc_html_e('Cost Center Allocation', 'big-screen-cinemas'); ?></h3>
          </div>

          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Distributor Royalties', 'big-screen-cinemas'); ?></span>
            <strong style="color:#ef4444;">-৳<?php echo number_format($distributor_exp, 2); ?></strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Payroll & Projectionists', 'big-screen-cinemas'); ?></span>
            <strong style="color:#ef4444;">-৳<?php echo number_format($payroll_exp, 2); ?></strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Electricity & Multiplex AC', 'big-screen-cinemas'); ?></span>
            <strong style="color:#ef4444;">-৳<?php echo number_format($utilities_exp, 2); ?></strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Concessions & Supplies', 'big-screen-cinemas'); ?></span>
            <strong style="color:#ef4444;">-৳<?php echo number_format($supplies_exp, 2); ?></strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Other Maintenance Costs', 'big-screen-cinemas'); ?></span>
            <strong style="color:#ef4444;">-৳<?php echo number_format($other_exp, 2); ?></strong>
          </div>
        </div>
      </div>

      <!-- Expense Ledger Table -->
      <div class="cinema-panel" style="margin-top:1.5rem;">
        <div class="cinema-panel-header">
          <h3 class="cinema-panel-title">📋 <?php echo esc_html(sprintf(__('Expense Transactions (%d)', 'big-screen-cinemas'), count($expenses))); ?></h3>
          <button type="button" class="button button-secondary" onclick="exportTableToCSV('bsc-expense-report.csv')">📥 <?php esc_html_e('Export CSV', 'big-screen-cinemas'); ?></button>
        </div>

        <div class="cinema-table-wrap">
          <table class="cinema-data-table" id="bscDataTable">
            <thead>
              <tr>
                <th>REF TOKEN</th>
                <th>PARTICULARS</th>
                <th>METHOD</th>
                <th>DATE</th>
                <th style="text-align:right;">DEBIT AMOUNT</th>
                <th style="text-align:center;">ACTION</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($expenses)): foreach ($expenses as $e): 
                $del_url = wp_nonce_url(admin_url('admin-post.php?action=bsc_delete_ledger_item&id=' . $e->id), 'bsc_delete_ledger_' . $e->id);
              ?>
                <tr>
                  <td><span class="cinema-voucher-tag" style="background:#fef2f2; border-color:#fecaca; color:#dc2626;"><?php echo esc_html($e->reference_no); ?></span></td>
                  <td><strong><?php echo esc_html($e->particulars); ?></strong></td>
                  <td><span class="cinema-badge-payment"><?php echo esc_html($e->method); ?></span></td>
                  <td><small style="color:var(--cinema-text-muted);"><?php echo esc_html($e->transaction_date); ?></small></td>
                  <td style="text-align:right; color:#ef4444; font-weight:900; font-family:var(--cinema-font-mono);">-৳<?php echo number_format($e->amount, 2); ?></td>
                  <td style="text-align:center;">
                    <a href="<?php echo esc_url($del_url); ?>" class="bsc-btn-action delete" onclick="return confirm('Delete this expense entry?');">✕</a>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center; padding:1.5rem;"><?php esc_html_e('No operational expenses recorded in this period.', 'big-screen-cinemas'); ?></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php
    /* ==========================================================================
       TAB 3: REPORTS & P&L STATEMENTS
       ========================================================================== */
    else:
      $combined_ledger = $wpdb->get_results("SELECT * FROM $table_ledger WHERE ($date_where) ORDER BY id DESC LIMIT 50");
    ?>
      <div style="display:flex; justify-content:flex-end; gap:8px; margin-bottom:1rem;">
        <button type="button" class="button button-primary" onclick="window.print()" style="background:#0d9488; border-color:#0d9488; font-weight:700;">🖨️ <?php esc_html_e('Print Financial Statement', 'big-screen-cinemas'); ?></button>
        <button type="button" class="button button-secondary" onclick="exportTableToCSV('bsc-pnl-report.csv')">📥 <?php esc_html_e('Download CSV', 'big-screen-cinemas'); ?></button>
      </div>

      <div class="bsc-report-two-col">
        <!-- P&L Statement -->
        <div class="bsc-report-sheet">
          <h4>📊 <?php esc_html_e('Income Statement (P&L)', 'big-screen-cinemas'); ?></h4>
          
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('1. Box Office & Gross Revenues', 'big-screen-cinemas'); ?></span>
            <strong style="color:#059669;">+৳<?php echo number_format($total_income, 2); ?></strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('2. Film Royalties & Direct Costs', 'big-screen-cinemas'); ?></span>
            <strong style="color:#ef4444;">-৳<?php echo number_format($distributor_exp, 2); ?></strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="font-weight:700; color:var(--cinema-text-main);"><?php esc_html_e('Gross Operating Margin', 'big-screen-cinemas'); ?></span>
            <strong style="color:#0d9488;">৳<?php echo number_format($total_income - $distributor_exp, 2); ?></strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('3. General Operating & Facility Expenses', 'big-screen-cinemas'); ?></span>
            <strong style="color:#ef4444;">-৳<?php echo number_format($total_expense - $distributor_exp, 2); ?></strong>
          </div>
          <div class="bsc-report-line-item" style="border-top:2px solid var(--cinema-border); padding-top:1rem; margin-top:0.5rem;">
            <span style="font-weight:900; color:var(--cinema-text-main); font-size:1.05rem;"><?php esc_html_e('Net Retained Profit', 'big-screen-cinemas'); ?></span>
            <strong style="font-size:1.25rem; color:<?php echo ($net_profit >= 0) ? '#0d9488' : '#dc2626'; ?>;">
              <?php echo ($net_profit >= 0) ? '+৳' : '-৳'; ?><?php echo number_format(abs($net_profit), 2); ?>
            </strong>
          </div>
        </div>

        <!-- Period Ratios & Efficiency Summary -->
        <div class="bsc-report-sheet">
          <h4>📑 <?php esc_html_e('Key Financial Ratios', 'big-screen-cinemas'); ?></h4>
          
          <?php
            $margin_rate = ($total_income > 0) ? round(($net_profit / $total_income) * 100, 1) : 0;
            $expense_ratio = ($total_income > 0) ? round(($total_expense / $total_income) * 100, 1) : 0;
          ?>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Net Profit Margin', 'big-screen-cinemas'); ?></span>
            <strong style="color:<?php echo ($margin_rate >= 0) ? '#059669' : '#dc2626'; ?>;"><?php echo esc_html($margin_rate); ?>%</strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Operating Expense Ratio (OER)', 'big-screen-cinemas'); ?></span>
            <strong><?php echo esc_html($expense_ratio); ?>%</strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Cash Settlement Share', 'big-screen-cinemas'); ?></span>
            <strong style="color:#059669;">৳<?php echo number_format($cash_income, 2); ?></strong>
          </div>
          <div class="bsc-report-line-item">
            <span style="color:var(--cinema-text-muted);"><?php esc_html_e('bKash Digital Collection', 'big-screen-cinemas'); ?></span>
            <strong style="color:#d946ef;">৳<?php echo number_format($bkash_income, 2); ?></strong>
          </div>
        </div>
      </div>

      <!-- Combined General Ledger Table -->
      <div class="cinema-panel" style="margin:0;">
        <div class="cinema-panel-header">
          <h3 class="cinema-panel-title">📑 <?php esc_html_e('Combined General Ledger Feed', 'big-screen-cinemas'); ?></h3>
        </div>

        <div class="cinema-table-wrap">
          <table class="cinema-data-table" id="bscDataTable">
            <thead>
              <tr>
                <th>TOKEN</th>
                <th>TYPE</th>
                <th>PARTICULARS</th>
                <th>CHANNEL</th>
                <th>DATE</th>
                <th style="text-align:right;">AMOUNT</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($combined_ledger)): foreach ($combined_ledger as $item): 
                $is_exp = ($item->txn_type === 'Expense');
              ?>
                <tr>
                  <td><span class="cinema-voucher-tag" style="<?php echo $is_exp ? 'background:#fef2f2; border-color:#fecaca; color:#dc2626;' : ''; ?>"><?php echo esc_html($item->reference_no); ?></span></td>
                  <td>
                    <span class="cinema-badge-pill active" style="<?php echo $is_exp ? 'background:rgba(239,68,68,0.15); color:#ef4444; border-color:rgba(239,68,68,0.3);' : ''; ?>">
                      <?php echo $is_exp ? 'EXPENSE' : 'INCOME'; ?>
                    </span>
                  </td>
                  <td><strong><?php echo esc_html($item->particulars); ?></strong></td>
                  <td><span class="cinema-badge-payment"><?php echo esc_html($item->method); ?></span></td>
                  <td><small style="color:var(--cinema-text-muted);"><?php echo esc_html($item->transaction_date); ?></small></td>
                  <td style="text-align:right; font-weight:900; font-family:var(--cinema-font-mono); color:<?php echo $is_exp ? '#ef4444' : '#059669'; ?>;">
                    <?php echo $is_exp ? '-৳' : '+৳'; ?><?php echo number_format($item->amount, 2); ?>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center; padding:2rem;"><?php esc_html_e('No transactions found in this date window.', 'big-screen-cinemas'); ?></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php endif; ?>
  </div>

  <script>
    function exportTableToCSV(filename) {
      let csv = [];
      const rows = document.querySelectorAll("#bscDataTable tr");
      for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
          row.push('"' + cols[j].innerText.replace(/"/g, '""').trim() + '"');
        }
        csv.push(row.join(","));
      }
      const csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
      const downloadLink = document.createElement("a");
      downloadLink.download = filename;
      downloadLink.href = window.URL.createObjectURL(csvFile);
      downloadLink.style.display = "none";
      document.body.appendChild(downloadLink);
      downloadLink.click();
      document.body.removeChild(downloadLink);
    }
  </script>
  <?php
  bsc_render_page_shell_close();
}