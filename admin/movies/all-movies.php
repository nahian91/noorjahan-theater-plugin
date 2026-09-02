<?php
if (!defined('ABSPATH')) exit;

// Extract unique categories for filter dropdown
$filter_categories = $wpdb->get_col("SELECT DISTINCT category FROM $table WHERE category != '' ORDER BY category ASC");
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

<div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
  <div class="cinema-panel-header">
    <div>
      <h3 class="cinema-panel-title">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
        <?php esc_html_e('All Feature Movies', 'big-screen-cinemas'); ?> (<span id="filteredMovieCount"><?php echo count($movies); ?></span>)
      </h3>
      <span style="font-size:0.78rem; color:var(--cinema-text-muted);"><?php esc_html_e('Manage multiplex film catalog, official publish dates, and daily screening schedules.', 'big-screen-cinemas'); ?></span>
    </div>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-movies&subtab=add')); ?>" class="button button-primary" style="background:#064e3b; border-color:#064e3b; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
      <?php esc_html_e('Add New Movie', 'big-screen-cinemas'); ?>
    </a>
  </div>

  <!-- Live Filter Toolbar -->
  <div class="bsc-table-filter-toolbar">
    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted);">Search Title:</span>
      <input type="text" id="filterMovieTitle" onkeyup="filterMoviesTable()" placeholder="e.g. Borbaad">
    </div>

    <div style="display:flex; align-items:center; gap:6px;">
      <span style="font-size:0.78rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted);">Category:</span>
      <select id="filterMovieCategory" onchange="filterMoviesTable()">
        <option value="">-- All Categories --</option>
        <?php foreach ($filter_categories as $fc): ?>
          <option value="<?php echo esc_attr($fc); ?>"><?php echo esc_html($fc); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="margin-left:auto;">
      <button type="button" class="button button-small" onclick="resetMovieFilters()" style="font-weight:700;">Reset Filters</button>
    </div>
  </div>

  <div class="cinema-table-wrap">
    <table class="cinema-data-table" id="bscMoviesTable">
      <thead>
        <tr>
          <th>POSTER</th>
          <th>TITLE</th>
          <th>CATEGORY</th>
          <th>RELEASE DATE</th>
          <th>SCREENING SCHEDULE</th>
          <th style="text-align:right;">ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($movies)): foreach ($movies as $m): 
          $view_admin_url = admin_url('admin.php?page=bsc-movies&subtab=view&id=' . $m->id);
          $edit_url       = admin_url('admin.php?page=bsc-movies&subtab=edit&id=' . $m->id);
          $delete_url     = wp_nonce_url(admin_url('admin-post.php?action=bsc_delete_movie&id=' . $m->id), 'bsc_delete_movie_' . $m->id);
          
          // Parse official theatrical release date
          $release_date_disp = '--';
          if (!empty($m->release_date)) {
            $decoded_rd = json_decode($m->release_date, true);
            $raw_rd = (is_array($decoded_rd) && !empty($decoded_rd[0])) ? $decoded_rd[0] : $m->release_date;
            $ts_rd  = strtotime($raw_rd);
            $release_date_disp = $ts_rd ? date_i18n('d M Y', $ts_rd) : esc_html($raw_rd);
          }

          // Parse hierarchical days and times schedule
          $schedules_summary = array();
          if (!empty($m->showtimes)) {
            $decoded_s = json_decode($m->showtimes, true);
            if (is_array($decoded_s) && !empty($decoded_s)) {
              if (isset($decoded_s[0]['date']) && isset($decoded_s[0]['times'])) {
                // Grouped day structure: [ ['date' => '...', 'times' => [...] ] ]
                foreach ($decoded_s as $day_slot) {
                  $d_label = date_i18n('d M', strtotime($day_slot['date']));
                  $day_name = date_i18n('D', strtotime($day_slot['date']));
                  $formatted_times = array_map(function($t) {
                    return (strpos($t, ':') !== false && strlen($t) <= 5) ? date('h:i A', strtotime($t)) : $t;
                  }, (array)($day_slot['times'] ?? array()));
                  
                  $schedules_summary[] = '<div style="margin-bottom:3px;"><strong style="color:var(--cinema-text-main); font-size:0.75rem;">' . esc_html($d_label . ' (' . $day_name . ')') . ':</strong> <span style="color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono); font-weight:700; font-size:0.75rem;">' . esc_html(implode(', ', $formatted_times)) . '</span></div>';
                }
              } else {
                // Legacy simple times array
                $formatted_times = array_map(function($t) {
                  $clean_t = is_array($t) ? ($t['time'] ?? '18:00') : $t;
                  return (strpos($clean_t, ':') !== false && strlen($clean_t) <= 5) ? date('h:i A', strtotime($clean_t)) : $clean_t;
                }, $decoded_s);
                $schedules_summary[] = '<span style="color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono); font-weight:700; font-size:0.78rem;">' . esc_html(implode(', ', $formatted_times)) . '</span>';
              }
            } else {
              $schedules_summary[] = '<span style="color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono); font-weight:700; font-size:0.78rem;">' . esc_html($m->showtimes) . '</span>';
            }
          }
        ?>
          <tr data-title="<?php echo esc_attr(strtolower($m->title)); ?>" data-category="<?php echo esc_attr($m->category ?: 'Now Showing'); ?>">
            <td><img src="<?php echo esc_url($m->poster_url); ?>" style="width:44px; height:60px; border-radius:4px; object-fit:cover;"></td>
            <td><strong><?php echo esc_html($m->title); ?></strong></td>
            <td><span class="cinema-badge-pill active" style="font-size:0.7rem;"><?php echo esc_html($m->category ?: 'Now Showing'); ?></span></td>
            <td><strong style="color:var(--cinema-text-main); font-size:0.8rem; font-family:var(--cinema-font-mono);"><?php echo esc_html($release_date_disp); ?></strong></td>
            <td>
              <div>
                <?php echo !empty($schedules_summary) ? implode('', $schedules_summary) : '<span style="color:var(--cinema-text-muted); font-size:0.75rem;">No active schedules</span>'; ?>
              </div>
            </td>
            <td style="text-align:right; white-space:nowrap;">
              <div class="bsc-table-actions">
                <a href="<?php echo esc_url($view_admin_url); ?>" class="bsc-action-btn view">
                  <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                  <span>View</span>
                </a>
                <a href="<?php echo esc_url($edit_url); ?>" class="bsc-action-btn edit">
                  <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                  <span>Edit</span>
                </a>
                <a href="<?php echo esc_url($delete_url); ?>" class="bsc-action-btn delete" onclick="return confirm('Are you sure you want to permanently delete this movie from the database?');">
                  <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                  <span>Delete</span>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr id="noMoviesRow"><td colspan="6" style="text-align:center; padding:2.5rem; color:var(--cinema-text-muted);"><?php esc_html_e('No movies published in the catalog.', 'big-screen-cinemas'); ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  function filterMoviesTable() {
    const searchTitle = document.getElementById('filterMovieTitle').value.toLowerCase().trim();
    const selectedCat = document.getElementById('filterMovieCategory').value;

    const rows = document.querySelectorAll('#bscMoviesTable tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
      if (row.id === 'noMoviesRow') return;

      const rTitle = row.getAttribute('data-title') || '';
      const rCat   = row.getAttribute('data-category') || '';

      let matchTitle = !searchTitle || rTitle.includes(searchTitle);
      let matchCat   = !selectedCat || rCat === selectedCat;

      if (matchTitle && matchCat) {
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
    document.getElementById('filterMovieCategory').value = '';
    filterMoviesTable();
  }
</script>