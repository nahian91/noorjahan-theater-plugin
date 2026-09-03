<?php
/**
 * Users / Staff Module - Master Controller
 *
 * Path: admin/page-users.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Delete & Form Submissions early on admin_init to prevent header errors
add_action( 'admin_init', 'bsc_users_handle_actions' );
function bsc_users_handle_actions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table_staff = $wpdb->prefix . 'bsc_staff_crew';
    $page        = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';

    if ( 'bsc-users' !== $page ) {
        return;
    }

    $subtab   = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : '';
    $staff_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Handle Delete
    if ( 'delete' === $subtab && $staff_id > 0 ) {
        if ( file_exists( BSC_PLUGIN_PATH . 'admin/users/delete-user.php' ) ) {
            require BSC_PLUGIN_PATH . 'admin/users/delete-user.php';
            exit;
        }
    }

    // Handle POST Submissions (Add / Edit Staff Member)
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['bsc_action'] ) ) {
        if ( file_exists( BSC_PLUGIN_PATH . 'admin/users/add-edit-user.php' ) ) {
            require BSC_PLUGIN_PATH . 'admin/users/add-edit-user.php';
        }
    }
}

// 2. Main Page Render Callback
function bsc_render_users_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
    }

    global $wpdb;
    $table_staff = $wpdb->prefix . 'bsc_staff_crew';

    $subtab          = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
    $staff_id        = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    $currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

    // Real-time metric computations
    $total_staff   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_staff}" );
    $on_duty_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_staff} WHERE duty_status = 'On Duty'" );
    $tech_count    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_staff} WHERE role_title LIKE '%Projection%' OR role_title LIKE '%Sound%' OR role_title LIKE '%Laser%'" );

    // Fetch Staff Roster Data
    $staff_members = $wpdb->get_results( "SELECT * FROM {$table_staff} ORDER BY id DESC" );
    if ( ! is_array( $staff_members ) ) {
        $staff_members = array();
    }

    // Open Layout Shell
    bsc_render_page_shell_open( 'bsc-users' );

    // Render Subtabs & Navigation Alerts Bar
    if ( file_exists( BSC_PLUGIN_PATH . 'admin/users/tabs-users.php' ) ) {
        require BSC_PLUGIN_PATH . 'admin/users/tabs-users.php';
    }

    // Route Subviews Safely
    if ( 'view' === $subtab && $staff_id > 0 ) {
        require BSC_PLUGIN_PATH . 'admin/users/view-user.php';
    } elseif ( 'add' === $subtab || 'edit' === $subtab ) {
        require BSC_PLUGIN_PATH . 'admin/users/add-edit-user.php';
    } else {
        require BSC_PLUGIN_PATH . 'admin/users/all-users.php';
    }

    // Close Layout Shell
    bsc_render_page_shell_close();
}