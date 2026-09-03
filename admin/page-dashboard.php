<?php
if (!defined('ABSPATH')) exit;

function bsc_render_dashboard_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Unauthorized user action.', 'bsc-cinema-manager'));
    }

    global $wpdb;
    $table_bookings = $wpdb->prefix . 'bsc_bookings';
    $table_movies   = $wpdb->prefix . 'bsc_control_movies';
    $table_ledger   = $wpdb->prefix . 'bsc_ledger';

    $today_date = current_time('Y-m-d');

    $vip_base_price  = floatval(get_option('bsc_vip_price', 500));
    $std_base_price  = floatval(get_option('bsc_standard_price', 400));
    $currency_symbol = sanitize_text_field(get_option('bsc_currency_symbol', 'BDT'));

    // Today's total booking orders count
    $today_bookings_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(id) FROM {$table_bookings} WHERE DATE(booking_date) = %s",
        $today_date
    ));

    // Today's revenue calculation from ledger
    $today_revenue = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$table_ledger} WHERE txn_type = 'Income' AND DATE(transaction_date) = %s",
        $today_date
    ));

    // Calculate total tickets sold today by parsing comma-separated seat lists
    $today_seats_rows = $wpdb->get_col($wpdb->prepare(
        "SELECT seats FROM {$table_bookings} WHERE DATE(booking_date) = %s",
        $today_date
    ));
    $today_tickets_sold = 0;
    if (!empty($today_seats_rows)) {
        foreach ($today_seats_rows as $st) {
            $parts = explode(',', $st);
            $today_tickets_sold += count(array_filter(array_map('trim', $parts)));
        }
    }

    // Cash revenue split for today
    $today_cash_revenue = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$table_ledger} WHERE txn_type = 'Income' AND DATE(transaction_date) = %s AND (method = 'Cash' OR method LIKE %s)",
        $today_date,
        '%' . $wpdb->esc_like('Cash') . '%'
    ));

    // Online/bKash revenue split for today
    $today_bkash_revenue = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$table_ledger} WHERE txn_type = 'Income' AND DATE(transaction_date) = %s AND (method = 'bKash' OR method LIKE %s OR method LIKE %s OR method LIKE %s)",
        $today_date,
        '%' . $wpdb->esc_like('bKash') . '%',
        '%' . $wpdb->esc_like('Online') . '%',
        '%' . $wpdb->esc_like('Portal') . '%'
    ));

    // Fetch strict today bookings list for the data table
    $today_bookings_list = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_bookings} WHERE DATE(booking_date) = %s ORDER BY id DESC",
        $today_date
    ));

    // Fetch all movies and parse their showtimes JSON safely
    $movies_raw = $wpdb->get_results("SELECT id, title, showtimes, badge_tag FROM {$table_movies} ORDER BY id DESC");
    $movies_list = array();
    if (!empty($movies_raw)) {
        foreach ($movies_raw as $m) {
            $parsed_showtimes = array();
            $decoded = json_decode($m->showtimes, true);
            if (is_array($decoded)) {
                foreach ($decoded as $day_entry) {
                    if (is_array($day_entry) && !empty($day_entry['times']) && is_array($day_entry['times'])) {
                        foreach ($day_entry['times'] as $t) {
                            $parsed_showtimes[] = sanitize_text_field($t);
                        }
                    } elseif (is_string($day_entry)) {
                        $parsed_showtimes[] = sanitize_text_field($day_entry);
                    }
                }
            }
            if (empty($parsed_showtimes)) {
                $parsed_showtimes = array('18:00');
            }
            $m->showtimes_json = wp_json_encode(array_values(array_unique($parsed_showtimes)));
            $movies_list[] = $m;
        }
    }

    // Fetch all of today's booking records for client-side interactive seat matrix filtering
    $today_bookings_for_js = $wpdb->get_results($wpdb->prepare(
        "SELECT movie_title, showtime, seats, customer_name, customer_phone, booking_token, payment_method, payment_status, total_amount, booking_date FROM {$table_bookings} WHERE DATE(booking_date) = %s",
        $today_date
    ));

    $current_user = wp_get_current_user();
    $user_name    = !empty($current_user->display_name) ? $current_user->display_name : 'Master Administrator';
    $avatar_url   = get_avatar_url($current_user->ID, array('size' => 140));

    $hour = (int) current_time('G');
    if ($hour < 12) {
        $greeting = __('Good Morning', 'bsc-cinema-manager');
    } elseif ($hour < 17) {
        $greeting = __('Good Afternoon', 'bsc-cinema-manager');
    } else {
        $greeting = __('Good Evening', 'bsc-cinema-manager');
    }

    if (function_exists('bsc_render_page_shell_open')) {
        bsc_render_page_shell_open('bsc-dashboard');
    }
    ?>
    <style>
        .bsc-hero-glass-card {
            background: linear-gradient(135deg, #09171c 0%, #062624 50%, #07171d 100%);
            border: 1px solid rgba(13, 148, 136, 0.25);
            border-radius: var(--cinema-radius-lg, 12px);
            padding: 1.75rem 2.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.08);
            position: relative;
            overflow: hidden;
        }
        .bsc-hero-glass-card::before {
            content: '';
            position: absolute;
            top: -40px;
            right: 20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(13, 148, 136, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .bsc-hero-profile-unit {
            display: flex;
            align-items: center;
            gap: 1.35rem;
            position: relative;
            z-index: 1;
        }
        .bsc-hero-avatar-ring {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 3px solid #0d9488;
            box-shadow: 0 0 18px rgba(13, 148, 136, 0.4);
            object-fit: cover;
        }
        .bsc-hero-headings h1 {
            font-size: 1.65rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            margin: 0.3rem 0;
            color: #ffffff;
            line-height: 1.2;
        }
        .bsc-hero-tags-row {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 0.45rem;
        }
        .bsc-hero-tags-row strong {
            color: #f8fafc;
            font-weight: 800;
        }
        .bsc-live-clock-deck {
            background: rgba(4, 10, 13, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1rem 1.6rem;
            border-radius: var(--cinema-radius-md, 8px);
            text-align: right;
            backdrop-filter: blur(8px);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        .bsc-live-clock-deck .date-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.02em;
        }
        .bsc-live-clock-deck .time-readout {
            font-size: 1.65rem;
            font-weight: 900;
            font-family: var(--cinema-font-mono, monospace);
            color: #ffffff;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        .bsc-kpi-deck-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .bsc-kpi-tile {
            background: #ffffff;
            border: 1px solid var(--cinema-border, #e2e8f0);
            border-radius: var(--cinema-radius-md, 8px);
            padding: 1.35rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .bsc-kpi-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }
        .bsc-kpi-title {
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--cinema-text-muted, #64748b);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.35rem;
        }
        .bsc-kpi-value {
            font-size: 1.75rem;
            font-weight: 900;
            font-family: var(--cinema-font-mono, monospace);
            color: var(--cinema-text-main, #0f172a);
            line-height: 1;
        }
        .bsc-kpi-icon-bubble {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }
        .bsc-theater-arena-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
            align-items: start;
            margin-bottom: 2rem;
        }
        .bsc-screen-filter-bar {
            background: #ffffff;
            border: 1px solid var(--cinema-border, #e2e8f0);
            border-radius: var(--cinema-radius-md, 8px);
            padding: 1.35rem 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
        }
        .bsc-form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .bsc-form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
            color: #334155;
        }
        .bsc-form-group select {
            width: 100%;
            height: 42px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            padding: 0 0.75rem;
        }
        .bsc-monitor-cinema-box {
            background: #060e12;
            border: 1px solid #172a33;
            border-radius: var(--cinema-radius-lg, 12px);
            padding: 1.75rem 2rem;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.15);
            position: relative;
        }
        .bsc-monitor-matrix-canvas {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            padding: 1.5rem 0;
            overflow-x: auto;
        }
        .bsc-seat-matrix-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.65rem;
        }
        .bsc-seat-matrix-col .col-label {
            font-size: 0.72rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.35rem;
        }
        .bsc-matrix-seat {
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
            transition: all 0.2s ease;
            user-select: none;
            cursor: default;
        }
        .bsc-matrix-seat.vip {
            background: #3b1704;
            border-color: #b45309;
            color: #fde68a;
        }
        .bsc-matrix-seat.standard {
            background: #09231f;
            border-color: #0d9488;
            color: #99f6e4;
        }
        .bsc-matrix-seat.occupied {
            background: #201317 !important;
            border-color: #ef4444 !important;
            color: #fca5a5 !important;
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
        }
        .bsc-matrix-screen-beacon {
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
        .bsc-matrix-legend-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            justify-content: center;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.8rem;
        }
        .bsc-legend-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #cbd5e1;
            font-weight: 600;
        }
        .bsc-legend-pill .dot {
            width: 14px;
            height: 14px;
            border-radius: 4px;
        }
        .bsc-telemetry-panel {
            background: #ffffff;
            border: 1px solid var(--cinema-border, #e2e8f0);
            border-radius: var(--cinema-radius-md, 8px);
            padding: 1.6rem;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
        }
        .bsc-progress-bar-wrapper {
            background: #f1f5f9;
            border-radius: 999px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .bsc-progress-bar-fill {
            background: #0d9488;
            height: 100%;
            border-radius: 999px;
            transition: width 0.3s ease;
        }
        .bsc-chips-roster-box {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            min-height: 48px;
            padding: 0.65rem;
            background: #f8fafc;
            border: 1px solid var(--cinema-border, #e2e8f0);
            border-radius: 8px;
            margin-bottom: 1.25rem;
            align-items: center;
        }
        .bsc-roster-badge {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 0.2rem 0.55rem;
            border-radius: 4px;
            font-family: var(--cinema-font-mono, monospace);
        }
        .bsc-slot-metric-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            margin-bottom: 1.5rem;
        }
        .bsc-slot-metric-table td {
            padding: 0.55rem 0;
            border-bottom: 1px dashed var(--cinema-border-light, #f1f5f9);
        }
        .bsc-slot-metric-table tr.total-row td {
            border-bottom: none;
            padding-top: 0.85rem;
            font-size: 1rem;
            font-weight: 900;
        }
        .cinema-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .cinema-data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cinema-data-table th, .cinema-data-table td {
            padding: 1rem 1.25rem;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }
        .cinema-voucher-tag {
            font-family: monospace;
            background: #f8fafc;
            padding: 2px 6px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .cinema-badge-pill {
            padding: 3px 8px;
            border-radius: 999px;
            font-weight: 700;
            display: inline-block;
        }
        .cinema-badge-pill.active {
            background: rgba(13, 148, 136, 0.1);
            color: #0d9488;
        }
        .cinema-badge-payment {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        @media (max-width: 1100px) {
            .bsc-kpi-deck-4 { grid-template-columns: repeat(2, 1fr); }
            .bsc-theater-arena-grid { grid-template-columns: 1fr; }
            .bsc-form-grid-2 { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .bsc-kpi-deck-4 { grid-template-columns: 1fr; }
            .bsc-hero-glass-card { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .bsc-live-clock-deck { text-align: left; width: 100%; box-sizing: border-box; }
        }
    </style>

    <header class="bsc-hero-glass-card">
        <div class="bsc-hero-profile-unit">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php esc_attr_e('Admin Avatar', 'bsc-cinema-manager'); ?>" class="bsc-hero-avatar-ring">
            <div class="bsc-hero-headings">
                <span class="cinema-badge-pill active" style="font-size:0.7rem; letter-spacing:0.06em;">
                    ⚡ <?php esc_html_e('LIVE MULTIPLEX CONTROLLER', 'bsc-cinema-manager'); ?>
                </span>
                <h1><?php echo esc_html(sprintf(__('%s, %s', 'bsc-cinema-manager'), $greeting, $user_name)); ?></h1>
                <div class="bsc-hero-tags-row">
                    <span><?php esc_html_e('Today\'s Passes:', 'bsc-cinema-manager'); ?> <strong><?php echo esc_html($today_bookings_count); ?></strong></span>
                    <span>•</span>
                    <span><?php esc_html_e('Tickets Issued:', 'bsc-cinema-manager'); ?> <strong><?php echo esc_html($today_tickets_sold); ?></strong></span>
                    <span>•</span>
                    <span style="color:#34d399; font-weight:800;">● <?php esc_html_e('BOX OFFICE ONLINE', 'bsc-cinema-manager'); ?></span>
                </div>
            </div>
        </div>

        <div class="bsc-live-clock-deck">
            <div class="date-label"><?php echo esc_html(date_i18n('l, jS F Y')); ?></div>
            <div class="time-readout" id="cinemaLiveClockMaster"><?php echo esc_html(date_i18n('H:i:s')); ?></div>
        </div>
    </header>

    <div class="bsc-kpi-deck-4">
        <div class="bsc-kpi-tile">
            <div>
                <div class="bsc-kpi-title"><?php esc_html_e('Today\'s Revenue', 'bsc-cinema-manager'); ?></div>
                <div class="bsc-kpi-value" style="color:#059669;">
                    <?php echo esc_html($currency_symbol); ?> <?php echo esc_html(number_format($today_revenue, 2)); ?>
                </div>
            </div>
            <div class="bsc-kpi-icon-bubble" style="background:#ecfdf5; color:#059669;">৳</div>
        </div>

        <div class="bsc-kpi-tile">
            <div>
                <div class="bsc-kpi-title"><?php esc_html_e('Tickets Issued', 'bsc-cinema-manager'); ?></div>
                <div class="bsc-kpi-value" style="color:#0284c7;">
                    <?php echo esc_html(number_format($today_tickets_sold)); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted, #64748b); font-weight:700;">Seats</span>
                </div>
            </div>
            <div class="bsc-kpi-icon-bubble" style="background:#f0f9ff; color:#0284c7;">🎟️</div>
        </div>

        <div class="bsc-kpi-tile">
            <div>
                <div class="bsc-kpi-title"><?php esc_html_e('Total Orders', 'bsc-cinema-manager'); ?></div>
                <div class="bsc-kpi-value" style="color:#d97706;">
                    <?php echo esc_html(number_format($today_bookings_count)); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted, #64748b); font-weight:700;">Passes</span>
                </div>
            </div>
            <div class="bsc-kpi-icon-bubble" style="background:#fef3c7; color:#d97706;">🧾</div>
        </div>

        <div class="bsc-kpi-tile">
            <div>
                <div class="bsc-kpi-title"><?php esc_html_e('Counter / Online Split', 'bsc-cinema-manager'); ?></div>
                <div class="bsc-kpi-value" style="font-size:1.15rem; margin-top:0.35rem;">
                    <span style="color:#059669;">৳<?php echo esc_html(number_format($today_cash_revenue, 0)); ?></span>
                    <span style="color:var(--cinema-text-muted, #64748b); font-size:0.85rem;">/</span>
                    <span style="color:#d946ef;">৳<?php echo esc_html(number_format($today_bkash_revenue, 0)); ?></span>
                </div>
            </div>
            <div class="bsc-kpi-icon-bubble" style="background:#fdf2f8; color:#db2777;">💳</div>
        </div>
    </div>

    <div class="bsc-theater-arena-grid">
        <div>
            <div class="bsc-screen-filter-bar">
                <h3 class="cinema-panel-title" style="margin-bottom: 1rem;">🎬 <?php esc_html_e('Select Active Screening Slot', 'bsc-cinema-manager'); ?></h3>
                <div class="bsc-form-grid-2">
                    <div class="bsc-form-group" style="margin-bottom:0;">
                        <label for="monitorMovieSelect"><?php esc_html_e('Feature Movie Presentation', 'bsc-cinema-manager'); ?></label>
                        <select id="monitorMovieSelect" required>
                            <?php if (!empty($movies_list)): foreach ($movies_list as $m): ?>
                                <option value="<?php echo esc_attr($m->title); ?>" data-showtimes="<?php echo esc_attr($m->showtimes_json); ?>">
                                    <?php echo esc_html($m->title); ?>
                                </option>
                            <?php endforeach; else: ?>
                                <option value="Borbaad" data-showtimes='["14:30","18:00","21:30"]'>Borbaad</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="bsc-form-group" style="margin-bottom:0;">
                        <label for="monitorShowtimeSelect"><?php esc_html_e('Screening Time Slot', 'bsc-cinema-manager'); ?></label>
                        <select id="monitorShowtimeSelect" required>
                            <option value="18:00">06:00 PM</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bsc-monitor-cinema-box">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
                    <div>
                        <span class="cinema-badge-pill active" style="font-size:0.68rem;"><?php esc_html_e('BOUTIQUE 23-SEAT MATRIX', 'bsc-cinema-manager'); ?></span>
                        <h4 style="font-size: 1.15rem; font-weight: 900; margin: 0.35rem 0 0 0; color:#fff;"><?php esc_html_e('Live Auditorium Monitor', 'bsc-cinema-manager'); ?></h4>
                    </div>
                    <span id="selectedSeatsCounter" style="font-family: var(--cinema-font-mono, monospace); font-size: 0.88rem; color: #ef4444; font-weight: 800; background:rgba(239,68,68,0.15); padding:0.25rem 0.65rem; border-radius:6px; border:1px solid rgba(239,68,68,0.3);">
                        0 Seats Booked
                    </span>

                </div>

                <div class="bsc-monitor-matrix-canvas">
                    <div class="bsc-seat-matrix-col">
                        <div class="col-label" style="color:#f59e0b;">Row A (VIP)</div>
                        <?php foreach (array('A1', 'A2', 'A3', 'A4', 'A5') as $s_id): ?>
                            <div class="bsc-matrix-seat vip" id="matrix-seat-<?php echo esc_attr($s_id); ?>">
                                <?php echo esc_html($s_id); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bsc-seat-matrix-col">
                        <div class="col-label">Row B</div>
                        <?php foreach (array('B1', 'B2', 'B3', 'B4', 'B5', 'B6') as $s_id): ?>
                            <div class="bsc-matrix-seat standard" id="matrix-seat-<?php echo esc_attr($s_id); ?>">
                                <?php echo esc_html($s_id); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bsc-seat-matrix-col">
                        <div class="col-label">Row C</div>
                        <?php foreach (array('C1', 'C2', 'C3', 'C4', 'C5', 'C6') as $s_id): ?>
                            <div class="bsc-matrix-seat standard" id="matrix-seat-<?php echo esc_attr($s_id); ?>">
                                <?php echo esc_html($s_id); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bsc-seat-matrix-col">
                        <div class="col-label">Row D</div>
                        <?php foreach (array('D1', 'D2', 'D3', 'D4', 'D5', 'D6') as $s_id): ?>
                            <div class="bsc-matrix-seat standard" id="matrix-seat-<?php echo esc_attr($s_id); ?>">
                                <?php echo esc_html($s_id); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

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
                        <span>Standard Available (<?php echo esc_html($currency_symbol); ?> <?php echo esc_html(number_format($std_base_price, 0)); ?>)</span>
                    </div>
                    <div class="bsc-legend-pill">
                        <div class="dot" style="background: #b45309;"></div>
                        <span>VIP Available (<?php echo esc_html($currency_symbol); ?> <?php echo esc_html(number_format($vip_base_price, 0)); ?>)</span>
                    </div>
                    <div class="bsc-legend-pill">
                        <div class="dot" style="background: #ef4444;"></div>
                        <span>Booked / Reserved for Slot</span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="bsc-telemetry-panel">
                <h3 class="cinema-panel-title" style="margin-bottom: 1.25rem;">📊 <?php esc_html_e('Slot Telemetry', 'bsc-cinema-manager'); ?></h3>

                <div style="display: flex; justify-content: space-between; font-size: 0.82rem; font-weight: 800; margin-bottom: 0.35rem;">
                    <span style="color: var(--cinema-text-muted, #64748b);"><?php esc_html_e('Occupancy Percentage', 'bsc-cinema-manager'); ?></span>
                    <span style="color: var(--cinema-accent-teal, #0d9488);" id="lblOccupancyPercent">0%</span>
                </div>
                <div class="bsc-progress-bar-wrapper">
                    <div class="bsc-progress-bar-fill" id="occupancyProgressBar" style="width: 0%;"></div>
                </div>

                <label style="font-weight: 800; font-size: 0.75rem; text-transform: uppercase; color: var(--cinema-text-muted, #64748b); display: block; margin-bottom: 0.35rem;">
                    <?php esc_html_e('Reserved Seats Roster', 'bsc-cinema-manager'); ?>
                </label>
                <div class="bsc-chips-roster-box" id="selectedChipsDeck">
                    <span style="font-size: 0.82rem; color: var(--cinema-text-muted, #64748b);"><?php esc_html_e('No seats booked for this slot yet.', 'bsc-cinema-manager'); ?></span>
                </div>

                <table class="bsc-slot-metric-table">
                    <tbody>
                        <tr>
                            <td style="color: var(--cinema-text-muted, #64748b);"><?php esc_html_e('Slot Booked Seats', 'bsc-cinema-manager'); ?></td>
                            <td style="text-align: right; font-weight: 800; color: #ef4444;" id="lblSlotBooked">0</td>
                        </tr>
                        <tr>
                            <td style="color: var(--cinema-text-muted, #64748b);"><?php esc_html_e('Available Seats', 'bsc-cinema-manager'); ?></td>
                            <td style="text-align: right; font-weight: 800; color: #10b981;" id="lblSlotFree">23</td>
                        </tr>
                        <tr class="total-row">
                            <td style="color: var(--cinema-text-main, #0f172a);"><?php esc_html_e('Slot Gross Revenue', 'bsc-cinema-manager'); ?></td>
                            <td style="text-align: right; color: var(--cinema-accent-teal, #0d9488); font-family: var(--cinema-font-mono, monospace);" id="lblSlotRevenue">
                                <?php echo esc_html($currency_symbol); ?> 0.00
                            </td>
                        </tr>
                    </tbody>
                </table>

                <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=add')); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; text-align:center; padding:11px; font-weight:900; font-size: 0.95rem; text-decoration:none; display:block; border-radius:8px; box-shadow:0 4px 14px rgba(6,78,59,0.25); box-sizing:border-box;">
                    🎟️ <?php esc_html_e('Manual Counter Booking', 'bsc-cinema-manager'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="cinema-panel" style="margin: 0; background:#fff; border: 1px solid var(--cinema-border, #e2e8f0); border-radius: var(--cinema-radius-md, 8px); box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
        <div class="cinema-panel-header">
            <div>
                <h3 class="cinema-panel-title" style="margin:0 0 4px 0;">🎟️ <?php esc_html_e('Today\'s Admission Passes & Orders', 'bsc-cinema-manager'); ?> (<?php echo count($today_bookings_list); ?>)</h3>
                <span style="font-size:0.78rem; color:var(--cinema-text-muted, #64748b);"><?php echo esc_html(sprintf(__('Showing transactions logged on %s', 'bsc-cinema-manager'), date_i18n('l, jS F Y'))); ?></span>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=all')); ?>" class="button button-secondary" style="font-weight:700;">
                <?php esc_html_e('View Complete Booking Archives', 'bsc-cinema-manager'); ?> →
            </a>
        </div>

        <div class="cinema-table-wrap" style="overflow-x: auto;">
            <table class="cinema-data-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('TOKEN', 'bsc-cinema-manager'); ?></th>
                        <th><?php esc_html_e('CUSTOMER', 'bsc-cinema-manager'); ?></th>
                        <th><?php esc_html_e('MOVIE', 'bsc-cinema-manager'); ?></th>
                        <th><?php esc_html_e('SHOWTIME', 'bsc-cinema-manager'); ?></th>
                        <th><?php esc_html_e('SEATS', 'bsc-cinema-manager'); ?></th>
                        <th><?php esc_html_e('METHOD', 'bsc-cinema-manager'); ?></th>
                        <th><?php esc_html_e('TOTAL AMOUNT', 'bsc-cinema-manager'); ?></th>
                        <th><?php esc_html_e('TIME', 'bsc-cinema-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($today_bookings_list)): foreach ($today_bookings_list as $b): ?>
                        <tr>
                            <td><span class="cinema-voucher-tag"><?php echo esc_html($b->booking_token); ?></span></td>
                            <td>
                                <strong><?php echo esc_html($b->customer_name ? $b->customer_name : 'Walk-In Guest'); ?></strong><br>
                                <small style="color:var(--cinema-text-muted, #64748b);"><?php echo esc_html($b->customer_phone ? $b->customer_phone : 'No phone'); ?></small>
                            </td>
                            <td>
                                <strong><?php echo esc_html($b->movie_title); ?></strong>
                            </td>
                            <td><span class="cinema-badge-pill active" style="font-size:0.68rem;"><?php echo esc_html($b->showtime ? $b->showtime : 'Counter'); ?></span></td>
                            <td><code><?php echo esc_html($b->seats); ?></code></td>
                            <td><span class="cinema-badge-payment"><?php echo esc_html($b->payment_method ? $b->payment_method : 'Cash'); ?></span></td>
                            <td><strong style="color:var(--cinema-accent-teal, #0d9488); font-family:var(--cinema-font-mono, monospace);"><?php echo esc_html($currency_symbol); ?> <?php echo esc_html(number_format($b->total_amount, 2)); ?></strong></td>
                            <td><small style="color:var(--cinema-text-muted, #64748b);"><?php echo esc_html(date_i18n('h:i A', strtotime($b->booking_date))); ?></small></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding: 2.5rem; color:var(--cinema-text-muted, #64748b);">
                                <?php esc_html_e('No ticket bookings recorded yet for today.', 'bsc-cinema-manager'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function() {
            const dbTodayBookings = <?php echo wp_json_encode($today_bookings_for_js ?: array()); ?>;
            const TOTAL_CAPACITY  = 23;
            const CURRENCY_TAG    = <?php echo wp_json_encode($currency_symbol); ?>;
            const allSeatIds = [
                'A1','A2','A3','A4','A5',
                'B1','B2','B3','B4','B5','B6',
                'C1','C2','C3','C4','C5','C6',
                'D1','D2','D3','D4','D5','D6'
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

                const showtimesRaw = opt.getAttribute('data-showtimes') || '["18:00"]';
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
                        const isVip = seatId.charAt(0) === 'A';
                        seatEl.className = isVip ? 'bsc-matrix-seat vip' : 'bsc-matrix-seat standard';
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
                        emptySpan.style.color = 'var(--cinema-text-muted, #64748b)';
                        emptySpan.textContent = '<?php echo esc_js(__('No seats booked for this slot yet.', 'bsc-cinema-manager')); ?>';
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
    </script>
    <?php
    if (function_exists('bsc_render_page_shell_close')) {
        bsc_render_page_shell_close();
    }
}