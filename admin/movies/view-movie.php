<?php
if (!defined('ABSPATH')) exit;

$movie = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $movie_id));
if (!$movie) {
  echo '<div class="cinema-panel"><p>' . esc_html__('Movie record not found.', 'big-screen-cinemas') . '</p></div>';
  return;
}

$cast_list = !empty($movie->cast_crew) ? json_decode($movie->cast_crew, true) : array();

// Parse official single theatrical release date
$official_release_date_str = '--';
if (!empty($movie->release_date)) {
  $decoded_rd = json_decode($movie->release_date, true);
  $raw_rd = (is_array($decoded_rd) && !empty($decoded_rd[0])) ? $decoded_rd[0] : $movie->release_date;
  $ts_rd  = strtotime($raw_rd);
  $official_release_date_str = $ts_rd ? date_i18n('d F Y', $ts_rd) : esc_html($raw_rd);
}

// Parse hierarchical screening schedule (Days & Time slots)
$day_schedules = array();
if (!empty($movie->showtimes)) {
  $decoded_s = json_decode($movie->showtimes, true);
  if (is_array($decoded_s) && !empty($decoded_s)) {
    if (isset($decoded_s[0]['date']) && isset($decoded_s[0]['times'])) {
      $day_schedules = $decoded_s;
    } elseif (isset($decoded_s[0]['date']) && isset($decoded_s[0]['time'])) {
      $grouped = array();
      foreach ($decoded_s as $item) {
        $d = $item['date'] ?? current_time('Y-m-d');
        $t = $item['time'] ?? '18:00';
        if (!isset($grouped[$d])) {
          $grouped[$d] = array('date' => $d, 'times' => array());
        }
        $grouped[$d]['times'][] = $t;
      }
      $day_schedules = array_values($grouped);
    } else {
      $day_schedules = array(
        array(
          'date'  => current_time('Y-m-d'),
          'times' => is_array($decoded_s) ? $decoded_s : array('18:00')
        )
      );
    }
  }
}
?>
<style>
  .bsc-movie-dossier-grid {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    align-items: start;
  }
  .bsc-poster-sidebar-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: 16px;
    padding: 1.25rem;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
  }
  .bsc-poster-sidebar-card img {
    width: 100%;
    height: auto;
    border-radius: 10px;
    border: 1px solid var(--cinema-border);
    display: block;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
  }
  .bsc-movie-content-main {
    background: #ffffff;
    border: 1px solid var(--cinema-border);
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    overflow: hidden;
  }
  .bsc-movie-banner-head {
    background: linear-gradient(135deg, #09171c 0%, #063d35 60%, #0d9488 100%);
    padding: 2rem 2.25rem;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .bsc-movie-banner-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: rgba(13, 148, 136, 0.25);
    color: #99f6e4;
    border: 1px solid rgba(13, 148, 136, 0.5);
  }
  .bsc-movie-body-wrap {
    padding: 2rem 2.25rem;
  }
  .bsc-metric-card-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.75rem;
  }
  .bsc-metric-item {
    background: #f8fafc;
    border: 1px solid var(--cinema-border);
    border-radius: 10px;
    padding: 1.15rem;
  }
  .bsc-metric-item .label {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--cinema-text-muted);
    letter-spacing: 0.05em;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
  }
  .bsc-metric-item .label svg {
    width: 13px;
    height: 13px;
    fill: currentColor;
  }
  .bsc-metric-item .val {
    font-size: 1.05rem;
    font-weight: 900;
    color: var(--cinema-text-main);
  }
  .bsc-metric-item .sub {
    font-size: 0.78rem;
    color: var(--cinema-text-muted);
    margin-top: 2px;
  }
  .bsc-synopsis-box {
    background: #f8fafc;
    border: 1px solid var(--cinema-border);
    border-radius: 12px;
    padding: 1.5rem;
  }
  .bsc-synopsis-box h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.88rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--cinema-text-muted);
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .bsc-synopsis-box h4 svg {
    width: 15px;
    height: 15px;
    fill: currentColor;
  }
  .bsc-schedule-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--cinema-border);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 1.75rem;
    background: #ffffff;
  }
  .bsc-schedule-table th {
    padding: 0.75rem 1rem;
    background: #f1f5f9;
    color: var(--cinema-text-muted);
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    border-bottom: 1px solid var(--cinema-border);
  }
  .bsc-schedule-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--cinema-border-light);
    font-size: 0.9rem;
    color: var(--cinema-text-main);
  }
  .bsc-schedule-table tr:last-child td {
    border-bottom: none;
  }
  @media (max-width: 1024px) {
    .bsc-movie-dossier-grid { grid-template-columns: 1fr; }
    .bsc-metric-card-grid { grid-template-columns: 1fr 1fr; }
    .bsc-movie-banner-head { flex-direction: column; gap: 1rem; }
  }
</style>

