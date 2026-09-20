<?php
/**
 * Booking Module - All Bookings View
 *
 * Path: admin/booking/all-booking.php
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

$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );
$filter_movies   = $wpdb->get_col( "SELECT DISTINCT movie_title FROM {$table_bookings} WHERE movie_title != '' ORDER BY movie_title ASC" );
$filter_times    = $wpdb->get_col( "SELECT DISTINCT showtime FROM {$table_bookings} WHERE showtime != '' ORDER BY showtime ASC" );

if ( ! isset( $bookings ) || ! is_array( $bookings ) ) {
    $bookings = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC LIMIT 250" );
}
if ( ! is_array( $bookings ) ) {
    $bookings = array();
}

$today_ymd     = current_time( 'Y-m-d' );
$booking_count = count( $bookings );

// Reusable 12-Hour AM/PM Formatter
if ( ! function_exists( 'bsc_format_display_time' ) ) {
    function bsc_format_display_time( $raw_time ) {
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
?>

<div class="bsc-panel" style="margin: 0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
    <div class="bsc-panel-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--bsc-border, #e2e8f0);">
        <div>
            <h3 class="bsc-panel-title" style="margin: 0 0 4px 0; font-size: 1.15rem; font-weight: 800; color: var(--bsc-text-main, #0f172a);">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align: -3px; margin-right: 4px;" aria-hidden="true"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
                <?php esc_html_e( 'All Customer Bookings & Passes', 'bcs-screen-manager' ); ?> (<span id="filteredCount"><?php echo esc_html( (string) $booking_count ); ?></span>)
            </h3>
            <span style="font-size: 0.78rem; color: var(--bsc-text-muted, #64748b);"><?php esc_html_e( 'Showing active admissions and turnstile passes.', 'bcs-screen-manager' ); ?></span>
        </div>
    </div>

    <!-- Live Filter Toolbar -->
    <div class="bsc-table-filter-toolbar">
        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--bsc-text-muted, #64748b);"><?php esc_html_e( 'Filter Movie:', 'bcs-screen-manager' ); ?></span>
            <select id="filterMovie" aria-label="<?php esc_attr_e( 'Filter by Movie', 'bcs-screen-manager' ); ?>">
                <option value="">-- <?php esc_html_e( 'All Movies', 'bcs-screen-manager' ); ?> --</option>
                <?php foreach ( $filter_movies as $fm ) : ?>
                    <option value="<?php echo esc_attr( $fm ); ?>"><?php echo esc_html( $fm ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--bsc-text-muted, #64748b);"><?php esc_html_e( 'Showtime:', 'bcs-screen-manager' ); ?></span>
            <select id="filterTime" aria-label="<?php esc_attr_e( 'Filter by Showtime', 'bcs-screen-manager' ); ?>">
                <option value="">-- <?php esc_html_e( 'All Times', 'bcs-screen-manager' ); ?> --</option>
                <?php foreach ( $filter_times as $ft ) : ?>
                    <option value="<?php echo esc_attr( $ft ); ?>"><?php echo esc_html( bsc_format_display_time( $ft ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--bsc-text-muted, #64748b);"><?php esc_html_e( 'Date:', 'bcs-screen-manager' ); ?></span>
            <input type="date" id="filterDate" value="<?php echo esc_attr( $today_ymd ); ?>" aria-label="<?php esc_attr_e( 'Filter by Date', 'bcs-screen-manager' ); ?>">
        </div>

        <div style="margin-left: auto; display: flex; gap: 8px;">
            <button type="button" class="button button-small" id="btnShowAllBookings" style="font-weight: 700;"><?php esc_html_e( 'Show All', 'bcs-screen-manager' ); ?></button>
            <button type="button" class="button button-small" id="btnTodayOnlyBookings" style="font-weight: 700;"><?php esc_html_e( 'Today Only', 'bcs-screen-manager' ); ?></button>
        </div>
    </div>

    <div class="bsc-table-wrap" style="overflow-x: auto;">
        <table class="bsc-data-table" id="bscBookingsTable" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--bsc-border, #e2e8f0); background: #f8fafc;">
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'TOKEN', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'CUSTOMER', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'MOVIE', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'SHOWTIME', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'SEATS', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'PAYMENT', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'DATE', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'TOTAL', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: right;"><?php esc_html_e( 'ACTIONS', 'bcs-screen-manager' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $bookings ) ) : foreach ( $bookings as $b ) : 
                    $booking_id   = absint( $b->id );
                    $view_url     = admin_url( 'admin.php?page=bsc-booking&subtab=view&id=' . $booking_id );
                    $edit_url     = admin_url( 'admin.php?page=bsc-booking&subtab=edit&id=' . $booking_id );
                    // Using default _wpnonce parameter expected by check_admin_referer()
                    $delete_url   = wp_nonce_url(
                        admin_url( 'admin.php?page=bsc-booking&subtab=delete&id=' . $booking_id ),
                        'bsc_delete_booking_' . $booking_id
                    );

                    $row_date     = ! empty( $b->booking_date ) ? wp_date( 'Y-m-d', strtotime( $b->booking_date ) ) : '';
                    $human_date   = ! empty( $b->booking_date ) ? wp_date( 'd M Y', strtotime( $b->booking_date ) ) : '--';
                    $human_time   = ! empty( $b->booking_date ) ? wp_date( 'h:i A', strtotime( $b->booking_date ) ) : '';
                    $formatted_st = bsc_format_display_time( $b->showtime ?? '' );
                ?>
                    <tr class="bsc-booking-row" data-movie="<?php echo esc_attr( (string) $b->movie_title ); ?>" data-time="<?php echo esc_attr( (string) $b->showtime ); ?>" data-date="<?php echo esc_attr( $row_date ); ?>" style="border-bottom: 1px solid var(--bsc-border-light, #f1f5f9);">
                        <td style="padding: 12px;">
                            <span class="bsc-voucher-tag"><?php echo esc_html( $b->booking_token ?? '' ); ?></span>
                        </td>
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html( ! empty( $b->customer_name ) ? $b->customer_name : __( 'Walk-In Guest', 'bcs-screen-manager' ) ); ?></strong>
                            <?php if ( ! empty( $b->customer_phone ) ) : ?>
                                <br><span style="font-family: var(--bsc-font-mono, monospace); font-size: 0.78rem; color: #64748b;"><?php echo esc_html( $b->customer_phone ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html( $b->movie_title ?? '' ); ?></strong>
                        </td>
                        <td style="padding: 12px;">
                            <span class="bsc-badge-pill active" style="font-size: 0.74rem; font-family: var(--bsc-font-mono, monospace);"><?php echo esc_html( $formatted_st ); ?></span>
                        </td>
                        <td style="padding: 12px;">
                            <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 140px;">
                                <?php 
                                $seat_chips = array_filter( array_map( 'trim', explode( ',', (string) ( $b->seats ?? '' ) ) ) );
                                foreach ( $seat_chips as $sc ) : ?>
                                    <span style="display: inline-block; background: #0f172a; color: #38bdf8; font-family: var(--bsc-font-mono, monospace); font-size: 0.7rem; font-weight: 800; padding: 2px 5px; border-radius: 4px;">
                                        <?php echo esc_html( $sc ); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td style="padding: 12px;">
                            <span class="bsc-badge-pill" style="font-size: 0.72rem;"><?php echo esc_html( ! empty( $b->payment_method ) ? $b->payment_method : 'Cash' ); ?></span>
                        </td>
                        <td style="padding: 12px;">
                            <span style="font-size: 0.85rem; font-family: var(--bsc-font-mono, monospace); color: #334155; font-weight: 700;"><?php echo esc_html( $human_date ); ?></span>
                            <?php if ( ! empty( $human_time ) ) : ?>
                                <br><small style="color: var(--bsc-text-muted, #64748b);"><?php echo esc_html( $human_time ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px;">
                            <strong style="color: var(--bsc-accent-teal, #0d9488); font-family: var(--bsc-font-mono, monospace);">
                                <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $b->total_amount ?? 0.00 ), 2 ) ); ?>
                            </strong>
                        </td>
                        <td style="padding: 12px; text-align: right; white-space: nowrap;">
                            <div class="bsc-table-actions">
                                <a href="<?php echo esc_url( $view_url ); ?>" class="bsc-action-btn view" title="<?php esc_attr_e( 'View', 'bcs-screen-manager' ); ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    <span><?php esc_html_e( 'View', 'bcs-screen-manager' ); ?></span>
                                </a>
                                <a href="<?php echo esc_url( $edit_url ); ?>" class="bsc-action-btn edit" title="<?php esc_attr_e( 'Edit', 'bcs-screen-manager' ); ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    <span><?php esc_html_e( 'Edit', 'bcs-screen-manager' ); ?></span>
                                </a>
                                <a href="<?php echo esc_url( $delete_url ); ?>" class="bsc-action-btn delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this booking record?', 'bcs-screen-manager' ) ); ?>');" title="<?php esc_attr_e( 'Delete', 'bcs-screen-manager' ); ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    <span><?php esc_html_e( 'Delete', 'bcs-screen-manager' ); ?></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr id="noBookingsRow">
                        <td colspan="9" style="text-align: center; padding: 2.5rem; color: var(--bsc-text-muted, #64748b);">
                            <?php esc_html_e( 'No bookings recorded yet.', 'bcs-screen-manager' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr id="bscFilterEmptyRow" style="display: none;">
                    <td colspan="9" style="text-align: center; padding: 2.5rem; color: var(--bsc-text-muted, #64748b);">
                        <?php esc_html_e( 'No bookings match your current filter selections.', 'bcs-screen-manager' ); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
$version = defined( 'BSC_VERSION' ) ? BSC_VERSION : '1.0.0';
wp_register_script( 'bsc-booking-catalog-runtime', false, array(), $version, true );
wp_enqueue_script( 'bsc-booking-catalog-runtime' );

$js_script = '
    (function() {
        document.addEventListener("DOMContentLoaded", function() {
            const filterMovie = document.getElementById("filterMovie");
            const filterTime  = document.getElementById("filterTime");
            const filterDate  = document.getElementById("filterDate");
            const counterEl   = document.getElementById("filteredCount");
            const btnShowAll  = document.getElementById("btnShowAllBookings");
            const btnToday    = document.getElementById("btnTodayOnlyBookings");
            const emptyFilter = document.getElementById("bscFilterEmptyRow");
            const todayStr    = ' . wp_json_encode( $today_ymd ) . ';

            function applyBookingFilters() {
                const selMovie = filterMovie ? filterMovie.value.toLowerCase().trim() : "";
                const selTime  = filterTime ? filterTime.value.toLowerCase().trim() : "";
                const selDate  = filterDate ? filterDate.value.trim() : "";
                const rows     = document.querySelectorAll("#bscBookingsTable tbody tr.bsc-booking-row");
                let visibleCount = 0;

                rows.forEach(function(row) {
                    const rMovie = (row.getAttribute("data-movie") || "").toLowerCase().trim();
                    const rTime  = (row.getAttribute("data-time") || "").toLowerCase().trim();
                    const rDate  = (row.getAttribute("data-date") || "").trim();

                    const matchMovie = !selMovie || rMovie.indexOf(selMovie) !== -1;
                    const matchTime  = !selTime || rTime === selTime;
                    const matchDate  = !selDate || rDate === selDate;

                    if (matchMovie && matchTime && matchDate) {
                        row.style.display = "";
                        visibleCount++;
                    } else {
                        row.style.display = "none";
                    }
                });

                if (emptyFilter) {
                    emptyFilter.style.display = (visibleCount === 0 && rows.length > 0) ? "" : "none";
                }

                if (counterEl) {
                    counterEl.textContent = visibleCount;
                }
            }

            if (filterMovie) filterMovie.addEventListener("change", applyBookingFilters);
            if (filterTime)  filterTime.addEventListener("change", applyBookingFilters);
            if (filterDate)  filterDate.addEventListener("change", applyBookingFilters);

            if (btnShowAll) {
                btnShowAll.addEventListener("click", function() {
                    if (filterMovie) filterMovie.value = "";
                    if (filterTime)  filterTime.value = "";
                    if (filterDate)  filterDate.value = "";
                    applyBookingFilters();
                });
            }

            if (btnToday) {
                btnToday.addEventListener("click", function() {
                    if (filterMovie) filterMovie.value = "";
                    if (filterTime)  filterTime.value = "";
                    if (filterDate)  filterDate.value = todayStr;
                    applyBookingFilters();
                });
            }

            // Run initial filter on page load (defaults to today)
            applyBookingFilters();
        });
    })();
';

wp_add_inline_script( 'bsc-booking-catalog-runtime', $js_script );