<?php
if (!defined('ABSPATH')) exit;

if ($subtab === 'edit' && $booking_id > 0):
  $edit_booking = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_bookings WHERE id = %d", $booking_id));
  if (!$edit_booking) { echo '<div class="error"><p>Booking not found.</p></div>'; return; }
?>
  <!-- Edit Booking Form View -->
  <div class="cinema-panel" style="max-width: 680px; margin: 0 auto; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);">
    <div class="cinema-panel-header">
      <h3 class="cinema-panel-title">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        <?php esc_html_e('Edit Booking Record:', 'big-screen-cinemas'); ?> <code><?php echo esc_html($edit_booking->booking_token); ?></code>
      </h3>
      <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=all')); ?>" class="button button-secondary">← Back to All</a>
    </div>

    <form method="POST" action="">
      <?php wp_nonce_field('bsc_edit_booking_verify'); ?>
      <input type="hidden" name="bsc_action" value="edit_booking">
      <input type="hidden" name="booking_id" value="<?php echo intval($edit_booking->id); ?>">

      <div class="bsc-form-group">
        <label>Feature Movie Presentation</label>
        <input type="text" class="regular-text" value="<?php echo esc_attr($edit_booking->movie_title); ?>" readonly style="background:#f1f5f9; font-weight:700;">
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
          <label>Total Amount (<?php echo esc_html($currency_symbol); ?>) *</label>
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

      <div style="margin-top: 1.5rem; text-align:right;">
        <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; padding:8px 20px; font-weight:800; border-radius:6px; display:inline-flex; align-items:center; gap:6px;">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
          <?php esc_html_e('Save Booking Changes', 'big-screen-cinemas'); ?>
        </button>
      </div>
    </form>
  </div>

