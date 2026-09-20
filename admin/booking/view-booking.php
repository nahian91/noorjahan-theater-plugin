<?php
/**
 * Booking Module - Single Booking Digital Pass & Thermal Voucher View
 *
 * Path: admin/booking/view-booking.php
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
$booking_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$view_booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $booking_id ) );

if ( ! $view_booking ) { 
    echo '<div class="notice notice-error" style="padding:15px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; color:#ef4444; font-weight:bold;"><p>' . esc_html__( 'Booking record not found.', 'bcs-screen-manager' ) . '</p></div>'; 
    return; 
}

// Format friendly screening and transaction timestamps
$booking_ts            = ! empty( $view_booking->booking_date ) ? strtotime( $view_booking->booking_date ) : time();
$friendly_booking_date = wp_date( 'd M Y - h:i A', $booking_ts );
$currency_symbol       = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

// Format Showtime cleanly to 12-Hour AM/PM format (e.g. 17:00 -> 05:00 PM)
if ( ! function_exists( 'bsc_format_view_booking_time' ) ) {
    function bsc_format_view_booking_time( $raw_time ) {
        $clean = trim( (string) $raw_time );
        if ( empty( $clean ) ) {
            return __( 'Counter', 'bcs-screen-manager' );
        }
        if ( false !== stripos( $clean, 'AM' ) || false !== stripos( $clean, 'PM' ) ) {
            return $clean;
        }
        if ( false !== strpos( $clean, ':' ) ) {
            $parts = explode( ':', $clean );
            $hour  = (int) $parts[0];
            $min   = isset( $parts[1] ) ? substr( $parts[1], 0, 2 ) : '00';
            $ampm  = $hour >= 12 ? 'PM' : 'AM';
            $h12   = ( $hour % 12 === 0 ) ? 12 : ( $hour % 12 );
            return sprintf( '%02d:%s %s', $h12, $min, $ampm );
        }
        return $clean;
    }
}

$formatted_showtime = bsc_format_view_booking_time( $view_booking->showtime ?? '' );

// Parse and clean seats
$seats_list = array_filter( array_map( 'trim', explode( ',', (string) ( $view_booking->seats ?? '' ) ) ) );
?>

<div class="bsc-staff-view-grid" id="bscPrintArea">
    
    <!-- LEFT COLUMN: Master Digital Pass Container -->
    <div class="bsc-movie-content-main">
        
        <!-- Banner Header -->
        <div class="bsc-movie-banner-head">
            <div>
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                    <span class="bsc-badge-pill active" style="font-size:0.7rem; font-weight:800; text-transform:uppercase;">
                        ● <?php echo esc_html( ! empty( $view_booking->payment_status ) ? $view_booking->payment_status : __( 'Confirmed', 'bcs-screen-manager' ) ); ?>
                    </span>
                    <span style="font-size:0.8rem; color:#38bdf8; font-family:var(--bsc-font-mono, monospace); font-weight:800;">
                        <?php echo esc_html( $view_booking->booking_token ?? '' ); ?>
                    </span>
                </div>
                <h2 style="font-size:1.6rem; font-weight:900; color:#fff; margin:0; letter-spacing:-0.02em;">
                    <?php echo esc_html( $view_booking->movie_title ?? '' ); ?>
                </h2>
                <div style="font-size:0.82rem; color:#cbd5e1; margin-top:4px; font-weight:600;">
                    <?php echo esc_html( ! empty( $view_booking->ticket_type ) ? $view_booking->ticket_type : __( 'Standard', 'bcs-screen-manager' ) ); ?> <?php esc_html_e( 'Pass Admission', 'bcs-screen-manager' ); ?>
                </div>
            </div>

            <div style="text-align: right;">
                <div style="font-size:0.7rem; color:#94a3b8; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;"><?php esc_html_e( 'Settlement', 'bcs-screen-manager' ); ?></div>
                <div style="font-size:1.5rem; font-weight:900; font-family:var(--bsc-font-mono, monospace); color:#34d399; margin-top:2px;">
                    <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $view_booking->total_amount ?? 0.00 ), 2 ) ); ?>
                </div>
            </div>
        </div>

        <!-- Body Details -->
        <div class="bsc-movie-body-wrap">
            <table class="bsc-staff-spec-table">
                <tbody>
                    <!-- Customer Profile -->
                    <tr>
                        <th>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                <span><?php esc_html_e( 'Customer Profile', 'bcs-screen-manager' ); ?></span>
                            </div>
                        </th>
                        <td>
                            <div style="font-weight:800; font-size:1rem; color:var(--bsc-text-main, #0f172a);">
                                <?php echo esc_html( ! empty( $view_booking->customer_name ) ? $view_booking->customer_name : __( 'Walk-In Guest', 'bcs-screen-manager' ) ); ?>
                            </div>
                            <div style="font-size:0.82rem; color:var(--bsc-text-muted, #64748b); margin-top:4px;">
                                <span>📞 <?php echo esc_html( ! empty( $view_booking->customer_phone ) ? $view_booking->customer_phone : __( 'No phone provided', 'bcs-screen-manager' ) ); ?></span>
                                <?php if ( ! empty( $view_booking->customer_email ) ) : ?>
                                    <span style="margin-left:12px;">✉️ <?php echo esc_html( $view_booking->customer_email ); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Screening Slot & Showtime -->
                    <tr>
                        <th>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                                <span><?php esc_html_e( 'Screening Slot', 'bcs-screen-manager' ); ?></span>
                            </div>
                        </th>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="bsc-time-pill-view">
                                    ⏰ <?php echo esc_html( $formatted_showtime ); ?>
                                </span>
                                <span style="font-size:0.8rem; font-weight:700; color:var(--bsc-text-muted, #64748b);">
                                    (<?php echo esc_html( ! empty( $view_booking->auditorium ) ? $view_booking->auditorium : 'Auditorium 1' ); ?>)
                                </span>
                            </div>
                            <div style="font-size:0.82rem; color:var(--bsc-text-muted, #64748b); margin-top:5px;">
                                <?php esc_html_e( 'Registered On:', 'bcs-screen-manager' ); ?> <strong><?php echo esc_html( $friendly_booking_date ); ?></strong>
                            </div>
                        </td>
                    </tr>

                    <!-- Reserved Seats -->
                    <tr>
                        <th>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M4 18v3h3v-3h10v3h3v-3h1a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H3a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h1zM5 8h14v6H5V8z"/></svg>
                                <span><?php esc_html_e( 'Reserved Seats', 'bcs-screen-manager' ); ?></span>
                            </div>
                        </th>
                        <td>
                            <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                                <?php if ( ! empty( $seats_list ) ) : ?>
                                    <?php foreach ( $seats_list as $seat_item ) : 
                                        $is_vip = ( strpos( strtoupper( $seat_item ), 'A' ) === 0 );
                                    ?>
                                        <span class="bsc-roster-badge" style="<?php echo $is_vip ? 'background:#fef3c7; color:#b45309; border-color:#fde68a;' : 'background:#0f172a; color:#38bdf8; border-color:#1e293b;'; ?>">
                                            <?php echo esc_html( $seat_item . ( $is_vip ? ' [VIP]' : '' ) ); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <span style="font-size:0.75rem; color:var(--bsc-text-muted, #64748b); margin-left:6px; font-weight:700;">
                                        (<?php echo esc_html( (string) count( $seats_list ) ); ?> <?php esc_html_e( 'Seats Total', 'bcs-screen-manager' ); ?>)
                                    </span>
                                <?php else : ?>
                                    <span style="color:#94a3b8; font-size:0.85rem;"><?php esc_html_e( 'No seats assigned', 'bcs-screen-manager' ); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Payment Details -->
                    <tr>
                        <th>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                                <span><?php esc_html_e( 'Payment Gateway', 'bcs-screen-manager' ); ?></span>
                            </div>
                        </th>
                        <td>
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-weight:800; font-size:0.92rem; color:var(--bsc-text-main, #0f172a);">
                                        <?php echo esc_html( ! empty( $view_booking->payment_method ) ? $view_booking->payment_method : __( 'Cash Desk', 'bcs-screen-manager' ) ); ?>
                                    </span>
                                    <span class="bsc-badge-pill" style="font-size:0.75rem;">
                                        <?php echo esc_html( ! empty( $view_booking->payment_status ) ? $view_booking->payment_status : __( 'Confirmed', 'bcs-screen-manager' ) ); ?>
                                    </span>
                                </div>
                                <div style="font-size:1.15rem; font-weight:900; font-family:var(--bsc-font-mono, monospace); color:#059669;">
                                    <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $view_booking->total_amount ?? 0.00 ), 2 ) ); ?>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Reference Token -->
                    <tr>
                        <th>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/></svg>
                                <span><?php esc_html_e( 'Voucher Token', 'bcs-screen-manager' ); ?></span>
                            </div>
                        </th>
                        <td>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span class="bsc-voucher-tag" style="font-size:0.9rem; padding:4px 10px;">
                                    <?php echo esc_html( $view_booking->booking_token ?? '' ); ?>
                                </span>
                                <span style="font-size:0.78rem; color:#059669; font-weight:800;">
                                    ✓ <?php esc_html_e( 'Turnstile Authorized', 'bcs-screen-manager' ); ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Action Footer -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid var(--bsc-border-light, #f1f5f9);">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=all' ) ); ?>" class="button button-secondary" style="font-weight:700; border-radius:6px;">
                    &larr; <?php esc_html_e( 'Back to All Bookings', 'bcs-screen-manager' ); ?>
                </a>
                
                <div style="display:flex; gap:10px;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=edit&id=' . absint( $view_booking->id ) ) ); ?>" class="button button-primary" style="background:#0284c7; border-color:#0284c7; font-weight:800; border-radius:6px;">
                        ✏️ <?php esc_html_e( 'Edit Record', 'bcs-screen-manager' ); ?>
                    </a>
                    <button type="button" class="button button-secondary" id="btnPrintBookingPass" style="font-weight:800; border-radius:6px;">
                        🖨️ <?php esc_html_e( 'Print Pass', 'bcs-screen-manager' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Thermal Voucher Preview -->
    <div>
        <div class="bsc-poster-sidebar-card" id="bscThermalVoucher" style="background:#ffffff; border:1px dashed #cbd5e1; box-shadow:0 8px 24px rgba(15,23,42,0.06); padding:1.5rem;">
            <div style="text-align:center; border-bottom:1px dashed #cbd5e1; padding-bottom:1rem; margin-bottom:1rem;">
                <div style="font-weight:900; font-size:1.15rem; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;">
                    <?php echo esc_html( get_option( 'bsc_theater_name', 'Big Screen Cinemas' ) ); ?>
                </div>
                <span style="font-size:0.7rem; color:var(--bsc-text-muted, #64748b); font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">
                    <?php esc_html_e( 'Admission Voucher', 'bcs-screen-manager' ); ?>
                </span>
            </div>

            <table class="bsc-slot-metric-table" style="margin-bottom:1rem;">
                <tbody>
                    <tr>
                        <td style="color:#64748b;"><?php esc_html_e( 'Token', 'bcs-screen-manager' ); ?></td>
                        <td style="text-align:right; font-family:var(--bsc-font-mono, monospace); font-weight:800; color:#0f172a;"><?php echo esc_html( $view_booking->booking_token ?? '' ); ?></td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;"><?php esc_html_e( 'Movie', 'bcs-screen-manager' ); ?></td>
                        <td style="text-align:right; font-weight:800; color:#0f172a;"><?php echo esc_html( $view_booking->movie_title ?? '' ); ?></td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;"><?php esc_html_e( 'Showtime', 'bcs-screen-manager' ); ?></td>
                        <td style="text-align:right; font-weight:800; color:#0284c7; font-family:var(--bsc-font-mono, monospace);"><?php echo esc_html( $formatted_showtime ); ?></td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;"><?php esc_html_e( 'Date', 'bcs-screen-manager' ); ?></td>
                        <td style="text-align:right; font-weight:700; color:#0f172a;"><?php echo esc_html( wp_date( 'd M Y', $booking_ts ) ); ?></td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;"><?php esc_html_e( 'Seats', 'bcs-screen-manager' ); ?></td>
                        <td style="text-align:right; font-family:var(--bsc-font-mono, monospace); font-weight:800; color:#059669;"><?php echo esc_html( $view_booking->seats ?? '' ); ?></td>
                    </tr>
                    <tr>
                        <td style="color:#64748b;"><?php esc_html_e( 'Method', 'bcs-screen-manager' ); ?></td>
                        <td style="text-align:right; font-weight:700;"><?php echo esc_html( ! empty( $view_booking->payment_method ) ? $view_booking->payment_method : __( 'Cash', 'bcs-screen-manager' ) ); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td style="color:#0f172a; font-weight:900;"><?php esc_html_e( 'Total Paid', 'bcs-screen-manager' ); ?></td>
                        <td style="text-align:right; color:#059669; font-family:var(--bsc-font-mono, monospace); font-weight:900;">
                            <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $view_booking->total_amount ?? 0.00 ), 2 ) ); ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Barcode Graphic -->
            <div style="height:36px; background:repeating-linear-gradient(90deg, #0f172a 0px, #0f172a 2px, transparent 2px, transparent 4px, #0f172a 4px, #0f172a 7px, transparent 7px, transparent 9px); margin:1.25rem 0 0.5rem 0; border-radius:3px;"></div>
            
            <div style="text-align:center; font-size:0.75rem; color:#64748b; font-family:var(--bsc-font-mono, monospace); font-weight:800;">
                * <?php echo esc_html( $view_booking->booking_token ?? '' ); ?> *
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #adminmenumain, #wpadminbar, .bsc-subtabs-nav-wrapper, .bsc-action-footer-bar, .notice {
        display: none !important;
    }
    #bscPrintArea, #bscPrintArea * {
        visibility: visible;
    }
    #bscPrintArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        display: block !important;
    }
    #bscThermalVoucher {
        border: 1px solid #000 !important;
        box-shadow: none !important;
        max-width: 380px;
        margin: 0 auto;
    }
}
</style>

<?php
$version = defined( 'BSC_VERSION' ) ? BSC_VERSION : '1.0.0';
wp_register_script( 'bsc-view-booking-runtime', false, array(), $version, true );
wp_enqueue_script( 'bsc-view-booking-runtime' );

$js_script = '
    (function() {
        document.addEventListener("DOMContentLoaded", function() {
            const btnPrint = document.getElementById("btnPrintBookingPass");
            if (btnPrint) {
                btnPrint.addEventListener("click", function() {
                    window.print();
                });
            }
        });
    })();
';

wp_add_inline_script( 'bsc-view-booking-runtime', $js_script );