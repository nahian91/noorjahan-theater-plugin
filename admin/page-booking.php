<?php
if (!defined('ABSPATH')) exit;

function bsc_render_booking_page() {
  global $wpdb;
  $table_bookings = $wpdb->prefix . 'bsc_bookings';
  $table_movies   = $wpdb->prefix . 'bsc_control_movies';

  $subtab = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'all';

  // Handle Edit Submission
  if (isset($_POST['bsc_action']) && $_POST['bsc_action'] === 'edit_booking') {
    if (check_admin_referer('bsc_edit_booking_verify')) {
      $edit_id = intval($_POST['booking_id']);
      $wpdb->update($table_bookings, array(
        'customer_name'  => sanitize_text_field($_POST['customer_name']),
        'customer_phone' => sanitize_text_field($_POST['customer_phone']),
        'customer_email' => sanitize_email($_POST['customer_email']),
        'seats'          => sanitize_text_field($_POST['seats']),
        'total_amount'   => floatval($_POST['total_amount']),
        'payment_method' => sanitize_text_field($_POST['payment_method']),
        'payment_status' => sanitize_text_field($_POST['payment_status'])
      ), array('id' => $edit_id));

      wp_redirect(admin_url('admin.php?page=bsc-booking&subtab=all&booking_updated=true'));
      exit;
    }
  }

  // Handle Delete Action
  if ($subtab === 'delete' && isset($_GET['id'])) {
    $del_id = intval($_GET['id']);
    if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'bsc_delete_booking_' . $del_id)) {
      $wpdb->delete($table_bookings, array('id' => $del_id));
      wp_redirect(admin_url('admin.php?page=bsc-booking&subtab=all&booking_deleted=true'));
      exit;
    }
  }

  $bookings = $wpdb->get_results("SELECT * FROM $table_bookings ORDER BY id DESC LIMIT 50");
  $movies   = $wpdb->get_results("SELECT * FROM $table_movies ORDER BY id DESC");

  // Get pricing configuration options
  $vip_base_price = floatval(get_option('bsc_vip_price', 500));
  $std_base_price = floatval(get_option('bsc_standard_price', 400));

  // Determine occupied seats from existing bookings
  $occupied_seats_raw = $wpdb->get_col("SELECT seats FROM $table_bookings");
  $all_occupied = array();
  if (!empty($occupied_seats_raw)) {
    foreach ($occupied_seats_raw as $s_str) {
      $parts = explode(',', $s_str);
      foreach ($parts as $p) {
        $trimmed = trim($p);
        if (!empty($trimmed)) {
          $all_occupied[] = $trimmed;
        }
      }
    }
  }
  $all_occupied = array_unique($all_occupied);

  bsc_render_page_shell_open('bsc-booking');
  ?>
  <style>
    /* Seat Map & Visual Booking Styles */
    .bsc-booking-builder-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 1.5rem;
      align-items: start;
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
      background: #1e293b;
      color: #cbd5e1;
      font-weight: 800;
      font-size: 0.8rem;
      font-family: var(--cinema-font-mono);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      user-select: none;
    }
    .bsc-seat-box:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
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
    .bsc-seat-box.selected {
      background: #f59e0b !important;
      border-color: #fbbf24 !important;
      color: #000 !important;
      box-shadow: 0 0 12px rgba(245, 158, 11, 0.6);
      transform: scale(1.05);
    }
    .bsc-seat-box:disabled, .bsc-seat-box.occupied {
      background: #1f2937 !important;
      border-color: #374151 !important;
      color: #4b5563 !important;
      cursor: not-allowed;
      opacity: 0.4;
      transform: none !important;
      box-shadow: none !important;
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
      background: #0d9488;
      color: #fff;
      font-size: 0.75rem;
      font-weight: 800;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      font-family: var(--cinema-font-mono);
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

    /* Cash / bKash Payment Options */
    .bsc-payment-grid-two {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
      margin-top: 0.35rem;
    }
    .bsc-payment-option {
      position: relative;
    }
    .bsc-payment-option input[type="radio"] {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
    }
    .bsc-payment-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 0.75rem 0.5rem;
      border: 1.5px solid var(--cinema-border);
      border-radius: var(--cinema-radius-sm);
      background: #f8fafc;
      cursor: pointer;
      transition: all 0.2s ease;
      text-align: center;
    }
    .bsc-payment-card .icon {
      font-size: 1.2rem;
      margin-bottom: 0.15rem;
    }
    .bsc-payment-card .title {
      font-size: 0.85rem;
      font-weight: 800;
      color: var(--cinema-text-main);
    }
    .bsc-payment-card .sub {
      font-size: 0.68rem;
      color: var(--cinema-text-muted);
    }
    .bsc-payment-option input[type="radio"]:checked + .bsc-payment-card {
      border-color: #0d9488;
      background: #f0fdfa;
      box-shadow: 0 0 0 1px #0d9488;
    }
    .bsc-payment-option input[type="radio"]:checked + .bsc-payment-card .title {
      color: #0d9488;
    }

    /* Modal Styling for Booking Details */
    .bsc-modal-overlay {
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.75);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 99999;
    }
    .bsc-modal-box {
      background: #ffffff;
      border-radius: 16px;
      width: 100%;
      max-width: 520px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      overflow: hidden;
      animation: bscModalPop 0.25s ease-out;
    }
    @keyframes bscModalPop {
      0% { transform: scale(0.95); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }
    .bsc-modal-header {
      background: linear-gradient(135deg, #0b171b 0%, #0d9488 100%);
      color: #fff;
      padding: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .bsc-modal-body {
      padding: 1.75rem;
      max-height: 75vh;
      overflow-y: auto;
    }
    .bsc-modal-footer {
      background: #f8fafc;
      padding: 1rem 1.75rem;
      border-top: 1px solid var(--cinema-border);
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
    }
  </style>

  <div class="bsc-subtabs-nav">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=all')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'all') ? 'active' : ''; ?>"><?php esc_html_e('All Bookings', 'big-screen-cinemas'); ?></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=add')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'add') ? 'active' : ''; ?>"><?php esc_html_e('Add Booking', 'big-screen-cinemas'); ?></a>
  </div>

  <?php if (isset($_GET['booking_added'])): ?>
    <div style="background:#ecfdf5; color:#10b981; padding:12px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      <?php esc_html_e('Walk-in booking issued successfully to customer!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['booking_updated'])): ?>
    <div style="background:#ecfdf5; color:#10b981; padding:12px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      <?php esc_html_e('Booking details updated successfully!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['booking_deleted'])): ?>
    <div style="background:#fef2f2; color:#ef4444; padding:12px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      <?php esc_html_e('Booking record deleted successfully.', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php if ($subtab === 'add'): ?>
    <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bscBookingForm">
      <?php wp_nonce_field('bsc_manual_booking_verify'); ?>
      <input type="hidden" name="action" value="bsc_manual_booking">
      <input type="hidden" name="manual_seats" id="input_manual_seats" required value="">
      <input type="hidden" name="manual_amount" id="input_manual_amount" required value="0">
      <input type="hidden" name="manual_audit" id="input_manual_audit" value="Auditorium 1 (23 Seats)">

      <div class="bsc-booking-builder-grid">
        
        <!-- Left: Configurations & Visual Seat Layout -->
        <div>
          <!-- Step 1: Movie, Showtime & Ticket Type Selector Card -->
          <div class="cinema-panel" style="margin-bottom: 1.5rem;">
            <h3 class="cinema-panel-title" style="margin-bottom: 1.25rem;">🎬 <?php esc_html_e('1. Select Movie & Screening Details', 'big-screen-cinemas'); ?></h3>
            
            <div class="bsc-form-grid-3">
              <div class="bsc-form-group">
                <label><?php esc_html_e('Select Feature Movie', 'big-screen-cinemas'); ?> *</label>
                <select name="manual_movie" id="select_movie" required onchange="onMovieChange(this)">
                  <option value="" data-showtimes="2:30 PM, 6:00 PM, 9:45 PM" data-audit="Auditorium 1 (23 Seats)" data-price="500">-- <?php esc_html_e('Select Feature Movie', 'big-screen-cinemas'); ?> --</option>
                  <?php if (!empty($movies)): foreach ($movies as $m): ?>
                    <option value="<?php echo esc_attr($m->title); ?>" data-showtimes="<?php echo esc_attr($m->showtimes); ?>" data-audit="<?php echo esc_attr($m->auditorium); ?>" data-price="<?php echo esc_attr($m->ticket_price); ?>">
                      <?php echo esc_html($m->title); ?>
                    </option>
                  <?php endforeach; else: ?>
                    <option value="FOOD COMBO (EVIL DEAD BURN)" data-showtimes="2:30 PM, 6:00 PM, 9:45 PM" data-audit="Auditorium 1 (23 Seats)" data-price="500">FOOD COMBO (EVIL DEAD BURN)</option>
                  <?php endif; ?>
                </select>
              </div>

              <div class="bsc-form-group">
                <label><?php esc_html_e('Select Showtime', 'big-screen-cinemas'); ?> *</label>
                <select name="manual_showtime" id="select_showtime" required>
                  <option value="2:30 PM">2:30 PM</option>
                  <option value="6:00 PM" selected>6:00 PM</option>
                  <option value="9:45 PM">9:45 PM</option>
                </select>
              </div>

              <div class="bsc-form-group">
                <label><?php esc_html_e('Ticket Tier Rate', 'big-screen-cinemas'); ?> *</label>
                <select name="manual_ticket_type" id="select_ticket_type" onchange="updateCalculations()">
                  <option value="standard" data-price="<?php echo esc_attr($std_base_price); ?>">Standard Seating (BDT <?php echo number_format($std_base_price, 2); ?>)</option>
                  <option value="vip" data-price="<?php echo esc_attr($vip_base_price); ?>" selected>VIP Recliner Experience (BDT <?php echo number_format($vip_base_price, 2); ?>)</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Step 2: Visual 23-Seat Matrix -->
          <div class="bsc-seat-matrix-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
              <div>
                <span class="cinema-badge-pill active" style="font-size: 0.68rem;"><?php esc_html_e('BOUTIQUE 23-SEAT MATRIX', 'big-screen-cinemas'); ?></span>
                <h4 style="font-size: 1.15rem; font-weight: 900; margin: 0.35rem 0 0 0; color:#fff;"><?php esc_html_e('Interactive Hall Seating Chart', 'big-screen-cinemas'); ?></h4>
              </div>
              <span id="selectedSeatsCounter" style="font-family: var(--cinema-font-mono); font-size: 0.85rem; color: #f59e0b; font-weight: 800;">0 Seats Picked</span>
            </div>

            <div class="bsc-seat-matrix-wrapper">
              
              <!-- Column 1: Row A (VIP - 5 Seats) -->
              <div class="bsc-seat-column">
                <div class="bsc-col-header">Row A (VIP)</div>
                <?php
                  $row_a = array('A1', 'A2', 'A3', 'A4', 'A5');
                  foreach ($row_a as $s_id):
                    $is_occ = in_array($s_id, $all_occupied);
                ?>
                  <button type="button" class="bsc-seat-box vip <?php echo $is_occ ? 'occupied' : ''; ?>" <?php echo $is_occ ? 'disabled' : ''; ?> data-seat="<?php echo esc_attr($s_id); ?>" data-type="vip" onclick="toggleAdminSeat('<?php echo esc_attr($s_id); ?>', this, 'vip')">
                    <?php echo esc_html($s_id); ?>
                  </button>
                <?php endforeach; ?>
              </div>

              <!-- Column 2: Row B (Standard - 6 Seats) -->
              <div class="bsc-seat-column">
                <div class="bsc-col-header">Row B</div>
                <?php
                  $row_b = array('B1', 'B2', 'B3', 'B4', 'B5', 'B6');
                  foreach ($row_b as $s_id):
                    $is_occ = in_array($s_id, $all_occupied);
                ?>
                  <button type="button" class="bsc-seat-box standard <?php echo $is_occ ? 'occupied' : ''; ?>" <?php echo $is_occ ? 'disabled' : ''; ?> data-seat="<?php echo esc_attr($s_id); ?>" data-type="standard" onclick="toggleAdminSeat('<?php echo esc_attr($s_id); ?>', this, 'standard')">
                    <?php echo esc_html($s_id); ?>
                  </button>
                <?php endforeach; ?>
              </div>

              <!-- Column 3: Row C (Standard - 6 Seats) -->
              <div class="bsc-seat-column">
                <div class="bsc-col-header">Row C</div>
                <?php
                  $row_c = array('C1', 'C2', 'C3', 'C4', 'C5', 'C6');
                  foreach ($row_c as $s_id):
                    $is_occ = in_array($s_id, $all_occupied);
                ?>
                  <button type="button" class="bsc-seat-box standard <?php echo $is_occ ? 'occupied' : ''; ?>" <?php echo $is_occ ? 'disabled' : ''; ?> data-seat="<?php echo esc_attr($s_id); ?>" data-type="standard" onclick="toggleAdminSeat('<?php echo esc_attr($s_id); ?>', this, 'standard')">
                    <?php echo esc_html($s_id); ?>
                  </button>
                <?php endforeach; ?>
              </div>

              <!-- Column 4: Row D (Standard - 6 Seats) -->
              <div class="bsc-seat-column">
                <div class="bsc-col-header">Row D</div>
                <?php
                  $row_d = array('D1', 'D2', 'D3', 'D4', 'D5', 'D6');
                  foreach ($row_d as $s_id):
                    $is_occ = in_array($s_id, $all_occupied);
                ?>
                  <button type="button" class="bsc-seat-box standard <?php echo $is_occ ? 'occupied' : ''; ?>" <?php echo $is_occ ? 'disabled' : ''; ?> data-seat="<?php echo esc_attr($s_id); ?>" data-type="standard" onclick="toggleAdminSeat('<?php echo esc_attr($s_id); ?>', this, 'standard')">
                    <?php echo esc_html($s_id); ?>
                  </button>
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
                <span>Standard (৳<?php echo esc_html($std_base_price); ?>)</span>
              </div>
              <div class="bsc-legend-item">
                <div class="bsc-legend-dot" style="background: #b45309;"></div>
                <span>VIP Recliner (৳<?php echo esc_html($vip_base_price); ?>)</span>
              </div>
              <div class="bsc-legend-item">
                <div class="bsc-legend-dot" style="background: #f59e0b;"></div>
                <span>Selected</span>
              </div>
              <div class="bsc-legend-item">
                <div class="bsc-legend-dot" style="background: #374151; opacity: 0.5;"></div>
                <span>Occupied / Blocked</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Customer Info, Payment Method & Live Checkout Summary -->
        <div>
          <div class="bsc-summary-sidebar">
            <h3 class="cinema-panel-title" style="margin-bottom: 1.25rem;">👤 <?php esc_html_e('Customer Information', 'big-screen-cinemas'); ?></h3>
            
            <div class="bsc-form-group">
              <label><?php esc_html_e('Customer Full Name', 'big-screen-cinemas'); ?> *</label>
              <input type="text" name="customer_name" required placeholder="e.g. Mahfuzur Rahman" value="Counter Guest">
            </div>

            <div class="bsc-form-group">
              <label><?php esc_html_e('Phone Number', 'big-screen-cinemas'); ?> *</label>
              <input type="text" name="customer_phone" required placeholder="e.g. +880 1700-000000" value="+880 ">
            </div>

            <div class="bsc-form-group">
              <label><?php esc_html_e('Email Address', 'big-screen-cinemas'); ?></label>
              <input type="email" name="customer_email" placeholder="customer@example.com">
            </div>

            <!-- Payment Method (Cash or bKash) -->
            <div class="bsc-form-group" style="margin-top: 1rem;">
              <label><?php esc_html_e('Payment Method', 'big-screen-cinemas'); ?> *</label>
              <div class="bsc-payment-grid-two">
                <label class="bsc-payment-option">
                  <input type="radio" name="payment_method" value="Cash" checked>
                  <div class="bsc-payment-card">
                    <div class="icon">💵</div>
                    <div class="title">Cash</div>
                    <div class="sub">Walk-In Desk</div>
                  </div>
                </label>

                <label class="bsc-payment-option">
                  <input type="radio" name="payment_method" value="bKash">
                  <div class="bsc-payment-card">
                    <div class="icon">📱</div>
                    <div class="title">bKash</div>
                    <div class="sub">Merchant Pay</div>
                  </div>
                </label>
              </div>
            </div>

            <hr style="border:0; border-top:1px solid var(--cinema-border-light); margin: 1.25rem 0;">

            <label style="font-weight: 800; font-size: 0.8rem; text-transform: uppercase; color: var(--cinema-text-muted); display: block; margin-bottom: 0.35rem;">
              <?php esc_html_e('Selected Seats', 'big-screen-cinemas'); ?>
            </label>
            <div class="bsc-selected-chips" id="selectedChipsDeck">
              <span style="font-size: 0.8rem; color: var(--cinema-text-muted);"><?php esc_html_e('No seats selected yet.', 'big-screen-cinemas'); ?></span>
            </div>

            <!-- Structured Pricing Breakdown -->
            <table class="bsc-bill-table">
              <tbody>
                <tr>
                  <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Tickets Subtotal', 'big-screen-cinemas'); ?></td>
                  <td style="text-align: right; font-weight: 700; font-family: var(--cinema-font-mono);" id="lblSubtotal">BDT 0.00</td>
                </tr>
                <tr>
                  <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Tax / Service Charge', 'big-screen-cinemas'); ?></td>
                  <td style="text-align: right; font-weight: 700; font-family: var(--cinema-font-mono);" id="lblTax">BDT 0.00</td>
                </tr>
                <tr class="grand-row">
                  <td style="color: var(--cinema-text-main);"><?php esc_html_e('Total Amount', 'big-screen-cinemas'); ?></td>
                  <td style="text-align: right; color: var(--cinema-accent-teal); font-family: var(--cinema-font-mono);" id="lblGrandTotal">BDT 0.00</td>
                </tr>
              </tbody>
            </table>

            <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:10px; font-weight:900; font-size: 0.95rem;">
              🎟️ <?php esc_html_e('Issue & Print Pass', 'big-screen-cinemas'); ?>
            </button>
          </div>
        </div>

      </div>
    </form>

    <script>
      let adminSelectedSeats = [];
      const VIP_PRICE = <?php echo floatval($vip_base_price); ?>;
      const STD_PRICE = <?php echo floatval($std_base_price); ?>;

      function onMovieChange(selectEl) {
        const option = selectEl.options[selectEl.selectedIndex];
        if (!option) return;

        const audit = option.getAttribute('data-audit') || 'Auditorium 1 (23 Seats)';
        const showtimes = option.getAttribute('data-showtimes') || '2:30 PM, 6:00 PM, 9:45 PM';

        document.getElementById('input_manual_audit').value = audit;

        // Populate Showtimes
        const timeSelect = document.getElementById('select_showtime');
        timeSelect.innerHTML = '';
        const slots = showtimes.split(',');
        slots.forEach(slot => {
          const opt = document.createElement('option');
          opt.value = slot.trim();
          opt.innerText = slot.trim();
          timeSelect.appendChild(opt);
        });

        updateCalculations();
      }

      function toggleAdminSeat(seatId, btnEl, type) {
        const price = (type === 'vip') ? VIP_PRICE : STD_PRICE;
        const index = adminSelectedSeats.findIndex(s => s.id === seatId);

        if (index > -1) {
          adminSelectedSeats.splice(index, 1);
          btnEl.classList.remove('selected');
        } else {
          adminSelectedSeats.push({ id: seatId, type: type, price: price });
          btnEl.classList.add('selected');
        }

        updateCalculations();
      }

      function updateCalculations() {
        const chipsDeck = document.getElementById('selectedChipsDeck');
        const counter = document.getElementById('selectedSeatsCounter');
        const hiddenSeats = document.getElementById('input_manual_seats');
        const hiddenAmount = document.getElementById('input_manual_amount');

        // Update Seats list & badges
        if (adminSelectedSeats.length === 0) {
          chipsDeck.innerHTML = `<span style="font-size: 0.8rem; color: var(--cinema-text-muted);">No seats selected yet.</span>`;
          counter.innerText = '0 Seats Picked';
          hiddenSeats.value = '';
        } else {
          chipsDeck.innerHTML = adminSelectedSeats.map(s => `<span class="bsc-seat-chip">${s.id}</span>`).join('');
          counter.innerText = `${adminSelectedSeats.length} Seat(s) Picked`;
          hiddenSeats.value = adminSelectedSeats.map(s => s.id).join(', ');
        }

        // Calculate Totals
        const subtotal = adminSelectedSeats.reduce((acc, s) => acc + s.price, 0);
        const tax = 0.00;
        const grand = subtotal + tax;

        document.getElementById('lblSubtotal').innerText = `BDT ${subtotal.toFixed(2)}`;
        document.getElementById('lblTax').innerText = `BDT ${tax.toFixed(2)}`;
        document.getElementById('lblGrandTotal').innerText = `BDT ${grand.toFixed(2)}`;

        hiddenAmount.value = grand.toFixed(2);
      }

      document.getElementById('bscBookingForm').addEventListener('submit', function(e) {
        if (adminSelectedSeats.length === 0) {
          e.preventDefault();
          alert('Please click and select at least one seat from the seat map to proceed.');
        }
      });
    </script>

  <?php elseif ($subtab === 'edit' && isset($_GET['id'])): 
    $edit_booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_bookings WHERE id = %d", intval($_GET['id'])));
    if (!$edit_booking) { echo '<div class="error"><p>Booking not found.</p></div>'; return; }
  ?>
    <!-- Edit Booking Form View -->
    <div class="cinema-panel" style="max-width: 650px; margin: 0 auto;">
      <div class="cinema-panel-header">
        <h3 class="cinema-panel-title">✏️ <?php esc_html_e('Edit Booking Record:', 'big-screen-cinemas'); ?> <code><?php echo esc_html($edit_booking->booking_token); ?></code></h3>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=all')); ?>" class="button button-secondary">← Back to All</a>
      </div>

      <form method="POST" action="">
        <?php wp_nonce_field('bsc_edit_booking_verify'); ?>
        <input type="hidden" name="bsc_action" value="edit_booking">
        <input type="hidden" name="booking_id" value="<?php echo intval($edit_booking->id); ?>">

        <div class="bsc-form-group">
          <label>Movie Feature</label>
          <input type="text" class="regular-text" value="<?php echo esc_attr($edit_booking->movie_title); ?>" readonly style="background:#f1f5f9;">
        </div>

        <div class="bsc-form-grid-2">
          <div class="bsc-form-group">
            <label>Customer Name *</label>
            <input type="text" name="customer_name" class="regular-text" value="<?php echo esc_attr($edit_booking->customer_name); ?>" required>
          </div>
          <div class="bsc-form-group">
            <label>Customer Phone *</label>
            <input type="text" name="customer_phone" class="regular-text" value="<?php echo esc_attr($edit_booking->customer_phone); ?>" required>
          </div>
        </div>

        <div class="bsc-form-grid-2">
          <div class="bsc-form-group">
            <label>Customer Email</label>
            <input type="email" name="customer_email" class="regular-text" value="<?php echo esc_attr($edit_booking->customer_email); ?>">
          </div>
          <div class="bsc-form-group">
            <label>Reserved Seats *</label>
            <input type="text" name="seats" class="regular-text" value="<?php echo esc_attr($edit_booking->seats); ?>" required>
          </div>
        </div>

        <div class="bsc-form-grid-3">
          <div class="bsc-form-group">
            <label>Total Amount (BDT) *</label>
            <input type="number" step="0.01" name="total_amount" class="regular-text" value="<?php echo esc_attr($edit_booking->total_amount); ?>" required>
          </div>
          <div class="bsc-form-group">
            <label>Payment Method</label>
            <select name="payment_method">
              <option value="Cash" <?php selected($edit_booking->payment_method, 'Cash'); ?>>Cash</option>
              <option value="bKash" <?php selected($edit_booking->payment_method, 'bKash'); ?>>bKash</option>
            </select>
          </div>
          <div class="bsc-form-group">
            <label>Payment Status</label>
            <select name="payment_status">
              <option value="Confirmed" <?php selected($edit_booking->payment_status, 'Confirmed'); ?>>Confirmed</option>
              <option value="Confirmed Online" <?php selected($edit_booking->payment_status, 'Confirmed Online'); ?>>Confirmed Online</option>
              <option value="bKash Paid" <?php selected($edit_booking->payment_status, 'bKash Paid'); ?>>bKash Paid</option>
            </select>
          </div>
        </div>

        <div style="margin-top: 1.5rem;">
          <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; padding:6px 16px; font-weight:800;">Save Booking Changes</button>
        </div>
      </form>
    </div>

  <?php else: ?>
    <!-- Step 3: All Bookings Directory with View, Edit & Delete Actions -->
    <div class="cinema-panel" style="margin:0;">
      <div class="cinema-panel-header">
        <h3 class="cinema-panel-title">🎟️ <?php esc_html_e('All Customer Bookings & Passes', 'big-screen-cinemas'); ?></h3>
      </div>
      <div class="cinema-table-wrap">
        <table class="cinema-data-table">
          <thead>
            <tr>
              <th>TOKEN</th>
              <th>CUSTOMER</th>
              <th>MOVIE</th>
              <th>SHOWTIME</th>
              <th>AUDITORIUM</th>
              <th>SEATS</th>
              <th>TOTAL</th>
              <th>STATUS</th>
              <th style="text-align: right;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($bookings)): foreach ($bookings as $b): 
              $edit_url   = admin_url('admin.php?page=bsc-booking&subtab=edit&id=' . $b->id);
              $delete_url = wp_nonce_url(admin_url('admin.php?page=bsc-booking&subtab=delete&id=' . $b->id), 'bsc_delete_booking_' . $b->id);
            ?>
              <tr>
                <td><span class="cinema-voucher-tag"><?php echo esc_html($b->booking_token); ?></span></td>
                <td>
                  <strong><?php echo esc_html($b->customer_name ?: 'Walk-In Guest'); ?></strong><br>
                  <small style="color:var(--cinema-text-muted);"><?php echo esc_html($b->customer_phone); ?></small>
                </td>
                <td><strong><?php echo esc_html($b->movie_title); ?></strong></td>
                <td><span class="cinema-badge-pill active" style="font-size:0.7rem;"><?php echo esc_html($b->showtime ?? 'Counter'); ?></span></td>
                <td><?php echo esc_html($b->auditorium); ?></td>
                <td><code><?php echo esc_html($b->seats); ?></code></td>
                <td><strong>BDT <?php echo number_format($b->total_amount, 2); ?></strong></td>
                <td><span class="cinema-badge-payment"><?php echo esc_html($b->payment_status); ?></span></td>
                <td style="text-align: right; white-space: nowrap;">
                  <button type="button" class="button button-secondary button-small" onclick='openBookingModal(<?php echo json_encode($b); ?>)' title="View Details">
                    👁️
                  </button>
                  <a href="<?php echo esc_url($edit_url); ?>" class="button button-small" title="Edit Booking">✏️</a>
                  <a href="<?php echo esc_url($delete_url); ?>" class="button button-small" style="color:#ef4444;" onclick="return confirm('Are you sure you want to delete this booking record?');" title="Delete Booking">🗑️</a>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="9" style="text-align:center; padding:2rem;"><?php esc_html_e('No bookings recorded yet.', 'big-screen-cinemas'); ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Booking Details Modal Overlay -->
    <div class="bsc-modal-overlay" id="bscBookingDetailsModal">
      <div class="bsc-modal-box">
        <div class="bsc-modal-header">
          <div>
            <span style="font-size:0.7rem; text-transform:uppercase; font-weight:800; letter-spacing:1px; color:#99f6e4;">Digital Voucher Pass</span>
            <h3 id="modalTokenTitle" style="margin:2px 0 0 0; color:#fff; font-size:1.25rem; font-family:var(--cinema-font-mono);">#BSC-0000-TX</h3>
          </div>
          <button type="button" onclick="closeBookingModal()" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <div class="bsc-modal-body">
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
              <span style="font-size:0.72rem; text-transform:uppercase; color:var(--cinema-text-muted); font-weight:800;">Customer Name</span>
              <div id="modalCustName" style="font-weight:800; font-size:1rem; color:var(--cinema-text-main);">--</div>
            </div>
            <div>
              <span style="font-size:0.72rem; text-transform:uppercase; color:var(--cinema-text-muted); font-weight:800;">Contact Phone</span>
              <div id="modalCustPhone" style="font-weight:700; font-size:0.95rem; font-family:var(--cinema-font-mono); color:var(--cinema-text-main);">--</div>
            </div>
          </div>

          <div style="background:#f8fafc; border:1px solid var(--cinema-border); border-radius:10px; padding:1rem; margin-bottom:1.25rem;">
            <div style="font-size:0.8rem; font-weight:800; color:var(--cinema-accent-teal); margin-bottom:0.35rem;" id="modalMovieTitle">Movie Title</div>
            <div style="font-size:0.85rem; color:var(--cinema-text-muted); margin-bottom:0.75rem;" id="modalAuditInfo">Auditorium 1</div>
            <div style="display:flex; justify-content:space-between; font-size:0.85rem; border-top:1px dashed var(--cinema-border); padding-top:0.5rem;">
              <span>Showtime: <strong id="modalShowtime">6:00 PM</strong></span>
              <span>Seats: <strong id="modalSeats" style="color:#d97706; font-family:var(--cinema-font-mono);">A1</strong></span>
            </div>
          </div>

          <table class="bsc-bill-table" style="margin-bottom:0;">
            <tbody>
              <tr>
                <td style="color:var(--cinema-text-muted);">Payment Method</td>
                <td style="text-align:right; font-weight:700;" id="modalPaymentMethod">Cash</td>
              </tr>
              <tr>
                <td style="color:var(--cinema-text-muted);">Payment Status</td>
                <td style="text-align:right;"><span class="cinema-badge-payment" id="modalPaymentStatus">Confirmed</span></td>
              </tr>
              <tr>
                <td style="color:var(--cinema-text-muted);">Timestamp</td>
                <td style="text-align:right; font-size:0.85rem;" id="modalBookingDate">2026-08-30</td>
              </tr>
              <tr class="grand-row">
                <td>Total Settlement</td>
                <td style="text-align:right; color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono);" id="modalTotalAmount">BDT 0.00</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="bsc-modal-footer">
          <button type="button" class="button button-secondary" onclick="window.print()">🖨️ Print Pass</button>
          <button type="button" class="button button-primary" onclick="closeBookingModal()" style="background:#064e3b; border-color:#064e3b;">Close Window</button>
        </div>
      </div>
    </div>

    <script>
      function openBookingModal(data) {
        document.getElementById('modalTokenTitle').innerText = data.booking_token;
        document.getElementById('modalCustName').innerText = data.customer_name || 'Walk-In Guest';
        document.getElementById('modalCustPhone').innerText = data.customer_phone || 'N/A';
        document.getElementById('modalMovieTitle').innerText = data.movie_title;
        document.getElementById('modalAuditInfo').innerText = data.auditorium;
        document.getElementById('modalShowtime').innerText = data.showtime || '6:00 PM';
        document.getElementById('modalSeats').innerText = data.seats;
        document.getElementById('modalPaymentMethod').innerText = data.payment_method;
        document.getElementById('modalPaymentStatus').innerText = data.payment_status;
        document.getElementById('modalBookingDate').innerText = data.booking_date;
        document.getElementById('modalTotalAmount').innerText = `BDT ${parseFloat(data.total_amount).toFixed(2)}`;

        document.getElementById('bscBookingDetailsModal').style.display = 'flex';
      }

      function closeBookingModal() {
        document.getElementById('bscBookingDetailsModal').style.display = 'none';
      }

      window.onclick = function(event) {
        const modal = document.getElementById('bscBookingDetailsModal');
        if (event.target === modal) {
          closeBookingModal();
        }
      }
    </script>
  <?php endif; ?>
  <?php
  bsc_render_page_shell_close();
}