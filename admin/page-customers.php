<?php
/**
 * Customer Module - Master Controller
 *
 * Path: admin/page-customers.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Delete & Form Submissions early on admin_init to prevent header errors
add_action( 'admin_init', 'bsc_customer_handle_actions' );
function bsc_customer_handle_actions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table_customers = $wpdb->prefix . 'bsc_customers';
    $page            = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

    if ( 'bsc-customers' !== $page ) {
        return;
    }

    $subtab      = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : '';
    $customer_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Handle Delete
    if ( 'delete' === $subtab && $customer_id > 0 ) {
        if ( file_exists( BSC_PLUGIN_PATH . 'admin/customers/delete-customer.php' ) ) {
            require BSC_PLUGIN_PATH . 'admin/customers/delete-customer.php';
            exit;
        }
    }

    // Handle POST Submissions (Add / Edit Customer)
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['bsc_action'] ) ) {
        if ( file_exists( BSC_PLUGIN_PATH . 'admin/customers/add-edit-customer.php' ) ) {
            require BSC_PLUGIN_PATH . 'admin/customers/add-edit-customer.php';
        }
    }
}

// 2. Main Page Render Callback
function bsc_render_customers_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
    }

    global $wpdb;
    $table_customers = $wpdb->prefix . 'bsc_customers';
    $table_bookings  = $wpdb->prefix . 'bsc_bookings';

    $subtab      = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
    $customer_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    $currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', 'BDT' ) );

    // Fetch Customers Data for the views
    $customers = $wpdb->get_results( "SELECT * FROM {$table_customers} ORDER BY id DESC" );
    if ( ! is_array( $customers ) ) {
        $customers = array();
    }

    // Open Layout Shell
    bsc_render_page_shell_open( 'bsc-customers' );

    // Render Subtabs & Navigation Alerts Bar
    if ( file_exists( BSC_PLUGIN_PATH . 'admin/customers/tabs-customers.php' ) ) {
        require BSC_PLUGIN_PATH . 'admin/customers/tabs-customers.php';
    }

    // Route Subviews Safely
    if ( 'view' === $subtab && $customer_id > 0 ) {
        require BSC_PLUGIN_PATH . 'admin/customers/view-customer.php';
    } elseif ( 'add' === $subtab || 'edit' === $subtab ) {
        require BSC_PLUGIN_PATH . 'admin/customers/add-edit-customer.php';
    } else {
        require BSC_PLUGIN_PATH . 'admin/customers/all-customers.php';
    }

    bsc_render_page_shell_close();
}