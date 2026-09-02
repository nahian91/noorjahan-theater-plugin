<?php
if (!defined('ABSPATH')) exit;

$expenses = $wpdb->get_results("SELECT * FROM $table_ledger WHERE ($date_where) AND txn_type = 'Expense' ORDER BY id DESC");
?>
<div id="bscPrintArea">
  <div class="bsc-report-two-col" style="align-items:start;">
    <!-- Post Operating Expense Form -->
    <div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
      <h3 class="cinema-panel-title" style="margin-bottom:1.25rem;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
        <?php esc_html_e('Post Operating Cost / Royalty', 'big-screen-cinemas'); ?>
      </h3>
      
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
          <label><?php esc_html_e('Debit Amount', 'big-screen-cinemas'); ?> (<?php echo esc_html($currency_symbol); ?>) *</label>
          <input type="number" step="0.01" name="expense_amount" required placeholder="0.00">
        </div>

        <button type="submit" class="button button-primary" style="background:#ef4444; border-color:#ef4444; width:100%; padding:10px; font-weight:800; border-radius:6px; display:flex; align-items:center; justify-content:center; gap:6px;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
          <?php esc_html_e('Record Outflow Expense', 'big-screen-cinemas'); ?>
        </button>
      </form>
    </div>

    <!-- Cost Center Breakdown -->
    <div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
      <div class="cinema-panel-header">
        <h3 class="cinema-panel-title">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
          <?php esc_html_e('Cost Center Allocation', 'big-screen-cinemas'); ?>
        </h3>
      </div>

      <div class="bsc-report-line-item">
        <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Distributor Royalties', 'big-screen-cinemas'); ?></span>
        <strong style="color:#ef4444;">-<?php echo esc_html($currency_symbol); ?> <?php echo number_format($distributor_exp, 2); ?></strong>
      </div>
      <div class="bsc-report-line-item">
        <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Payroll & Projectionists', 'big-screen-cinemas'); ?></span>
        <strong style="color:#ef4444;">-<?php echo esc_html($currency_symbol); ?> <?php echo number_format($payroll_exp, 2); ?></strong>
      </div>
      <div class="bsc-report-line-item">
        <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Electricity & Multiplex AC', 'big-screen-cinemas'); ?></span>
        <strong style="color:#ef4444;">-<?php echo esc_html($currency_symbol); ?> <?php echo number_format($utilities_exp, 2); ?></strong>
      </div>
      <div class="bsc-report-line-item">
        <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Concessions & Supplies', 'big-screen-cinemas'); ?></span>
        <strong style="color:#ef4444;">-<?php echo esc_html($currency_symbol); ?> <?php echo number_format($supplies_exp, 2); ?></strong>
      </div>
      <div class="bsc-report-line-item">
        <span style="color:var(--cinema-text-muted);"><?php esc_html_e('Other Maintenance Costs', 'big-screen-cinemas'); ?></span>
        <strong style="color:#ef4444;">-<?php echo esc_html($currency_symbol); ?> <?php echo number_format($other_exp, 2); ?></strong>
      </div>
    </div>
  </div>

  <!-- Expense Ledger Table -->
  <div class="cinema-panel" style="margin-top:1.5rem; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
    <div class="cinema-panel-header">
      <h3 class="cinema-panel-title">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
        <?php echo esc_html(sprintf(__('Expense Transactions (%d)', 'big-screen-cinemas'), count($expenses))); ?>
      </h3>
      <button type="button" class="button button-secondary" onclick="exportTableToCSV('bsc-expense-report.csv')" style="display:inline-flex; align-items:center; gap:6px; font-weight:700;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
        <?php esc_html_e('Export CSV', 'big-screen-cinemas'); ?>
      </button>
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
            <th style="text-align:center;">ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($expenses)): foreach ($expenses as $e): 
            $del_url = wp_nonce_url(admin_url('admin.php?page=bsc-accounts&subtab=expense&action=delete&id=' . $e->id), 'bsc_delete_ledger_' . $e->id);
          ?>
            <tr>
              <td><span class="cinema-voucher-tag" style="background:#fef2f2; border-color:#fecaca; color:#dc2626;"><?php echo esc_html($e->reference_no); ?></span></td>
              <td><strong><?php echo esc_html($e->particulars); ?></strong></td>
              <td><span class="cinema-badge-payment"><?php echo esc_html($e->method); ?></span></td>
              <td><small style="color:var(--cinema-text-muted);"><?php echo esc_html($e->transaction_date); ?></small></td>
              <td style="text-align:right; color:#ef4444; font-weight:900; font-family:var(--cinema-font-mono);">
                -<?php echo esc_html($currency_symbol); ?> <?php echo number_format($e->amount, 2); ?>
              </td>
              <td style="text-align:center;">
                <div class="bsc-table-actions">
                  <a href="<?php echo esc_url($del_url); ?>" class="bsc-action-btn delete" onclick="return confirm('Delete this expense entry?');">
                    <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    <span><?php esc_html_e('Delete', 'big-screen-cinemas'); ?></span>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--cinema-text-muted);"><?php esc_html_e('No operational expenses recorded in this period.', 'big-screen-cinemas'); ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>