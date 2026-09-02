<?php
if (!defined('ABSPATH')) exit;

function bsc_render_dashboard_page() {
  global $wpdb;
  $table_bookings = $wpdb->prefix . 'bsc_bookings';
  $table_movies   = $wpdb->prefix . 'bsc_control_movies';
  $table_ledger   = $wpdb->prefix . 'bsc_ledger';

  $today_date = current_time('Y-m-d');

  // Base pricing defaults
  $pricing_tiers   = get_option('bsc_pricing_tiers', array());
  $vip_base_price  = floatval(get_option('bsc_vip_price', 500));
  $std_base_price  = floatval(get_option('bsc_standard_price', 400));
  $currency_symbol = get_option('bsc_currency_symbol', 'BDT');

  // Today's Core Metrics
  $today_bookings_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_bookings WHERE DATE(booking_date) = %s",
    $today_date
  ));

  $today_revenue = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount) FROM $table_ledger WHERE txn_type = 'Income' AND DATE(transaction_date) = %s",
    $today_date
  ));

  // Calculate today's individual tickets/seats count
  $today_seats_rows = $wpdb->get_col($wpdb->prepare(
    "SELECT seats FROM $table_bookings WHERE DATE(booking_date) = %s",
    $today_date
  ));
  $today_tickets_sold = 0;
  if (!empty($today_seats_rows)) {
    foreach ($today_seats_rows as $st) {
      $parts = explode(',', $st);
      $today_tickets_sold += count(array_filter(array_map('trim', $parts)));
    }
  }

  // Today's Payment Method Split (Cash vs bKash / Gateway)
  $today_cash_revenue = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount) FROM $table_ledger WHERE txn_type = 'Income' AND DATE(transaction_date) = %s AND (method = 'Cash' OR method LIKE '%%Cash%%')",
    $today_date
  ));
  $today_bkash_revenue = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount) FROM $table_ledger WHERE txn_type = 'Income' AND DATE(transaction_date) = %s AND (method = 'bKash' OR method LIKE '%%bKash%%' OR method LIKE '%%Online%%')",
    $today_date
  ));

  // Today's Transactions List
  $today_bookings_list = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_bookings WHERE DATE(booking_date) = %s ORDER BY id DESC",
    $today_date
  ));

  // Movies & Today's Bookings for Live Interactive Filtering
  $movies_list = $wpdb->get_results("SELECT id, title, auditorium, showtimes, badge_tag, ticket_price FROM $table_movies ORDER BY id DESC");
  $today_bookings_for_js = $wpdb->get_results($wpdb->prepare(
    "SELECT movie_title, showtime, seats, customer_name, customer_phone, booking_token, payment_method, payment_status, total_amount, booking_date FROM $table_bookings WHERE DATE(booking_date) = %s",
    $today_date
  ));

  $current_user = wp_get_current_user();
  $user_name    = !empty($current_user->display_name) ? $current_user->display_name : 'Master Administrator';
  $avatar_url   = get_avatar_url($current_user->ID, array('size' => 140));

  $hour = (int) current_time('G');
  if ($hour < 12) {
    $greeting = __('Good Morning', 'big-screen-cinemas');
  } elseif ($hour < 17) {
    $greeting = __('Good Afternoon', 'big-screen-cinemas');
  } else {
    $greeting = __('Good Evening', 'big-screen-cinemas');
  }

  bsc_render_page_shell_open('bsc-dashboard');
  ?>
  <style>
    /* ==========================================================================
       DASHBOARD ELEVATED STYLES
       ========================================================================== */
    .bsc-hero-glass-card {
      background: linear-gradient(135deg, #09171c 0%, #062624 50%, #07171d 100%);
      border: 1px solid rgba(13, 148, 136, 0.25);
      border-radius: var(--cinema-radius-lg);
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
      border-radius: var(--cinema-radius-md);
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
      font-family: var(--cinema-font-mono);
      color: #ffffff;
      letter-spacing: 1px;
      margin-top: 2px;
    }

    /* KPI Cards Deck */
    .bsc-kpi-deck-4 {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.25rem;
      margin-bottom: 2rem;
    }
    .bsc-kpi-tile {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: var(--cinema-radius-md);
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
      color: var(--cinema-text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.35rem;
    }
    .bsc-kpi-value {
      font-size: 1.75rem;
      font-weight: 900;
      font-family: var(--cinema-font-mono);
      color: var(--cinema-text-main);
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

    /* Matrix & Telemetry Arena */
    .bsc-theater-arena-grid {
      display: grid;
      grid-template-columns: 1fr 350px;
      gap: 1.5rem;
      align-items: start;
      margin-bottom: 2rem;
    }
    .bsc-screen-filter-bar {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: var(--cinema-radius-md);
      padding: 1.35rem 1.5rem;
      margin-bottom: 1.25rem;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
    }
    .bsc-monitor-cinema-box {
      background: #060e12;
      border: 1px solid #172a33;
      border-radius: var(--cinema-radius-lg);
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
      font-family: var(--cinema-font-mono);
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

    /* Telemetry Sidebar Box */
    .bsc-telemetry-panel {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: var(--cinema-radius-md);
      padding: 1.6rem;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
    }
    .bsc-chips-roster-box {
      display: flex;
      flex-wrap: wrap;
      gap: 0.45rem;
      min-height: 48px;
      padding: 0.65rem;
      background: #f8fafc;
      border: 1px solid var(--cinema-border);
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
      font-family: var(--cinema-font-mono);
    }
    .bsc-slot-metric-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.88rem;
      margin-bottom: 1.5rem;
    }
    .bsc-slot-metric-table td {
      padding: 0.55rem 0;
      border-bottom: 1px dashed var(--cinema-border-light);
    }
    .bsc-slot-metric-table tr.total-row td {
      border-bottom: none;
      padding-top: 0.85rem;
      font-size: 1rem;
      font-weight: 900;
    }

    @media (max-width: 1100px) {
      .bsc-kpi-deck-4 { grid-template-columns: repeat(2, 1fr); }
      .bsc-theater-arena-grid { grid-template-columns: 1fr; }
    }
  </style>

  <!-- 01. EXECUTIVE HERO BANNER -->
  <header class="bsc-hero-glass-card">
    <div class="bsc-hero-profile-unit">
      <img src="<?php echo esc_url($avatar_url); ?>" alt="Admin Avatar" class="bsc-hero-avatar-ring">
      <div class="bsc-hero-headings">
        <span class="cinema-badge-pill active" style="font-size:0.7rem; letter-spacing:0.06em;">
          ⚡ <?php esc_html_e('LIVE MULTIPLEX CONTROLLER', 'big-screen-cinemas'); ?>
        </span>
        <h1><?php echo esc_html(sprintf(__('%s, %s', 'big-screen-cinemas'), $greeting, $user_name)); ?></h1>
        <div class="bsc-hero-tags-row">
          <span><?php esc_html_e('Today\'s Passes:', 'big-screen-cinemas'); ?> <strong><?php echo intval($today_bookings_count); ?></strong></span>
          <span>•</span>
          <span><?php esc_html_e('Tickets Issued:', 'big-screen-cinemas'); ?> <strong><?php echo intval($today_tickets_sold); ?></strong></span>
          <span>•</span>
          <span style="color:#34d399; font-weight:800;">● <?php esc_html_e('BOX OFFICE ONLINE', 'big-screen-cinemas'); ?></span>
        </div>
      </div>
    </div>

    <div class="bsc-live-clock-deck">
      <div class="date-label"><?php echo esc_html(date_i18n('l, jS F Y')); ?></div>
      <div class="time-readout" id="cinemaLiveClockMaster"><?php echo esc_html(date_i18n('H:i:s')); ?></div>
    </div>
  </header>

  <!-- 02. TODAY'S FINANCIAL & TICKET KPI METRICS -->
  <div class="bsc-kpi-deck-4">
    
    <div class="bsc-kpi-tile">
      <div>
        <div class="bsc-kpi-title"><?php esc_html_e('Today\'s Revenue', 'big-screen-cinemas'); ?></div>
        <div class="bsc-kpi-value" style="color:#059669;">
          <?php echo esc_html($currency_symbol); ?> <?php echo number_format($today_revenue, 2); ?>
        </div>
      </div>
      <div class="bsc-kpi-icon-bubble" style="background:#ecfdf5; color:#059669;">৳</div>
    </div>

    <div class="bsc-kpi-tile">
      <div>
        <div class="bsc-kpi-title"><?php esc_html_e('Tickets Issued', 'big-screen-cinemas'); ?></div>
        <div class="bsc-kpi-value" style="color:#0284c7;">
          <?php echo number_format($today_tickets_sold); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted); font-weight:700;">Seats</span>
        </div>
      </div>
      <div class="bsc-kpi-icon-bubble" style="background:#f0f9ff; color:#0284c7;">🎟️</div>
    </div>

    <div class="bsc-kpi-tile">
      <div>
        <div class="bsc-kpi-title"><?php esc_html_e('Total Orders', 'big-screen-cinemas'); ?></div>
        <div class="bsc-kpi-value" style="color:#d97706;">
          <?php echo number_format($today_bookings_count); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted); font-weight:700;">Passes</span>
        </div>
      </div>
      <div class="bsc-kpi-icon-bubble" style="background:#fef3c7; color:#d97706;">🧾</div>
    </div>

    <div class="bsc-kpi-tile">
      <div>
        <div class="bsc-kpi-title"><?php esc_html_e('Counter / Online Split', 'big-screen-cinemas'); ?></div>
        <div class="bsc-kpi-value" style="font-size:1.15rem; margin-top:0.35rem;">
          <span style="color:#059669;">৳<?php echo number_format($today_cash_revenue, 0); ?></span>
          <span style="color:var(--cinema-text-muted); font-size:0.85rem;">/</span>
          <span style="color:#d946ef;">৳<?php echo number_format($today_bkash_revenue, 0); ?></span>
        </div>
      </div>
      <div class="bsc-kpi-icon-bubble" style="background:#fdf2f8; color:#db2777;">💳</div>
    </div>

  </div>

  <!-- 03. LIVE SCREENING SEATING MATRIX & TELEMETRY -->
  <div class="bsc-theater-arena-grid">
    
    <!-- Left: Screening Selector & 23-Seat Matrix Canvas -->
    <div>
      
      <div class="bsc-screen-filter-bar">
        <h3 class="cinema-panel-title" style="margin-bottom: 1rem;">🎬 <?php esc_html_e('Select Active Screening Slot', 'big-screen-cinemas'); ?></h3>
        <div class="bsc-form-grid-2">
          <div class="bsc-form-group" style="margin-bottom:0;">
            <label><?php esc_html_e('Feature Movie Presentation', 'big-screen-cinemas'); ?></label>
            <select id="monitorMovieSelect" required onchange="onMonitorMovieChange(this)">
              <?php if (!empty($movies_list)): foreach ($movies_list as $m): ?>
                <option value="<?php echo esc_attr($m->title); ?>" data-showtimes="<?php echo esc_attr($m->showtimes); ?>" data-audit="<?php echo esc_attr($m->auditorium); ?>" data-price="<?php echo esc_attr($m->ticket_price); ?>">
                  <?php echo esc_html($m->title); ?>
                </option>
              <?php endforeach; else: ?>
                <option value="Borbaad" data-showtimes='["14:30", "18:00", "21:30"]' data-audit="Auditorium 1 (23 Seats)" data-price="400">Borbaad</option>
              <?php endif; ?>
            </select>
          </div>

          <div class="bsc-form-group" style="margin-bottom:0;">
            <label><?php esc_html_e('Screening Time Slot', 'big-screen-cinemas'); ?></label>
            <select id="monitorShowtimeSelect" required onchange="renderLiveMatrix()">
              <option value="18:00">06:00 PM</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Matrix Seating Box -->
      <div class="bsc-monitor-cinema-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
          <div>
            <span class="cinema-badge-pill active" style="font-size:0.68rem;"><?php esc_html_e('BOUTIQUE 23-SEAT MATRIX', 'big-screen-cinemas'); ?></span>
            <h4 style="font-size: 1.15rem; font-weight: 900; margin: 0.35rem 0 0 0; color:#fff;"><?php esc_html_e('Live Auditorium Monitor', 'big-screen-cinemas'); ?></h4>
          </div>
          <span id="selectedSeatsCounter" style="font-family: var(--cinema-font-mono); font-size: 0.88rem; color: #ef4444; font-weight: 800; background:rgba(239,68,68,0.15); padding:0.25rem 0.65rem; border-radius:6px; border:1px solid rgba(239,68,68,0.3);">
            0 Seats Booked
          </span>
        </div>

        <div class="bsc-monitor-matrix-canvas">
          
          <!-- Row A (VIP - 5 Seats) -->
          <div class="bsc-seat-matrix-col">
            <div class="col-label" style="color:#f59e0b;">Row A (VIP)</div>
            <?php foreach (array('A1', 'A2', 'A3', 'A4', 'A5') as $s_id): ?>
              <div class="bsc-matrix-seat vip" id="matrix-seat-<?php echo esc_attr($s_id); ?>">
                <?php echo esc_html($s_id); ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Row B (Standard - 6 Seats) -->
          <div class="bsc-seat-matrix-col">
            <div class="col-label">Row B</div>
            <?php foreach (array('B1', 'B2', 'B3', 'B4', 'B5', 'B6') as $s_id): ?>
              <div class="bsc-matrix-seat standard" id="matrix-seat-<?php echo esc_attr($s_id); ?>">
                <?php echo esc_html($s_id); ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Row C (Standard - 6 Seats) -->
          <div class="bsc-seat-matrix-col">
            <div class="col-label">Row C</div>
            <?php foreach (array('C1', 'C2', 'C3', 'C4', 'C5', 'C6') as $s_id): ?>
              <div class="bsc-matrix-seat standard" id="matrix-seat-<?php echo esc_attr($s_id); ?>">
                <?php echo esc_html($s_id); ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Row D (Standard - 6 Seats) -->
          <div class="bsc-seat-matrix-col">
            <div class="col-label">Row D</div>
            <?php foreach (array('D1', 'D2', 'D3', 'D4', 'D5', 'D6') as $s_id): ?>
              <div class="bsc-matrix-seat standard" id="matrix-seat-<?php echo esc_attr($s_id); ?>">
                <?php echo esc_html($s_id); ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Right Screen Beacon -->
          <div class="bsc-matrix-screen-beacon">
            <span>S</span><span>C</span><span>R</span><span>E</span><span>E</span><span>N</span>
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" style="margin-top: 8px;">
              <path d="M5 12h14M13 5l7 7-7 7"/>
            </svg>
          </div>

        </div>

        <!-- Matrix Legend -->
        <div class="bsc-matrix-legend-row">
          <div class="bsc-legend-pill">
            <div class="dot" style="background: #0d9488;"></div>
            <span>Standard Available (<?php echo esc_html($currency_symbol); ?> <?php echo number_format($std_base_price, 0); ?>)</span>
          </div>
          <div class="bsc-legend-pill">
            <div class="dot" style="background: #b45309;"></div>
            <span>VIP Available (<?php echo esc_html($currency_symbol); ?> <?php echo number_format($vip_base_price, 0); ?>)</span>
          </div>
          <div class="bsc-legend-pill">
            <div class="dot" style="background: #ef4444;"></div>
            <span>Booked / Reserved Today</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Right: Telemetry Sidebar -->
    <div>
      <div class="bsc-telemetry-panel">
        <h3 class="cinema-panel-title" style="margin-bottom: 1.25rem;">📊 <?php esc_html_e('Slot Telemetry', 'big-screen-cinemas'); ?></h3>
        
        <div style="display: flex; justify-content: space-between; font-size: 0.82rem; font-weight: 800; margin-bottom: 0.35rem;">
          <span style="color: var(--cinema-text-muted);"><?php esc_html_e('Occupancy Percentage', 'big-screen-cinemas'); ?></span>
          <span style="color: var(--cinema-accent-teal);" id="lblOccupancyPercent">0%</span>
        </div>
        <div class="bsc-progress-bar-wrapper">
          <div class="bsc-progress-bar-fill" id="occupancyProgressBar" style="width: 0%;"></div>
        </div>

        <label style="font-weight: 800; font-size: 0.75rem; text-transform: uppercase; color: var(--cinema-text-muted); display: block; margin-bottom: 0.35rem;">
          <?php esc_html_e('Reserved Seats Roster', 'big-screen-cinemas'); ?>
        </label>
        <div class="bsc-chips-roster-box" id="selectedChipsDeck">
          <span style="font-size: 0.82rem; color: var(--cinema-text-muted);"><?php esc_html_e('No seats booked yet for this slot.', 'big-screen-cinemas'); ?></span>
        </div>

        <table class="bsc-slot-metric-table">
          <tbody>
            <tr>
              <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Auditorium Hall', 'big-screen-cinemas'); ?></td>
              <td style="text-align: right; font-weight: 800;" id="lblSlotAudit">Auditorium 1 (23 Seats)</td>
            </tr>
            <tr>
              <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Reserved Seats', 'big-screen-cinemas'); ?></td>
              <td style="text-align: right; font-weight: 800; color: #ef4444;" id="lblSlotBooked">0</td>
            </tr>
            <tr>
              <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Available Seats', 'big-screen-cinemas'); ?></td>
              <td style="text-align: right; font-weight: 800; color: #10b981;" id="lblSlotFree">23</td>
            </tr>
            <tr class="total-row">
              <td style="color: var(--cinema-text-main);"><?php esc_html_e('Estimated Slot Gross', 'big-screen-cinemas'); ?></td>
              <td style="text-align: right; color: var(--cinema-accent-teal); font-family: var(--cinema-font-mono);" id="lblSlotRevenue">
                <?php echo esc_html($currency_symbol); ?> 0.00
              </td>
            </tr>
          </tbody>
        </table>

        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=add')); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; text-align:center; padding:11px; font-weight:900; font-size: 0.95rem; text-decoration:none; display:block; border-radius:8px; box-shadow:0 4px 14px rgba(6,78,59,0.25);">
          🎟️ <?php esc_html_e('Manual Counter Booking', 'big-screen-cinemas'); ?>
        </a>
      </div>
    </div>

  </div>

  <!-- 04. TODAY'S SALES & TICKET TRANSACTION FEED -->
  <div class="cinema-panel" style="margin: 0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
    <div class="cinema-panel-header">
      <div>
        <h3 class="cinema-panel-title">🎟️ <?php esc_html_e('Today\'s Admission Passes & Orders', 'big-screen-cinemas'); ?> (<?php echo count($today_bookings_list); ?>)</h3>
        <span style="font-size:0.78rem; color:var(--cinema-text-muted);"><?php echo esc_html(sprintf(__('Showing transactions logged on %s', 'big-screen-cinemas'), date_i18n('l, jS F Y'))); ?></span>
      </div>
      <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=all')); ?>" class="button button-secondary" style="font-weight:700;">
        <?php esc_html_e('View Complete Booking Archives', 'big-screen-cinemas'); ?> →
      </a>
    </div>

    <div class="cinema-table-wrap">
      <table class="cinema-data-table">
        <thead>
          <tr>
            <th>TOKEN</th>
            <th>CUSTOMER</th>
            <th>MOVIE &amp; AUDITORIUM</th>
            <th>SHOWTIME</th>
            <th>SEATS</th>
            <th>METHOD</th>
            <th>TOTAL AMOUNT</th>
            <th>TIME</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($today_bookings_list)): foreach ($today_bookings_list as $b): ?>
            <tr>
              <td><span class="cinema-voucher-tag"><?php echo esc_html($b->booking_token); ?></span></td>
              <td>
                <strong><?php echo esc_html($b->customer_name ?: 'Walk-In Guest'); ?></strong><br>
                <small style="color:var(--cinema-text-muted);"><?php echo esc_html($b->customer_phone ?: 'No phone'); ?></small>
              </td>
              <td>
                <strong><?php echo esc_html($b->movie_title); ?></strong><br>
                <small style="color:var(--cinema-text-muted);"><?php echo esc_html($b->auditorium); ?></small>
              </td>
              <td><span class="cinema-badge-pill active" style="font-size:0.68rem;"><?php echo esc_html($b->showtime ?? 'Counter'); ?></span></td>
              <td><code><?php echo esc_html($b->seats); ?></code></td>
              <td><span class="cinema-badge-payment"><?php echo esc_html($b->payment_method ?? 'Cash'); ?></span></td>
              <td><strong style="color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono);"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($b->total_amount, 2); ?></strong></td>
              <td><small style="color:var(--cinema-text-muted);"><?php echo esc_html(date_i18n('h:i A', strtotime($b->booking_date))); ?></small></td>
            </tr>
          <?php endforeach; else: ?>
            <tr>
              <td colspan="8" style="text-align:center; padding: 2.5rem; color:var(--cinema-text-muted);">
                <?php esc_html_e('No ticket bookings recorded yet for today.', 'big-screen-cinemas'); ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 05. LIVE TELEMETRY JAVASCRIPT LOGIC -->
  <script>
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
      if (el) el.innerText = `${hrs}:${mins}:${secs}`;
    }
    setInterval(updateLiveClockMaster, 1000);
    updateLiveClockMaster();

    function formatSlotTime(timeStr) {
      if (!timeStr) return '6:00 PM';
      if (timeStr.includes(':') && timeStr.length <= 5) {
        const [h, m] = timeStr.split(':');
        const hour = parseInt(h, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const formattedHour = hour % 12 || 12;
        return `${String(formattedHour).padStart(2, '0')}:${m} ${ampm}`;
      }
      return timeStr;
    }

    function onMonitorMovieChange(selectEl) {
      const opt = selectEl.options[selectEl.selectedIndex];
      if (!opt) return;

      let showtimesRaw = opt.getAttribute('data-showtimes') || '["18:00"]';
      const audit = opt.getAttribute('data-audit') || 'Auditorium 1 (23 Seats)';
      
      const lblSlotAudit = document.getElementById('lblSlotAudit');
      if (lblSlotAudit) lblSlotAudit.innerText = audit;

      const showtimeSelect = document.getElementById('monitorShowtimeSelect');
      showtimeSelect.innerHTML = '';

      let slots = [];
      try {
        const parsed = JSON.parse(showtimesRaw);
        if (Array.isArray(parsed)) slots = parsed;
        else slots = [showtimesRaw];
      } catch(e) {
        slots = showtimesRaw.split(',');
      }

      slots.forEach(slot => {
        const trimmed = slot.trim();
        if (trimmed) {
          const o = document.createElement('option');
          o.value = trimmed;
          o.innerText = formatSlotTime(trimmed);
          showtimeSelect.appendChild(o);
        }
      });

      renderLiveMatrix();
    }

    function renderLiveMatrix() {
      const movieSelect    = document.getElementById('monitorMovieSelect');
      const showtimeSelect = document.getElementById('monitorShowtimeSelect');

      if (!movieSelect || !showtimeSelect) return;

      const currentMovie    = movieSelect.value.trim().toLowerCase();
      const currentShowtime = showtimeSelect.value.trim().toLowerCase();

      const occupiedSeats = [];
      let calculatedSlotRevenue = 0;

      dbTodayBookings.forEach(b => {
        const isMovieMatch = b.movie_title.trim().toLowerCase() === currentMovie;
        const bShowtime    = (b.showtime || '').trim().toLowerCase();
        const isTimeMatch  = !bShowtime || bShowtime === 'walk-in counter' || bShowtime === currentShowtime;

        if (isMovieMatch && isTimeMatch && b.seats) {
          calculatedSlotRevenue += parseFloat(b.total_amount || 0);
          const parts = b.seats.split(',');
          parts.forEach(p => {
            const trimmed = p.trim();
            if (trimmed && !occupiedSeats.includes(trimmed)) {
              occupiedSeats.push(trimmed);
            }
          });
        }
      });

      // Update seat visuals
      allSeatIds.forEach(seatId => {
        const seatEl = document.getElementById(`matrix-seat-${seatId}`);
        if (!seatEl) return;

        if (occupiedSeats.includes(seatId)) {
          seatEl.className = 'bsc-matrix-seat occupied';
          seatEl.title = `Seat ${seatId} [OCCUPIED TODAY]`;
        } else {
          const isVip = seatId.startsWith('A');
          seatEl.className = isVip ? 'bsc-matrix-seat vip' : 'bsc-matrix-seat standard';
          seatEl.title = `Seat ${seatId} [AVAILABLE]`;
        }
      });

      // Update telemetry numbers
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

      if (counter) counter.innerText = `${bookedCount} Seats Booked`;
      if (elBooked) elBooked.innerText = bookedCount;
      if (elFree) elFree.innerText = freeCount;
      if (elPct) elPct.innerText = `${percent}%`;
      if (elBar) elBar.style.width = `${percent}%`;
      if (elSlotRevenue) elSlotRevenue.innerText = `${CURRENCY_TAG} ${calculatedSlotRevenue.toFixed(2)}`;

      if (chipsDeck) {
        if (occupiedSeats.length === 0) {
          chipsDeck.innerHTML = `<span style="font-size: 0.82rem; color: var(--cinema-text-muted);">No seats booked yet for this slot.</span>`;
        } else {
          chipsDeck.innerHTML = occupiedSeats.map(s => `<span class="bsc-roster-badge">${s}</span>`).join('');
        }
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      const movieSelect = document.getElementById('monitorMovieSelect');
      if (movieSelect) {
        onMonitorMovieChange(movieSelect);
      }
    });
  </script>
  <?php
  bsc_render_page_shell_close();
}