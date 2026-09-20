<?php
/**
 * Customer Module - Master Controller
 *
 * Path: admin/page-customers.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// 1. EARLY ACTION HANDLER (CSRF VERIFIED & SAFE REDIRECTS)
// =============================================================================
add_action( 'admin_init', 'bsc_customer_handle_actions' );

function bsc_customer_handle_actions() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        return;
    }

    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( 'bsc-customers' !== $page ) {
        return;
    }

    $subtab      = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';
    $customer_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Handle Delete Action with Strict CSRF Nonce Verification
    if ( 'delete' === $subtab && $customer_id > 0 ) {
        check_admin_referer( 'bsc_delete_customer_' . $customer_id, '_bsc_nonce' );

        $delete_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/customers/delete-customer.php' : '';
        if ( ! empty( $delete_file ) && file_exists( $delete_file ) ) {
            require $delete_file;
            exit;
        }
    }

    // Handle POST Submissions (Add / Edit Customer)
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['bsc_action'] ) ) {
        check_admin_referer( 'bsc_customer_form_action', '_bsc_nonce' );

        $action = sanitize_key( wp_unslash( $_POST['bsc_action'] ) );
        $valid_actions = array( 'add_customer', 'edit_customer' );

        if ( in_array( $action, $valid_actions, true ) ) {
            $action_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/customers/add-edit-customer.php' : '';
            if ( ! empty( $action_file ) && file_exists( $action_file ) ) {
                require $action_file;
                exit;
            }
        }
    }
}

// =============================================================================
// 2. MAIN PAGE RENDER CALLBACK
// =============================================================================
function bsc_render_customers_page() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
    }

    global $wpdb;
    $table_customers = $wpdb->prefix . 'bsc_customers';

    $subtab          = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
    $customer_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    $currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

    // Fetch records conditionally to preserve memory on edit/view screens
    $customers = array();
    if ( 'all' === $subtab ) {
        $per_page     = 50;
        $current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset       = ( $current_page - 1 ) * $per_page;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_customers} ORDER BY id DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        if ( is_array( $results ) ) {
            $customers = $results;
        }
    }

    // Open Layout Shell
    if ( function_exists( 'bsc_render_page_shell_open' ) ) {
        bsc_render_page_shell_open( 'bsc-customers' );
    }

    // Render Subtabs & Navigation Alerts Bar
    $tabs_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/customers/tabs-customers.php' : '';
    if ( ! empty( $tabs_file ) && file_exists( $tabs_file ) ) {
        require $tabs_file;
    }

    // Route Subviews Safely
    $view_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/customers/all-customers.php' : '';
    if ( 'view' === $subtab && $customer_id > 0 ) {
        $view_file = BSC_PLUGIN_PATH . 'admin/customers/view-customer.php';
    } elseif ( 'add' === $subtab || 'edit' === $subtab ) {
        $view_file = BSC_PLUGIN_PATH . 'admin/customers/add-edit-customer.php';
    }

    if ( ! empty( $view_file ) && file_exists( $view_file ) ) {
        require $view_file;
    } else {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'The requested customer view could not be loaded.', 'bcs-screen-manager' )
        );
    }

    // Close Layout Shell
    if ( function_exists( 'bsc_render_page_shell_close' ) ) {
        bsc_render_page_shell_close();
    }
}