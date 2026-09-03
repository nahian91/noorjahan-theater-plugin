<?php
/**
 * Accounts Module - Direct Revenue & Concessions View
 *
 * Path: admin/accounts/income-accounts.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_ledger = isset( $table_ledger ) ? $table_ledger : $wpdb->prefix . 'bsc_ledger';
$date_where   = isset( $date_where ) ? $date_where : '1=1';

$incomes            = $wpdb->get_results( "SELECT * FROM {$table_ledger} WHERE ({$date_where}) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') ORDER BY id DESC" );
$today_date_default = current_time( 'Y-m-d' );
$currency_symbol    = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );
?>
<div id="bscPrintArea">
  <div class="cinema-panel" style="max-width:750px; margin:0 auto; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
    <h3 class="cinema-panel-title" style="margin-bottom:1.25rem; display:flex; align-items:center; gap:8px;">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      <?php esc_html_e( 'Other Income', 'bsc-cinema-manager' ); ?>
    </h3>
    
    <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=income' ) ); ?>">
      <?php wp_nonce_field( 'bsc_add_income_verify' ); ?>
      <input type="hidden" name="bsc_action" value="add_income">

      <div class="bsc-form-group" style="margin-bottom:1rem;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Particulars / Revenue Item', 'bsc-cinema-manager' ); ?> *</label>
        <input type="text" name="income_particulars" required placeholder="e.g. Gourmet Popcorn Combo Sales, Hall Private Rental" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
      </div>

      <div class="bsc-form-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:1rem;">
        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Revenue Stream', 'bsc-cinema-manager' ); ?> *</label>
          <select name="income_category" id="incomeCategorySelect" required onchange="onIncomeCategoryChange(this)" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
            <option value="Concessions & F&B"><?php esc_html_e( 'Concessions & Snacks F&B', 'bsc-cinema-manager' ); ?></option>
            <option value="Private Hall Booking"><?php esc_html_e( 'Private Hall Booking', 'bsc-cinema-manager' ); ?></option>
            <option value="On-Screen Sponsorship"><?php esc_html_e( 'On-Screen Brand Sponsorship', 'bsc-cinema-manager' ); ?></option>
            <option value="VIP Lounge Upgrades"><?php esc_html_e( 'VIP Lounge Upgrades', 'bsc-cinema-manager' ); ?></option>
            <option value="Merchandise Sales"><?php esc_html_e( 'Merchandise Sales', 'bsc-cinema-manager' ); ?></option>
            <option value="Other"><?php esc_html_e( 'Other', 'bsc-cinema-manager' ); ?></option>
          </select>
        </div>

        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Payment Channel', 'bsc-cinema-manager' ); ?> *</label>
          <select name="income_method" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
            <option value="Cash"><?php esc_html_e( 'Counter Cash', 'bsc-cinema-manager' ); ?></option>
            <option value="bKash"><?php esc_html_e( 'bKash Merchant', 'bsc-cinema-manager' ); ?></option>
            <option value="Bank Transfer"><?php esc_html_e( 'Bank Transfer', 'bsc-cinema-manager' ); ?></option>
          </select>
        </div>
      </div>

      <!-- Dynamic Other Details Box (Hidden by default) -->
      <div class="bsc-form-group" id="otherDetailsBox" style="margin-bottom:1rem; display:none;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Other Stream Details', 'bsc-cinema-manager' ); ?> *</label>
        <textarea name="income_other_details" id="incomeOtherDetailsInput" rows="2" placeholder="Specify revenue source details..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;"></textarea>
      </div>

      <div class="bsc-form-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:1rem;">
        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Transaction Date', 'bsc-cinema-manager' ); ?> *</label>
          <input type="date" name="income_date" required max="<?php echo esc_attr( $today_date_default ); ?>" value="<?php echo esc_attr( $today_date_default ); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
        </div>

        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php echo esc_html( sprintf( __( 'Credit Amount (%s)', 'bsc-cinema-manager' ), $currency_symbol ) ); ?> *</label>
          <input type="number" step="0.01" name="income_amount" required placeholder="0.00" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
        </div>
      </div>

      <div class="bsc-form-group" style="margin-bottom:1.5rem;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Additional Note / Memo', 'bsc-cinema-manager' ); ?></label>
        <input type="text" name="income_note" placeholder="Optional receipt or voucher ref..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
      </div>

      <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:11px; font-weight:900; font-size:0.95rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12.5 6.5H5.5V17.5H18.5V6.5H12.5ZM17 16H7V8H17V16ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z"/></svg>
        <span><?php esc_html_e( 'Record Revenue Credit', 'bsc-cinema-manager' ); ?></span>
      </button>
    </form>
  </div>

  <!-- Income Ledger Table -->
  <div class="cinema-panel" style="margin-top:1.5rem; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
    <div class="cinema-panel-header" style="display:flex; justify-content:flex-end; border-bottom:none; padding-bottom:0;">
      <button type="button" class="button button-secondary" onclick="exportTableToCSV('bsc-income-report.csv')" style="display:inline-flex; align-items:center; gap:6px; font-weight:700;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
        <span><?php esc_html_e( 'Export CSV', 'bsc-cinema-manager' ); ?></span>
      </button>
    </div>

    <div class="cinema-table-wrap" style="margin-top:0.75rem; overflow-x:auto;">
      <table class="cinema-data-table" id="bscDataTable" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
            <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'REF TOKEN', 'bsc-cinema-manager' ); ?></th>
            <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'PARTICULARS', 'bsc-cinema-manager' ); ?></th>
            <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'PAYMENT METHOD', 'bsc-cinema-manager' ); ?></th>
            <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'DATE &amp; TIME', 'bsc-cinema-manager' ); ?></th>
            <th style="padding:10px 12px; text-align:right;"><?php esc_html_e( 'CREDIT AMOUNT', 'bsc-cinema-manager' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if ( ! empty( $incomes ) ) : foreach ( $incomes as $l ) : ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
              <td style="padding:12px;"><span class="cinema-voucher-tag" style="font-family:var(--cinema-font-mono, monospace); background:#f8fafc; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px;"><?php echo esc_html( $l->reference_no ); ?></span></td>
              <td style="padding:12px;"><strong><?php echo esc_html( $l->particulars ); ?></strong></td>
              <td style="padding:12px;"><span class="cinema-badge-payment" style="font-weight:700;"><?php echo esc_html( $l->method ); ?></span></td>
              <td style="padding:12px;"><small style="color:var(--cinema-text-muted, #64748b); font-weight:700;"><?php echo esc_html( $l->transaction_date ); ?></small></td>
              <td style="padding:12px; text-align:right; color:#059669; font-weight:900; font-family:var(--cinema-font-mono, monospace);">
                +<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $l->amount, 2 ) ); ?>
              </td>
            </tr>
          <?php endforeach; else : ?>
            <tr><td colspan="5" style="text-align:center; padding:2.5rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'No direct revenue records found.', 'bsc-cinema-manager' ); ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  function onIncomeCategoryChange(selectEl) {
    const otherBox = document.getElementById('otherDetailsBox');
    const otherInput = document.getElementById('incomeOtherDetailsInput');

    if (selectEl.value === 'Other') {
      otherBox.style.display = 'block';
      otherInput.required = true;
    } else {
      otherBox.style.display = 'none';
      otherInput.required = false;
      otherInput.value = '';
    }
  }

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

  document.addEventListener('DOMContentLoaded', function() {
    const catSelect = document.getElementById('incomeCategorySelect');
    if (catSelect) {
      onIncomeCategoryChange(catSelect);
    }
  });
</script>