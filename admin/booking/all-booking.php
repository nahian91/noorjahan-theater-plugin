<?php
/**
 * Booking Module - All Bookings View
 *
 * Path: admin/booking/all-booking.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_bookings = $wpdb->prefix . 'bsc_bookings';

$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );
$filter_movies   = $wpdb->get_col( "SELECT DISTINCT movie_title FROM {$table_bookings} WHERE movie_title != '' ORDER BY movie_title ASC" );
$filter_times    = $wpdb->get_col( "SELECT DISTINCT showtime FROM {$table_bookings} WHERE showtime != '' ORDER BY showtime ASC" );

if ( ! isset( $bookings ) || ! is_array( $bookings ) ) {
    $bookings = $wpdb->get_results( "SELECT * FROM {$table_bookings} ORDER BY id DESC" );
}

$today_ymd = current_time( 'Y-m-d' );

// Reusable 12-Hour AM/PM Formatter
function bsc_format_display_time( $raw_time ) {
    $clean = trim( (string) $raw_time );
    if ( empty( $clean ) ) {
        return 'Counter';
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
?>
<style>
  .bsc-table-filter-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    background: #f8fafc;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 1.25rem;
  }
  .bsc-table-filter-toolbar select,
  .bsc-table-filter-toolbar input {
    padding: 6px 10px;
    font-size: 0.85rem;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 6px;
    background: #ffffff;
    color: var(--cinema-text-main, #0f172a);
    outline: none;
  }
  .bsc-table-filter-toolbar select:focus,
  .bsc-table-filter-toolbar input:focus {
    border-color: var(--cinema-accent-teal, #0d9488);
  }
  .bsc-table-actions {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .bsc-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    line-height: 1;
  }
  .bsc-action-btn svg {
    width: 13px;
    height: 13px;
    fill: currentColor;
  }
  .bsc-action-btn.view {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
  }
  .bsc-action-btn.view:hover {
    background: #1d4ed8;
    color: #fff;
  }
  .bsc-action-btn.edit {
    background: #fefce8;
    border-color: #fef08a;
    color: #a16207;
  }
  .bsc-action-btn.edit:hover {
    background: #a16207;
    color: #fff;
  }
  .bsc-action-btn.delete {
    background: #fef2f2;
    border-color: #fecaca;
    color: #b91c1c;
  }
  .bsc-action-btn.delete:hover {
    background: #b91c1c;
    color: #fff;
  }
  .cinema-voucher-tag {
    font-family: var(--cinema-font-mono, monospace);
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
</style>

<div class="cinema-panel" style="margin:0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
  <div class="cinema-panel-header" style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0;">
    <div>
      <h3 class="cinema-panel-title" style="margin:0 0 4px 0; font-size:1.15rem; font-weight:800; color:#0f172a;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align:-3px; margin-right:4px;"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
        <?php esc_html_e( 'All Customer Bookings & Passes', 'bsc-cinema-manager' ); ?> (<span id="filteredCount"><?php echo count( $bookings ); ?></span>)
      </h3>
      <span style="font-size:0.78rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'Showing active admissions and turnstile passes.', 'bsc-cinema-manager' ); ?></span>
    </div>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking&subtab=add' ) ); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      <?php esc_html_e( 'New Booking', 'bsc-cinema-manager' ); ?>
    </a>
  </div>

  <!-- Live Filter Toolbar -->
  <div class="bsc-table-filter-toolbar">
    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'Filter Movie:', 'bsc-cinema-manager' ); ?></span>
      <select id="filterMovie" onchange="filterBookingsTable()">
        <option value="">-- <?php esc_html_e( 'All Movies', 'bsc-cinema-manager' ); ?> --</option>
        <?php foreach ( $filter_movies as $fm ) : ?>
          <option value="<?php echo esc_attr( $fm ); ?>"><?php echo esc_html( $fm ); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'Showtime:', 'bsc-cinema-manager' ); ?></span>
      <select id="filterTime" onchange="filterBookingsTable()">
        <option value="">-- <?php esc_html_e( 'All Times', 'bsc-cinema-manager' ); ?> --</option>
        <?php foreach ( $filter_times as $ft ) : ?>
          <option value="<?php echo esc_attr( $ft ); ?>"><?php echo esc_html( bsc_format_display_time( $ft ) ); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'Date:', 'bsc-cinema-manager' ); ?></span>
      <input type="date" id="filterDate" value="<?php echo esc_attr( $today_ymd ); ?>" onchange="filterBookingsTable()">
    </div>

    <div style="margin-left:auto; display:flex; gap:8px;">
      <button type="button" class="button button-small" onclick="showAllBookings()" style="font-weight:700;"><?php esc_html_e( 'Show All', 'bsc-cinema-manager' ); ?></button>
      <button type="button" class="button button-small" onclick="resetBookingFilters()" style="font-weight:700;"><?php esc_html_e( 'Today Only', 'bsc-cinema-manager' ); ?></button>
    </div>
  </div>

  <div class="cinema-table-wrap" style="overflow-x:auto;">
    <table class="cinema-data-table" id="bscBookingsTable" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
          <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'TOKEN', 'bsc-cinema-manager' ); ?></th>
          <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'CUSTOMER NAME', 'bsc-cinema-manager' ); ?></th>
          <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'CUSTOMER PHONE', 'bsc-cinema-manager' ); ?></th>
          <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'MOVIE', 'bsc-cinema-manager' ); ?></th>
          <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'SHOWTIME', 'bsc-cinema-manager' ); ?></th>
          <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'DATE', 'bsc-cinema-manager' ); ?></th>
          <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'TOTAL', 'bsc-cinema-manager' ); ?></th>
          <th style="padding:10px 12px; text-align:right;"><?php esc_html_e( 'ACTIONS', 'bsc-cinema-manager' ); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if ( ! empty( $bookings ) ) : foreach ( $bookings as $b ) : 
          $view_url   = admin_url( 'admin.php?page=bsc-booking&subtab=view&id=' . absint( $b->id ) );
          $edit_url   = admin_url( 'admin.php?page=bsc-booking&subtab=edit&id=' . absint( $b->id ) );
          $delete_url = wp_nonce_url(
            admin_url( 'admin.php?page=bsc-booking&subtab=delete&id=' . absint( $b->id ) ),
            'bsc_delete_booking_' . absint( $b->id )
          );

          $row_date   = ! empty( $b->booking_date ) ? date( 'Y-m-d', strtotime( $b->booking_date ) ) : '';
          $human_date = ! empty( $b->booking_date ) ? date_i18n( 'd M Y', strtotime( $b->booking_date ) ) : '--';
          $formatted_st = bsc_format_display_time( $b->showtime );
        ?>
          <tr data-movie="<?php echo esc_attr( $b->movie_title ); ?>" data-time="<?php echo esc_attr( $b->showtime ); ?>" data-date="<?php echo esc_attr( $row_date ); ?>" style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:12px;"><span class="cinema-voucher-tag"><?php echo esc_html( $b->booking_token ); ?></span></td>
            <td style="padding:12px;"><strong><?php echo esc_html( $b->customer_name ? $b->customer_name : 'Walk-In Guest' ); ?></strong></td>
            <td style="padding:12px;"><span style="font-family:monospace; font-size:0.85rem; color:#64748b;"><?php echo esc_html( $b->customer_phone ); ?></span></td>
            <td style="padding:12px;"><strong><?php echo esc_html( $b->movie_title ); ?></strong></td>
            <td style="padding:12px;"><span class="cinema-badge-pill active" style="font-size:0.7rem;"><?php echo esc_html( $formatted_st ); ?></span></td>
            <td style="padding:12px;"><span style="font-size:0.85rem; font-family:monospace; color:#334155; font-weight:700;"><?php echo esc_html( $human_date ); ?></span></td>
            <td style="padding:12px;"><strong style="color:#0d9488; font-family:monospace;"><?php echo esc_html( $currency_symbol ); ?> <?php echo esc_html( number_format( (float) $b->total_amount, 2 ) ); ?></strong></td>
            <td style="padding:12px; text-align:right; white-space:nowrap;">
              <div class="bsc-table-actions">
                <a href="<?php echo esc_url( $view_url ); ?>" class="bsc-action-btn view" title="<?php esc_attr_e( 'View', 'bsc-cinema-manager' ); ?>">
                  <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                  <span><?php esc_html_e( 'View', 'bsc-cinema-manager' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $edit_url ); ?>" class="bsc-action-btn edit" title="<?php esc_attr_e( 'Edit', 'bsc-cinema-manager' ); ?>">
                  <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                  <span><?php esc_html_e( 'Edit', 'bsc-cinema-manager' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $delete_url ); ?>" class="bsc-action-btn delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this booking record?', 'bsc-cinema-manager' ) ); ?>');" title="<?php esc_attr_e( 'Delete', 'bsc-cinema-manager' ); ?>">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  <span><?php esc_html_e( 'Delete', 'bsc-cinema-manager' ); ?></span>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; else : ?>
          <tr id="noBookingsRow"><td colspan="8" style="text-align:center; padding:2.5rem; color:#64748b;"><?php esc_html_e( 'No bookings recorded yet.', 'bsc-cinema-manager' ); ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  function filterBookingsTable() {
    const selectedMovie = document.getElementById('filterMovie').value.toLowerCase();
    const selectedTime  = document.getElementById('filterTime').value.trim().toLowerCase();
    const selectedDate  = document.getElementById('filterDate').value;
    
    const rows = document.querySelectorAll('#bscBookingsTable tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
      if (row.id === 'noBookingsRow') return;

      const rMovie = (row.getAttribute('data-movie') || '').toLowerCase();
      const rTime  = (row.getAttribute('data-time') || '').trim().toLowerCase();
      const rDate  = row.getAttribute('data-date') || '';

      let matchMovie = !selectedMovie || rMovie.indexOf(selectedMovie) !== -1;
      let matchTime  = !selectedTime || rTime === selectedTime;
      let matchDate  = !selectedDate || rDate === selectedDate;

      if (matchMovie && matchTime && matchDate) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    const counterEl = document.getElementById('filteredCount');
    if (counterEl) counterEl.textContent = visibleCount;
  }

  function resetBookingFilters() {
    document.getElementById('filterMovie').value = '';
    document.getElementById('filterTime').value = '';
    document.getElementById('filterDate').value = '<?php echo esc_js( $today_ymd ); ?>';
    filterBookingsTable();
  }

  function showAllBookings() {
    document.getElementById('filterMovie').value = '';
    document.getElementById('filterTime').value = '';
    document.getElementById('filterDate').value = '';
    filterBookingsTable();
  }

  document.addEventListener('DOMContentLoaded', function() {
    filterBookingsTable();
  });
</script>