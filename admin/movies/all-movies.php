<?php
/**
 * Movie Module - All Movies Catalog View
 *
 * Path: admin/movies/all-movies.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$today_ymd = current_time( 'Y-m-d' );
global $wpdb;
$table_bookings      = $wpdb->prefix . 'bsc_bookings';
$total_hall_capacity = 23; // Boutique 23-seat matrix capacity per screening
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
  .bsc-table-filter-toolbar input {
    padding: 6px 10px;
    font-size: 0.85rem;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 6px;
    background: #ffffff;
    color: var(--cinema-text-main, #0f172a);
    outline: none;
  }
  .bsc-table-filter-toolbar input:focus {
    border-color: var(--cinema-accent-teal, #0d9488);
  }
  .bsc-showtime-chip {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    padding: 4px 10px;
    margin: 3px 6px 3px 0;
    border-radius: 6px;
    background: #f0fdfa;
    color: #0d9488;
    border: 1px solid #ccfbf1;
    font-family: var(--cinema-font-mono, monospace);
    font-weight: 700;
    font-size: 0.78rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
  }
  .bsc-showtime-chip .seats-left {
    font-size: 0.68rem;
    font-weight: 800;
    padding: 1px 5px;
    border-radius: 4px;
    margin-top: 2px;
    background: #ecfdf5;
    color: #059669;
  }
  .bsc-showtime-chip .seats-left.sold-out {
    background: #fef2f2;
    color: #ef4444;
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
  .bsc-action-btn.view:hover { background: #1d4ed8; color: #fff; }
  .bsc-action-btn.edit {
    background: #fefce8;
    border-color: #fef08a;
    color: #a16207;
  }
  .bsc-action-btn.edit:hover { background: #a16207; color: #fff; }
  .bsc-action-btn.delete {
    background: #fef2f2;
    border-color: #fecaca;
    color: #b91c1c;
  }
  .bsc-action-btn.delete:hover { background: #b91c1c; color: #fff; }
</style>

<div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
  <div class="cinema-panel-header" style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0;">
    <div>
      <h3 class="cinema-panel-title" style="margin:0 0 4px 0; font-size:1.15rem; font-weight:800; color:#0f172a;">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align:-3px; margin-right:4px;"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
        <?php esc_html_e( 'All Feature Movies', 'bsc-cinema-manager' ); ?> (<span id="filteredMovieCount"><?php echo count( $movies ); ?></span>)
      </h3>
      <span style="font-size:0.78rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'Manage multiplex film catalog, official publish dates, and today\'s live seat availability.', 'bsc-cinema-manager' ); ?></span>
    </div>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-movies&subtab=add' ) ); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      <?php esc_html_e( 'Add New Movie', 'bsc-cinema-manager' ); ?>
    </a>
  </div>

  <!-- Live Filter Toolbar -->
  <div class="bsc-table-filter-toolbar">
    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted, #64748b);">Search Title:</span>
      <input type="text" id="filterMovieTitle" onkeyup="filterMoviesTable()" placeholder="e.g. Borbaad">
    </div>

    <div style="margin-left:auto;">
      <button type="button" class="button button-small" onclick="resetMovieFilters()" style="font-weight:700;">Reset Filter</button>
    </div>
  </div>

  <div class="cinema-table-wrap" style="overflow-x:auto;">
    <table class="cinema-data-table" id="bscMoviesTable" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
          <th style="padding:10px 12px; text-align:left;">POSTER</th>
          <th style="padding:10px 12px; text-align:left;">TITLE</th>
          <th style="padding:10px 12px; text-align:left;">RELEASE DATE</th>
          <th style="padding:10px 12px; text-align:left;">TODAY'S SHOWTIMES & REMAINING TICKETS</th>
          <th style="padding:10px 12px; text-align:right;">ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        <?php if ( ! empty( $movies ) ) : foreach ( $movies as $m ) : 
          $view_admin_url = admin_url( 'admin.php?page=bsc-movies&subtab=view&id=' . absint( $m->id ) );
          $edit_url       = admin_url( 'admin.php?page=bsc-movies&subtab=edit&id=' . absint( $m->id ) );
          $delete_url     = wp_nonce_url(
            admin_url( 'admin.php?page=bsc-movies&subtab=delete&id=' . absint( $m->id ) ),
            'bsc_delete_movie_' . absint( $m->id )
          );
          
          // Parse official theatrical release date
          $release_date_disp = '--';
          if ( ! empty( $m->release_date ) ) {
            $decoded_rd = json_decode( $m->release_date, true );
            $raw_rd = ( is_array( $decoded_rd ) && ! empty( $decoded_rd[0] ) ) ? $decoded_rd[0] : $m->release_date;
            $ts_rd  = strtotime( $raw_rd );
            $release_date_disp = $ts_rd ? date_i18n( 'd M Y', $ts_rd ) : esc_html( $raw_rd );
          }

          // Parse showtimes for TODAY and calculate live remaining tickets
          $today_show_data = array();
          if ( ! empty( $m->showtimes ) ) {
            $decoded_s = json_decode( $m->showtimes, true );
            if ( is_array( $decoded_s ) && ! empty( $decoded_s ) ) {
              if ( isset( $decoded_s[0]['date'] ) && isset( $decoded_s[0]['times'] ) ) {
                foreach ( $decoded_s as $day_slot ) {
                  if ( ! empty( $day_slot['date'] ) && $day_slot['date'] === $today_ymd && ! empty( $day_slot['times'] ) ) {
                    foreach ( (array) $day_slot['times'] as $t ) {
                      $formatted_time = ( false !== strpos( $t, ':' ) && strlen( $t ) <= 5 ) ? date( 'h:i A', strtotime( $t ) ) : $t;
                      
                      $booked_seats_string = $wpdb->get_var( $wpdb->prepare(
                        "SELECT GROUP_CONCAT(seats SEPARATOR ', ') FROM {$table_bookings} WHERE movie_title = %s AND showtime = %s AND DATE(booking_date) = %s AND payment_status != 'Cancelled'",
                        $m->title,
                        $t,
                        $today_ymd
                      ) );

                      $booked_count = 0;
                      if ( ! empty( $booked_seats_string ) ) {
                        $seats_array = array_filter( array_map( 'trim', explode( ',', $booked_seats_string ) ) );
                        $booked_count = count( $seats_array );
                      }

                      $remaining_seats = max( 0, $total_hall_capacity - $booked_count );
                      $today_show_data[] = array(
                        'time'      => $formatted_time,
                        'remaining' => $remaining_seats,
                      );
                    }
                  }
                }
              }
            }
          }
        ?>
          <tr data-title="<?php echo esc_attr( strtolower( $m->title ) ); ?>" style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:12px;">
              <img src="<?php echo esc_url( $m->poster_url ); ?>" alt="<?php echo esc_attr( $m->title ); ?>" style="width:44px; height:60px; border-radius:4px; object-fit:cover;">
            </td>
            <td style="padding:12px;"><strong><?php echo esc_html( $m->title ); ?></strong></td>
            <td style="padding:12px;"><strong style="color:var(--cinema-text-main, #0f172a); font-size:0.8rem; font-family:var(--cinema-font-mono, monospace);"><?php echo esc_html( $release_date_disp ); ?></strong></td>
            <td style="padding:12px;">
              <?php if ( ! empty( $today_show_data ) ) : ?>
                <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                  <?php foreach ( $today_show_data as $show ) : 
                    $is_sold_out = ( 0 === $show['remaining'] );
                  ?>
                    <span class="bsc-showtime-chip">
                      <span><?php echo esc_html( $show['time'] ); ?></span>
                      <span class="seats-left <?php echo $is_sold_out ? 'sold-out' : ''; ?>">
                        <?php echo $is_sold_out ? esc_html__( 'Sold Out', 'bsc-cinema-manager' ) : esc_html( $show['remaining'] . ' seats left' ); ?>
                      </span>
                    </span>
                  <?php endforeach; ?>
                </div>
              <?php else : ?>
                <span style="color:var(--cinema-text-muted, #64748b); font-size:0.75rem; font-style:italic;"><?php esc_html_e( 'No shows today', 'bsc-cinema-manager' ); ?></span>
              <?php endif; ?>
            </td>
            <td style="padding:12px; text-align:right; white-space:nowrap;">
              <div class="bsc-table-actions">
                <a href="<?php echo esc_url( $view_admin_url ); ?>" class="bsc-action-btn view" title="<?php esc_attr_e( 'View', 'bsc-cinema-manager' ); ?>">
                  <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                  <span><?php esc_html_e( 'View', 'bsc-cinema-manager' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $edit_url ); ?>" class="bsc-action-btn edit" title="<?php esc_attr_e( 'Edit', 'bsc-cinema-manager' ); ?>">
                  <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                  <span><?php esc_html_e( 'Edit', 'bsc-cinema-manager' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $delete_url ); ?>" class="bsc-action-btn delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this movie from the database?', 'bsc-cinema-manager' ) ); ?>');" title="<?php esc_attr_e( 'Delete', 'bsc-cinema-manager' ); ?>">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  <span><?php esc_html_e( 'Delete', 'bsc-cinema-manager' ); ?></span>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; else : ?>
          <tr id="noMoviesRow"><td colspan="5" style="text-align:center; padding:2.5rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'No movies published in the catalog.', 'bsc-cinema-manager' ); ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  function filterMoviesTable() {
    const searchTitle = document.getElementById('filterMovieTitle').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#bscMoviesTable tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
      if (row.id === 'noMoviesRow') return;

      const rTitle = row.getAttribute('data-title') || '';
      let matchTitle = !searchTitle || rTitle.includes(searchTitle);

      if (matchTitle) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    const counterEl = document.getElementById('filteredMovieCount');
    if (counterEl) counterEl.innerText = visibleCount;
  }

  function resetMovieFilters() {
    document.getElementById('filterMovieTitle').value = '';
    filterMoviesTable();
  }
</script>