<?php
if (!defined('ABSPATH')) exit;

// Extract unique movies and showtimes for filter dropdowns
$filter_movies =$wpdb->get_col("SELECT DISTINCT movie_title FROM $table_bookings WHERE movie_title != '' ORDER BY movie_title ASC");
$filter_times  =$wpdb->get_col("SELECT DISTINCT showtime FROM $table_bookings WHERE showtime != '' ORDER BY showtime ASC");
?>
<style>
  .bsc-table-filter-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    background: #f8fafc;
    border: 1px solid var(--cinema-border);
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 1.25rem;
  }
  .bsc-table-filter-toolbar select,
  .bsc-table-filter-toolbar input {
    padding: 6px 10px;
    font-size: 0.85rem;
    border: 1px solid var(--cinema-border);
    border-radius: 6px;
    background: #ffffff;
    color: var(--cinema-text-main);
    outline: none;
  }
  .bsc-table-filter-toolbar select:focus,
  .bsc-table-filter-toolbar input:focus {
    border-color: var(--cinema-accent-teal);
  }
</style>

<div class="cinema-panel" style="margin:0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
  <div class="cinema-panel-header">
    <div>
      <h3 class="cinema-panel-title">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
        <?php esc_html_e('All Customer Bookings & Passes', 'big-screen-cinemas'); ?> (<span id="filteredCount"><?php echo count($bookings); ?></span>)
      </h3>
      <span style="font-size:0.78rem; color:var(--cinema-text-muted);"><?php esc_html_e('Showing active admissions and turnstile passes.', 'big-screen-cinemas'); ?></span>
    </div>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-booking&subtab=add')); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      <?php esc_html_e('New Booking', 'big-screen-cinemas'); ?>
    </a>
  </div>

  <!-- Live Filter Toolbar -->
  <div class="bsc-table-filter-toolbar">
    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted);">Filter Movie:</span>
      <select id="filterMovie" onchange="filterBookingsTable()">
        <option value="">-- All Movies --</option>
        <?php foreach ($filter_movies as$fm): ?>
          <option value="<?php echo esc_attr($fm); ?>"><?php echo esc_html($fm); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted);">Showtime:</span>
      <select id="filterTime" onchange="filterBookingsTable()">
        <option value="">-- All Times --</option>
        <?php foreach ($filter_times as$ft): ?>
          <option value="<?php echo esc_attr($ft); ?>"><?php echo esc_html($ft); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted);">Date:</span>
      <input type="date" id="filterDate" onchange="filterBookingsTable()">
    </div>

    <div style="margin-left:auto;">
      <button type="button" class="button button-small" onclick="resetBookingFilters()" style="font-weight:700;">Reset Filters</button>
    </div>
  </div>

  <div class="cinema-table-wrap">
    <table class="cinema-data-table" id="bscBookingsTable">
      <thead>
        <tr>
          <th>TOKEN</th>
          <th>CUSTOMER NAME</th>
          <th>CUSTOMER PHONE</th>
          <th>MOVIE</th>
          <th>SHOWTIME</th>
          <th>TOTAL</th>
          <th style="text-align: right;">ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($bookings)): foreach ($bookings as$b): 
          $view_url   = admin_url('admin.php?page=bsc-booking&subtab=view&id=' . $b->id);
          $edit_url   = admin_url('admin.php?page=bsc-booking&subtab=edit&id=' . $b->id);
          $delete_url = wp_nonce_url(admin_url('admin.php?page=bsc-booking&subtab=delete&id=' . $b->id), 'bsc_delete_booking_' . $b->id);$row_date   = !empty($b->booking_date) ? date('Y-m-d', strtotime($b->booking_date)) : '';
        ?>
          <tr data-movie="<?php echo esc_attr($b->movie_title); ?>" data-time="<?php echo esc_attr($b->showtime); ?>" data-date="<?php echo esc_attr($row_date); ?>">
            <td><span class="cinema-voucher-tag"><?php echo esc_html($b->booking_token); ?></span></td>
            <td><strong><?php echo esc_html($b->customer_name ?: 'Walk-In Guest'); ?></strong></td>
            <td><span style="font-family:var(--cinema-font-mono); font-size:0.85rem; color:var(--cinema-text-muted);"><?php echo esc_html($b->customer_phone); ?></span></td>
            <td><strong><?php echo esc_html($b->movie_title); ?></strong></td>
            <td><span class="cinema-badge-pill active" style="font-size:0.7rem;"><?php echo esc_html($b->showtime ?? 'Counter'); ?></span></td>
            <td><strong style="color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono);"><?php echo esc_html($currency_symbol); ?> <?php echo number_format($b->total_amount, 2); ?></strong></td>
            <td style="text-align: right; white-space: nowrap;">
              <div class="bsc-table-actions">
                <a href="<?php echo esc_url($view_url); ?>" class="bsc-action-btn view">
                  <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                  <span>View</span>
                </a>
                <a href="<?php echo esc_url($edit_url); ?>" class="bsc-action-btn edit">
                  <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                  <span>Edit</span>
                </a>
                <a href="<?php echo esc_url($delete_url); ?>" class="bsc-action-btn delete" onclick="return confirm('Are you sure you want to delete this booking record?');">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  <span>Delete</span>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr id="noBookingsRow"><td colspan="7" style="text-align:center; padding:2.5rem; color:var(--cinema-text-muted);"><?php esc_html_e('No bookings recorded yet.', 'big-screen-cinemas'); ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  function filterBookingsTable() {
    const selectedMovie = document.getElementById('filterMovie').value.toLowerCase();
    const selectedTime  = document.getElementById('filterTime').value.toLowerCase();
    const selectedDate  = document.getElementById('filterDate').value;
    
    const rows = document.querySelectorAll('#bscBookingsTable tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
      if (row.id === 'noBookingsRow') return;

      const rMovie = (row.getAttribute('data-movie') || '').toLowerCase();
      const rTime  = (row.getAttribute('data-time') || '').toLowerCase();
      const rDate  = row.getAttribute('data-date') || '';

      let matchMovie = !selectedMovie || rMovie.includes(selectedMovie);
      let matchTime  = !selectedTime || rTime.includes(selectedTime);
      let matchDate  = !selectedDate || rDate === selectedDate;

      if (matchMovie && matchTime && matchDate) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    const counterEl = document.getElementById('filteredCount');
    if (counterEl) counterEl.innerText = visibleCount;
  }

  function resetBookingFilters() {
    document.getElementById('filterMovie').value = '';
    document.getElementById('filterTime').value = '';
    document.getElementById('filterDate').value = '';
    filterBookingsTable();
  }
</script>