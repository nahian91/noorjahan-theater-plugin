<?php
/**
 * Booking Module - Unified Add & Edit Interactive Workspace
 *
 * Path: admin/booking/add-edit-booking.php
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
$table_bookings = $wpdb->prefix . 'bsc_bookings';
$table_movies   = $wpdb->prefix . 'bsc_control_movies';

$subtab     = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'add';
$booking_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$is_edit    = ( 'edit' === $subtab && $booking_id > 0 );

$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );
$std_base_price  = (float) get_option( 'bsc_standard_price', 400.00 );
$vip_base_price  = (float) get_option( 'bsc_vip_price', 500.00 );

if ( $std_base_price <= 0 ) {
    $std_base_price = 400.00;
}
if ( $vip_base_price <= 0 ) {
    $vip_base_price = 500.00;
}

$service_charge_pct = max( 0.0, (float) get_option( 'bsc_service_charge_pct', 5.00 ) );
$enable_tax         = ( 'yes' === get_option( 'bsc_enable_tax', 'yes' ) );

// Fetch Edit Target Data
$edit_booking      = null;
$preselected_seats = array();
if ( $is_edit ) {
    $edit_booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $booking_id ) );
    if ( ! edit_booking ) {
        echo '<div class="notice notice-error" style="padding:15px;"><p>' . esc_html__( 'Booking record not found.', 'bcs-screen-manager' ) . '</p></div>';
        return;
    }
    $preselected_seats = array_filter( array_map( 'trim', explode( ',', (string) $edit_booking->seats ) ) );
}

// Fetch all movies
$movies = $wpdb->get_results( "SELECT id, title, showtimes FROM {$table_movies} ORDER BY id DESC" );
if ( ! is_array( $movies ) ) {
    $movies = array();
}

// Map existing occupied seats across other bookings (excluding current booking when in edit mode)
if ( $is_edit ) {
    $all_bookings_raw = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, movie_title, showtime, seats, booking_date FROM {$table_bookings} WHERE id != %d AND payment_status != 'Cancelled'",
            $booking_id
        )
    );
} else {
    $all_bookings_raw = $wpdb->get_results(
        "SELECT id, movie_title, showtime, seats, booking_date FROM {$table_bookings} WHERE payment_status != 'Cancelled'"
    );
}

$slot_booked_seats_map = array();

if ( ! empty( $all_bookings_raw ) && is_array( $all_bookings_raw ) ) {
    foreach ( $all_bookings_raw as $b_row ) {
        $b_movie  = trim( strtolower( (string) $b_row->movie_title ) );
        $b_date   = ! empty( $b_row->booking_date ) ? wp_date( 'Y-m-d', strtotime( $b_row->booking_date ) ) : '';
        $b_time   = trim( (string) $b_row->showtime );
        $slot_key = $b_movie . '_' . $b_date . '_' . $b_time;

        if ( ! isset( $slot_booked_seats_map[ $slot_key ] ) ) {
            $slot_booked_seats_map[ $slot_key ] = array();
        }

        $seats = explode( ',', (string) $b_row->seats );
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
$current_date      = $is_edit && ! empty( $edit_booking->booking_date ) ? wp_date( 'Y-m-d', strtotime( $edit_booking->booking_date ) ) : current_time( 'Y-m-d' );
$current_time      = $is_edit ? trim( (string) $edit_booking->showtime ) : '11:00 AM';
$current_cust_name = $is_edit ? $edit_booking->customer_name : 'Walk-In Guest';
$current_cust_ph   = $is_edit ? $edit_booking->customer_phone : '+880 ';
$current_cust_em   = $is_edit ? $edit_booking->customer_email : '';
$current_method    = $is_edit ? $edit_booking->payment_method : 'Cash';
$current_status    = $is_edit ? $edit_booking->payment_status : 'Confirmed';
?>

<form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking' ) ); ?>" id="bscBookingForm">
    <?php 
    if ( $is_edit && $edit_booking ) {
        wp_nonce_field( 'bsc_edit_booking_verify' );
        echo '<input type="hidden" name="bsc_action" value="edit_booking">';
        echo '<input type="hidden" name="booking_id" value="' . esc_attr( (string) $edit_booking->id ) . '">';
    } else {
        wp_nonce_field( 'bsc_manual_booking_verify' );
        echo '<input type="hidden" name="bsc_action" value="add_booking">';
    }
    ?>

    <input type="hidden" name="manual_auditorium" value="Auditorium 1 (30 Seats)">
    <input type="hidden" name="manual_seats" id="input_manual_seats" required value="<?php echo esc_attr( implode( ', ', $preselected_seats ) ); ?>">
    <input type="hidden" name="seats" id="input_seats_sync" value="<?php echo esc_attr( implode( ', ', $preselected_seats ) ); ?>">
    <input type="hidden" name="manual_amount" id="input_manual_amount" required value="<?php echo esc_attr( $is_edit ? (string) $edit_booking->total_amount : '0.00' ); ?>">
    <input type="hidden" name="total_amount" id="input_total_amount_sync" value="<?php echo esc_attr( $is_edit ? (string) $edit_booking->total_amount : '0.00' ); ?>">
    <input type="hidden" name="manual_ticket_type" id="input_manual_ticket_type" value="<?php echo esc_attr( $is_edit ? (string) $edit_booking->ticket_type : 'Standard' ); ?>">

    <div class="bsc-theater-arena-grid">
        <!-- LEFT COLUMN: Movie / Date / Showtime & Visual 30-Seat Matrix -->
        <div>
            <!-- Step 1: Movie & Showtimes Selection -->
            <div class="bsc-screen-filter-bar">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 class="bsc-panel-title" style="margin:0; font-size:1.05rem;">
                        🎬 <?php echo $is_edit ? esc_html__( '1. Modify Feature Movie & Screening Schedule', 'bcs-screen-manager' ) : esc_html__( '1. Select Movie & Screening Schedule', 'bcs-screen-manager' ); ?>
                    </h3>
                    <?php if ( $is_edit && $edit_booking ) : ?>
                        <span class="bsc-voucher-tag" style="font-weight:800;"><?php echo esc_html( $edit_booking->booking_token ); ?></span>
                    <?php endif; ?>
                </div>

                <div class="bsc-grid-3" style="margin-bottom:0;">
                    <div class="bsc-form-group" style="margin-bottom:0;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Feature Movie', 'bcs-screen-manager' ); ?> *</label>
                        <select name="manual_movie" id="select_movie" required>
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
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Screening Date', 'bcs-screen-manager' ); ?> *</label>
                        <select name="manual_booking_date" id="select_schedule_date" required>
                            <option value="<?php echo esc_attr( $current_date ); ?>"><?php echo esc_html( wp_date( 'd M Y', strtotime( $current_date ) ) ); ?></option>
                        </select>
                    </div>

                    <div class="bsc-form-group" style="margin-bottom:0;">
                        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Showtime Slot', 'bcs-screen-manager' ); ?> *</label>
                        <select name="manual_showtime" id="select_showtime" required>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Step 2: Interactive 30-Seat Seating Matrix (Rows F to A, Seats 1 to 5) -->
            <div class="bsc-monitor-cinema-box">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
                    <div>
                        <span class="bsc-badge-pill active" style="font-size:0.68rem;"><?php esc_html_e( 'AUDITORIUM 30-SEAT MATRIX', 'bcs-screen-manager' ); ?></span>
                        <h4 style="font-size:1.15rem; font-weight:900; margin:0.35rem 0 0 0; color:#fff;">
                            <?php echo $is_edit ? esc_html__( 'Seat Reservation Adjustments (Click to Change)', 'bcs-screen-manager' ) : esc_html__( 'Interactive 30-Seat Reservation Chart', 'bcs-screen-manager' ); ?>
                        </h4>
                    </div>
                    <span id="selectedSeatsCounter" style="font-family:var(--bsc-font-mono, monospace); font-size:0.88rem; color:#ef4444; font-weight:800; background:rgba(239,68,68,0.15); padding:0.25rem 0.65rem; border-radius:6px; border:1px solid rgba(239,68,68,0.3);">
                        0 <?php esc_html_e( 'Seats Picked', 'bcs-screen-manager' ); ?>
                    </span>
                </div>

                <div class="bsc-monitor-matrix-canvas" id="bscSeatMatrixBoard">
                    <?php 
                    $matrix_rows = array( 'F', 'E', 'D', 'C', 'B', 'A' );

                    foreach ( $matrix_rows as $row_letter ) : ?>
                        <div class="bsc-seat-matrix-col">
                            <div class="col-label" style="color:#94a3b8; font-weight:900;"><?php echo esc_html( $row_letter ); ?></div>
                            <?php for ( $seat_num = 1; $seat_num <= 5; $seat_num++ ) : 
                                $s_id = $row_letter . $seat_num;
                            ?>
                                <div class="bsc-matrix-seat standard" id="matrix-seat-<?php echo esc_attr( $s_id ); ?>" data-seat="<?php echo esc_attr( $s_id ); ?>" style="cursor:pointer;">
                                    <?php echo esc_html( $s_id ); ?>
                                </div>
                            <?php endfor; ?>
                            <div class="col-label" style="color:#94a3b8; font-weight:900; margin-top:8px;"><?php echo esc_html( $row_letter ); ?></div>
                        </div>
                    <?php endforeach; ?>

                    <div class="bsc-matrix-screen-beacon">
                        <span>S</span><span>C</span><span>R</span><span>E</span><span>E</span><span>N</span>
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" style="margin-top: 8px;">
                            <path d="M5 12h14M13 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>

                <div class="bsc-matrix-legend-row">
                    <div class="bsc-legend-pill">
                        <div class="dot" style="background: #0d9488;"></div>
                        <span><?php esc_html_e( 'Available', 'bcs-screen-manager' ); ?> (<?php echo esc_html( $currency_symbol . ' ' . number_format( $std_base_price, 0 ) ); ?>)</span>
                    </div>
                    <div class="bsc-legend-pill">
                        <div class="dot" style="background: #0284c7;"></div>
                        <span><?php esc_html_e( 'Selected', 'bcs-screen-manager' ); ?></span>
                    </div>
                    <div class="bsc-legend-pill">
                        <div class="dot" style="background: #ef4444;"></div>
                        <span><?php esc_html_e( 'Booked / Reserved for Slot', 'bcs-screen-manager' ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Customer Details & Checkout Calculation -->
        <div>
            <div class="bsc-telemetry-panel">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
                    <h3 class="bsc-panel-title" style="margin:0; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                        👤 <?php esc_html_e( 'Customer Information', 'bcs-screen-manager' ); ?>
                    </h3>
                    <?php if ( $is_edit ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=all' ) ); ?>" style="font-size:0.75rem; font-weight:800; color:var(--bsc-text-muted, #64748b); text-decoration:none;">
                            &larr; <?php esc_html_e( 'Cancel', 'bcs-screen-manager' ); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="bsc-form-group">
                    <label><?php esc_html_e( 'Customer Full Name *', 'bcs-screen-manager' ); ?></label>
                    <input type="text" name="customer_name" required value="<?php echo esc_attr( $current_cust_name ); ?>">
                </div>

                <div class="bsc-form-group">
                    <label><?php esc_html_e( 'Phone Number *', 'bcs-screen-manager' ); ?></label>
                    <input type="text" name="customer_phone" required value="<?php echo esc_attr( $current_cust_ph ); ?>">
                </div>

                <div class="bsc-form-group">
                    <label><?php esc_html_e( 'Email Address', 'bcs-screen-manager' ); ?></label>
                    <input type="email" name="customer_email" value="<?php echo esc_attr( $current_cust_em ); ?>" placeholder="customer@example.com">
                </div>

                <div class="bsc-form-group" style="margin-bottom:16px;">
                    <label><?php esc_html_e( 'Payment Method *', 'bcs-screen-manager' ); ?></label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <label style="display:flex; align-items:center; gap:8px; border:1px solid var(--bsc-border, #e2e8f0); padding:8px 10px; border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:700; background:#f8fafc;">
                            <input type="radio" name="payment_method" value="Cash" <?php checked( $current_method, 'Cash' ); ?>>
                            <span><?php esc_html_e( 'Cash Desk', 'bcs-screen-manager' ); ?></span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; border:1px solid var(--bsc-border, #e2e8f0); padding:8px 10px; border-radius:8px; cursor:pointer; font-size:0.85rem; font-weight:700; background:#f8fafc;">
                            <input type="radio" name="payment_method" value="bKash" <?php checked( $current_method, 'bKash' ); ?>>
                            <span><?php esc_html_e( 'bKash', 'bcs-screen-manager' ); ?></span>
                        </label>
                    </div>
                </div>

                <?php if ( $is_edit ) : ?>
                    <div class="bsc-form-group" style="margin-bottom:16px;">
                        <label><?php esc_html_e( 'Payment Status', 'bcs-screen-manager' ); ?></label>
                        <select name="payment_status">
                            <option value="Confirmed" <?php selected( $current_status, 'Confirmed' ); ?>>Confirmed</option>
                            <option value="Confirmed Online" <?php selected( $current_status, 'Confirmed Online' ); ?>>Confirmed Online</option>
                            <option value="bKash Paid" <?php selected( $current_status, 'bKash Paid' ); ?>>bKash Paid</option>
                        </select>
                    </div>
                <?php endif; ?>

                <label style="font-weight: 800; font-size: 0.72rem; text-transform: uppercase; color: var(--bsc-text-muted, #64748b); display: block; margin-bottom: 0.4rem;">
                    <?php esc_html_e( 'Selected Seats Roster', 'bcs-screen-manager' ); ?>
                </label>
                <div class="bsc-chips-roster-box" id="selectedChipsDeck">
                    <span style="font-size: 0.82rem; color: var(--bsc-text-muted, #64748b);"><?php esc_html_e( 'No seats selected yet.', 'bcs-screen-manager' ); ?></span>
                </div>

                <table class="bsc-slot-metric-table">
                    <tbody>
                        <tr>
                            <td style="color: var(--bsc-text-muted, #64748b);">
                                <div style="font-weight:700; color:var(--bsc-text-main, #0f172a);"><?php esc_html_e( 'Admission Tickets', 'bcs-screen-manager' ); ?></div>
                                <span id="lblStdQuantity" style="font-size:0.75rem; color:var(--bsc-text-muted, #94a3b8);">(0 &times; <?php echo esc_html( $currency_symbol . number_format( $std_base_price, 0 ) ); ?>)</span>
                            </td>
                            <td style="text-align: right; font-weight: 800; font-family: var(--bsc-font-mono, monospace); vertical-align: top; padding-top: 8px;" id="lblStdSubtotal">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                        <tr>
                            <td style="color: var(--bsc-text-muted, #64748b); font-weight:700;">
                                <?php esc_html_e( 'Tickets Subtotal', 'bcs-screen-manager' ); ?>
                            </td>
                            <td style="text-align: right; font-weight: 800; font-family: var(--bsc-font-mono, monospace);" id="lblSubtotal">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                        <tr>
                            <td style="color: var(--bsc-text-muted, #64748b);">
                                <?php esc_html_e( 'Service / VAT', 'bcs-screen-manager' ); ?>
                                <?php if ( $enable_tax && $service_charge_pct > 0 ) : ?>
                                    <span style="font-size:0.75rem; color:var(--bsc-text-muted, #94a3b8);">(<?php echo esc_html( (string) $service_charge_pct ); ?>%)</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 800; font-family: var(--bsc-font-mono, monospace);" id="lblTax">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                        <tr class="total-row">
                            <td style="color: var(--bsc-text-main, #0f172a); font-weight:800; font-size:0.95rem;">
                                <?php 
                                /* translators: %s: Currency symbol */
                                printf( esc_html__( 'Total Settlement (%s)', 'bcs-screen-manager' ), esc_html( $currency_symbol ) ); 
                                ?>
                            </td>
                            <td style="text-align: right; color: #059669; font-family: var(--bsc-font-mono, monospace); font-weight:900; font-size:1.15rem;" id="lblGrandTotal">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:12px; font-weight:900; font-size: 0.95rem; border-radius:8px; box-shadow:0 4px 14px rgba(6,78,59,0.25); display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer;">
                    <span><?php echo $is_edit ? esc_html__( 'Update & Save Changes', 'bcs-screen-manager' ) : esc_html__( 'Confirm & Issue Pass', 'bcs-screen-manager' ); ?></span>
                </button>
            </div>
        </div>
    </div>
