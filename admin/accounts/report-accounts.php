<?php
if (!defined('ABSPATH')) exit;

$combined_ledger = $wpdb->get_results("SELECT * FROM $table_ledger WHERE ($date_where) ORDER BY id DESC LIMIT 50");
?>
<div id="bscPrintArea">
  <div style="display:flex; justify-content:flex-end; gap:8px; margin-bottom:1.25rem;">
    <button type="button" class="button button-primary" onclick="window.print()" style="background:#0d9488; border-color:#0d9488; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
      <?php esc_html_e('Print Financial Statement', 'big-screen-cinemas'); ?>
    </button>
    <button type="button" class="button button-secondary" onclick="exportTableToCSV('bsc-pnl-report.csv')" style="font-weight:700; display:inline-flex; align-items:center; gap:6px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
      <?php esc_html_e('Download CSV', 'big-screen-cinemas'); ?>
    </button>
  </div>

  <div class="bsc-report-two-col">
    <!-- P&L Statement -->
    <div class="bsc-report-sheet">
      <h4>
        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
        <?php esc_html_e('Income Statement (P&L)', 'big-screen-cinemas'); ?>
      </h4>
      
      <div class="bsc-report-line-item">
        <span style="color:var(--cinema-text-muted);"><?php esc_html_e('1. Box Office & Gross Revenues', 'big-screen-cinemas'); ?></span>
        <strong style="color:#059669;">+<?php echo esc_html($currency_symbol); ?> <?php echo number_format($total_income, 2); ?></strong>
      </div>
      <div class="bsc-report-line-item">
        <span style="color:var(--cinema-text-muted);"><?php esc_html_e('2. Film Royalties & Direct Costs', 'big-screen-cinemas'); ?></span>
        <strong style="color:#ef4444;">-<?php echo esc_html($currency_symbol); ?> <?php echo number_format($distributor_exp, 2); ?></strong>
      </div>
      <div class="bsc-report-line-item">
        <span style="font-weight:700; color:var(--cinema-text-main);"><?php esc_html_e('Gross Operating Margin', 'big-screen-cinemas'); ?></span>
        <strong style="color:#0d9488;"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($total_income - $distributor_exp, 2); ?></strong>
      </div>
      <div class="bsc-report-line-item">
        <span style="color:var(--cinema-text-muted);"><?php esc_html_e('3. General Operating & Facility Expenses', 'big-screen-cinemas'); ?></span>
        <strong style="color:#ef4444;">-<?php echo esc_html($currency_symbol); ?> <?php echo number_format($total_expense - $distributor_exp, 2); ?></strong>
      </div>
      <div class="bsc-report-line-item" style="border-top:2px solid var(--cinema-border); padding-top:1rem; margin-top:0.5rem;">
        <span style="font-weight:900; color:var(--cinema-text-main); font-size:1.05rem;"><?php esc_html_e('Net Retained Profit', 'big-screen-cinemas'); ?></span>
        <strong style="font-size:1.25rem; color:<?php echo ($net_profit >= 0) ? '#0d9488' : '#dc2626'; ?>;">
          <?php echo ($net_profit >= 0) ? '+' : '-'; ?><?php echo esc_html($currency_symbol); ?> <?php echo number_format(abs($net_profit), 2); ?>
        </strong>
      </div>
    </div>

    <!-- Period Ratios & Efficiency Summary -->
    <div class="bsc-report-sheet">
      <h4>
        <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
        <?php esc_html_e('Key Financial Ratios', 'big-screen-cinemas'); ?>
      </h4>
      
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
        <strong style="color:#059669;"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($cash_income, 2); ?></strong>
      </div>
      <div class="bsc-report-line-item">
        <span style="color:var(--cinema-text-muted);"><?php esc_html_e('bKash Digital Collection', 'big-screen-cinemas'); ?></span>
        <strong style="color:#d946ef;"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($bkash_income, 2); ?></strong>
      </div>
    </div>
  </div>

  <!-- Combined General Ledger Table -->
  <div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
    <div class="cinema-panel-header">
      <h3 class="cinema-panel-title">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
        <?php esc_html_e('Combined General Ledger Feed', 'big-screen-cinemas'); ?>
      </h3>
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
                <?php echo $is_exp ? '-' : '+'; ?><?php echo esc_html($currency_symbol); ?> <?php echo number_format($item->amount, 2); ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center; padding:2.5rem; color:var(--cinema-text-muted);"><?php esc_html_e('No transactions found in this date window.', 'big-screen-cinemas'); ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
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