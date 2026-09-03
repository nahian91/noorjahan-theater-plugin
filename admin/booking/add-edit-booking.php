<?php
/**
 * Booking Module - Unified Add & Edit Interactive Workspace
 *
 * Path: admin/booking/add-edit-booking.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
}

global $wpdb;
$table_bookings = $wpdb->prefix . 'bsc_bookings';
$table_movies   = $wpdb->prefix . 'bsc_control_movies';

$subtab     = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'add';
$booking_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$is_edit    = ( 'edit' === $subtab && $booking_id > 0 );

$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );
$std_base_price  = floatval( get_option( 'bsc_standard_price', 400.00 ) );
$vip_base_price  = floatval( get_option( 'bsc_vip_price', 500.00 ) );

if ( $std_base_price <= 0 ) {
    $std_base_price = 400.00;
}
if ( $vip_base_price <= 0 ) {
    $vip_base_price = 500.00;
}

$service_charge_pct = max( 0, floatval( get_option( 'bsc_service_charge_pct', 5.00 ) ) );
$enable_tax         = ( 'yes' === get_option( 'bsc_enable_tax', 'yes' ) );

// Fetch Edit Target Data
$edit_booking = null;
$preselected_seats = array();
if ( $is_edit ) {
    $edit_booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $booking_id ) );
    if ( ! $edit_booking ) {
        echo '<div class="notice notice-error" style="padding:15px;"><p>' . esc_html__( 'Booking record not found.', 'bsc-cinema-manager' ) . '</p></div>';
        return;
    }
    $preselected_seats = array_filter( array_map( 'trim', explode( ',', $edit_booking->seats ) ) );
}

// Fetch all movies
$movies = $wpdb->get_results( "SELECT id, title, showtimes FROM {$table_movies} ORDER BY id DESC" );

// Map existing occupied seats across other bookings (excluding current booking when in edit mode)
$query_occupied = "SELECT id, movie_title, showtime, seats, booking_date FROM {$table_bookings}";
if ( $is_edit ) {
    $query_occupied .= $wpdb->prepare( " WHERE id != %d", $booking_id );
}
$all_bookings_raw = $wpdb->get_results( $query_occupied );
$slot_booked_seats_map = array();

if ( ! empty( $all_bookings_raw ) ) {
    foreach ( $all_bookings_raw as $b_row ) {
        $b_movie  = trim( strtolower( $b_row->movie_title ) );
        $b_date   = date( 'Y-m-d', strtotime( $b_row->booking_date ) );
        $b_time   = trim( $b_row->showtime );
        $slot_key = $b_movie . '_' . $b_date . '_' . $b_time;

        if ( ! isset( $slot_booked_seats_map[ $slot_key ] ) ) {
            $slot_booked_seats_map[ $slot_key ] = array();
        }

        $seats = explode( ',', $b_row->seats );
        foreach ( $seats as $st ) {
            $clean = trim( strtoupper( $st ) );
            if ( ! empty( $clean ) && ! in_array( $clean, $slot_booked_seats_map[ $slot_key ], true ) ) {
                $slot_booked_seats_map[ $slot_key ][] = $clean;
            }
        }
    }
}

// Prepare baseline values
$current_movie     = $is_edit ? $edit_booking->movie_title : ( ! empty( $movies ) ? $movies[0]->title : 'Feature Presentation' );
$current_date      = $is_edit && ! empty( $edit_booking->booking_date ) ? date( 'Y-m-d', strtotime( $edit_booking->booking_date ) ) : current_time( 'Y-m-d' );
$current_time      = $is_edit ? trim( $edit_booking->showtime ) : '11:00 AM';
$current_cust_name = $is_edit ? $edit_booking->customer_name : 'Walk-In Guest';
$current_cust_ph   = $is_edit ? $edit_booking->customer_phone : '+880 ';
$current_cust_em   = $is_edit ? $edit_booking->customer_email : '';
$current_method    = $is_edit ? $edit_booking->payment_method : 'Cash';
$current_status    = $is_edit ? $edit_booking->payment_status : 'Confirmed';
?>

<style>
    .bsc-booking-builder-grid {
        display: grid;
        grid-template-columns: 1fr 360px;
        gap: 1.5rem;
        align-items: start;
        max-width: 1180px;
        margin: 10px auto 60px auto;
    }
    .bsc-seat-matrix-card {
        background: #060e12;
        border-radius: var(--cinema-radius-md, 8px);
        padding: 1.75rem 2rem;
        color: #fff;
        border: 1px solid #172a33;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.15);
    }
    .bsc-seat-matrix-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        padding: 1.5rem 0;
        overflow-x: auto;
    }
    .bsc-seat-column {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        align-items: center;
    }
    .bsc-col-header {
        font-size: 0.72rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.35rem;
    }
    .bsc-seat-box {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        border: 2px solid transparent;
        font-weight: 800;
        font-size: 0.8rem;
        font-family: var(--cinema-font-mono, monospace);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
        outline: none;
    }
    .bsc-seat-box.vip {
        background: #3b1704;
        border-color: #b45309;
        color: #fde68a;
    }
    .bsc-seat-box.standard {
        background: #09231f;
        border-color: #0d9488;
        color: #99f6e4;
    }
    .bsc-seat-box:hover:not(:disabled) {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 14px rgba(0,0,0,0.4);
    }
    .bsc-seat-box.selected {
        background: #f59e0b !important;
        border-color: #fde68a !important;
        color: #000000 !important;
        box-shadow: 0 0 16px rgba(245, 158, 11, 0.7);
        transform: scale(1.08);
    }
    .bsc-seat-box:disabled, .bsc-seat-box.occupied {
        background: #1e1215 !important;
        border-color: #ef4444 !important;
        color: #fca5a5 !important;
        cursor: not-allowed;
        opacity: 0.45;
        transform: none !important;
        box-shadow: none !important;
    }
    .bsc-screen-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding-left: 1.25rem;
        border-left: 2px dashed rgba(255, 255, 255, 0.12);
        color: #0d9488;
        font-weight: 900;
        font-size: 0.75rem;
        letter-spacing: 3px;
    }
    .bsc-screen-indicator svg {
        width: 22px;
        height: 22px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        margin-top: 8px;
    }
    .bsc-seat-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        justify-content: center;
        padding-top: 1.25rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 0.8rem;
    }
    .bsc-summary-sidebar {
        background: #ffffff;
        border: 1px solid var(--cinema-border, #e2e8f0);
        border-radius: var(--cinema-radius-md, 8px);
        padding: 1.6rem;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
    }
    .bsc-selected-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        min-height: 44px;
        padding: 0.65rem;
        background: #f8fafc;
        border: 1px solid var(--cinema-border, #e2e8f0);
        border-radius: 8px;
        margin-bottom: 1.25rem;
        align-items: center;
    }
    .bsc-seat-chip {
        background: #0d9488;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 800;
        padding: 0.2rem 0.55rem;
        border-radius: 4px;
        font-family: var(--cinema-font-mono, monospace);
    }
    .bsc-bill-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
        margin-bottom: 1.5rem;
    }
    .bsc-bill-table td {
        padding: 0.55rem 0;
        border-bottom: 1px dashed var(--cinema-border-light, #f1f5f9);
    }
    .bsc-bill-table tr.grand-row td {
        border-bottom: none;
        padding-top: 0.85rem;
        font-size: 1.05rem;
        font-weight: 900;
    }
    @media (max-width: 1024px) {
        .bsc-booking-builder-grid { grid-template-columns: 1fr; }
    }
</style>

<form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking' ) ); ?>" id="bscBookingForm">
    <?php if ( $is_edit ) : ?>
        <?php wp_nonce_field( 'bsc_edit_booking_verify' ); ?>
        <input type="hidden" name="bsc_action" value="edit_booking">
        <input type="hidden" name="booking_id" value="<?php echo esc_attr( $edit_booking->id ); ?>">
    <?php else : ?>
        <?php wp_nonce_field( 'bsc_manual_booking_verify' ); ?>
        <input type="hidden" name="bsc_action" value="add_booking">
    <?php endif; ?>

    <input type="hidden" name="manual_seats" id="input_manual_seats" required value="<?php echo esc_attr( implode( ', ', $preselected_seats ) ); ?>">
    <input type="hidden" name="seats" id="input_seats_sync" value="<?php echo esc_attr( implode( ', ', $preselected_seats ) ); ?>">
    <input type="hidden" name="manual_amount" id="input_manual_amount" required value="<?php echo esc_attr( $is_edit ? $edit_booking->total_amount : '0.00' ); ?>">
    <input type="hidden" name="total_amount" id="input_total_amount_sync" value="<?php echo esc_attr( $is_edit ? $edit_booking->total_amount : '0.00' ); ?>">
    <input type="hidden" name="manual_ticket_type" id="input_manual_ticket_type" value="<?php echo esc_attr( $is_edit ? $edit_booking->ticket_type : 'Standard' ); ?>">

    <div class="bsc-booking-builder-grid">
        <!-- LEFT COLUMN: Movie / Date / Showtime & Visual Matrix -->
        <div>
            <!-- Step 1: Movie & Showtimes Selection -->
            <div class="cinema-panel" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
                    <h3 style="margin:0; font-size:1.05rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="#0d9488"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                        <span><?php echo $is_edit ? esc_html__( '1. Modify Feature Movie & Screening Schedule', 'bsc-cinema-manager' ) : esc_html__( '1. Select Movie & Screening Schedule', 'bsc-cinema-manager' ); ?></span>
                    </h3>
                    <?php if ( $is_edit ) : ?>
                        <code style="background:#f1f5f9; padding:2px 8px; border-radius:4px; font-size:0.85rem; font-weight:800; color:#0f172a;"><?php echo esc_html( $edit_booking->booking_token ); ?></code>
                    <?php endif; ?>
                </div>

                <div class="bsc-form-grid-3" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px;">
                    <div class="bsc-form-group" style="margin-bottom:0;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Feature Movie', 'bsc-cinema-manager' ); ?> *</label>
                        <select name="manual_movie" id="select_movie" required onchange="onMovieChange(this)" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <?php if ( ! empty( $movies ) ) : foreach ( $movies as $m ) : ?>
                                <option value="<?php echo esc_attr( $m->title ); ?>" data-showtimes="<?php echo esc_attr( $m->showtimes ); ?>" <?php selected( $current_movie, $m->title ); ?>>
                                    <?php echo esc_html( $m->title ); ?>
                                </option>
                            <?php endforeach; else : ?>
                                <option value="<?php echo esc_attr( $current_movie ); ?>" data-showtimes='[{"date":"<?php echo esc_attr( $current_date ); ?>","times":["11:00 AM","02:00 PM","05:00 PM"]}]'><?php echo esc_html( $current_movie ); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="bsc-form-group" style="margin-bottom:0;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Screening Date', 'bsc-cinema-manager' ); ?> *</label>
                        <select name="manual_booking_date" id="select_schedule_date" required onchange="onScheduleDateChange(this)" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <option value="<?php echo esc_attr( $current_date ); ?>"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $current_date ) ) ); ?></option>
                        </select>
                    </div>

                    <div class="bsc-form-group" style="margin-bottom:0;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Showtime Slot', 'bsc-cinema-manager' ); ?> *</label>
                        <select name="manual_showtime" id="select_showtime" required onchange="onShowtimeChange(this)" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                        </select>
                    </div>
                </div>
            </div>

            <!-- Step 2: Interactive Seating Matrix -->
            <div class="bsc-seat-matrix-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.25rem;">
                    <h4 style="font-size:1.1rem; font-weight:800; margin:0; color:#fff;">
                        <?php echo $is_edit ? esc_html__( 'Seat Reservation Adjustments (Click to Change)', 'bsc-cinema-manager' ) : esc_html__( 'Interactive Seat Reservation Chart', 'bsc-cinema-manager' ); ?>
                    </h4>
                    <span id="selectedSeatsCounter" style="font-family:monospace; font-size:0.85rem; color:#38bdf8; font-weight:800; background:rgba(56,189,248,0.1); padding:4px 10px; border-radius:6px; border:1px solid rgba(56,189,248,0.25);">
                        0 Seats Picked
                    </span>
                </div>

                <div class="bsc-seat-matrix-wrapper">
                    <!-- Row A (VIP - 5 Seats) -->
                    <div class="bsc-seat-column">
                        <div class="bsc-col-header" style="color:#fbbf24;">Row A (VIP)</div>
                        <?php foreach ( array( 'A1', 'A2', 'A3', 'A4', 'A5' ) as $s_id ) : ?>
                            <button type="button" class="bsc-seat-box vip" data-seat="<?php echo esc_attr( $s_id ); ?>" data-type="vip" onclick="toggleAdminSeat('<?php echo esc_attr( $s_id ); ?>', this, 'vip')">
                                <?php echo esc_html( $s_id ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Row B (Standard - 6 Seats) -->
                    <div class="bsc-seat-column">
                        <div class="bsc-col-header">Row B</div>
                        <?php foreach ( array( 'B1', 'B2', 'B3', 'B4', 'B5', 'B6' ) as $s_id ) : ?>
                            <button type="button" class="bsc-seat-box standard" data-seat="<?php echo esc_attr( $s_id ); ?>" data-type="standard" onclick="toggleAdminSeat('<?php echo esc_attr( $s_id ); ?>', this, 'standard')">
                                <?php echo esc_html( $s_id ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Row C (Standard - 6 Seats) -->
                    <div class="bsc-seat-column">
                        <div class="bsc-col-header">Row C</div>
                        <?php foreach ( array( 'C1', 'C2', 'C3', 'C4', 'C5', 'C6' ) as $s_id ) : ?>
                            <button type="button" class="bsc-seat-box standard" data-seat="<?php echo esc_attr( $s_id ); ?>" data-type="standard" onclick="toggleAdminSeat('<?php echo esc_attr( $s_id ); ?>', this, 'standard')">
                                <?php echo esc_html( $s_id ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Row D (Standard - 6 Seats) -->
                    <div class="bsc-seat-column">
                        <div class="bsc-col-header">Row D</div>
                        <?php foreach ( array( 'D1', 'D2', 'D3', 'D4', 'D5', 'D6' ) as $s_id ) : ?>
                            <button type="button" class="bsc-seat-box standard" data-seat="<?php echo esc_attr( $s_id ); ?>" data-type="standard" onclick="toggleAdminSeat('<?php echo esc_attr( $s_id ); ?>', this, 'standard')">
                                <?php echo esc_html( $s_id ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="bsc-screen-indicator">
                        <span>S</span><span>C</span><span>R</span><span>E</span><span>E</span><span>N</span>
                        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </div>
                </div>

                <div class="bsc-seat-legend">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <div style="width:12px; height:12px; border-radius:3px; background:#09231f; border:1px solid #0d9488;"></div>
                        <span><?php esc_html_e( 'Standard', 'bsc-cinema-manager' ); ?> (<?php echo esc_html( $currency_symbol . number_format( $std_base_price, 0 ) ); ?>)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <div style="width:12px; height:12px; border-radius:3px; background:#3b1704; border:1px solid #b45309;"></div>
                        <span><?php esc_html_e( 'VIP Recliner', 'bsc-cinema-manager' ); ?> (<?php echo esc_html( $currency_symbol . number_format( $vip_base_price, 0 ) ); ?>)</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <div style="width:12px; height:12px; border-radius:3px; background:#f59e0b;"></div>
                        <span><?php esc_html_e( 'Selected', 'bsc-cinema-manager' ); ?></span>
                    </div>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <div style="width:12px; height:12px; border-radius:3px; background:#1e1215; border:1px solid #ef4444;"></div>
                        <span><?php esc_html_e( 'Occupied', 'bsc-cinema-manager' ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Customer Details & Checkout Calculation -->
        <div>
            <div class="bsc-summary-sidebar">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
                    <h3 style="margin:0; font-size:1.05rem; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="#0d9488"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <span><?php esc_html_e( 'Customer Information', 'bsc-cinema-manager' ); ?></span>
                    </h3>
                    <?php if ( $is_edit ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=all' ) ); ?>" style="font-size:0.75rem; font-weight:700; color:#64748b; text-decoration:none;">
                            &larr; <?php esc_html_e( 'Cancel', 'bsc-cinema-manager' ); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="bsc-form-group" style="margin-bottom:12px;">
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:4px;"><?php esc_html_e( 'Customer Full Name *', 'bsc-cinema-manager' ); ?></label>
                    <input type="text" name="customer_name" required value="<?php echo esc_attr( $current_cust_name ); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>

                <div class="bsc-form-group" style="margin-bottom:12px;">
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:4px;"><?php esc_html_e( 'Phone Number *', 'bsc-cinema-manager' ); ?></label>
                    <input type="text" name="customer_phone" required value="<?php echo esc_attr( $current_cust_ph ); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>

                <div class="bsc-form-group" style="margin-bottom:16px;">
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:4px;"><?php esc_html_e( 'Email Address', 'bsc-cinema-manager' ); ?></label>
                    <input type="email" name="customer_email" value="<?php echo esc_attr( $current_cust_em ); ?>" placeholder="customer@example.com" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>

                <!-- Payment Method -->
                <div class="bsc-form-group" style="margin-bottom:16px;">
                    <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:6px;"><?php esc_html_e( 'Payment Method *', 'bsc-cinema-manager' ); ?></label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <label style="display:flex; align-items:center; gap:8px; border:1px solid #cbd5e1; padding:8px 10px; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:700;">
                            <input type="radio" name="payment_method" value="Cash" <?php checked( $current_method, 'Cash' ); ?>>
                            <span><?php esc_html_e( 'Cash Desk', 'bsc-cinema-manager' ); ?></span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; border:1px solid #cbd5e1; padding:8px 10px; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:700;">
                            <input type="radio" name="payment_method" value="bKash" <?php checked( $current_method, 'bKash' ); ?>>
                            <span><?php esc_html_e( 'bKash', 'bsc-cinema-manager' ); ?></span>
                        </label>
                    </div>
                </div>

                <?php if ( $is_edit ) : ?>
                    <div class="bsc-form-group" style="margin-bottom:16px;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:4px;"><?php esc_html_e( 'Payment Status', 'bsc-cinema-manager' ); ?></label>
                        <select name="payment_status" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                            <option value="Confirmed" <?php selected( $current_status, 'Confirmed' ); ?>>Confirmed</option>
                            <option value="Confirmed Online" <?php selected( $current_status, 'Confirmed Online' ); ?>>Confirmed Online</option>
                            <option value="bKash Paid" <?php selected( $current_status, 'bKash Paid' ); ?>>bKash Paid</option>
                        </select>
                    </div>
                <?php endif; ?>

                <hr style="border:0; border-top:1px solid #f1f5f9; margin: 1.25rem 0;">

                <label style="font-weight: 800; font-size: 0.75rem; text-transform: uppercase; color: #64748b; display: block; margin-bottom: 0.4rem;">
                    <?php esc_html_e( 'Selected Seats', 'bsc-cinema-manager' ); ?>
                </label>
                <div class="bsc-selected-chips" id="selectedChipsDeck" style="display:flex; flex-wrap:wrap; gap:6px; min-height:30px; margin-bottom:14px;">
                    <span style="font-size: 0.82rem; color: #94a3b8;"><?php esc_html_e( 'No seats selected yet.', 'bsc-cinema-manager' ); ?></span>
                </div>

                <table class="bsc-bill-table">
                    <tbody>
                        <tr>
                            <td style="color: #64748b; padding:8px 0;">
                                <div style="font-weight:700; color:#1e293b;"><?php esc_html_e( 'Standard Seats', 'bsc-cinema-manager' ); ?></div>
                                <span id="lblStdQuantity" style="font-size:0.75rem; color:#94a3b8;">(0 &times; <?php echo esc_html( $currency_symbol . number_format( $std_base_price, 0 ) ); ?>)</span>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-family: monospace; vertical-align: top; padding-top: 8px;" id="lblStdSubtotal">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                        <tr>
                            <td style="color: #64748b; padding:8px 0;">
                                <div style="font-weight:700; color:#1e293b;"><?php esc_html_e( 'VIP Recliners', 'bsc-cinema-manager' ); ?></div>
                                <span id="lblVipQuantity" style="font-size:0.75rem; color:#94a3b8;">(0 &times; <?php echo esc_html( $currency_symbol . number_format( $vip_base_price, 0 ) ); ?>)</span>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-family: monospace; vertical-align: top; padding-top: 8px;" id="lblVipSubtotal">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                        <tr>
                            <td style="color: #64748b; padding:8px 0; font-weight:700;">
                                <?php esc_html_e( 'Tickets Subtotal', 'bsc-cinema-manager' ); ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-family: monospace;" id="lblSubtotal">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                        <tr>
                            <td style="color: #64748b; padding:8px 0;">
                                <?php esc_html_e( 'Service / VAT', 'bsc-cinema-manager' ); ?>
                                <?php if ( $enable_tax && $service_charge_pct > 0 ) : ?>
                                    <span style="font-size:0.75rem; color:#94a3b8;">(<?php echo esc_html( $service_charge_pct ); ?>%)</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; font-family: monospace;" id="lblTax">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                        <tr class="grand-row" style="border-top:2px solid #e2e8f0;">
                            <td style="color: #0f172a; font-weight:800; padding:12px 0; font-size:0.95rem;"><?php echo esc_html( sprintf( __( 'Total Settlement (%s)', 'bsc-cinema-manager' ), $currency_symbol ) ); ?></td>
                            <td style="text-align: right; color: #059669; font-family: monospace; font-weight:900; font-size:1.15rem; padding:12px 0;" id="lblGrandTotal">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" style="background:#059669; border-color:#059669; width:100%; padding:11px; font-weight:900; font-size: 0.95rem; border-radius:8px; box-shadow:0 4px 14px rgba(5,150,105,0.2); display:flex; align-items:center; justify-content:center; gap:8px;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                    <span><?php echo $is_edit ? esc_html__( 'Update & Save Changes', 'bsc-cinema-manager' ) : esc_html__( 'Save Booking', 'bsc-cinema-manager' ); ?></span>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    const adminSelectedSeats = new Map();
    const VIP_PRICE          = <?php echo (float) $vip_base_price; ?>;
    const STD_PRICE          = <?php echo (float) $std_base_price; ?>;
    const TAX_RATE           = <?php echo ( $enable_tax && $service_charge_pct > 0 ) ? ( (float) $service_charge_pct / 100 ) : 0; ?>;
    const CURRENCY_TAG       = <?php echo wp_json_encode( $currency_symbol ); ?>;
    const SLOT_BOOKED_MAP    = <?php echo wp_json_encode( $slot_booked_seats_map ); ?>;
    const INITIAL_SEATS      = <?php echo wp_json_encode( $preselected_seats ); ?>;
    const PRESELECTED_TIME   = <?php echo wp_json_encode( $current_time ); ?>;
    const PRESELECTED_DATE   = <?php echo wp_json_encode( $current_date ); ?>;
    let movieScheduleMap     = {};

    function formatTimeSlot(timeStr) {
        if (!timeStr) return '06:00 PM';
        const clean = String(timeStr).trim();
        if (clean.toUpperCase().includes('AM') || clean.toUpperCase().includes('PM')) {
            return clean;
        }
        if (clean.indexOf(':') !== -1 && clean.length <= 5) {
            const parts = clean.split(':');
            let hour = parseInt(parts[0], 10);
            const minute = parts[1];
            const ampm = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12 || 12;
            return String(hour).padStart(2, '0') + ':' + minute + ' ' + ampm;
        }
        return clean;
    }

    function formatFriendlyDate(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const d = new Date(parts[0], parts[1] - 1, parts[2]);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const day = String(d.getDate()).padStart(2, '0');
        return day + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    function onMovieChange(selectEl) {
        const option = selectEl.options[selectEl.selectedIndex];
        if (!option) return;

        const showtimesRaw = option.getAttribute('data-showtimes') || '[]';
        const dateSelect = document.getElementById('select_schedule_date');
        dateSelect.innerHTML = '';
        movieScheduleMap = {};

        let schedules = [];
        try {
            const parsed = JSON.parse(showtimesRaw);
            if (Array.isArray(parsed)) schedules = parsed;
        } catch(e) {
            schedules = [];
        }

        if (schedules.length > 0 && schedules[0].date && schedules[0].times) {
            schedules.forEach(item => {
                const d = item.date;
                movieScheduleMap[d] = item.times || ['11:00 AM'];

                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = formatFriendlyDate(d);
                if (d === PRESELECTED_DATE) {
                    opt.selected = true;
                }
                dateSelect.appendChild(opt);
            });
        } else {
            const dateVal = PRESELECTED_DATE || '<?php echo esc_js( current_time( 'Y-m-d' ) ); ?>';
            const flatTimes = (schedules.length > 0) ? schedules : ['11:00 AM', '02:00 PM', '05:00 PM'];
            movieScheduleMap[dateVal] = flatTimes;

            const opt = document.createElement('option');
            opt.value = dateVal;
            opt.textContent = formatFriendlyDate(dateVal);
            opt.selected = true;
            dateSelect.appendChild(opt);
        }

        if (!dateSelect.value && dateSelect.options.length > 0) {
            dateSelect.selectedIndex = 0;
        }

        onScheduleDateChange(dateSelect);
    }

    function onScheduleDateChange(selectEl) {
        const selectedDate = selectEl.value;
        const timeSelect = document.getElementById('select_showtime');
        timeSelect.innerHTML = '';

        const times = movieScheduleMap[selectedDate] || ['11:00 AM'];
        times.forEach(t => {
            const cleanTime = String(t).trim();
            const opt = document.createElement('option');
            opt.value = cleanTime;
            opt.textContent = formatTimeSlot(cleanTime);
            if (cleanTime === PRESELECTED_TIME) {
                opt.selected = true;
            }
            timeSelect.appendChild(opt);
        });

        if (!timeSelect.value && timeSelect.options.length > 0) {
            timeSelect.selectedIndex = 0;
        }

        refreshSeatMatrixState();
    }

    function onShowtimeChange(selectEl) {
        refreshSeatMatrixState();
    }

    function refreshSeatMatrixState() {
        const movieSelect = document.getElementById('select_movie');
        const dateSelect  = document.getElementById('select_schedule_date');
        const timeSelect  = document.getElementById('select_showtime');

        if (!movieSelect || !dateSelect || !timeSelect) return;

        const movieTitle = movieSelect.value.trim().toLowerCase();
        const dateVal    = dateSelect.value;
        const timeVal    = timeSelect.value.trim();
        const slotKey    = `${movieTitle}_${dateVal}_${timeVal}`;

        const occupiedList = SLOT_BOOKED_MAP[slotKey] || [];
        const seatButtons  = document.querySelectorAll('.bsc-seat-box');

        seatButtons.forEach(btn => {
            const seatId = btn.getAttribute('data-seat');

            if (occupiedList.includes(seatId)) {
                btn.disabled = true;
                btn.classList.add('occupied');
                btn.classList.remove('selected');
                adminSelectedSeats.delete(seatId);
            } else {
                btn.disabled = false;
                btn.classList.remove('occupied');
                if (adminSelectedSeats.has(seatId)) {
                    btn.classList.add('selected');
                } else {
                    btn.classList.remove('selected');
                }
            }
        });

        calculateTotals();
    }

    function toggleAdminSeat(seatId, btnEl, seatType) {
        if (btnEl.disabled || btnEl.classList.contains('occupied')) return;

        const isVIP = (seatType === 'vip' || seatId.charAt(0).toUpperCase() === 'A');
        const price = isVIP ? VIP_PRICE : STD_PRICE;
        const type  = isVIP ? 'VIP' : 'Standard';

        if (adminSelectedSeats.has(seatId)) {
            adminSelectedSeats.delete(seatId);
            btnEl.classList.remove('selected');
        } else {
            adminSelectedSeats.set(seatId, { id: seatId, type: type, price: price });
            btnEl.classList.add('selected');
        }

        calculateTotals();
    }

    function calculateTotals() {
        const chipsDeck       = document.getElementById('selectedChipsDeck');
        const counter         = document.getElementById('selectedSeatsCounter');
        const hiddenSeats     = document.getElementById('input_manual_seats');
        const hiddenSeatsSync = document.getElementById('input_seats_sync');
        const hiddenAmount    = document.getElementById('input_manual_amount');
        const hiddenAmtSync   = document.getElementById('input_total_amount_sync');
        const hiddenTier      = document.getElementById('input_manual_ticket_type');

        let stdCount    = 0;
        let vipCount    = 0;
        let stdSubtotal = 0;
        let vipSubtotal = 0;
        const seatNames = [];

        adminSelectedSeats.forEach(s => {
            seatNames.push(s.id);
            if (s.type === 'VIP') {
                vipCount++;
                vipSubtotal += parseFloat(s.price);
            } else {
                stdCount++;
                stdSubtotal += parseFloat(s.price);
            }
        });

        if (seatNames.length === 0) {
            chipsDeck.innerHTML = '<span style="font-size: 0.82rem; color: #94a3b8;"><?php echo esc_js( __( 'No seats selected yet.', 'bsc-cinema-manager' ) ); ?></span>';
            counter.textContent = '0 Seats Picked';
            hiddenSeats.value = '';
            if (hiddenSeatsSync) hiddenSeatsSync.value = '';
            hiddenTier.value = 'Standard';
        } else {
            chipsDeck.innerHTML = '';
            seatNames.forEach(id => {
                const chip = document.createElement('span');
                chip.style.cssText = 'display:inline-block; padding:3px 8px; border-radius:5px; background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:0.8rem; font-weight:800; font-family:monospace;';
                chip.textContent = id;
                chipsDeck.appendChild(chip);
            });
            counter.textContent = seatNames.length + ' Seat(s) Picked';
            const joined = seatNames.join(', ');
            hiddenSeats.value = joined;
            if (hiddenSeatsSync) hiddenSeatsSync.value = joined;
            hiddenTier.value = (vipCount > 0 && stdCount === 0) ? 'VIP' : (vipCount > 0 ? 'Mixed (VIP + Standard)' : 'Standard');
        }

        const subtotal = Math.round((stdSubtotal + vipSubtotal + Number.EPSILON) * 100) / 100;
        const tax      = Math.round(((subtotal * TAX_RATE) + Number.EPSILON) * 100) / 100;
        const grand    = Math.round(((subtotal + tax) + Number.EPSILON) * 100) / 100;

        document.getElementById('lblStdQuantity').textContent = `(${stdCount} × ${CURRENCY_TAG}${STD_PRICE.toFixed(0)})`;
        document.getElementById('lblStdSubtotal').textContent = `${CURRENCY_TAG} ${stdSubtotal.toFixed(2)}`;

        document.getElementById('lblVipQuantity').textContent = `(${vipCount} × ${CURRENCY_TAG}${VIP_PRICE.toFixed(0)})`;
        document.getElementById('lblVipSubtotal').textContent = `${CURRENCY_TAG} ${vipSubtotal.toFixed(2)}`;

        document.getElementById('lblSubtotal').textContent   = `${CURRENCY_TAG} ${subtotal.toFixed(2)}`;
        document.getElementById('lblTax').textContent        = `${CURRENCY_TAG} ${tax.toFixed(2)}`;
        document.getElementById('lblGrandTotal').textContent = `${CURRENCY_TAG} ${grand.toFixed(2)}`;

        hiddenAmount.value = grand.toFixed(2);
        if (hiddenAmtSync) hiddenAmtSync.value = grand.toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Pre-populate pre-selected seats when in Edit mode
        if (Array.isArray(INITIAL_SEATS) && INITIAL_SEATS.length > 0) {
            INITIAL_SEATS.forEach(st => {
                const cleanId = String(st).trim().toUpperCase();
                const isVIP   = (cleanId.charAt(0) === 'A');
                adminSelectedSeats.set(cleanId, {
                    id: cleanId,
                    type: isVIP ? 'VIP' : 'Standard',
                    price: isVIP ? VIP_PRICE : STD_PRICE
                });
            });
        }

        const movieSelect = document.getElementById('select_movie');
        if (movieSelect) {
            onMovieChange(movieSelect);
        }
    });

    const bookingForm = document.getElementById('bscBookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            if (adminSelectedSeats.size === 0) {
                e.preventDefault();
                alert('<?php echo esc_js( __( 'Please select at least one seat from the seating chart.', 'bsc-cinema-manager' ) ); ?>');
            }
        });
    }
</script>