<div class="bsc-movie-dossier-grid">
  
  <!-- Left Column: Poster & Direct Asset Access -->
  <div class="bsc-poster-sidebar-card">
    <img src="<?php echo esc_url($movie->poster_url ?: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=700&auto=format&fit=crop&q=75'); ?>" alt="<?php echo esc_attr($movie->title); ?>">
    
    <div style="margin-top: 1.25rem; background:#f8fafc; border:1px solid var(--cinema-border); padding:1rem; border-radius:10px;">
      <span style="font-size:0.72rem; text-transform:uppercase; font-weight:800; color:var(--cinema-text-muted); display:block; margin-bottom:8px;">Direct Media Links</span>
      <div style="font-size:0.82rem; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
        <strong style="color:var(--cinema-text-main);">Poster File:</strong> 
        <a href="<?php echo esc_url($movie->poster_url); ?>" target="_blank" style="color:var(--cinema-accent-teal); font-weight:700; text-decoration:none;">Open Asset ↗</a>
      </div>
      <?php if (!empty($movie->trailer_url)): ?>
        <div style="font-size:0.82rem; display:flex; justify-content:space-between; align-items:center;">
          <strong style="color:var(--cinema-text-main);">Trailer Stream:</strong> 
          <a href="<?php echo esc_url($movie->trailer_url); ?>" target="_blank" style="color:var(--cinema-accent-teal); font-weight:700; text-decoration:none;">Open Video ↗</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Cinematic Banner & Deep Metadata -->
  <div class="bsc-movie-content-main">
    
    <!-- Cinematic Header Banner -->
    <div class="bsc-movie-banner-head">
      <div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
          <span class="bsc-movie-banner-badge"><?php echo esc_html($movie->category ?: 'Now Showing'); ?></span>
          <span style="font-size:0.75rem; color:#99f6e4; font-weight:800; font-family:var(--cinema-font-mono);"><?php echo esc_html($movie->badge_tag); ?></span>
        </div>
        <h2 style="font-size: 2.1rem; font-weight: 900; margin: 0 0 0.4rem 0; color: #ffffff;">
          <?php echo esc_html($movie->title); ?>
        </h2>
        <div style="font-size: 0.85rem; color: #cbd5e1; font-family: var(--cinema-font-mono);">
          Record ID: <strong>#MOVIE-<?php echo intval($movie->id); ?></strong> • Registered: <strong><?php echo esc_html($movie->created_at ?? 'N/A'); ?></strong>
        </div>
      </div>

      <div class="bsc-table-actions">
        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-movies&subtab=edit&id=' . $movie->id)); ?>" class="bsc-action-btn edit" style="padding:8px 14px; font-size:0.85rem; background:#fef9c3; border-color:#fde047; color:#854d0e;">
          <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
          <span>Edit Movie</span>
        </a>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bsc_delete_movie&id=' . $movie->id), 'bsc_delete_movie_' . $movie->id)); ?>" class="bsc-action-btn delete" style="padding:8px 14px; font-size:0.85rem;" onclick="return confirm('Permanently delete this movie?');">
          <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
          <span>Delete</span>
        </a>
      </div>
    </div>

    <!-- Body Information -->
    <div class="bsc-movie-body-wrap">
      
      <!-- Key Metric Grid Cards (Now includes Official Release Date) -->
      <div class="bsc-metric-card-grid">
        <div class="bsc-metric-item">
          <div class="label">
            <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
            Master Rating
          </div>
          <div class="val" style="color:var(--cinema-accent-amber);">★ <?php echo esc_html($movie->rating); ?> / 10</div>
          <div class="sub"><?php echo esc_html($movie->age_rating); ?></div>
        </div>

        <div class="bsc-metric-item">
          <div class="label">
            <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
            Release Date
          </div>
          <div class="val" style="color:var(--cinema-accent-teal); font-size:0.95rem; font-family:var(--cinema-font-mono);"><?php echo esc_html($official_release_date_str); ?></div>
          <div class="sub">Official Theatrical</div>
        </div>

        <div class="bsc-metric-item">
          <div class="label">
            <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            Runtime & Sound
          </div>
          <div class="val" style="font-size:0.95rem;"><?php echo esc_html($movie->duration); ?></div>
          <div class="sub"><?php echo esc_html($movie->audio_language); ?></div>
        </div>

        <div class="bsc-metric-item">
          <div class="label">
            <svg viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
            Auditorium
          </div>
          <div class="val" style="color:var(--cinema-accent-teal); font-size:0.95rem;"><?php echo esc_html($movie->auditorium); ?></div>
          <div class="sub"><?php echo esc_html($movie->genre); ?></div>
        </div>
      </div>

      <!-- Production Team Strip -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.75rem;">
        <div style="background:#f8fafc; border:1px solid var(--cinema-border); border-radius:10px; padding:1.15rem;">
          <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted); display:block; margin-bottom:4px;">Director</span>
          <strong style="font-size:1.05rem; color:var(--cinema-text-main);"><?php echo esc_html($movie->director ?: 'Not Specified'); ?></strong>
        </div>
        <div style="background:#f8fafc; border:1px solid var(--cinema-border); border-radius:10px; padding:1.15rem;">
          <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted); display:block; margin-bottom:4px;">Screenwriters & Story</span>
          <strong style="font-size:1.05rem; color:var(--cinema-text-main);"><?php echo esc_html($movie->writers ?: 'Not Specified'); ?></strong>
        </div>
      </div>

      <!-- Theater Screening Schedules Table (Date, Day & Time Slots) -->
      <div style="margin-bottom:1.75rem;">
        <h4 style="margin:0 0 0.75rem 0; font-size:0.88rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted); letter-spacing:0.05em; display:flex; align-items:center; gap:6px;">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
          Theater Screening Schedule (Days & Time Slots)
        </h4>

        <table class="bsc-schedule-table">
          <thead>
            <tr>
              <th style="width:40px;">#</th>
              <th style="width:170px;">Screening Date</th>
              <th style="width:130px;">Day</th>
              <th>Available Showtimes</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              if (!empty($day_schedules)):
                $row_idx = 1;
                foreach ($day_schedules as $day_slot):
                  $d_raw = $day_slot['date'] ?? current_time('Y-m-d');
                  $human_d = date_i18n('d F Y', strtotime($d_raw));
                  $day_name = date_i18n('l', strtotime($d_raw));
                  $times_arr = (array)($day_slot['times'] ?? array('18:00'));
            ?>
              <tr>
                <td style="font-weight:800; color:var(--cinema-text-muted);"><?php echo $row_idx++; ?></td>
                <td><strong style="font-family:var(--cinema-font-mono); color:var(--cinema-text-main);"><?php echo esc_html($human_d); ?></strong></td>
                <td><span style="font-weight:800; color:var(--cinema-accent-amber); font-size:0.85rem; text-transform:uppercase;"><?php echo esc_html($day_name); ?></span></td>
                <td>
                  <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    <?php foreach ($times_arr as $t): 
                      $formatted_t = (strpos($t, ':') !== false && strlen($t) <= 5) ? date('h:i A', strtotime($t)) : $t;
                    ?>
                      <span class="cinema-badge-pill active" style="font-family:var(--cinema-font-mono); font-size:0.8rem; padding:3px 8px;">
                        ⏰ <?php echo esc_html($formatted_t); ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </td>
              </tr>
            <?php 
                endforeach;
              else: 
            ?>
              <tr><td colspan="4" style="text-align:center; color:var(--cinema-text-muted); padding:1.5rem;">No theater screening schedules recorded.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- 2-Column: Short Summary & Full Storyline -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.75rem;">
        <div class="bsc-synopsis-box">
          <h4>
            <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            Short Summary (Card Excerpt)
          </h4>
          <p style="font-size: 0.92rem; line-height: 1.8; color: var(--cinema-text-secondary); margin: 0;">
            <?php echo nl2br(esc_html($movie->short_description ?: 'No short summary provided.')); ?>
          </p>
        </div>

        <div class="bsc-synopsis-box">
          <h4>
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            Full Storyline & Synopsis
          </h4>
          <p style="font-size: 0.92rem; line-height: 1.8; color: var(--cinema-text-secondary); margin: 0;">
            <?php echo nl2br(esc_html($movie->synopsis ?: 'No full synopsis provided.')); ?>
          </p>
        </div>
      </div>

      <!-- 4K Trailer Preview Video -->
      <?php if (!empty($movie->trailer_url)): ?>
        <div style="margin-bottom:1.75rem;">
          <h4 style="margin:0 0 0.75rem 0; font-size:0.88rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted); letter-spacing:0.05em; display:flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            Official 4K Trailer Preview
          </h4>
          <video src="<?php echo esc_url($movie->trailer_url); ?>" controls style="width:100%; max-height:400px; border-radius:12px; background:#000; display:block; box-shadow:0 10px 30px rgba(0,0,0,0.2);"></video>
        </div>
      <?php endif; ?>

      <!-- Lead Cast Roster -->
      <?php if (!empty($cast_list)): ?>
        <div style="margin-bottom:1.5rem;">
          <h4 style="margin:0 0 0.85rem 0; font-size:0.88rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted); letter-spacing:0.05em; display:flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            Lead Cast Members (<?php echo count($cast_list); ?>)
          </h4>
          <div class="bsc-cast-grid-view">
            <?php foreach ($cast_list as $cast): ?>
              <div class="bsc-cast-card-mini">
                <img src="<?php echo esc_url(!empty($cast['photo']) ? $cast['photo'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80'); ?>" class="bsc-cast-avatar" alt="<?php echo esc_attr($cast['name']); ?>">
                <div>
                  <strong style="font-size:0.92rem; color:var(--cinema-text-main); display:block;"><?php echo esc_html($cast['name'] ?: 'Unknown'); ?></strong>
                  <span style="font-size:0.78rem; color:var(--cinema-text-muted);"><?php echo esc_html($cast['role'] ?: 'Cast Member'); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Footer Navigation -->
      <div style="margin-top:2rem; border-top:1px solid var(--cinema-border-light); padding-top:1.25rem;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-movies&subtab=all')); ?>" class="button button-secondary" style="font-weight:700;">
          ← <?php esc_html_e('Back to All Movies', 'big-screen-cinemas'); ?>
        </a>
      </div>

    </div>

  </div>

</div>