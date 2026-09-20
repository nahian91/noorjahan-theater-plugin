<?php
/**
 * Booking Module - Master Controller
 *
 * Path: admin/page-booking.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// 1. EARLY ACTION HANDLER (CSRF VERIFIED & SAFE REDIRECTS)
// =============================================================================
add_action( 'admin_init', 'bsc_booking_handle_actions' );

function bsc_booking_handle_actions() {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( 'bsc-booking' !== $page ) {
        return;
    }

    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        return;
    }

    global $wpdb;
    $table_bookings = $wpdb->prefix . 'bsc_bookings';
    $subtab         = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : '';

    // -------------------------------------------------------------------------
    // A. Handle Delete Action
    // -------------------------------------------------------------------------
    if ( 'delete' === $subtab ) {
        $del_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

        if ( $del_id > 0 ) {
            check_admin_referer( 'bsc_delete_booking_' . $del_id, '_wpnonce' );

            $wpdb->delete(
                $table_bookings,
                array( 'id' => $del_id ),
                array( '%d' )
            );

            wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_deleted=true' ) );
            exit;
        }

        wp_die( esc_html__( 'Invalid booking ID.', 'bcs-screen-manager' ), 400 );
    }

    // -------------------------------------------------------------------------
    // B. Handle POST Submissions
    // -------------------------------------------------------------------------
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['bsc_action'] ) ) {
        $action = sanitize_key( wp_unslash( $_POST['bsc_action'] ) );

        // Edit Existing Booking
        if ( 'edit_booking' === $action ) {
            check_admin_referer( 'bsc_edit_booking_verify' );

            $edit_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
            if ( $edit_id > 0 ) {
                $customer_name  = ! empty( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : 'Walk-In Guest';
                $customer_phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
                $customer_email = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';
                $seats_raw      = isset( $_POST['seats'] ) ? sanitize_text_field( wp_unslash( $_POST['seats'] ) ) : '';

                // Clean and normalize comma-separated seats
                $clean_seats_arr = array_filter( array_map( 'trim', explode( ',', strtoupper( $seats_raw ) ) ) );
                $seats           = implode( ', ', $clean_seats_arr );

                $total_amount   = isset( $_POST['total_amount'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['total_amount'] ) ) : 0.00;
                $payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Cash';
                $payment_status = isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : 'Confirmed';

                $wpdb->update(
                    $table_bookings,
                    array(
                        'customer_name'  => $customer_name,
                        'customer_phone' => $customer_phone,
                        'customer_email' => $customer_email,
                        'seats'          => $seats,
                        'total_amount'   => $total_amount,
                        'payment_method' => $payment_method,
                        'payment_status' => $payment_status,
                    ),
                    array( 'id' => $edit_id ),
                    array( '%s', '%s', '%s', '%s', '%f', '%s', '%s' ),
                    array( '%d' )
                );

                wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_updated=true' ) );
                exit;
            }
        }

        // Add Manual Booking
        if ( 'add_booking' === $action ) {
            check_admin_referer( 'bsc_manual_booking_verify' );

            $seats_raw = isset( $_POST['manual_seats'] ) ? sanitize_text_field( wp_unslash( $_POST['manual_seats'] ) ) : '';
            if ( empty( $seats_raw ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=add&booking_error=no_seats' ) );
                exit;
            }

            // Clean, format, and ensure valid seats (1 to 5 per row)
            $clean_seats_arr = array_filter( array_map( 'trim', explode( ',', strtoupper( $seats_raw ) ) ) );
            if ( empty( $clean_seats_arr ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=add&booking_error=invalid_seats' ) );
                exit;
            }
            $seats = implode( ', ', $clean_seats_arr );

            // Centralized ticket token generation
            $token = function_exists( 'bsc_generate_cinema_ticket_id' )
                ? bsc_generate_cinema_ticket_id( 'BCS' )
                : sprintf( '#BSC-%s-TX', strtoupper( wp_generate_password( 8, false, false ) ) );

            // Validate date format (YYYY-MM-DD)
            $raw_date     = isset( $_POST['manual_booking_date'] ) ? sanitize_text_field( wp_unslash( $_POST['manual_booking_date'] ) ) : '';
            $booking_date = current_time( 'Y-m-d' );
            if ( ! empty( $raw_date ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_date ) ) {
                $booking_date = $raw_date;
            }

            $customer_name  = ! empty( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : 'Walk-In Guest';
            $customer_phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
            $customer_email = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';
            $movie_title    = isset( $_POST['manual_movie'] ) ? sanitize_text_field( wp_unslash( $_POST['manual_movie'] ) ) : '';
            $auditorium     = ! empty( $_POST['manual_auditorium'] ) ? sanitize_text_field( wp_unslash( $_POST['manual_auditorium'] ) ) : 'Auditorium 1 (30 Seats)';
            $showtime       = isset( $_POST['manual_showtime'] ) ? sanitize_text_field( wp_unslash( $_POST['manual_showtime'] ) ) : '';
            $ticket_type    = isset( $_POST['manual_ticket_type'] ) ? sanitize_text_field( wp_unslash( $_POST['manual_ticket_type'] ) ) : 'Standard';
            $total_amount   = isset( $_POST['manual_amount'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['manual_amount'] ) ) : 0.00;
            $payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Cash';

            // Double Booking Conflict Prevention Guard
            $date_day_start = $booking_date . ' 00:00:00';
            $date_day_end   = $booking_date . ' 23:59:59';

            $existing_slots = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT seats FROM {$table_bookings} 
                     WHERE movie_title = %s 
                       AND showtime = %s 
                       AND booking_date >= %s 
                       AND booking_date <= %s",
                    $movie_title,
                    $showtime,
                    $date_day_start,
                    $date_day_end
                )
            );

            if ( ! empty( $existing_slots ) ) {
                $taken_seats = array();
                foreach ( $existing_slots as $slot_seats ) {
                    $exploded = array_filter( array_map( 'trim', explode( ',', strtoupper( $slot_seats ) ) ) );
                    $taken_seats = array_merge( $taken_seats, $exploded );
                }

                $conflicts = array_intersect( $clean_seats_arr, array_unique( $taken_seats ) );
                if ( ! empty( $conflicts ) ) {
                    wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=add&booking_error=seat_conflict&conflicts=' . urlencode( implode( ',', $conflicts ) ) ) );
                    exit;
                }
            }

            $wpdb->insert(
                $table_bookings,
                array(
                    'booking_token'  => $token,
                    'movie_title'    => $movie_title,
                    'auditorium'     => $auditorium,
                    'showtime'       => $showtime,
                    'ticket_type'    => $ticket_type,
                    'seats'          => $seats,
                    'customer_name'  => $customer_name,
                    'customer_phone' => $customer_phone,
                    'customer_email' => $customer_email,
                    'total_amount'   => $total_amount,
                    'payment_method' => $payment_method,
                    'payment_status' => 'Confirmed',
                    'booking_date'   => $booking_date . ' ' . current_time( 'H:i:s' ),
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
            );

            wp_safe_redirect( admin_url( 'admin.php?page=bsc-booking&subtab=all&booking_added=true' ) );
            exit;
        }
    }
}

// =============================================================================
// 2. MAIN PAGE RENDER CALLBACK
// =============================================================================
function bsc_render_booking_page() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
    }

    $subtab     = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'all';
    $booking_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

    // Open Layout Shell
    if ( function_exists( 'bsc_render_page_shell_open' ) ) {
        bsc_render_page_shell_open( 'bsc-booking' );
    }

    // Render Notifications
    if ( isset( $_GET['booking_added'] ) ) {
        echo '<div class="bsc-notification-banner success"><span class="bsc-notification-icon">✓</span>' . esc_html__( 'New admission pass generated successfully.', 'bcs-screen-manager' ) . '</div>';
    } elseif ( isset( $_GET['booking_updated'] ) ) {
        echo '<div class="bsc-notification-banner success"><span class="bsc-notification-icon">✓</span>' . esc_html__( 'Booking records updated successfully.', 'bcs-screen-manager' ) . '</div>';
    } elseif ( isset( $_GET['booking_deleted'] ) ) {
        echo '<div class="bsc-notification-banner success"><span class="bsc-notification-icon">✓</span>' . esc_html__( 'Admission pass record removed.', 'bcs-screen-manager' ) . '</div>';
    } elseif ( isset( $_GET['booking_error'] ) ) {
        $err = sanitize_key( wp_unslash( $_GET['booking_error'] ) );
        if ( 'seat_conflict' === $err ) {
            $conflicts = isset( $_GET['conflicts'] ) ? sanitize_text_field( wp_unslash( $_GET['conflicts'] ) ) : '';
            echo '<div class="bsc-notification-banner danger"><span class="bsc-notification-icon">✕</span>' . esc_html( sprintf( __( 'Selected seat(s) [%s] are already booked for this screening slot!', 'bcs-screen-manager' ), $conflicts ) ) . '</div>';
        } elseif ( 'no_seats' === $err ) {
            echo '<div class="bsc-notification-banner danger"><span class="bsc-notification-icon">✕</span>' . esc_html__( 'Please select at least one seat to proceed.', 'bcs-screen-manager' ) . '</div>';
        }
    }

    // Render Subtabs Navigation
    $tabs_file = BSC_PLUGIN_PATH . 'admin/booking/tabs-booking.php';
    if ( file_exists( $tabs_file ) ) {
        require $tabs_file;
    }

    // Route Subviews Safely
    $view_file = BSC_PLUGIN_PATH . 'admin/booking/all-booking.php';
    if ( 'view' === $subtab && $booking_id > 0 ) {
        $view_file = BSC_PLUGIN_PATH . 'admin/booking/view-booking.php';
    } elseif ( 'add' === $subtab || 'edit' === $subtab ) {
        $view_file = BSC_PLUGIN_PATH . 'admin/booking/add-edit-booking.php';
    }

    if ( file_exists( $view_file ) ) {
        require $view_file;
    } else {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'The requested view file could not be found.', 'bcs-screen-manager' )
        );
    }

    // Close Layout Shell
    if ( function_exists( 'bsc_render_page_shell_close' ) ) {
        bsc_render_page_shell_close();
    }
}