<?php else: ?>
  <!-- Add New Walk-in Reservation Form -->
  <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="bscBookingForm">
    <?php wp_nonce_field('bsc_manual_booking_verify'); ?>
    <input type="hidden" name="action" value="bsc_manual_booking">
    <input type="hidden" name="manual_seats" id="input_manual_seats" required value="">
    <input type="hidden" name="manual_amount" id="input_manual_amount" required value="0">
    <input type="hidden" name="manual_audit" id="input_manual_audit" value="Auditorium 1 (23 Seats)">

    <div class="bsc-booking-builder-grid">
      
      <div>
        <!-- Step 1: Presentation & Showtime Config -->
        <div class="cinema-panel" style="margin-bottom: 1.5rem;">
          <h3 class="cinema-panel-title" style="margin-bottom: 1.25rem;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
            <?php esc_html_e('1. Select Feature Presentation & Showtime', 'big-screen-cinemas'); ?>
          </h3>
          
          <div class="bsc-form-grid-3">
            <div class="bsc-form-group" style="margin-bottom:0;">
              <label><?php esc_html_e('Feature Movie', 'big-screen-cinemas'); ?> *</label>
              <select name="manual_movie" id="select_movie" required onchange="onMovieChange(this)">
                <?php if (!empty($movies)): foreach ($movies as $m): ?>
                  <option value="<?php echo esc_attr($m->title); ?>" data-showtimes="<?php echo esc_attr($m->showtimes); ?>" data-audit="<?php echo esc_attr($m->auditorium); ?>" data-price="<?php echo esc_attr($m->ticket_price); ?>">
                    <?php echo esc_html($m->title); ?>
                  </option>
                <?php endforeach; else: ?>
                  <option value="Borbaad" data-showtimes='["14:30", "18:00", "21:30"]' data-audit="Auditorium 1 (23 Seats)" data-price="400">Borbaad</option>
                <?php endif; ?>
              </select>
            </div>

            <div class="bsc-form-group" style="margin-bottom:0;">
              <label><?php esc_html_e('Screening Time', 'big-screen-cinemas'); ?> *</label>
              <select name="manual_showtime" id="select_showtime" required>
                <option value="18:00">06:00 PM</option>
              </select>
            </div>

            <div class="bsc-form-group" style="margin-bottom:0;">
              <label><?php esc_html_e('Ticket Tier Rate', 'big-screen-cinemas'); ?> *</label>
              <select name="manual_ticket_type" id="select_ticket_type" onchange="updateCalculations()">
                <option value="standard" data-price="<?php echo esc_attr($std_base_price); ?>">Standard (<?php echo esc_html($currency_symbol); ?> <?php echo number_format($std_base_price, 0); ?>)</option>
                <option value="vip" data-price="<?php echo esc_attr($vip_base_price); ?>" selected>VIP Recliner (<?php echo esc_html($currency_symbol); ?> <?php echo number_format($vip_base_price, 0); ?>)</option>
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
            <span id="selectedSeatsCounter" style="font-family: var(--cinema-font-mono); font-size: 0.88rem; color: #f59e0b; font-weight: 800; background:rgba(245,158,11,0.15); padding:0.25rem 0.65rem; border-radius:6px; border:1px solid rgba(245,158,11,0.3);">
              0 Seats Picked
            </span>
          </div>

          <div class="bsc-seat-matrix-wrapper">
            
            <!-- Column 1: Row A (VIP - 5 Seats) -->
            <div class="bsc-seat-column">
              <div class="bsc-col-header" style="color:#f59e0b;">Row A (VIP)</div>
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
              <span>Standard (<?php echo esc_html($currency_symbol); ?> <span id="legendStdPrice"><?php echo number_format($std_base_price, 0); ?></span>)</span>
            </div>
            <div class="bsc-legend-item">
              <div class="bsc-legend-dot" style="background: #b45309;"></div>
              <span>VIP Recliner (<?php echo esc_html($currency_symbol); ?> <span id="legendVipPrice"><?php echo number_format($vip_base_price, 0); ?></span>)</span>
            </div>
            <div class="bsc-legend-item">
              <div class="bsc-legend-dot" style="background: #f59e0b;"></div>
              <span>Selected</span>
            </div>
            <div class="bsc-legend-item">
              <div class="bsc-legend-dot" style="background: #ef4444; opacity: 0.6;"></div>
              <span>Occupied / Reserved</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Customer Info, Payment Method & Checkout Summary -->
      <div>
        <div class="bsc-summary-sidebar">
          <h3 class="cinema-panel-title" style="margin-bottom: 1.25rem;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <?php esc_html_e('Customer Information', 'big-screen-cinemas'); ?>
          </h3>
          
          <div class="bsc-form-group">
            <label><?php esc_html_e('Customer Full Name', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="customer_name" required placeholder="e.g. Mahfuzur Rahman" value="Walk-In Guest">
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
            <label><?php esc_html_e('Payment Gateway / Counter Method', 'big-screen-cinemas'); ?> *</label>
            <div class="bsc-payment-grid-two">
              <label class="bsc-payment-option">
                <input type="radio" name="payment_method" value="Cash" checked>
                <div class="bsc-payment-card">
                  <div class="icon">
                    <svg viewBox="0 0 24 24"><path d="M12.5 6.5H5.5V17.5H18.5V6.5H12.5ZM17 16H7V8H17V16ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z"/></svg>
                  </div>
                  <div class="title">Cash</div>
                  <div class="sub">Counter Desk</div>
                </div>
              </label>

              <label class="bsc-payment-option">
                <input type="radio" name="payment_method" value="bKash">
                <div class="bsc-payment-card">
                  <div class="icon">
                    <svg viewBox="0 0 24 24"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg>
                  </div>
                  <div class="title">bKash</div>
                  <div class="sub">Merchant Pay</div>
                </div>
              </label>
            </div>
          </div>

          <hr style="border:0; border-top:1px solid var(--cinema-border-light); margin: 1.25rem 0;">

          <label style="font-weight: 800; font-size: 0.75rem; text-transform: uppercase; color: var(--cinema-text-muted); display: block; margin-bottom: 0.35rem;">
            <?php esc_html_e('Selected Seats', 'big-screen-cinemas'); ?>
          </label>
          <div class="bsc-selected-chips" id="selectedChipsDeck">
            <span style="font-size: 0.82rem; color: var(--cinema-text-muted);"><?php esc_html_e('No seats selected yet.', 'big-screen-cinemas'); ?></span>
          </div>

          <!-- Structured Pricing Breakdown -->
          <table class="bsc-bill-table">
            <tbody>
              <tr>
                <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Tickets Subtotal', 'big-screen-cinemas'); ?></td>
                <td style="text-align: right; font-weight: 700; font-family: var(--cinema-font-mono);" id="lblSubtotal">
                  <?php echo esc_html($currency_symbol); ?> 0.00
                </td>
              </tr>
              <tr>
                <td style="color: var(--cinema-text-muted);"><?php esc_html_e('Service / Tax', 'big-screen-cinemas'); ?></td>
                <td style="text-align: right; font-weight: 700; font-family: var(--cinema-font-mono);" id="lblTax">
                  <?php echo esc_html($currency_symbol); ?> 0.00
                </td>
              </tr>
              <tr class="grand-row">
                <td style="color: var(--cinema-text-main);"><?php esc_html_e('Total Settlement', 'big-screen-cinemas'); ?></td>
                <td style="text-align: right; color: var(--cinema-accent-teal); font-family: var(--cinema-font-mono);" id="lblGrandTotal">
                  <?php echo esc_html($currency_symbol); ?> 0.00
                </td>
              </tr>
            </tbody>
          </table>

          <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:11px; font-weight:900; font-size: 0.95rem; border-radius:8px; box-shadow:0 4px 14px rgba(6,78,59,0.25); display:flex; align-items:center; justify-content:center; gap:8px;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
            <?php esc_html_e('Issue & Print Pass', 'big-screen-cinemas'); ?>
          </button>
        </div>
      </div>

    </div>
  </form>

  <script>
    let adminSelectedSeats = [];
    const VIP_PRICE = <?php echo floatval($vip_base_price); ?>;
    const STD_PRICE = <?php echo floatval($std_base_price); ?>;
    const CURRENCY_TAG = <?php echo wp_json_encode($currency_symbol); ?>;

    function formatTimeSlot(timeStr) {
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

    function onMovieChange(selectEl) {
      const option = selectEl.options[selectEl.selectedIndex];
      if (!option) return;

      const audit = option.getAttribute('data-audit') || 'Auditorium 1 (23 Seats)';
      const showtimesRaw = option.getAttribute('data-showtimes') || '["18:00"]';

      document.getElementById('input_manual_audit').value = audit;

      const timeSelect = document.getElementById('select_showtime');
      timeSelect.innerHTML = '';

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
          const opt = document.createElement('option');
          opt.value = trimmed;
          opt.innerText = formatTimeSlot(trimmed);
          timeSelect.appendChild(opt);
        }
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

      if (adminSelectedSeats.length === 0) {
        chipsDeck.innerHTML = `<span style="font-size: 0.82rem; color: var(--cinema-text-muted);">No seats selected yet.</span>`;
        counter.innerText = '0 Seats Picked';
        hiddenSeats.value = '';
      } else {
        chipsDeck.innerHTML = adminSelectedSeats.map(s => `<span class="bsc-seat-chip">${s.id}</span>`).join('');
        counter.innerText = `${adminSelectedSeats.length} Seat(s) Picked`;
        hiddenSeats.value = adminSelectedSeats.map(s => s.id).join(', ');
      }

      const subtotal = adminSelectedSeats.reduce((acc, s) => acc + s.price, 0);
      const tax = 0.00;
      const grand = subtotal + tax;

      document.getElementById('lblSubtotal').innerText = `${CURRENCY_TAG} ${subtotal.toFixed(2)}`;
      document.getElementById('lblTax').innerText = `${CURRENCY_TAG} ${tax.toFixed(2)}`;
      document.getElementById('lblGrandTotal').innerText = `${CURRENCY_TAG} ${grand.toFixed(2)}`;

      hiddenAmount.value = grand.toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', function() {
      const movieSelect = document.getElementById('select_movie');
      if (movieSelect) {
        onMovieChange(movieSelect);
      }
    });

    document.getElementById('bscBookingForm').addEventListener('submit', function(e) {
      if (adminSelectedSeats.length === 0) {
        e.preventDefault();
        alert('Please click and select at least one seat from the seat chart to issue tickets.');
      }
    });
  </script>
<?php endif; ?>