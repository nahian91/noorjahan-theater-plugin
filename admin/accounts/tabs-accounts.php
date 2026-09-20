<?php
/**
 * Accounts Subtabs Navigation & Toast Notifications
 *
 * Path: admin/accounts/tabs-accounts.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_ledger = $wpdb->prefix . 'bsc_ledger';

// Prepared counts matching master controller logic
$income_count = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(id) FROM {$table_ledger} WHERE txn_type = %s OR txn_type = '' OR txn_type IS NULL",
        'Income'
    )
);

$expense_count = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(id) FROM {$table_ledger} WHERE txn_type = %s",
        'Expense'
    )
);

$subtab = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'income';
?>

<!-- TAB NAVIGATION TOOLBAR -->
<div class="bsc-subtabs-nav-wrapper">
    <nav class="bsc-subtabs-nav" aria-label="<?php esc_attr_e( 'Financials Navigation', 'bcs-screen-manager' ); ?>">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=income' ) ); ?>" class="bsc-subtab-btn <?php echo ( 'income' === $subtab ) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.5 6.5H5.5V17.5H18.5V6.5H12.5ZM17 16H7V8H17V16ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z"/></svg>
            <span><?php esc_html_e( 'Income', 'bcs-screen-manager' ); ?></span>
            <span class="bsc-tab-badge"><?php echo esc_html( number_format( $income_count ) ); ?></span>
        </a>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=expense' ) ); ?>" class="bsc-subtab-btn <?php echo ( 'expense' === $subtab ) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
            <span><?php esc_html_e( 'Expense', 'bcs-screen-manager' ); ?></span>
            <span class="bsc-tab-badge"><?php echo esc_html( number_format( $expense_count ) ); ?></span>
        </a>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=report' ) ); ?>" class="bsc-subtab-btn <?php echo ( 'report' === $subtab ) ? 'active' : ''; ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
            <span><?php esc_html_e( 'Reports', 'bcs-screen-manager' ); ?></span>
        </a>
    </nav>
</div>

<!-- NOTIFICATION TOAST BANNERS -->
<?php if ( isset( $_GET['income_added'] ) ) : ?>
    <div class="bsc-notification-banner success" role="alert">
        <div class="bsc-notification-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <div><?php esc_html_e( 'Revenue credit entry logged successfully!', 'bcs-screen-manager' ); ?></div>
    </div>
<?php endif; ?>

<?php if ( isset( $_GET['expense_added'] ) ) : ?>
    <div class="bsc-notification-banner success" role="alert">
        <div class="bsc-notification-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <div><?php esc_html_e( 'Expense record posted to accounts ledger!', 'bcs-screen-manager' ); ?></div>
    </div>
<?php endif; ?>

<?php if ( isset( $_GET['deleted'] ) ) : ?>
    <div class="bsc-notification-banner danger" role="alert">
        <div class="bsc-notification-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </div>
        <div><?php esc_html_e( 'Ledger entry removed.', 'bcs-screen-manager' ); ?></div>
    </div>
<?php endif; ?>