<?php
/**
 * Users / Staff Module - Master Controller
 *
 * Path: admin/page-users.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// 1. EARLY ACTION HANDLER (CSRF VERIFIED & SAFE REDIRECTS)
// =============================================================================
add_action( 'admin_init', 'bsc_users_handle_actions' );

function bsc_users_handle_actions() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        return;
    }

    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( 'bsc-users' !== $page ) {
        return;
    }

    $subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';
    $staff_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Handle Delete Action with Nonce Verification
    if ( 'delete' === $subtab && $staff_id > 0 ) {
        check_admin_referer( 'bsc_delete_user_' . $staff_id, '_bsc_nonce' );

        $delete_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/users/delete-user.php' : '';
        if ( ! empty( $delete_file ) && file_exists( $delete_file ) ) {
            require $delete_file;
            exit;
        }
    }

    // Handle POST Submissions (Add / Edit Staff Member)
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['bsc_action'] ) ) {
        check_admin_referer( 'bsc_user_form_action', 'bsc_user_nonce' );

        $action        = sanitize_key( wp_unslash( $_POST['bsc_action'] ) );
        $valid_actions = array( 'add_user', 'edit_user' );

        if ( in_array( $action, $valid_actions, true ) ) {
            $form_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/users/add-edit-user.php' : '';
            if ( ! empty( $form_file ) && file_exists( $form_file ) ) {
                require $form_file;
                exit;
            }
        }
    }
}

// =============================================================================
// 2. MAIN PAGE RENDER CALLBACK
// =============================================================================
function bsc_render_users_page() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
    }

    global $wpdb;
    $table_staff = $wpdb->prefix . 'bsc_staff_crew';

    $subtab          = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
    $staff_id        = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    $currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

    // Real-time metric computations using prepared queries
    $total_staff   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_staff}" );
    $on_duty_count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_staff} WHERE duty_status = %s",
            'On Duty'
        )
    );
    $tech_count    = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_staff} WHERE role_title LIKE %s OR role_title LIKE %s OR role_title LIKE %s",
            '%' . $wpdb->esc_like( 'Projection' ) . '%',
            '%' . $wpdb->esc_like( 'Sound' ) . '%',
            '%' . $wpdb->esc_like( 'Laser' ) . '%'
        )
    );

    // Fetch Staff Roster Data with pagination when in roster list view
    $staff_members = array();
    if ( 'all' === $subtab ) {
        $per_page     = 30;
        $current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset       = ( $current_page - 1 ) * $per_page;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_staff} ORDER BY id DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        if ( is_array( $results ) ) {
            $staff_members = $results;
        }
    }

    // Open Layout Shell
    if ( function_exists( 'bsc_render_page_shell_open' ) ) {
        bsc_render_page_shell_open( 'bsc-users' );
    }

    // Render Subtabs & Navigation Alerts Bar
    $tabs_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/users/tabs-users.php' : '';
    if ( ! empty( $tabs_file ) && file_exists( $tabs_file ) ) {
        require $tabs_file;
    }

    // Route Subviews Safely
    $view_file = '';
    if ( 'view' === $subtab && $staff_id > 0 ) {
        $view_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/users/view-user.php' : '';
    } elseif ( 'add' === $subtab || 'edit' === $subtab ) {
        $view_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/users/add-edit-user.php' : '';
    } else {
        $view_file = defined( 'BSC_PLUGIN_PATH' ) ? BSC_PLUGIN_PATH . 'admin/users/all-users.php' : '';
    }

    if ( ! empty( $view_file ) && file_exists( $view_file ) ) {
        require $view_file;
    } else {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'The requested staff view could not be loaded.', 'bcs-screen-manager' )
        );
    }

    // Close Layout Shell
    if ( function_exists( 'bsc_render_page_shell_close' ) ) {
        bsc_render_page_shell_close();
    }
}