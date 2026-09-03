<?php
/**
 * Accounts Module - Operating Expenses & Payroll View
 *
 * Path: admin/accounts/expense-accounts.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_staff_crew = $wpdb->prefix . 'bsc_staff_crew';
$staff_members    = $wpdb->get_results( "SELECT id, name, role_title, salary FROM {$table_staff_crew} ORDER BY name ASC" );

$today_date_default = current_time( 'Y-m-d' );
$current_month_num  = (int) date( 'n' );
$current_year_num   = (int) date( 'Y' );
$current_month_year = date( 'F Y' ); // e.g. September 2026
$currency_symbol    = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );
?>
<div id="bscPrintArea">
  <div class="cinema-panel" style="max-width:750px; margin:0 auto; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
    <h3 class="cinema-panel-title" style="margin-bottom:1.25rem; display:flex; align-items:center; gap:8px;">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
      <?php esc_html_e( 'Log Theater Expense', 'bsc-cinema-manager' ); ?>
    </h3>
    
    <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=expense' ) ); ?>">
      <?php wp_nonce_field( 'bsc_add_expense_verify' ); ?>
      <input type="hidden" name="bsc_action" value="add_expense">

      <div class="bsc-form-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:1rem;">
        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Cost Category', 'bsc-cinema-manager' ); ?> *</label>
          <select name="expense_category" id="expenseCategorySelect" required onchange="onCategoryChange(this)" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
            <option value="Salary & Payroll"><?php esc_html_e( 'Salary & Payroll', 'bsc-cinema-manager' ); ?></option>
            <option value="Utility Bill"><?php esc_html_e( 'Utility Bill (Electricity & AC)', 'bsc-cinema-manager' ); ?></option>
            <option value="Film Distributor Share"><?php esc_html_e( 'Film Distributor Royalty / Share', 'bsc-cinema-manager' ); ?></option>
            <option value="Concession Supplies"><?php esc_html_e( 'Popcorn & Concession Stock', 'bsc-cinema-manager' ); ?></option>
            <option value="Equipment Maintenance"><?php esc_html_e( 'Equipment & Projector Maintenance', 'bsc-cinema-manager' ); ?></option>
            <option value="Facility Cleaning"><?php esc_html_e( 'Cleaning & Sanitation', 'bsc-cinema-manager' ); ?></option>
          </select>
        </div>

        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Billing Month & Year', 'bsc-cinema-manager' ); ?> *</label>
          <select name="expense_billing_period" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
            <?php
              $months = array(
                  1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 
                  5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 
                  9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
              );
              for ( $y = $current_year_num; $y >= $current_year_num - 2; $y-- ) {
                  $max_m = ( $y === $current_year_num ) ? $current_month_num : 12;
                  for ( $m = $max_m; $m >= 1; $m-- ) {
                      $val = $months[ $m ] . ' ' . $y;
                      echo '<option value="' . esc_attr( $val ) . '" ' . selected( $val, $current_month_year, false ) . '>' . esc_html( $val ) . '</option>';
                  }
              }
            ?>
          </select>
        </div>
      </div>

      <!-- Dynamic Crew Selector (Loads from bsc_staff_crew table) -->
      <div class="bsc-form-group" id="staffSelectorBox" style="margin-bottom:1rem; display:block;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Select Staff Member', 'bsc-cinema-manager' ); ?> *</label>
        <select name="staff_id" id="staffMemberSelect" onchange="onStaffSelect(this)" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
          <option value="">-- Select Crew Member --</option>
          <?php if ( ! empty( $staff_members ) ) : foreach ( $staff_members as $sm ) : ?>
            <option value="<?php echo esc_attr( $sm->id ); ?>" data-salary="<?php echo esc_attr( $sm->salary ); ?>" data-name="<?php echo esc_attr( $sm->name ); ?>" data-role="<?php echo esc_attr( $sm->role_title ); ?>">
              <?php echo esc_html( $sm->name ); ?> (<?php echo esc_html( $sm->role_title ); ?> - <?php echo esc_html( $currency_symbol ); ?> <?php echo number_format( (float) $sm->salary, 2 ); ?>)
            </option>
          <?php endforeach; endif; ?>
        </select>
      </div>

      <div class="bsc-form-group" style="margin-bottom:1rem;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Particulars / Note', 'bsc-cinema-manager' ); ?> *</label>
        <input type="text" name="expense_particulars" id="expenseParticularsInput" required placeholder="e.g. Monthly Staff Salary, August Electricity Bill" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
      </div>

      <div class="bsc-form-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:1rem;">
        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Transaction Date', 'bsc-cinema-manager' ); ?> *</label>
          <input type="date" name="expense_date" required max="<?php echo esc_attr( $today_date_default ); ?>" value="<?php echo esc_attr( $today_date_default ); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
        </div>

        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Payment Method', 'bsc-cinema-manager' ); ?> *</label>
          <select name="expense_method" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
            <option value="Cash"><?php esc_html_e( 'Box Office Cash Drawer', 'bsc-cinema-manager' ); ?></option>
            <option value="Bank Transfer"><?php esc_html_e( 'Corporate Bank Account', 'bsc-cinema-manager' ); ?></option>
            <option value="bKash"><?php esc_html_e( 'bKash Corporate', 'bsc-cinema-manager' ); ?></option>
          </select>
        </div>
      </div>

      <div class="bsc-form-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:1.5rem;">
        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php echo esc_html( sprintf( __( 'Payout Amount (%s)', 'bsc-cinema-manager' ), $currency_symbol ) ); ?> *</label>
          <input type="number" step="0.01" name="expense_amount" id="expenseAmountInput" required placeholder="0.00" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
        </div>

        <div class="bsc-form-group" style="margin-bottom:0;">
          <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Additional Note / Memo', 'bsc-cinema-manager' ); ?></label>
          <input type="text" name="expense_note" placeholder="Optional voucher/receipt ref..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
        </div>
      </div>

      <button type="submit" class="button button-primary" style="background:#ef4444; border-color:#ef4444; width:100%; padding:11px; font-weight:900; font-size:0.95rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
        <span><?php esc_html_e( 'Record Outflow Expense', 'bsc-cinema-manager' ); ?></span>
      </button>
    </form>
  </div>
</div>

<script>
  function onCategoryChange(selectEl) {
    const staffBox = document.getElementById('staffSelectorBox');
    const particularsInput = document.getElementById('expenseParticularsInput');
    const amountInput = document.getElementById('expenseAmountInput');
    const staffSelect = document.getElementById('staffMemberSelect');

    if (selectEl.value === 'Salary & Payroll') {
      staffBox.style.display = 'block';
      staffSelect.required = true;
      onStaffSelect(staffSelect);
    } else {
      staffBox.style.display = 'none';
      staffSelect.required = false;
      staffSelect.value = '';
      particularsInput.value = '';
      amountInput.value = '';
    }
  }

  function onStaffSelect(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const particularsInput = document.getElementById('expenseParticularsInput');
    const amountInput = document.getElementById('expenseAmountInput');

    if (opt && opt.value) {
      const staffName = opt.getAttribute('data-name') || '';
      const staffRole = opt.getAttribute('data-role') || '';
      const salary = opt.getAttribute('data-salary') || '0.00';
      particularsInput.value = 'Monthly Salary - ' + staffName + ' (' + staffRole + ')';
      amountInput.value = salary;
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    const catSelect = document.getElementById('expenseCategorySelect');
    if (catSelect) {
      onCategoryChange(catSelect);
    }
  });
</script>