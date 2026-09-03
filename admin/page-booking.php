<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Process Actions (Add, Edit, Delete) before any HTML output
add_action( 'admin_init', 'bsc_booking_handle_actions' );
function bsc_booking_handle_actions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table_bookings = $wpdb->prefix . 'bsc_bookings';

    // Handle Delete
    if ( isset( $_GET['page'], $_GET['subtab'] ) && 'bsc-booking' === $_GET['page'] && 'delete' === $_GET['subtab'] ) {
        $del_id = absint( $_GET['id'] ?? 0 );
        $nonce  = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

        if ( $del_id > 0 && wp_verify_nonce( $nonce, 'bsc_delete_booking_' . $del_id ) ) {
            $wpdb->delete( $table_bookings, array( 'id' => $del_id ), array( '%d' ) );
            wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_deleted=true' ) );
            exit;
        } else {
            wp_die( esc_html__( 'Security check failed.', 'bsc-cinema-manager' ), 403 );
        }
    }

    // Handle POST Submissions
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['bsc_action'] ) ) {

        // Edit Booking
        if ( 'edit_booking' === $_POST['bsc_action'] ) {
            check_admin_referer( 'bsc_edit_booking_verify' );
            $edit_id = absint( $_POST['booking_id'] ?? 0 );

            if ( $edit_id > 0 ) {
                $wpdb->update(
                    $table_bookings,
                    array(
                        'customer_name'  => sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) ),
                        'customer_phone' => sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) ),
                        'customer_email' => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
                        'seats'          => sanitize_text_field( wp_unslash( $_POST['seats'] ?? '' ) ),
                        'total_amount'   => floatval( $_POST['total_amount'] ?? 0.00 ),
                        'payment_method' => sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? 'Cash' ) ),
                        'payment_status' => sanitize_text_field( wp_unslash( $_POST['payment_status'] ?? 'Confirmed' ) ),
                    ),
                    array( 'id' => $edit_id ),
                    array( '%s', '%s', '%s', '%s', '%f', '%s', '%s' ),
                    array( '%d' )
                );

                wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_updated=true' ) );
                exit;
            }
        }

        // Add Booking
        if ( 'add_booking' === $_POST['bsc_action'] ) {
            check_admin_referer( 'bsc_manual_booking_verify' );

            $seats = sanitize_text_field( wp_unslash( $_POST['manual_seats'] ?? '' ) );
            if ( empty( $seats ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=add&booking_error=no_seats' ) );
                exit;
            }

            $token = sprintf( '#BSC-%s-TX', strtoupper( wp_generate_password( 6, false, false ) ) );
            $booking_date = sanitize_text_field( wp_unslash( $_POST['manual_booking_date'] ?? current_time( 'Y-m-d' ) ) );

            $wpdb->insert(
                $table_bookings,
                array(
                    'booking_token'  => $token,
                    'customer_name'  => sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? 'Walk-In Guest' ) ),
                    'customer_phone' => sanitize_text_field( wp_unslash( $_POST['customer_phone'] ?? '' ) ),
                    'customer_email' => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
                    'movie_title'    => sanitize_text_field( wp_unslash( $_POST['manual_movie'] ?? '' ) ),
                    'showtime'       => sanitize_text_field( wp_unslash( $_POST['manual_showtime'] ?? '' ) ),
                    'booking_date'   => $booking_date . ' ' . current_time( 'H:i:s' ),
                    'seats'          => $seats,
                    'ticket_type'    => sanitize_text_field( wp_unslash( $_POST['manual_ticket_type'] ?? 'Standard' ) ),
                    'total_amount'   => floatval( $_POST['manual_amount'] ?? 0.00 ),
                    'payment_method' => sanitize_text_field( wp_unslash( $_POST['payment_method'] ?? 'Cash' ) ),
                    'payment_status' => 'Confirmed',
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' )
            );

            wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_added=true' ) );
            exit;
        }
    }
}

// 2. Main Page Render Callback
function bsc_render_booking_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
    }

    $subtab     = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'all';
    $booking_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Open Layout Shell
    bsc_render_page_shell_open( 'bsc-booking' );

    // Render Subtabs Navigation
    require BSC_PLUGIN_PATH . 'admin/booking/tabs-booking.php';

    // Route Subviews safely
    if ( 'view' === $subtab && $booking_id > 0 ) {
        require BSC_PLUGIN_PATH . 'admin/booking/view-booking.php';
    } elseif ( 'add' === $subtab || 'edit' === $subtab ) {
        require BSC_PLUGIN_PATH . 'admin/booking/add-edit-booking.php';
    } else {
        require BSC_PLUGIN_PATH . 'admin/booking/all-booking.php';
    }

    // Close Layout Shell
    bsc_render_page_shell_close();
}