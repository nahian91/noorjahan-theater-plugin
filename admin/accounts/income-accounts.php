<?php
if (!defined('ABSPATH')) exit;

$incomes = $wpdb->get_results("SELECT * FROM $table_ledger WHERE ($date_where) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') ORDER BY id DESC");
?>
<div id="bscPrintArea">
  <div class="bsc-report-two-col" style="align-items:start;">
    <!-- Manual Revenue Entry Form -->
    <div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
      <h3 class="cinema-panel-title" style="margin-bottom:1.25rem;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        <?php esc_html_e('Log Direct Revenue / Concessions', 'big-screen-cinemas'); ?>
      </h3>
      
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
          <label><?php esc_html_e('Credit Amount', 'big-screen-cinemas'); ?> (<?php echo esc_html($currency_symbol); ?>) *</label>
          <input type="number" step="0.01" name="income_amount" required placeholder="0.00">
        </div>

        <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:10px; font-weight:800; border-radius:6px; display:flex; align-items:center; justify-content:center; gap:6px;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12.5 6.5H5.5V17.5H18.5V6.5H12.5ZM17 16H7V8H17V16ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z"/></svg>
          <?php esc_html_e('Record Revenue Credit', 'big-screen-cinemas'); ?>
        </button>
      </form>
    </div>

    <!-- Income Stream Breakdown -->
    <div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
      <div class="cinema-panel-header">
        <h3 class="cinema-panel-title">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
          <?php esc_html_e('Revenue Channel Analytics', 'big-screen-cinemas'); ?>
        </h3>
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
              <td style="text-align:right; font-weight:800; color:#059669; font-family:var(--cinema-font-mono);"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($total_income, 2); ?></td>
            </tr>
            <tr>
              <td><strong>Physical Cash Desk</strong></td>
              <td><span class="cinema-badge-payment">Cash Desk</span></td>
              <td style="text-align:right; font-weight:800; color:#059669; font-family:var(--cinema-font-mono);"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($cash_income, 2); ?></td>
            </tr>
            <tr>
              <td><strong>bKash Merchant Pay</strong></td>
              <td><span class="cinema-badge-payment" style="color:#d946ef;">MFS Pay</span></td>
              <td style="text-align:right; font-weight:800; color:#d946ef; font-family:var(--cinema-font-mono);"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($bkash_income, 2); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Income Ledger Table -->
  <div class="cinema-panel" style="margin-top:1.5rem; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
    <div class="cinema-panel-header">
      <h3 class="cinema-panel-title">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
        <?php echo esc_html(sprintf(__('Income Transactions (%d)', 'big-screen-cinemas'), count($incomes))); ?>
      </h3>
      <button type="button" class="button button-secondary" onclick="exportTableToCSV('bsc-income-report.csv')" style="display:inline-flex; align-items:center; gap:6px; font-weight:700;">
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
                +<?php echo esc_html($currency_symbol); ?> <?php echo number_format($l->amount, 2); ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="5" style="text-align:center; padding:2rem; color:var(--cinema-text-muted);"><?php esc_html_e('No income records match this filter period.', 'big-screen-cinemas'); ?></td></tr>
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