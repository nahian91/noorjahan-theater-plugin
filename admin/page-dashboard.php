<?php
/**
 * Dashboard Module - Master Controller
 *
 * Path: admin/page-dashboard.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders the Cinema Master Dashboard
 */
function bsc_render_dashboard_page() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized user action.', 'bcs-screen-manager' ), 403 );
    }

    global $wpdb;
    $table_bookings = $wpdb->prefix . 'bsc_bookings';
    $table_movies   = $wpdb->prefix . 'bsc_control_movies';
    $table_ledger   = $wpdb->prefix . 'bsc_ledger';

    $today_start = current_time( 'Y-m-d 00:00:00' );
    $today_end   = current_time( 'Y-m-d 23:59:59' );

    $std_base_price  = (float) get_option( 'bsc_standard_price', 400 );
    $currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

    // =========================================================================
    // 1. CONSOLIDATED LEDGER METRICS (Single Query)
    // =========================================================================
    $cash_like   = '%' . $wpdb->esc_like( 'Cash' ) . '%';
    $bkash_like  = '%' . $wpdb->esc_like( 'bKash' ) . '%';
    $online_like = '%' . $wpdb->esc_like( 'Online' ) . '%';
    $portal_like = '%' . $wpdb->esc_like( 'Portal' ) . '%';

    $ledger_stats = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT 
                COALESCE(SUM(amount), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN (method = 'Cash' OR method LIKE %s) THEN amount ELSE 0 END), 0) AS cash_revenue,
                COALESCE(SUM(CASE WHEN (method = 'bKash' OR method LIKE %s OR method LIKE %s OR method LIKE %s) THEN amount ELSE 0 END), 0) AS bkash_revenue
            FROM {$table_ledger}
            WHERE txn_type = 'Income' 
              AND transaction_date >= %s 
              AND transaction_date <= %s",
            $cash_like,
            $bkash_like,
            $online_like,
            $portal_like,
            $today_start,
            $today_end
        )
    );

    $today_revenue       = (float) ( $ledger_stats->total_revenue ?? 0.00 );
    $today_cash_revenue  = (float) ( $ledger_stats->cash_revenue ?? 0.00 );
    $today_bkash_revenue = (float) ( $ledger_stats->bkash_revenue ?? 0.00 );

    // =========================================================================
    // 2. UNIFIED TODAY'S BOOKINGS (Single Query for Table, Metrics & JS Matrix)
    // =========================================================================
    $today_bookings = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, booking_token, customer_name, customer_phone, movie_title, auditorium, showtime, seats, payment_method, payment_status, total_amount, booking_date 
             FROM {$table_bookings} 
             WHERE booking_date >= %s AND booking_date <= %s 
             ORDER BY id DESC",
            $today_start,
            $today_end
        )
    );

    if ( ! is_array( $today_bookings ) ) {
        $today_bookings = array();
    }

    $today_bookings_count  = count( $today_bookings );
    $today_tickets_sold    = 0;
    $today_bookings_for_js = array();

    foreach ( $today_bookings as $b ) {
        if ( ! empty( $b->seats ) ) {
            $seat_parts         = explode( ',', $b->seats );
            $clean_seats        = array_filter( array_map( 'trim', $seat_parts ) );
            $today_tickets_sold += count( $clean_seats );
        }

        $today_bookings_for_js[] = array(
            'movie_title'    => $b->movie_title,
            'showtime'       => $b->showtime,
            'seats'          => $b->seats,
            'customer_name'  => $b->customer_name,
            'customer_phone' => $b->customer_phone,
            'booking_token'  => $b->booking_token,
            'payment_method' => $b->payment_method,
            'payment_status' => $b->payment_status,
            'total_amount'   => (float) $b->total_amount,
            'booking_date'   => $b->booking_date,
        );
    }

    // =========================================================================
    // 3. MOVIE CATALOG & SHOWTIMES
    // =========================================================================
    $movies_raw  = $wpdb->get_results( "SELECT id, title, showtimes, badge_tag FROM {$table_movies} ORDER BY id DESC" );
    $movies_list = array();

    if ( ! empty( $movies_raw ) && is_array( $movies_raw ) ) {
        foreach ( $movies_raw as $m ) {
            $parsed_showtimes = array();
            $decoded          = json_decode( $m->showtimes, true );

            if ( is_array( $decoded ) ) {
                foreach ( $decoded as $day_entry ) {
                    if ( is_array( $day_entry ) && ! empty( $day_entry['times'] ) && is_array( $day_entry['times'] ) ) {
                        foreach ( $day_entry['times'] as $t ) {
                            $parsed_showtimes[] = sanitize_text_field( $t );
                        }
                    } elseif ( is_string( $day_entry ) ) {
                        $parsed_showtimes[] = sanitize_text_field( $day_entry );
                    }
                }
            }

            if ( empty( $parsed_showtimes ) ) {
                $parsed_showtimes = array( '18:00' );
            }

            $m->showtimes_json = wp_json_encode( array_values( array_unique( $parsed_showtimes ) ) );
            $movies_list[]     = $m;
        }
    }

    $current_user = wp_get_current_user();
    $user_name    = $current_user->exists() ? $current_user->display_name : __( 'Master Administrator', 'bcs-screen-manager' );
    $avatar_url   = get_avatar_url( $current_user->ID, array( 'size' => 140 ) );

    $hour = (int) current_time( 'G' );
    if ( $hour < 12 ) {
        $greeting = __( 'Good Morning', 'bcs-screen-manager' );
    } elseif ( $hour < 17 ) {
        $greeting = __( 'Good Afternoon', 'bcs-screen-manager' );
    } else {
        $greeting = __( 'Good Evening', 'bcs-screen-manager' );
    }

    if ( function_exists( 'bsc_render_page_shell_open' ) ) {
        bsc_render_page_shell_open( 'bsc-dashboard' );
    }
    ?>

    <header class="bsc-hero-glass-card">
        <div class="bsc-hero-profile-unit">
            <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php esc_attr_e( 'Admin Avatar', 'bcs-screen-manager' ); ?>" class="bsc-hero-avatar-ring">
            <div class="bsc-hero-headings">
                <span class="bsc-badge-pill active" style="font-size:0.7rem; letter-spacing:0.06em;">
                    ⚡ <?php esc_html_e( 'LIVE MULTIPLEX CONTROLLER', 'bcs-screen-manager' ); ?>
                </span>
                <h1><?php echo esc_html( sprintf( '%s, %s', $greeting, $user_name ) ); ?></h1>
                <div class="bsc-hero-tags-row">
                    <span><?php esc_html_e( "Today's Passes:", 'bcs-screen-manager' ); ?> <strong><?php echo esc_html( (string) $today_bookings_count ); ?></strong></span>
                    <span>•</span>
                    <span><?php esc_html_e( 'Tickets Issued:', 'bcs-screen-manager' ); ?> <strong><?php echo esc_html( (string) $today_tickets_sold ); ?></strong></span>
                    <span>•</span>
                    <span style="color:#34d399; font-weight:800;">● <?php esc_html_e( 'BOX OFFICE ONLINE', 'bcs-screen-manager' ); ?></span>
                </div>
            </div>
        </div>

        <div class="bsc-live-clock-deck">
            <div class="date-label"><?php echo esc_html( wp_date( 'l, jS F Y' ) ); ?></div>
            <div class="time-readout" id="cinemaLiveClockMaster"><?php echo esc_html( wp_date( 'H:i:s' ) ); ?></div>
        </div>
    </header>

    <div class="bsc-kpi-deck-4">
        <div class="bsc-kpi-tile">
            <div>
                <div class="bsc-kpi-title"><?php esc_html_e( "Today's Revenue", 'bcs-screen-manager' ); ?></div>
                <div class="bsc-kpi-value" style="color:#059669;">
                    <?php echo esc_html( $currency_symbol . ' ' . number_format( $today_revenue, 2 ) ); ?>
                </div>
            </div>
            <div class="bsc-kpi-icon-bubble" style="background:#ecfdf5; color:#059669;"><?php echo esc_html( $currency_symbol ); ?></div>
        </div>

        <div class="bsc-kpi-tile">
            <div>
                <div class="bsc-kpi-title"><?php esc_html_e( 'Tickets Issued', 'bcs-screen-manager' ); ?></div>
                <div class="bsc-kpi-value" style="color:#0284c7;">
                    <?php echo esc_html( number_format( $today_tickets_sold ) ); ?> <span style="font-size:0.85rem; color:var(--bsc-text-muted, #64748b); font-weight:700;"><?php esc_html_e( 'Seats', 'bcs-screen-manager' ); ?></span>
                </div>
            </div>
            <div class="bsc-kpi-icon-bubble" style="background:#f0f9ff; color:#0284c7;">🎟️</div>
        </div>

        <div class="bsc-kpi-tile">
            <div>
                <div class="bsc-kpi-title"><?php esc_html_e( 'Total Orders', 'bcs-screen-manager' ); ?></div>
                <div class="bsc-kpi-value" style="color:#d97706;">
                    <?php echo esc_html( number_format( $today_bookings_count ) ); ?> <span style="font-size:0.85rem; color:var(--bsc-text-muted, #64748b); font-weight:700;"><?php esc_html_e( 'Passes', 'bcs-screen-manager' ); ?></span>
                </div>
            </div>
            <div class="bsc-kpi-icon-bubble" style="background:#fef3c7; color:#d97706;">🧾</div>
        </div>

        <div class="bsc-kpi-tile">
            <div>
                <div class="bsc-kpi-title"><?php esc_html_e( 'Counter / Online Split', 'bcs-screen-manager' ); ?></div>
                <div class="bsc-kpi-value" style="font-size:1.15rem; margin-top:0.35rem;">
                    <span style="color:#059669;"><?php echo esc_html( $currency_symbol . number_format( $today_cash_revenue, 0 ) ); ?></span>
                    <span style="color:var(--bsc-text-muted, #64748b); font-size:0.85rem;">/</span>
                    <span style="color:#d946ef;"><?php echo esc_html( $currency_symbol . number_format( $today_bkash_revenue, 0 ) ); ?></span>
                </div>
            </div>
            <div class="bsc-kpi-icon-bubble" style="background:#fdf2f8; color:#db2777;">💳</div>
        </div>
    </div>

    <div class="bsc-theater-arena-grid">
        <div>
            <div class="bsc-screen-filter-bar">
                <h3 class="bsc-panel-title" style="margin-bottom: 1rem;">🎬 <?php esc_html_e( 'Select Active Screening Slot', 'bcs-screen-manager' ); ?></h3>
                <div class="bsc-form-grid-2">
                    <div class="bsc-form-group" style="margin-bottom:0;">
                        <label for="monitorMovieSelect"><?php esc_html_e( 'Feature Movie Presentation', 'bcs-screen-manager' ); ?></label>
                        <select id="monitorMovieSelect" required>
                            <?php if ( ! empty( $movies_list ) ) : foreach ( $movies_list as $m ) : ?>
                                <option value="<?php echo esc_attr( $m->title ); ?>" data-showtimes="<?php echo esc_attr( $m->showtimes_json ); ?>">
                                    <?php echo esc_html( $m->title ); ?>
                                </option>
                            <?php endforeach; else : ?>
                                <option value="Feature Film" data-showtimes='["14:30","18:00","21:30"]'><?php esc_html_e( 'Standard Schedule', 'bcs-screen-manager' ); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="bsc-form-group" style="margin-bottom:0;">
                        <label for="monitorShowtimeSelect"><?php esc_html_e( 'Screening Time Slot', 'bcs-screen-manager' ); ?></label>
                        <select id="monitorShowtimeSelect" required>
                            <option value="18:00">06:00 PM</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bsc-monitor-cinema-box">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
                    <div>
                        <span class="bsc-badge-pill active" style="font-size:0.68rem;"><?php esc_html_e( 'AUDITORIUM 30-SEAT MATRIX', 'bcs-screen-manager' ); ?></span>
                        <h4 style="font-size: 1.15rem; font-weight: 900; margin: 0.35rem 0 0 0; color:#fff;"><?php esc_html_e( 'Live Auditorium Monitor', 'bcs-screen-manager' ); ?></h4>
                    </div>
                    <span id="selectedSeatsCounter" style="font-family: var(--bsc-font-mono, monospace); font-size: 0.88rem; color: #ef4444; font-weight: 800; background:rgba(239,68,68,0.15); padding:0.25rem 0.65rem; border-radius:6px; border:1px solid rgba(239,68,68,0.3);">
                        0 <?php esc_html_e( 'Seats Booked', 'bcs-screen-manager' ); ?>
                    </span>
                </div>

                <div class="bsc-monitor-matrix-canvas">
                    <?php 
                    $matrix_rows = array( 'F', 'E', 'D', 'C', 'B', 'A' );

                    foreach ( $matrix_rows as $row_letter ) : ?>
                        <div class="bsc-seat-matrix-col">
                            <div class="col-label" style="color:#94a3b8; font-weight:900;"><?php echo esc_html( $row_letter ); ?></div>
                            <?php for ( $seat_num = 1; $seat_num <= 5; $seat_num++ ) : 
                                $s_id = $row_letter . $seat_num;
                            ?>
                                <div class="bsc-matrix-seat standard" id="matrix-seat-<?php echo esc_attr( $s_id ); ?>">
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
                        <div class="dot" style="background: #ef4444;"></div>
                        <span><?php esc_html_e( 'Booked / Reserved for Slot', 'bcs-screen-manager' ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="bsc-telemetry-panel">
                <h3 class="bsc-panel-title" style="margin-bottom: 1.25rem;">📊 <?php esc_html_e( 'Slot Telemetry', 'bcs-screen-manager' ); ?></h3>

                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; font-weight: 800; margin-bottom: 0.35rem;">
                    <span style="color: var(--bsc-text-muted, #64748b);"><?php esc_html_e( 'Occupancy Percentage', 'bcs-screen-manager' ); ?></span>
                    <span style="color: var(--bsc-accent-teal, #0d9488);" id="lblOccupancyPercent">0%</span>
                </div>
                <div class="bsc-progress-bar-wrapper">
                    <div class="bsc-progress-bar-fill" id="occupancyProgressBar" style="width: 0%;"></div>
                </div>

                <label style="font-weight: 800; font-size: 0.75rem; text-transform: uppercase; color: var(--bsc-text-muted, #64748b); display: block; margin-bottom: 0.35rem;">
                    <?php esc_html_e( 'Reserved Seats Roster', 'bcs-screen-manager' ); ?>
                </label>
                <div class="bsc-chips-roster-box" id="selectedChipsDeck">
                    <span style="font-size: 0.82rem; color: var(--bsc-text-muted, #64748b);"><?php esc_html_e( 'No seats booked for this slot yet.', 'bcs-screen-manager' ); ?></span>
                </div>

                <table class="bsc-slot-metric-table">
                    <tbody>
                        <tr>
                            <td style="color: var(--bsc-text-muted, #64748b);"><?php esc_html_e( 'Slot Booked Seats', 'bcs-screen-manager' ); ?></td>
                            <td style="text-align: right; font-weight: 800; color: #ef4444;" id="lblSlotBooked">0</td>
                        </tr>
                        <tr>
                            <td style="color: var(--bsc-text-muted, #64748b);"><?php esc_html_e( 'Available Seats', 'bcs-screen-manager' ); ?></td>
                            <td style="text-align: right; font-weight: 800; color: #10b981;" id="lblSlotFree">30</td>
                        </tr>
                        <tr class="total-row">
                            <td style="color: var(--bsc-text-main, #0f172a);"><?php esc_html_e( 'Slot Gross Revenue', 'bcs-screen-manager' ); ?></td>
                            <td style="text-align: right; color: var(--bsc-accent-teal, #0d9488); font-family: var(--bsc-font-mono, monospace);" id="lblSlotRevenue">
                                <?php echo esc_html( $currency_symbol ); ?> 0.00
                            </td>
                        </tr>
                    </tbody>
                </table>

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=add' ) ); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; text-align:center; padding:11px; font-weight:900; font-size: 0.95rem; text-decoration:none; display:block; border-radius:8px; box-shadow:0 4px 14px rgba(6,78,59,0.25); box-sizing:border-box;">
                    🎟️ <?php esc_html_e( 'Manual Counter Booking', 'bcs-screen-manager' ); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="bsc-panel" style="margin: 0; background:#fff; border: 1px solid var(--bsc-border, #e2e8f0); border-radius: var(--bsc-radius-md, 8px); box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
        <div class="bsc-panel-header">
            <div>
                <h3 class="bsc-panel-title" style="margin:0 0 4px 0;">🎟️ <?php esc_html_e( "Today's Admission Passes & Orders", 'bcs-screen-manager' ); ?> (<?php echo esc_html( (string) $today_bookings_count ); ?>)</h3>
                <span style="font-size:0.78rem; color:var(--bsc-text-muted, #64748b);">
                    <?php 
                    /* translators: %s: formatted localized current date */
                    printf( esc_html__( 'Showing transactions logged on %s', 'bcs-screen-manager' ), esc_html( wp_date( 'l, jS F Y' ) ) ); 
                    ?>
                </span>
            </div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=all' ) ); ?>" class="button button-secondary" style="font-weight:700;">
                <?php esc_html_e( 'View Complete Booking Archives', 'bcs-screen-manager' ); ?> →
            </a>
        </div>

        <div class="bsc-table-wrap" style="overflow-x: auto;">
            <table class="bsc-data-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'TOKEN', 'bcs-screen-manager' ); ?></th>
                        <th><?php esc_html_e( 'CUSTOMER', 'bcs-screen-manager' ); ?></th>
                        <th><?php esc_html_e( 'MOVIE', 'bcs-screen-manager' ); ?></th>
                        <th><?php esc_html_e( 'SHOWTIME', 'bcs-screen-manager' ); ?></th>
                        <th><?php esc_html_e( 'SEATS', 'bcs-screen-manager' ); ?></th>
                        <th><?php esc_html_e( 'METHOD', 'bcs-screen-manager' ); ?></th>
                        <th><?php esc_html_e( 'TOTAL AMOUNT', 'bcs-screen-manager' ); ?></th>
                        <th><?php esc_html_e( 'TIME', 'bcs-screen-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $today_bookings ) ) : foreach ( $today_bookings as $b ) : ?>
                        <tr>
                            <td><span class="bsc-voucher-tag"><?php echo esc_html( $b->booking_token ); ?></span></td>
                            <td>
                                <strong><?php echo esc_html( ! empty( $b->customer_name ) ? $b->customer_name : __( 'Walk-In Guest', 'bcs-screen-manager' ) ); ?></strong><br>
                                <small style="color:var(--bsc-text-muted, #64748b);"><?php echo esc_html( ! empty( $b->customer_phone ) ? $b->customer_phone : __( 'No phone', 'bcs-screen-manager' ) ); ?></small>
                            </td>
                            <td>
                                <strong><?php echo esc_html( $b->movie_title ); ?></strong>
                            </td>
                            <td><span class="bsc-badge-pill active" style="font-size:0.68rem;"><?php echo esc_html( ! empty( $b->showtime ) ? $b->showtime : __( 'Counter', 'bcs-screen-manager' ) ); ?></span></td>
                            <td><code><?php echo esc_html( $b->seats ); ?></code></td>
                            <td><span class="bsc-badge-pill"><?php echo esc_html( ! empty( $b->payment_method ) ? $b->payment_method : 'Cash' ); ?></span></td>
                            <td><strong style="color:var(--bsc-accent-teal, #0d9488); font-family:var(--bsc-font-mono, monospace);"><?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $b->total_amount, 2 ) ); ?></strong></td>
                            <td><small style="color:var(--bsc-text-muted, #64748b);"><?php echo esc_html( wp_date( 'h:i A', strtotime( $b->booking_date ) ) ); ?></small></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding: 2.5rem; color:var(--bsc-text-muted, #64748b);">
                                <?php esc_html_e( 'No ticket bookings recorded yet for today.', 'bcs-screen-manager' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    // Pass strictly sanitized payload to client-side runtime script
    $version = defined( 'BSC_VERSION' ) ? BSC_VERSION : '1.0.0';
    wp_register_script( 'bsc-dashboard-runtime', false, array(), $version, true );
    wp_enqueue_script( 'bsc-dashboard-runtime' );

    $empty_roster_msg = esc_js( __( 'No seats booked for this slot yet.', 'bcs-screen-manager' ) );

    $js_script = "
        (function() {
            const dbTodayBookings = " . wp_json_encode( $today_bookings_for_js ) . ";
            const TOTAL_CAPACITY  = 30;
            const CURRENCY_TAG    = " . wp_json_encode( $currency_symbol ) . ";
            const allSeatIds = [
                'A1','A2','A3','A4','A5',
                'B1','B2','B3','B4','B5',
                'C1','C2','C3','C4','C5',
                'D1','D2','D3','D4','D5',
                'E1','E2','E3','E4','E5',
                'F1','F2','F3','F4','F5'
            ];

            function updateLiveClockMaster() {
                const now = new Date();
                const hrs = String(now.getHours()).padStart(2, '0');
                const mins = String(now.getMinutes()).padStart(2, '0');
                const secs = String(now.getSeconds()).padStart(2, '0');
                const el = document.getElementById('cinemaLiveClockMaster');
                if (el) el.textContent = hrs + ':' + mins + ':' + secs;
            }
            setInterval(updateLiveClockMaster, 1000);
            updateLiveClockMaster();

            function formatSlotTime(timeStr) {
                if (!timeStr) return '06:00 PM';
                if (timeStr.indexOf(':') !== -1 && timeStr.length <= 5) {
                    const parts = timeStr.split(':');
                    const hour = parseInt(parts[0], 10);
                    const ampm = hour >= 12 ? 'PM' : 'AM';
                    const formattedHour = hour % 12 || 12;
                    return String(formattedHour).padStart(2, '0') + ':' + parts[1] + ' ' + ampm;
                }
                return timeStr;
            }

            function onMonitorMovieChange(selectEl) {
                if (!selectEl) return;
                const opt = selectEl.options[selectEl.selectedIndex];
                if (!opt) return;

                const showtimesRaw = opt.getAttribute('data-showtimes') || '[\"18:00\"]';
                const showtimeSelect = document.getElementById('monitorShowtimeSelect');
                if (!showtimeSelect) return;
                showtimeSelect.innerHTML = '';

                let slots = [];
                try {
                    const parsed = JSON.parse(showtimesRaw);
                    if (Array.isArray(parsed)) slots = parsed;
                    else slots = [showtimesRaw];
                } catch(e) {
                    slots = showtimesRaw.split(',');
                }

                slots.forEach(function(slot) {
                    const trimmed = String(slot).trim();
                    if (trimmed) {
                        const o = document.createElement('option');
                        o.value = trimmed;
                        o.textContent = formatSlotTime(trimmed);
                        showtimeSelect.appendChild(o);
                    }
                });

                renderLiveMatrix();
            }

            function renderLiveMatrix() {
                const movieSelect = document.getElementById('monitorMovieSelect');
                const showtimeSelect = document.getElementById('monitorShowtimeSelect');

                const selectedMovie = movieSelect ? movieSelect.value.trim().toLowerCase() : '';
                const selectedTime = showtimeSelect ? showtimeSelect.value.trim() : '';

                const occupiedSeats = [];
                let slotRevenue = 0;

                dbTodayBookings.forEach(function(b) {
                    const bMovie = (b.movie_title || '').trim().toLowerCase();
                    const bTime = (b.showtime || '').trim();

                    if (bMovie === selectedMovie && bTime === selectedTime) {
                        slotRevenue += parseFloat(b.total_amount || 0);
                        if (b.seats) {
                            const parts = b.seats.split(',');
                            parts.forEach(function(p) {
                                const trimmed = p.trim().toUpperCase();
                                if (trimmed && occupiedSeats.indexOf(trimmed) === -1) {
                                    occupiedSeats.push(trimmed);
                                }
                            });
                        }
                    }
                });

                allSeatIds.forEach(function(seatId) {
                    const seatEl = document.getElementById('matrix-seat-' + seatId);
                    if (!seatEl) return;

                    if (occupiedSeats.indexOf(seatId) !== -1) {
                        seatEl.className = 'bsc-matrix-seat occupied';
                        seatEl.title = 'Seat ' + seatId + ' [OCCUPIED FOR SLOT]';
                    } else {
                        seatEl.className = 'bsc-matrix-seat standard';
                        seatEl.title = 'Seat ' + seatId + ' [AVAILABLE]';
                    }
                });

                const bookedCount = occupiedSeats.length;
                const freeCount   = Math.max(0, TOTAL_CAPACITY - bookedCount);
                const percent     = Math.round((bookedCount / TOTAL_CAPACITY) * 100);

                const counter        = document.getElementById('selectedSeatsCounter');
                const chipsDeck      = document.getElementById('selectedChipsDeck');
                const elBooked       = document.getElementById('lblSlotBooked');
                const elFree         = document.getElementById('lblSlotFree');
                const elPct          = document.getElementById('lblOccupancyPercent');
                const elBar          = document.getElementById('occupancyProgressBar');
                const elSlotRevenue  = document.getElementById('lblSlotRevenue');

                if (counter) counter.textContent = bookedCount + ' Seats Booked';
                if (elBooked) elBooked.textContent = bookedCount;
                if (elFree) elFree.textContent = freeCount;
                if (elPct) elPct.textContent = percent + '%';
                if (elBar) elBar.style.width = percent + '%';
                if (elSlotRevenue) elSlotRevenue.textContent = CURRENCY_TAG + ' ' + slotRevenue.toFixed(2);

                if (chipsDeck) {
                    chipsDeck.innerHTML = '';
                    if (occupiedSeats.length === 0) {
                        const emptySpan = document.createElement('span');
                        emptySpan.style.fontSize = '0.82rem';
                        emptySpan.style.color = 'var(--bsc-text-muted, #64748b)';
                        emptySpan.textContent = " . wp_json_encode( $empty_roster_msg ) . ";
                        chipsDeck.appendChild(emptySpan);
                    } else {
                        occupiedSeats.forEach(function(s) {
                            const badge = document.createElement('span');
                            badge.className = 'bsc-roster-badge';
                            badge.textContent = s;
                            chipsDeck.appendChild(badge);
                        });
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const movieSelect = document.getElementById('monitorMovieSelect');
                const showtimeSelect = document.getElementById('monitorShowtimeSelect');

                if (movieSelect) {
                    movieSelect.addEventListener('change', function() {
                        onMonitorMovieChange(this);
                    });
                    onMonitorMovieChange(movieSelect);
                }

                if (showtimeSelect) {
                    showtimeSelect.addEventListener('change', renderLiveMatrix);
                }

                renderLiveMatrix();
            });
        })();
    ";

    wp_add_inline_script( 'bsc-dashboard-runtime', $js_script );

    if ( function_exists( 'bsc_render_page_shell_close' ) ) {
        bsc_render_page_shell_close();
    }
}