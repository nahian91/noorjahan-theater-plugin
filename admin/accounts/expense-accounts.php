<?php
/**
 * Accounts Module - Operating Expenses & Payroll View
 *
 * Path: admin/accounts/expense-accounts.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
	wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
}

global $wpdb;
$table_staff_crew = $wpdb->prefix . 'nmbp_staff_crew';
$table_ledger     = isset( $table_ledger ) ? $table_ledger : $wpdb->prefix . 'nmbp_ledger';

$staff_members = $wpdb->get_results( "SELECT id, name, role_title, salary FROM {$table_staff_crew} ORDER BY name ASC" );
if ( ! is_array( $staff_members ) ) {
	$staff_members = array();
}

$safe_where_sql = ! empty( $where_sql ) ? $where_sql : '1=1';
$expenses_query = $wpdb->prepare(
	"SELECT * FROM {$table_ledger} WHERE ({$safe_where_sql}) AND txn_type = %s ORDER BY id DESC LIMIT 200",
	'Expense'
);

$expenses = $wpdb->get_results( $expenses_query );
if ( ! is_array( $expenses ) ) {
	$expenses = array();
}

$today_date_default = current_time( 'Y-m-d' );
$current_month_num  = (int) current_time( 'n' );
$current_year_num   = (int) current_time( 'Y' );
$current_month_year = current_time( 'F Y' );
$currency_symbol    = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );
?>
<div id="nmbpPrintArea">
	<div class="nmbp-panel" style="max-width: 750px; margin: 0 auto; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
		<h3 class="nmbp-panel-title" style="margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
			<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
			<?php esc_html_e( 'Log Theater Expense', 'nahian-movie-booking-plugin' ); ?>
		</h3>

		<form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-accounts&subtab=expense' ) ); ?>">
			<?php wp_nonce_field( 'nmbp_ledger_form_action', '_nmbp_nonce' ); ?>
			<input type="hidden" name="nmbp_action" value="add_expense">

			<div class="nmbp-form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 1rem;">
				<div class="nmbp-form-group" style="margin-bottom: 0;">
					<label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;">
						<?php esc_html_e( 'Cost Category', 'nahian-movie-booking-plugin' ); ?> *
					</label>
					<select name="expense_category" id="expenseCategorySelect" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
						<option value="Salary & Payroll"><?php esc_html_e( 'Salary & Payroll', 'nahian-movie-booking-plugin' ); ?></option>
						<option value="Utility Bill"><?php esc_html_e( 'Utility Bill (Electricity & AC)', 'nahian-movie-booking-plugin' ); ?></option>
						<option value="Film Distributor Share"><?php esc_html_e( 'Film Distributor Royalty / Share', 'nahian-movie-booking-plugin' ); ?></option>
						<option value="Concession Supplies"><?php esc_html_e( 'Popcorn & Concession Stock', 'nahian-movie-booking-plugin' ); ?></option>
						<option value="Equipment Maintenance"><?php esc_html_e( 'Equipment & Projector Maintenance', 'nahian-movie-booking-plugin' ); ?></option>
						<option value="Facility Cleaning"><?php esc_html_e( 'Cleaning & Sanitation', 'nahian-movie-booking-plugin' ); ?></option>
					</select>
				</div>

				<div class="nmbp-form-group" style="margin-bottom: 0;">
					<label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;">
						<?php esc_html_e( 'Billing Month & Year', 'nahian-movie-booking-plugin' ); ?> *
					</label>
					<select name="expense_billing_period" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
						<?php
						$months = array(
							1  => __( 'January', 'nahian-movie-booking-plugin' ),
							2  => __( 'February', 'nahian-movie-booking-plugin' ),
							3  => __( 'March', 'nahian-movie-booking-plugin' ),
							4  => __( 'April', 'nahian-movie-booking-plugin' ),
							5  => __( 'May', 'nahian-movie-booking-plugin' ),
							6  => __( 'June', 'nahian-movie-booking-plugin' ),
							7  => __( 'July', 'nahian-movie-booking-plugin' ),
							8  => __( 'August', 'nahian-movie-booking-plugin' ),
							9  => __( 'September', 'nahian-movie-booking-plugin' ),
							10 => __( 'October', 'nahian-movie-booking-plugin' ),
							11 => __( 'November', 'nahian-movie-booking-plugin' ),
							12 => __( 'December', 'nahian-movie-booking-plugin' ),
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

			<!-- Dynamic Crew Selector -->
			<div class="nmbp-form-group" id="staffSelectorBox" style="margin-bottom: 1rem; display: block;">
				<label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;">
					<?php esc_html_e( 'Select Staff Member', 'nahian-movie-booking-plugin' ); ?> *
				</label>
				<select name="staff_id" id="staffMemberSelect" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
					<option value=""><?php esc_html_e( '-- Select Crew Member --', 'nahian-movie-booking-plugin' ); ?></option>
					<?php
					if ( ! empty( $staff_members ) ) :
						foreach ( $staff_members as $sm ) :
							?>
						<option value="<?php echo esc_attr( (string) $sm->id ); ?>" data-salary="<?php echo esc_attr( (string) $sm->salary ); ?>" data-name="<?php echo esc_attr( $sm->name ); ?>" data-role="<?php echo esc_attr( $sm->role_title ); ?>">
							<?php echo esc_html( $sm->name ); ?> (<?php echo esc_html( $sm->role_title ); ?> - <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $sm->salary, 2 ) ); ?>)
						</option>
							<?php
						endforeach;
					endif;
					?>
				</select>
			</div>

			<div class="nmbp-form-group" style="margin-bottom: 1rem;">
				<label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;">
					<?php esc_html_e( 'Particulars / Note', 'nahian-movie-booking-plugin' ); ?> *
				</label>
				<input type="text" name="expense_particulars" id="expenseParticularsInput" required placeholder="<?php esc_attr_e( 'e.g. Monthly Staff Salary, August Electricity Bill', 'nahian-movie-booking-plugin' ); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
			</div>

			<div class="nmbp-form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 1rem;">
				<div class="nmbp-form-group" style="margin-bottom: 0;">
					<label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;">
						<?php esc_html_e( 'Transaction Date', 'nahian-movie-booking-plugin' ); ?> *
					</label>
					<input type="date" name="expense_date" required max="<?php echo esc_attr( $today_date_default ); ?>" value="<?php echo esc_attr( $today_date_default ); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
				</div>

				<div class="nmbp-form-group" style="margin-bottom: 0;">
					<label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;">
						<?php esc_html_e( 'Payment Method', 'nahian-movie-booking-plugin' ); ?> *
					</label>
					<select name="expense_method" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
						<option value="Cash"><?php esc_html_e( 'Box Office Cash Drawer', 'nahian-movie-booking-plugin' ); ?></option>
						<option value="Bank Transfer"><?php esc_html_e( 'Corporate Bank Account', 'nahian-movie-booking-plugin' ); ?></option>
						<option value="bKash"><?php esc_html_e( 'bKash Corporate', 'nahian-movie-booking-plugin' ); ?></option>
					</select>
				</div>
			</div>

			<div class="nmbp-form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 1.5rem;">
				<div class="nmbp-form-group" style="margin-bottom: 0;">
					<label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;">
						<?php
						/* translators: %s: Currency symbol */
						printf( esc_html__( 'Payout Amount (%s)', 'nahian-movie-booking-plugin' ), esc_html( $currency_symbol ) );
						?> *
					</label>
					<input type="number" step="0.01" min="0" name="expense_amount" id="expenseAmountInput" required placeholder="0.00" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
				</div>

				<div class="nmbp-form-group" style="margin-bottom: 0;">
					<label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;">
						<?php esc_html_e( 'Additional Note / Memo', 'nahian-movie-booking-plugin' ); ?>
					</label>
					<input type="text" name="expense_note" placeholder="<?php esc_attr_e( 'Optional voucher/receipt ref...', 'nahian-movie-booking-plugin' ); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
				</div>
			</div>

			<button type="submit" class="button button-primary" style="background: #ef4444; border-color: #ef4444; width: 100%; padding: 11px; font-weight: 900; font-size: 0.95rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 8px;">
				<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
				<span><?php esc_html_e( 'Record Outflow Expense', 'nahian-movie-booking-plugin' ); ?></span>
			</button>
		</form>
	</div>

	<!-- Expense Ledger Table -->
	<div class="nmbp-panel" style="margin-top: 1.5rem; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
		<div class="nmbp-panel-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: none; padding-bottom: 0;">
			<h3 class="nmbp-panel-title" style="margin: 0; font-size: 1.1rem;">
				<?php esc_html_e( 'Expense Records', 'nahian-movie-booking-plugin' ); ?>
			</h3>
			<button type="button" class="button button-secondary" id="btnExportExpenseCsv" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/></svg>
				<span><?php esc_html_e( 'Export CSV', 'nahian-movie-booking-plugin' ); ?></span>
			</button>
		</div>

		<div class="nmbp-table-wrap" style="margin-top: 0.75rem; overflow-x: auto;">
			<table class="nmbp-data-table" id="nmbpExpenseDataTable" style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="border-bottom: 1px solid var(--nmbp-border, #e2e8f0); background: #f8fafc;">
						<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'REF TOKEN', 'nahian-movie-booking-plugin' ); ?></th>
						<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'PARTICULARS', 'nahian-movie-booking-plugin' ); ?></th>
						<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'PAYMENT METHOD', 'nahian-movie-booking-plugin' ); ?></th>
						<th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'DATE & TIME', 'nahian-movie-booking-plugin' ); ?></th>
						<th style="padding: 10px 12px; text-align: right;"><?php esc_html_e( 'DEBIT AMOUNT', 'nahian-movie-booking-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( ! empty( $expenses ) ) :
						foreach ( $expenses as $exp ) :
							?>
						<tr style="border-bottom: 1px solid var(--nmbp-border-light, #f1f5f9);">
							<td style="padding: 12px;">
								<span class="nmbp-voucher-tag"><?php echo esc_html( $exp->reference_no ?? '' ); ?></span>
							</td>
							<td style="padding: 12px;">
								<strong><?php echo esc_html( $exp->particulars ?? '' ); ?></strong>
							</td>
							<td style="padding: 12px;">
								<span class="nmbp-badge-pill"><?php echo esc_html( $exp->method ?? 'Cash' ); ?></span>
							</td>
							<td style="padding: 12px;">
								<small style="color: var(--nmbp-text-muted, #64748b); font-weight: 700;"><?php echo esc_html( $exp->transaction_date ?? '' ); ?></small>
							</td>
							<td style="padding: 12px; text-align: right; color: #ef4444; font-weight: 900; font-family: var(--nmbp-font-mono, monospace);">
								-<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $exp->amount ?? 0.00 ), 2 ) ); ?>
							</td>
						</tr>
							<?php
						endforeach;
					else :
						?>
						<tr>
							<td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--nmbp-text-muted, #64748b);">
								<?php esc_html_e( 'No expense transactions found.', 'nahian-movie-booking-plugin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php
$version = defined( 'NMBP_VERSION' ) ? NMBP_VERSION : '1.0';
wp_register_script( 'nmbp-expense-runtime', false, array(), $version, true );
wp_enqueue_script( 'nmbp-expense-runtime' );

$js_script = '
    (function() {
        document.addEventListener("DOMContentLoaded", function() {
            const catSelect = document.getElementById("expenseCategorySelect");
            const staffBox = document.getElementById("staffSelectorBox");
            const staffSelect = document.getElementById("staffMemberSelect");
            const particularsInput = document.getElementById("expenseParticularsInput");
            const amountInput = document.getElementById("expenseAmountInput");
            const btnExport = document.getElementById("btnExportExpenseCsv");

            function updateCategory() {
                if (!catSelect || !staffBox || !staffSelect || !particularsInput || !amountInput) return;
                if (catSelect.value === "Salary & Payroll") {
                    staffBox.style.display = "block";
                    staffSelect.required = true;
                    updateStaffData();
                } else {
                    staffBox.style.display = "none";
                    staffSelect.required = false;
                    staffSelect.value = "";
                    particularsInput.value = "";
                    amountInput.value = "";
                }
            }

            function updateStaffData() {
                if (!staffSelect || !particularsInput || !amountInput) return;
                const opt = staffSelect.options[staffSelect.selectedIndex];
                if (opt && opt.value) {
                    const staffName = opt.getAttribute("data-name") || "";
                    const staffRole = opt.getAttribute("data-role") || "";
                    const salary = opt.getAttribute("data-salary") || "0.00";
                    particularsInput.value = "Monthly Salary - " + staffName + " (" + staffRole + ")";
                    amountInput.value = salary;
                }
            }

            if (catSelect) {
                catSelect.addEventListener("change", updateCategory);
                updateCategory();
            }

            if (staffSelect) {
                staffSelect.addEventListener("change", updateStaffData);
            }

            if (btnExport) {
                btnExport.addEventListener("click", function() {
                    const rows = document.querySelectorAll("#nmbpExpenseDataTable tr");
                    let csv = [];
                    for (let i = 0; i < rows.length; i++) {
                        let row = [], cols = rows[i].querySelectorAll("td, th");
                        for (let j = 0; j < cols.length; j++) {
                            row.push(\'"\' + cols[j].innerText.replace(/"/g, \'""\').trim() + \'"\');
                        }
                        csv.push(row.join(","));
                    }
                    const blob = new Blob([csv.join("\\n")], { type: "text/csv" });
                    const link = document.createElement("a");
                    link.download = "nmbp-expense-report.csv";
                    link.href = window.URL.createObjectURL(blob);
                    link.style.display = "none";
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            }
        });
    })();
';

wp_add_inline_script( 'nmbp-expense-runtime', $js_script );