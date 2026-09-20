<?php
/**
 * Accounts Module - Delete Ledger Entry Processor
 *
 * Path: admin/accounts/delete-ledger.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
}

global $wpdb;
$table_ledger = isset( $table_ledger ) ? $table_ledger : $wpdb->prefix . 'bsc_ledger';
$item_id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

if ( $item_id <= 0 ) {
    wp_die(
        esc_html__( 'Invalid ledger identifier.', 'bcs-screen-manager' ),
        esc_html__( 'Error', 'bcs-screen-manager' ),
        array( 'response' => 400 )
    );
}

// Verify strict nonce matching the master controller and view links
check_admin_referer( 'bsc_delete_ledger_' . $item_id, '_bsc_nonce' );

// Determine transaction type before deletion for context-aware redirect
$txn_type = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT txn_type FROM {$table_ledger} WHERE id = %d",
        $item_id
    )
);

// Fallback return subtab based on transaction type or query parameter
$return_subtab = isset( $_GET['return_subtab'] ) ? sanitize_key( wp_unslash( $_GET['return_subtab'] ) ) : '';
if ( empty( $return_subtab ) ) {
    $return_subtab = ( 'Income' === $txn_type ) ? 'income' : 'expense';
}

$deleted = $wpdb->delete(
    $table_ledger,
    array( 'id' => $item_id ),
    array( '%d' )
);

if ( false !== $deleted ) {
    wp_safe_redirect( admin_url( 'admin.php?page=bsc-accounts&subtab=' . $return_subtab . '&deleted=1' ) );
    exit;
} else {
    wp_safe_redirect( admin_url( 'admin.php?page=bsc-accounts&subtab=' . $return_subtab . '&error=db_failed' ) );
    exit;
}