</form>

<?php
$version = defined( 'BSC_VERSION' ) ? BSC_VERSION : '1.0.0';
wp_register_script( 'bsc-booking-builder-runtime', false, array(), $version, true );
wp_enqueue_script( 'bsc-booking-builder-runtime' );

$booking_config = array(
    'stdPrice'          => (float) $std_base_price,
    'taxRate'           => ( $enable_tax && $service_charge_pct > 0 ) ? ( (float) $service_charge_pct / 100 ) : 0,
    'currencyTag'       => (string) $currency_symbol,
    'slotBookedMap'     => $slot_booked_seats_map,
    'initialSeats'      => $preselected_seats,
    'preselectedTime'   => (string) $current_time,
    'preselectedDate'   => (string) $current_date,
    'todayDate'         => current_time( 'Y-m-d' ),
    'i18n'              => array(
        'noSeats'       => __( 'No seats selected yet.', 'bcs-screen-manager' ),
        'seatsPicked'   => __( 'Seats Picked', 'bcs-screen-manager' ),
        'zeroPicked'    => __( '0 Seats Picked', 'bcs-screen-manager' ),
        'selectAlert'   => __( 'Please select at least one seat from the 30-seat chart.', 'bcs-screen-manager' ),
    ),
);

$js_script = '
    (function() {
        const config = ' . wp_json_encode( $booking_config ) . ';
        const adminSelectedSeats = new Map();
        let movieScheduleMap = {};

        function formatTimeSlot(timeStr) {
            if (!timeStr) return "06:00 PM";
            const clean = String(timeStr).trim();
            if (clean.toUpperCase().includes("AM") || clean.toUpperCase().includes("PM")) {
                return clean;
            }
            if (clean.indexOf(":") !== -1 && clean.length <= 5) {
                const parts = clean.split(":");
                let hour = parseInt(parts[0], 10);
                const minute = parts[1];
                const ampm = hour >= 12 ? "PM" : "AM";
                hour = hour % 12 || 12;
                return String(hour).padStart(2, "0") + ":" + minute + " " + ampm;
            }
            return clean;
        }

        function formatFriendlyDate(dateStr) {
            if (!dateStr) return "";
            const parts = dateStr.split("-");
            if (parts.length !== 3) return dateStr;
            const d = new Date(parts[0], parts[1] - 1, parts[2]);
            const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            const day = String(d.getDate()).padStart(2, "0");
            return day + " " + months[d.getMonth()] + " " + d.getFullYear();
        }

        function onMovieChange(selectEl) {
            const option = selectEl.options[selectEl.selectedIndex];
            if (!option) return;

            const showtimesRaw = option.getAttribute("data-showtimes") || "[]";
            const dateSelect = document.getElementById("select_schedule_date");
            dateSelect.innerHTML = "";
            movieScheduleMap = {};

            let schedules = [];
            try {
                const parsed = JSON.parse(showtimesRaw);
                if (Array.isArray(parsed)) schedules = parsed;
            } catch(e) {
                schedules = [];
            }

            if (schedules.length > 0 && schedules[0].date && schedules[0].times) {
                schedules.forEach(function(item) {
                    const d = item.date;
                    movieScheduleMap[d] = item.times || ["11:00 AM"];

                    const opt = document.createElement("option");
                    opt.value = d;
                    opt.textContent = formatFriendlyDate(d);
                    if (d === config.preselectedDate) {
                        opt.selected = true;
                    }
                    dateSelect.appendChild(opt);
                });
            } else {
                const dateVal = config.preselectedDate || config.todayDate;
                const flatTimes = (schedules.length > 0) ? schedules : ["11:00 AM", "02:00 PM", "05:00 PM"];
                movieScheduleMap[dateVal] = flatTimes;

                const opt = document.createElement("option");
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
            const timeSelect = document.getElementById("select_showtime");
            timeSelect.innerHTML = "";

            const times = movieScheduleMap[selectedDate] || ["11:00 AM"];
            times.forEach(function(t) {
                const cleanTime = String(t).trim();
                const opt = document.createElement("option");
                opt.value = cleanTime;
                opt.textContent = formatTimeSlot(cleanTime);
                if (cleanTime === config.preselectedTime) {
                    opt.selected = true;
                }
                timeSelect.appendChild(opt);
            });

            if (!timeSelect.value && timeSelect.options.length > 0) {
                timeSelect.selectedIndex = 0;
            }

            refreshSeatMatrixState();
        }

        function refreshSeatMatrixState() {
            const movieSelect = document.getElementById("select_movie");
            const dateSelect  = document.getElementById("select_schedule_date");
            const timeSelect  = document.getElementById("select_showtime");

            if (!movieSelect || !dateSelect || !timeSelect) return;

            const movieTitle = movieSelect.value.trim().toLowerCase();
            const dateVal    = dateSelect.value;
            const timeVal    = timeSelect.value.trim();
            const slotKey    = `${movieTitle}_${dateVal}_${timeVal}`;

            const occupiedList = config.slotBookedMap[slotKey] || [];
            const seatElements = document.querySelectorAll(".bsc-matrix-seat");

            seatElements.forEach(function(el) {
                const seatId = el.getAttribute("data-seat");

                if (occupiedList.indexOf(seatId) !== -1) {
                    el.className = "bsc-matrix-seat occupied";
                    el.title = "Seat " + seatId + " [OCCUPIED]";
                    el.style.pointerEvents = "none";
                    adminSelectedSeats.delete(seatId);
                } else {
                    el.style.pointerEvents = "auto";
                    if (adminSelectedSeats.has(seatId)) {
                        el.className = "bsc-matrix-seat selected";
                        el.title = "Seat " + seatId + " [SELECTED]";
                    } else {
                        el.className = "bsc-matrix-seat standard";
                        el.title = "Seat " + seatId + " [AVAILABLE]";
                    }
                }
            });

            calculateTotals();
        }

        function toggleAdminSeat(seatId, seatEl) {
            if (seatEl.classList.contains("occupied")) return;

            const price = config.stdPrice;
            const type  = "Standard";

            if (adminSelectedSeats.has(seatId)) {
                adminSelectedSeats.delete(seatId);
                seatEl.className = "bsc-matrix-seat standard";
                seatEl.title = "Seat " + seatId + " [AVAILABLE]";
            } else {
                adminSelectedSeats.set(seatId, { id: seatId, type: type, price: price });
                seatEl.className = "bsc-matrix-seat selected";
                seatEl.title = "Seat " + seatId + " [SELECTED]";
            }

            calculateTotals();
        }

        function calculateTotals() {
            const chipsDeck       = document.getElementById("selectedChipsDeck");
            const counter         = document.getElementById("selectedSeatsCounter");
            const hiddenSeats     = document.getElementById("input_manual_seats");
            const hiddenSeatsSync = document.getElementById("input_seats_sync");
            const hiddenAmount    = document.getElementById("input_manual_amount");
            const hiddenAmtSync   = document.getElementById("input_total_amount_sync");
            const hiddenTier      = document.getElementById("input_manual_ticket_type");

            let stdCount    = 0;
            let stdSubtotal = 0;
            const seatNames = [];

            adminSelectedSeats.forEach(function(s) {
                seatNames.push(s.id);
                stdCount++;
                stdSubtotal += parseFloat(s.price);
            });

            if (seatNames.length === 0) {
                chipsDeck.innerHTML = `<span style="font-size: 0.82rem; color: var(--bsc-text-muted, #64748b);">${config.i18n.noSeats}</span>`;
                counter.textContent = config.i18n.zeroPicked;
                hiddenSeats.value = "";
                if (hiddenSeatsSync) hiddenSeatsSync.value = "";
                hiddenTier.value = "Standard";
            } else {
                chipsDeck.innerHTML = "";
                seatNames.forEach(function(id) {
                    const chip = document.createElement("span");
                    chip.className = "bsc-roster-badge";
                    chip.textContent = id;
                    chipsDeck.appendChild(chip);
                });
                counter.textContent = `${seatNames.length} ${config.i18n.seatsPicked}`;
                const joined = seatNames.join(", ");
                hiddenSeats.value = joined;
                if (hiddenSeatsSync) hiddenSeatsSync.value = joined;
                hiddenTier.value = "Standard";
            }

            const subtotal = Math.round((stdSubtotal + Number.EPSILON) * 100) / 100;
            const tax      = Math.round(((subtotal * config.taxRate) + Number.EPSILON) * 100) / 100;
            const grand    = Math.round(((subtotal + tax) + Number.EPSILON) * 100) / 100;

            document.getElementById("lblStdQuantity").textContent = `(${stdCount} × ${config.currencyTag}${config.stdPrice.toFixed(0)})`;
            document.getElementById("lblStdSubtotal").textContent = `${config.currencyTag} ${stdSubtotal.toFixed(2)}`;

            document.getElementById("lblSubtotal").textContent   = `${config.currencyTag} ${subtotal.toFixed(2)}`;
            document.getElementById("lblTax").textContent        = `${config.currencyTag} ${tax.toFixed(2)}`;
            document.getElementById("lblGrandTotal").textContent = `${config.currencyTag} ${grand.toFixed(2)}`;

            hiddenAmount.value = grand.toFixed(2);
            if (hiddenAmtSync) hiddenAmtSync.value = grand.toFixed(2);
        }

        document.addEventListener("DOMContentLoaded", function() {
            if (Array.isArray(config.initialSeats) && config.initialSeats.length > 0) {
                config.initialSeats.forEach(function(st) {
                    const cleanId = String(st).trim().toUpperCase();
                    adminSelectedSeats.set(cleanId, {
                        id: cleanId,
                        type: "Standard",
                        price: config.stdPrice
                    });
                });
            }

            const movieSelect = document.getElementById("select_movie");
            const dateSelect  = document.getElementById("select_schedule_date");
            const timeSelect  = document.getElementById("select_showtime");
            const matrixBoard = document.getElementById("bscSeatMatrixBoard");
            const bookingForm = document.getElementById("bscBookingForm");

            if (movieSelect) {
                movieSelect.addEventListener("change", function() {
                    onMovieChange(this);
                });
                onMovieChange(movieSelect);
            }

            if (dateSelect) {
                dateSelect.addEventListener("change", function() {
                    onScheduleDateChange(this);
                });
            }

            if (timeSelect) {
                timeSelect.addEventListener("change", function() {
                    refreshSeatMatrixState();
                });
            }

            if (matrixBoard) {
                matrixBoard.addEventListener("click", function(e) {
                    const seatEl = e.target.closest(".bsc-matrix-seat");
                    if (!seatEl) return;
                    const seatId = seatEl.getAttribute("data-seat");
                    toggleAdminSeat(seatId, seatEl);
                });
            }

            if (bookingForm) {
                bookingForm.addEventListener("submit", function(e) {
                    if (adminSelectedSeats.size === 0) {
                        e.preventDefault();
                        alert(config.i18n.selectAlert);
                    }
                });
            }
        });
    })();
';

wp_add_inline_script( 'bsc-booking-builder-runtime', $js_script );