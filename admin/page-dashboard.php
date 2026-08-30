<?php
if (!defined('ABSPATH')) exit;

function bsc_render_dashboard_page() {
  global $wpdb;
  $table_bookings = $wpdb->prefix . 'bsc_bookings';
  $table_movies   = $wpdb->prefix . 'bsc_control_movies';
  $table_ledger   = $wpdb->prefix . 'bsc_ledger';

  $today_date = current_time('Y-m-d');

  // Base pricing defaults
  $vip_base_price = floatval(get_option('bsc_vip_price', 500));
  $std_base_price = floatval(get_option('bsc_standard_price', 400));

  // Today's Core Metrics
  $today_bookings_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_bookings WHERE DATE(booking_date) = %s",
    $today_date
  ));

  $today_revenue = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount) FROM $table_ledger WHERE DATE(transaction_date) = %s",
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

  // Today's Payment Method Split (Cash vs bKash)
  $today_cash_revenue = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount) FROM $table_ledger WHERE DATE(transaction_date) = %s AND (method = 'Cash' OR method LIKE '%%Cash%%')",
    $today_date
  ));
  $today_bkash_revenue = (float) $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount) FROM $table_ledger WHERE DATE(transaction_date) = %s AND (method = 'bKash' OR method LIKE '%%bKash%%' OR method LIKE '%%Online%%')",
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
  $user_name    = !empty($current_user->display_name) ? $current_user->display_name : 'Admin';
  $avatar_url   = get_avatar_url($current_user->ID, array('size' => 128));

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
    /* Executive Metric Counters */
    .bsc-today-kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.25rem;
      margin-bottom: 2rem;
    }

    .bsc-kpi-card {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: var(--cinema-radius-md);
      padding: 1.25rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    }

    .bsc-kpi-card .tag-label {
      font-size: 0.72rem;
      text-transform: uppercase;
      font-weight: 800;
      color: var(--cinema-text-muted);
      letter-spacing: 0.05em;
    }

    .bsc-kpi-card .metric-number {
      font-size: 1.65rem;
      font-weight: 900;
      color: var(--cinema-text-main);
      font-family: var(--cinema-font-mono);
      margin-top: 0.25rem;
      line-height: 1.1;
    }

    .bsc-kpi-badge-icon {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
    }

    /* Live Screening Seat Monitor Layout (Identical to Booking Plan) */
    .bsc-monitor-builder-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 1.5rem;
      align-items: start;
      margin-bottom: 2rem;
    }

    .bsc-seat-matrix-card {
      background: #0b171b;
      border-radius: var(--cinema-radius-md);
      padding: 1.5rem;
      color: #fff;
      border: 1px solid #1e293b;
    }

    .bsc-seat-matrix-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1.25rem;
      padding: 1.5rem 0;
      overflow-x: auto;
    }

    .bsc-seat-column {
      display: flex;
      flex-direction: column;
      gap: 0.6rem;
      align-items: center;
    }

    .bsc-col-header {
      font-size: 0.75rem;
      font-weight: 800;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.25rem;
    }

    .bsc-seat-box {
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
      position: relative;
    }

    .bsc-seat-box.vip {
      background: #451a03;
      border-color: #b45309;
      color: #fde68a;
    }

    .bsc-seat-box.standard {
      background: #0f2e28;
      border-color: #0d9488;
      color: #99f6e4;
    }

    .bsc-seat-box.occupied {
      background: #1f2937 !important;
      border-color: #ef4444 !important;
      color: #f87171 !important;
      opacity: 0.9;
      box-shadow: 0 0 10px rgba(239, 68, 68, 0.35);
    }

    .bsc-screen-indicator {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding-left: 1rem;
      border-left: 2px dashed rgba(255, 255, 255, 0.15);
      color: #0d9488;
      font-weight: 900;
      font-size: 0.75rem;
      letter-spacing: 2px;
    }

    .bsc-seat-legend {
      display: flex;
      flex-wrap: wrap;
      gap: 1.25rem;
      justify-content: center;
      padding-top: 1rem;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 0.78rem;
    }

    .bsc-legend-item {
      display: flex;
      align-items: center;
      gap: 0.45rem;
      color: #cbd5e1;
    }

    .bsc-legend-dot {
      width: 14px;
      height: 14px;
      border-radius: 4px;
    }

    .bsc-summary-sidebar {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: var(--cinema-radius-md);
      padding: 1.5rem;
    }

    .bsc-selected-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 0.4rem;
      min-height: 38px;
      padding: 0.5rem;
      background: #f8fafc;
      border: 1px solid var(--cinema-border);
      border-radius: 6px;
      margin-bottom: 1rem;
      align-items: center;
    }

    .bsc-seat-chip {
      background: #ef4444;
      color: #fff;
      font-size: 0.75rem;
      font-weight: 800;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      font-family: var(--cinema-font-mono);
    }

    .bsc-progress-bar-wrapper {
      background: #e2e8f0;
      border-radius: 9999px;
      height: 10px;
      overflow: hidden;
      margin: 0.5rem 0 1.25rem 0;
    }

    .bsc-progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #10b981, #0d9488);
      border-radius: 9999px;
      transition: width 0.4s ease;
    }

    .bsc-bill-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
      margin-bottom: 1.25rem;
    }

    .bsc-bill-table td {
      padding: 0.45rem 0;
      border-bottom: 1px dashed var(--cinema-border);
    }

    .bsc-bill-table tr.grand-row td {
      border-bottom: none;
      padding-top: 0.75rem;
      font-size: 1rem;
      font-weight: 900;
    }
  </style>

  <!-- 01. DASHBOARD HERO BANNER (LIVE CLOCK + ADMIN GREETING) -->
  <header class="cinema-hero-banner">
    <div class="cinema-hero-user-block">
      <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar" class="cinema-hero-avatar-large">
      <div class="cinema-hero-meta">
        <span class="cinema-badge-pill active"><?php esc_html_e('TODAY\'S MASTER CONTROLLER', 'big-screen-cinemas'); ?></span>
        <h1><?php echo esc_html(sprintf(__('%s, %s', 'big-screen-cinemas'), $greeting, $user_name)); ?></h1>
        <div class="cinema-hero-stats-strip">
          <span><?php esc_html_e('Today\'s Passes:', 'big-screen-cinemas'); ?> <strong><?php echo intval($today_bookings_count); ?></strong></span>
          <span>•</span>
          <span><?php esc_html_e('Today\'s Sold Seats:', 'big-screen-cinemas'); ?> <strong><?php echo intval($today_tickets_sold); ?></strong></span>
          <span>•</span>
          <span style="color:#34d399; font-weight:700;">● <?php esc_html_e('BOX OFFICE ACTIVE', 'big-screen-cinemas'); ?></span>
        </div>
      </div>
    </div>

    <div class="cinema-clock-card">
      <div class="date"><?php echo esc_html(date_i18n('l, jS F Y')); ?></div>
      <div class="time" id="cinemaLiveClockMaster"><?php echo esc_html(date_i18n('H:i:s')); ?></div>
    </div>
  </header>

  <!-- 02. TODAY'S EXECUTIVE KPI METRICS BAR -->
  <div class="bsc-today-kpi-grid">
    <div class="bsc-kpi-card">
      <div>
        <div class="tag-label"><?php esc_html_e('Today\'s Gross Revenue', 'big-screen-cinemas'); ?></div>
        <div class="metric-number" style="color:#059669;">৳<?php echo number_format($today_revenue, 2); ?></div>
      </div>
      <div class="bsc-kpi-badge-icon" style="background:#ecfdf5; color:#10b981;">৳</div>
    </div>

    <div class="bsc-kpi-card">
      <div>
        <div class="tag-label"><?php esc_html_e('Today\'s Tickets Sold', 'big-screen-cinemas'); ?></div>
        <div class="metric-number"><?php echo number_format($today_tickets_sold); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted);">Seats</span></div>
      </div>
      <div class="bsc-kpi-badge-icon" style="background:#eff6ff; color:#3b82f6;">🎟️</div>
    </div>

    <div class="bsc-kpi-card">
      <div>
        <div class="tag-label"><?php esc_html_e('Today\'s Transactions', 'big-screen-cinemas'); ?></div>
        <div class="metric-number"><?php echo number_format($today_bookings_count); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted);">Orders</span></div>
      </div>
      <div class="bsc-kpi-badge-icon" style="background:#fef3c7; color:#d97706;">🧾</div>
    </div>

    <div class="bsc-kpi-card">
      <div>
        <div class="tag-label"><?php esc_html_e('Cash / bKash Ratio', 'big-screen-cinemas'); ?></div>
        <div class="metric-number" style="font-size:1.15rem; margin-top:0.35rem;">
          <span style="color:#059669;">৳<?php echo number_format($today_cash_revenue, 0); ?></span>
          <span style="color:var(--cinema-text-muted); font-size:0.85rem;">/</span>
          <span style="color:#d946ef;">৳<?php echo number_format($today_bkash_revenue, 0); ?></span>
        </div>
      </div>
      <div class="bsc-kpi-badge-icon" style="background:#fdf2f8; color:#db2777;">💳</div>
    </div>
  </div>

  <!-- 03. TODAY'S LIVE SEAT OCCUPANCY MATRIX (SAME EXACT SEAT PLAN LAYOUT) -->
  <div class="bsc-monitor-builder-grid">
    
    <!-- Left: Screening Selector & Exact 23-Seat Matrix -->
    <div>
      <!-- Filter Selector Panel -->
      <div class="cinema-panel" style="margin-bottom: 1.5rem;">
        <h3 class="cinema-panel-title" style="margin-bottom: 1.25rem;">🎬 <?php esc_html_e('1. Today\'s Screening Filter', 'big-screen-cinemas'); ?></h3>
        
        <div class="bsc-form-grid-2">
          <div class="bsc-form-group" style="margin-bottom:0;">
            <label><?php esc_html_e('Select Feature Movie', 'big-screen-cinemas'); ?> *</label>
            <select id="monitorMovieSelect" required onchange="onMonitorMovieChange(this)">
              <?php if (!empty($movies_list)): foreach ($movies_list as $m): ?>
                <option value="<?php echo esc_attr($m->title); ?>" data-showtimes="<?php echo esc_attr($m->showtimes); ?>" data-audit="<?php echo esc_attr($m->auditorium); ?>" data-price="<?php echo esc_attr($m->ticket_price); ?>">
                  <?php echo esc_html($m->title); ?>
                </option>
              <?php endforeach; else: ?>
                <option value="FOOD COMBO (EVIL DEAD BURN)" data-showtimes="2:30 PM, 6:00 PM, 9:45 PM" data-audit="Auditorium 1 (23 Seats)" data-price="500">FOOD COMBO (EVIL DEAD BURN)</option>
              <?php endif; ?>
            </select>
          </div>

          <div class="bsc-form-group" style="margin-bottom:0;">
            <label><?php esc_html_e('Select Showtime Slot', 'big-screen-cinemas'); ?> *</label>
            <select id="monitorShowtimeSelect" required onchange="renderLiveMatrix()">
              <option value="6:00 PM">6:00 PM</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Exact 23-Seat Matrix Plan Layout -->
      <div class="bsc-seat-matrix-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
          <div>
            <span class="cinema-badge-pill active" style="font-size: 0.68rem;"><?php esc_html_e('BOUTIQUE 23-SEAT MATRIX', 'big-screen-cinemas'); ?></span>
            <h4 style="font-size: 1.15rem; font-weight: 900; margin: 0.35rem 0 0 0; color:#fff;"><?php esc_html_e('Today\'s Live Screening Seating Monitor', 'big-screen-cinemas'); ?></h4>
          </div>
          <span id="selectedSeatsCounter" style="font-family: var(--cinema-font-mono); font-size: 0.85rem; color: #ef4444; font-weight: 800;">
            0 Seats Booked
          </span>
        </div>

        <div class="bsc-seat-matrix-wrapper">
          
          <!-- Column 1: Row A (VIP - 5 Seats) -->
          <div class="bsc-seat-column">
            <div class="bsc-col-header">Row A (VIP)</div>
            <?php foreach (array('A1', 'A2', 'A3', 'A4', 'A5') as $s_id): ?>
              <div class="bsc-seat-box vip" id="matrix-seat-<?php echo esc_attr($s_id); ?>" data-seat="<?php echo esc_attr($s_id); ?>">
                <?php echo esc_html($s_id); ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Column 2: Row B (Standard - 6 Seats) -->
          <div class="bsc-seat-column">
            <div class="bsc-col-header">Row B</div>
            <?php foreach (array('B1', 'B2', 'B3', 'B4', 'B5', 'B6') as $s_id): ?>
              <div class="bsc-seat-box standard" id="matrix-seat-<?php echo esc_attr($s_id); ?>" data-seat="<?php echo esc_attr($s_id); ?>">
                <?php echo esc_html($s_id); ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Column 3: Row C (Standard - 6 Seats) -->
          <div class="bsc-seat-column">
            <div class="bsc-col-header">Row C</div>
            <?php foreach (array('C1', 'C2', 'C3', 'C4', 'C5', 'C6') as $s_id): ?>
              <div class="bsc-seat-box standard" id="matrix-seat-<?php echo esc_attr($s_id); ?>" data-seat="<?php echo esc_attr($s_id); ?>">
                <?php echo esc_html($s_id); ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Column 4: Row D (Standard - 6 Seats) -->
          <div class="bsc-seat-column">
            <div class="bsc-col-header">Row D</div>
            <?php foreach (array('D1', 'D2', 'D3', 'D4', 'D5', 'D6') as $s_id): ?>
              <div class="bsc-seat-box standard" id="matrix-seat-<?php echo esc_attr($s_id); ?>" data-seat="<?php echo esc_attr($s_id); ?>">
                <?php echo esc_html($s_id); ?>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Right Screen Indicator -->
          <div class="bsc-screen-indicator">
            <span>S</span><span>C</span><span>R</span><span>E</span><span>E</span><span>N</span>
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" style="margin-top: 8px;">
              <path d="M5 12h14M13 5l7 7-7 7"/>
            </svg>
          </div>

        </div>

        <!-- Legend -->
        <div class="bsc-seat-legend">
          <div class="bsc-legend-item">
            <div class="bsc-legend-dot" style="background: #0d9488;"></div>
            <span>Standard Available (৳<?php echo esc_html($std_base_price); ?>)</span>
          </div>
          <div class="bsc-legend-item">
            <div class="bsc-legend-dot" style="background: #b45309;"></div>
            <span>VIP Available (৳<?php echo esc_html($vip_base_price); ?>)</span>
          </div>
          <div class="bsc-legend-item">
            <div class="bsc-legend-dot" style="background: #ef4444;"></div>
            <span>Booked / Occupied (Today)</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Telemetry & Slot Summary -->
    <div>
      <div class="bsc-summary-sidebar">
        <h3 class="cinema-panel-title" style="margin-bottom: 1.25rem;">📊 <?php esc_html_e('Today\'s Slot Status', 'big-screen-cinemas'); ?></h3>
        
        <div style="display: flex; justify-content: space-between; font-size: 0.82rem; font-weight: 800; margin-bottom: 0.35rem;">
          <span style="color: var(--cinema-text-muted);"><?php esc_html_e('Slot Occupancy Rate', 'big-screen-cinemas'); ?></span>
          <span style="color: var(--cinema-accent-teal);" id="lblOccupancyPercent">0%</span>
        </div>
        <div class="bsc-progress-bar-wrapper">
          <div class="bsc-progress-bar-fill" id="occupancyProgressBar" style="width: 0%;"></div>
        </div>

        <label style="font-weight: 800; font-size: 0.8rem; text-transform: uppercase; color: var(--cinema-text-muted); display: block; margin-bottom: 0.35rem;">
          <?php esc_html_e('Booked Seats Roster', 'big-screen-cinemas'); ?>
        </label>
        <div class="bsc-selected-chips" id="selectedChipsDeck">
          <span style="font-size: 0.8rem; color: var(--cinema-text-muted);"><?php esc_html_e('No seats booked yet for this slot.', 'big-screen-cinemas'); ?></span>
        </div>

        <!-- Structured Slot Breakdown -->
        <table class="bsc-bill-table">
          <tbody>
            <tr>
              <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Auditorium Hall', 'big-screen-cinemas'); ?></td>
              <td style="text-align: right; font-weight: 700;" id="lblSlotAudit">Auditorium 1 (23 Seats)</td>
            </tr>
            <tr>
              <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Booked Seats', 'big-screen-cinemas'); ?></td>
              <td style="text-align: right; font-weight: 700; color: #ef4444;" id="lblSlotBooked">0</td>
            </tr>
            <tr>
              <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Available Seats', 'big-screen-cinemas'); ?></td>
              <td style="text-align: right; font-weight: 700; color: #10b981;" id="lblSlotFree">23</td>
            </tr>
            <tr class="grand-row">
              <td style="color: var(--cinema-text-main);"><?php esc_html_e('Estimated Slot Gross', 'big-screen-cinemas'); ?></td>
              <td style="text-align: right; color: var(--cinema-accent-teal); font-family: var(--cinema-font-mono);" id="lblSlotRevenue">BDT 0.00</td>
            </tr>
          </tbody>
        </table>

        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=add')); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; text-align:center; padding:10px; font-weight:900; font-size: 0.95rem; text-decoration:none; display:block;">
          🎟️ <?php esc_html_e('Book Open Seats', 'big-screen-cinemas'); ?>
        </a>
      </div>
    </div>

  </div>

  <!-- 04. TODAY'S SALES & TICKET TRANSACTION FEED -->
  <div class="cinema-panel" style="margin: 0;">
    <div class="cinema-panel-header">
      <div>
        <h3 class="cinema-panel-title">🎟️ <?php esc_html_e('Today\'s Admission Passes & Orders', 'big-screen-cinemas'); ?> (<?php echo count($today_bookings_list); ?>)</h3>
        <span style="font-size:0.78rem; color:var(--cinema-text-muted);"><?php echo esc_html(sprintf(__('Showing transactions logged on %s', 'big-screen-cinemas'), date_i18n('l, jS F Y'))); ?></span>
      </div>
      <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=all')); ?>" class="button button-secondary">
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
              <td><strong style="color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono);">BDT <?php echo number_format($b->total_amount, 2); ?></strong></td>
              <td><small style="color:var(--cinema-text-muted);"><?php echo esc_html(date_i18n('h:i A', strtotime($b->booking_date))); ?></small></td>
            </tr>
          <?php endforeach; else: ?>
            <tr>
              <td colspan="8" style="text-align:center; padding: 2rem; color:var(--cinema-text-muted);">
                <?php esc_html_e('No ticket bookings recorded yet for today.', 'big-screen-cinemas'); ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- 05. LIVE CLOCK & SEAT MATRIX SCRIPT -->
  <script>
    const dbTodayBookings = <?php echo wp_json_encode($today_bookings_for_js ?: array()); ?>;
    const VIP_PRICE = <?php echo floatval($vip_base_price); ?>;
    const STD_PRICE = <?php echo floatval($std_base_price); ?>;
    const TOTAL_CAPACITY = 23;
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

    function onMonitorMovieChange(selectEl) {
      const opt = selectEl.options[selectEl.selectedIndex];
      if (!opt) return;

      const showtimes = opt.getAttribute('data-showtimes') || '2:30 PM, 6:00 PM, 9:45 PM';
      const audit = opt.getAttribute('data-audit') || 'Auditorium 1 (23 Seats)';
      
      const lblSlotAudit = document.getElementById('lblSlotAudit');
      if (lblSlotAudit) lblSlotAudit.innerText = audit;

      const showtimeSelect = document.getElementById('monitorShowtimeSelect');
      showtimeSelect.innerHTML = '';
      const slots = showtimes.split(',');
      slots.forEach(slot => {
        const o = document.createElement('option');
        o.value = slot.trim();
        o.innerText = slot.trim();
        showtimeSelect.appendChild(o);
      });

      renderLiveMatrix();
    }

    function renderLiveMatrix() {
      const movieSelect = document.getElementById('monitorMovieSelect');
      const showtimeSelect = document.getElementById('monitorShowtimeSelect');

      if (!movieSelect || !showtimeSelect) return;

      const currentMovie = movieSelect.value;
      const currentShowtime = showtimeSelect.value;

      // Filter occupied seats matching today's selected movie & showtime
      const occupiedSeats = [];
      let calculatedSlotRevenue = 0;

      dbTodayBookings.forEach(b => {
        const isMovieMatch = b.movie_title.trim().toLowerCase() === currentMovie.trim().toLowerCase();
        const isTimeMatch  = !b.showtime || b.showtime === 'Walk-In Counter' || b.showtime.trim().toLowerCase() === currentShowtime.trim().toLowerCase();

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

      // Update seat visuals matching the 23-seat matrix
      allSeatIds.forEach(seatId => {
        const seatEl = document.getElementById(`matrix-seat-${seatId}`);
        if (!seatEl) return;

        if (occupiedSeats.includes(seatId)) {
          seatEl.className = 'bsc-seat-box occupied';
          seatEl.title = `Seat ${seatId} [OCCUPIED TODAY]`;
        } else {
          const isVip = seatId.startsWith('A');
          seatEl.className = isVip ? 'bsc-seat-box vip' : 'bsc-seat-box standard';
          seatEl.title = `Seat ${seatId} [AVAILABLE]`;
        }
      });

      // Update occupancy & slot numbers
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
      if (elSlotRevenue) elSlotRevenue.innerText = `BDT ${calculatedSlotRevenue.toFixed(2)}`;

      if (chipsDeck) {
        if (occupiedSeats.length === 0) {
          chipsDeck.innerHTML = `<span style="font-size: 0.8rem; color: var(--cinema-text-muted);">No seats booked yet for this slot.</span>`;
        } else {
          chipsDeck.innerHTML = occupiedSeats.map(s => `<span class="bsc-seat-chip">${s}</span>`).join('');
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