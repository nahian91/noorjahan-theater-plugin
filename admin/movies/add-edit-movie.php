<?php
if (!defined('ABSPATH')) exit;

$movie = null;
$is_edit = ($subtab === 'edit' && $movie_id > 0);

if ($is_edit) {
  $movie = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $movie_id));
}

$cast_list = array();
if ($movie && !empty($movie->cast_crew)) {
  $cast_list = json_decode($movie->cast_crew, true);
}
if (empty($cast_list)) {
  $cast_list = array(array('name' => '', 'role' => '', 'photo' => ''));
}

// Parse official movie release date (publish date)
$official_release_date = current_time('Y-m-d');
if ($movie && !empty($movie->release_date)) {
  $decoded_dates = json_decode($movie->release_date, true);
  if (is_array($decoded_dates) && !empty($decoded_dates)) {
    $official_release_date = reset($decoded_dates);
  } else {
    $parts = array_map('trim', explode(',', $movie->release_date));
    if (!empty($parts[0])) {
      $official_release_date = $parts[0];
    }
  }
}

// Parse grouped date schedules with nested times
$day_schedules = array(
  array(
    'date'  => current_time('Y-m-d'),
    'times' => array('18:00')
  )
);

if ($movie && !empty($movie->showtimes)) {
  $decoded_showtimes = json_decode($movie->showtimes, true);
  if (is_array($decoded_showtimes) && !empty($decoded_showtimes)) {
    if (isset($decoded_showtimes[0]['date']) && isset($decoded_showtimes[0]['times'])) {
      $day_schedules = $decoded_showtimes;
    } elseif (isset($decoded_showtimes[0]['date']) && isset($decoded_showtimes[0]['time'])) {
      $grouped = array();
      foreach ($decoded_showtimes as $item) {
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
          'times' => is_array($decoded_showtimes) ? $decoded_showtimes : array('18:00')
        )
      );
    }
  }
}
?>
<div class="cinema-panel" style="max-width:900px; margin:0 auto; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
  <h3 class="cinema-panel-title" style="margin-bottom:1.5rem;">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
    <?php echo $is_edit ? esc_html__('Edit Feature Movie Presentation', 'big-screen-cinemas') : esc_html__('Add New Feature Movie (Full Spec)', 'big-screen-cinemas'); ?>
  </h3>
  
  <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php 
    if ($is_edit) {
      wp_nonce_field('bsc_edit_movie_verify');
      echo '<input type="hidden" name="action" value="bsc_edit_movie">';
      echo '<input type="hidden" name="movie_id" value="' . esc_attr($movie->id) . '">';
    } else {
      wp_nonce_field('bsc_add_movie_verify');
      echo '<input type="hidden" name="action" value="bsc_add_movie">';
    }
    ?>

    <div class="bsc-form-grid-2">
      <div class="bsc-form-group">
        <label><?php esc_html_e('Movie Title', 'big-screen-cinemas'); ?> *</label>
        <input type="text" name="movie_title" required value="<?php echo esc_attr($movie->title ?? ''); ?>" placeholder="e.g. Borbaad">
      </div>
      <div class="bsc-form-group">
        <label><?php esc_html_e('Category', 'big-screen-cinemas'); ?> *</label>
        <select name="movie_category" required>
          <?php
            $categories = array('Now Showing', 'Upcoming', 'Special Premier', 'VIP Exclusive');
            $selected_cat = $movie->category ?? 'Now Showing';
            foreach ($categories as $cat) {
              echo '<option value="' . esc_attr($cat) . '" ' . selected($selected_cat, $cat, false) . '>' . esc_html($cat) . '</option>';
            }
          ?>
        </select>
      </div>
    </div>

    <div class="bsc-form-grid-3">
      <div class="bsc-form-group">
        <label><?php esc_html_e('Genre', 'big-screen-cinemas'); ?> *</label>
        <input type="text" name="movie_genre" required value="<?php echo esc_attr($movie->genre ?? ''); ?>" placeholder="e.g. Action / Thriller">
      </div>
      <div class="bsc-form-group">
        <label><?php esc_html_e('Rating (IMDb / Master)', 'big-screen-cinemas'); ?> *</label>
        <input type="text" name="movie_rating" required value="<?php echo esc_attr($movie->rating ?? ''); ?>" placeholder="e.g. 9.0">
      </div>
      <div class="bsc-form-group">
        <label><?php esc_html_e('Age / Censor Rating', 'big-screen-cinemas'); ?> *</label>
        <input type="text" name="movie_age_rating" required value="<?php echo esc_attr($movie->age_rating ?? 'R-Rated (18+)'); ?>" placeholder="e.g. R-Rated (18+), PG-13">
      </div>
    </div>

    <div class="bsc-form-grid-3">
      <div class="bsc-form-group">
        <label><?php esc_html_e('Duration', 'big-screen-cinemas'); ?> *</label>
        <input type="text" name="movie_duration" required value="<?php echo esc_attr($movie->duration ?? ''); ?>" placeholder="e.g. 02h 25m">
      </div>
      <div class="bsc-form-group">
        <label><?php esc_html_e('Language & Audio System', 'big-screen-cinemas'); ?> *</label>
        <input type="text" name="movie_audio_lang" required value="<?php echo esc_attr($movie->audio_language ?? 'Bengali (Dolby Atmos 128-Ch)'); ?>" placeholder="e.g. Bengali (Dolby Atmos 128-Ch)">
      </div>
      <div class="bsc-form-group">
        <label><?php esc_html_e('Format Badge Tag', 'big-screen-cinemas'); ?> *</label>
        <input type="text" name="movie_badge" required value="<?php echo esc_attr($movie->badge_tag ?? '4K DUAL LASER'); ?>" placeholder="e.g. 4K DUAL LASER, IMAX LASER">
      </div>
    </div>

    <div class="bsc-form-grid-2">
      <div class="bsc-form-group">
        <label><?php esc_html_e('Director', 'big-screen-cinemas'); ?> *</label>
        <input type="text" name="movie_director" required value="<?php echo esc_attr($movie->director ?? ''); ?>" placeholder="e.g. Raihan Rafi">
      </div>
      <div class="bsc-form-group">
        <label><?php esc_html_e('Screenwriters / Story', 'big-screen-cinemas'); ?> *</label>
        <input type="text" name="movie_writers" required value="<?php echo esc_attr($movie->writers ?? ''); ?>" placeholder="e.g. Mehedi Hasan Hadan">
      </div>
    </div>

    <div class="bsc-form-grid-2">
      <div class="bsc-form-group">
        <label><?php esc_html_e('Auditorium Allocation', 'big-screen-cinemas'); ?> *</label>
        <select name="movie_auditorium" required>
          <?php
            $halls = array('Auditorium 1 (23 Seats)', 'Auditorium 2 (23 Seats)', 'Auditorium 3 (23 Seats)');
            $selected_hall = $movie->auditorium ?? 'Auditorium 1 (23 Seats)';
            foreach ($halls as $hall) {
              echo '<option value="' . esc_attr($hall) . '" ' . selected($selected_hall, $hall, false) . '>' . esc_html($hall) . '</option>';
            }
          ?>
        </select>
      </div>

      <!-- OFFICIAL THEATRICAL / PUBLISHER RELEASE DATE -->
      <div class="bsc-form-group">
        <label><?php esc_html_e('Official Movie Release Date', 'big-screen-cinemas'); ?> *</label>
        <input type="date" name="movie_release_date" required value="<?php echo esc_attr($official_release_date); ?>">
        <p class="description" style="font-size:0.75rem; color:#64748b; margin-top:3px;">
          <?php esc_html_e('The official public release date from the film studio/director.', 'big-screen-cinemas'); ?>
        </p>
      </div>
    </div>

    <!-- HIERARCHICAL DAY & SHOWTIMES REPEATER -->
    <div class="bsc-form-group" style="background:#f8fafc; border:1px solid var(--cinema-border); padding:1.25rem; border-radius:var(--cinema-radius-sm); margin-bottom: 1.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <div>
          <label style="margin:0; font-weight:800; display:flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
            <?php esc_html_e('Screening Schedule (Days & Daily Showtimes)', 'big-screen-cinemas'); ?> *
          </label>
          <span style="font-size:0.75rem; color:#64748b;">
            <?php esc_html_e('Add each screening day and configure multiple showtimes for that specific day.', 'big-screen-cinemas'); ?>
          </span>
        </div>
        <button type="button" class="button button-primary" id="btn_add_day_block" style="background:#0f766e; border-color:#0f766e;">+ <?php esc_html_e('Add Day Schedule', 'big-screen-cinemas'); ?></button>
      </div>

      <div id="bsc_day_schedule_container" style="display:flex; flex-direction:column; gap:14px;">
        <?php foreach ($day_schedules as $day_index => $schedule_item): 
          $item_date = $schedule_item['date'] ?? current_time('Y-m-d');
          $item_times = !empty($schedule_item['times']) ? (array)$schedule_item['times'] : array('18:00');
        ?>
          <div class="bsc-day-schedule-card" data-day-index="<?php echo intval($day_index); ?>" style="background:#fff; border:1px solid var(--cinema-border); border-radius:8px; padding:14px;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:12px;">
              <div style="display:flex; align-items:center; gap:10px; flex:1;">
                <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:#0f766e; letter-spacing:0.04em;">Screening Day</span>
                <input type="date" name="schedule_days[<?php echo intval($day_index); ?>][date]" value="<?php echo esc_attr($item_date); ?>" required style="max-width:180px;">
              </div>
              <button type="button" class="bsc-btn-remove-day" onclick="removeDayBlock(this)" style="background:none; border:none; color:#ef4444; font-weight:700; cursor:pointer; font-size:0.85rem;">✕ Remove Day</button>
            </div>

            <div>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <label style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; margin:0;">Showtime Slots for this Day</label>
                <button type="button" class="button button-small" onclick="addTimeSlotToDay(this)">+ Add Showtime</button>
              </div>

              <div class="bsc-time-slots-container" style="display:flex; flex-wrap:wrap; gap:8px;">
                <?php foreach ($item_times as $time_slot): ?>
                  <div class="bsc-time-slot-pill" style="display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; padding:4px 8px; border:1px solid #cbd5e1; border-radius:6px;">
                    <input type="time" name="schedule_days[<?php echo intval($day_index); ?>][times][]" value="<?php echo esc_attr($time_slot); ?>" required style="border:1px solid #cbd5e1; border-radius:4px; padding:2px 4px; font-size:0.85rem;">
                    <button type="button" onclick="removeTimeSlotPill(this)" style="background:none; border:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size:0.95rem; line-height:1;">✕</button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 2-COLUMN: POSTER IMAGE & 4K TRAILER PREVIEW -->
    <div class="bsc-form-grid-2">
      <div class="bsc-form-group">
        <label><?php esc_html_e('Movie Poster Image', 'big-screen-cinemas'); ?> *</label>
        <div class="bsc-media-uploader-box">
          <input type="hidden" name="movie_poster" id="movie_poster_url" required value="<?php echo esc_url($movie->poster_url ?? ''); ?>">
          <img id="poster_preview" src="<?php echo esc_url($movie->poster_url ?? ''); ?>" alt="Preview" class="bsc-media-preview-img" style="<?php echo !empty($movie->poster_url) ? 'display:block;' : 'display:none;'; ?> max-height:160px; object-fit:cover; margin-bottom:8px; border-radius:6px;">
          <div>
            <button type="button" class="button" id="btn_upload_poster">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
              <?php esc_html_e('Choose / Upload Poster', 'big-screen-cinemas'); ?>
            </button>
            <button type="button" class="button" id="btn_remove_poster" style="<?php echo !empty($movie->poster_url) ? 'display:inline-block;' : 'display:none;'; ?> color:red; margin-left:8px;"><?php esc_html_e('Remove', 'big-screen-cinemas'); ?></button>
          </div>
        </div>
      </div>

      <div class="bsc-form-group">
        <label><?php esc_html_e('Official Trailer Preview', 'big-screen-cinemas'); ?></label>
        <div class="bsc-media-uploader-box">
          <input type="hidden" name="movie_trailer" id="movie_trailer_url" value="<?php echo esc_url($movie->trailer_url ?? ''); ?>">
          <video id="trailer_preview" src="<?php echo esc_url($movie->trailer_url ?? ''); ?>" controls class="bsc-media-preview-video" style="<?php echo !empty($movie->trailer_url) ? 'display:block;' : 'display:none;'; ?> max-height:160px; width:100%; border-radius:6px; margin-bottom:8px;"></video>
          <div>
            <button type="button" class="button" id="btn_upload_trailer">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M8 5v14l11-7z"/></svg>
              <?php esc_html_e('Choose / Upload Trailer Video', 'big-screen-cinemas'); ?>
            </button>
            <button type="button" class="button" id="btn_remove_trailer" style="<?php echo !empty($movie->trailer_url) ? 'display:inline-block;' : 'display:none;'; ?> color:red; margin-left:8px;"><?php esc_html_e('Remove', 'big-screen-cinemas'); ?></button>
          </div>
        </div>
      </div>
    </div>

    <!-- 2-COLUMN: SHORT SUMMARY & FULL STORYLINE -->
    <div class="bsc-form-grid-2">
      <div class="bsc-form-group">
        <label><?php esc_html_e('Short Summary (Card Excerpt)', 'big-screen-cinemas'); ?> *</label>
        <textarea name="movie_short_desc" rows="6" required style="width:100%;" placeholder="<?php esc_attr_e('Brief 1-2 sentence overview for movie grid cards...', 'big-screen-cinemas'); ?>"><?php echo esc_textarea($movie->short_description ?? ''); ?></textarea>
      </div>

      <div class="bsc-form-group">
        <label><?php esc_html_e('Full Storyline & Synopsis', 'big-screen-cinemas'); ?> *</label>
        <textarea name="movie_synopsis" rows="6" required style="width:100%;" placeholder="<?php esc_attr_e('Detailed storyline, director notes, hall experience details...', 'big-screen-cinemas'); ?>"><?php echo esc_textarea($movie->synopsis ?? ''); ?></textarea>
      </div>
    </div>

    <div class="bsc-form-group" style="background:#f8fafc; border:1px solid var(--cinema-border); padding:1.25rem; border-radius:var(--cinema-radius-sm);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <label style="margin:0; font-size:0.9rem; font-weight:800; display:flex; align-items:center; gap:6px;">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
          <?php esc_html_e('Lead Cast & Production Team (With Photos)', 'big-screen-cinemas'); ?>
        </label>
        <button type="button" class="button button-secondary" id="btn_add_cast_row">+ <?php esc_html_e('Add Cast Member', 'big-screen-cinemas'); ?></button>
      </div>
      
      <div id="bsc_cast_repeater_container">
        <?php foreach ($cast_list as $cast): ?>
          <div class="bsc-repeater-row">
            <input type="text" name="cast_names[]" placeholder="<?php esc_attr_e('Actor Name (e.g. Shakib Khan)', 'big-screen-cinemas'); ?>" value="<?php echo esc_attr($cast['name'] ?? ''); ?>" required>
            <input type="text" name="cast_roles[]" placeholder="<?php esc_attr_e('Role / Character', 'big-screen-cinemas'); ?>" value="<?php echo esc_attr($cast['role'] ?? ''); ?>" required>
            
            <div style="display:flex; align-items:center; gap:6px;">
              <input type="hidden" name="cast_photos[]" class="cast-photo-url-field" value="<?php echo esc_url($cast['photo'] ?? ''); ?>">
              <img src="<?php echo esc_url($cast['photo'] ?? ''); ?>" alt="" class="bsc-cast-photo-thumb" style="<?php echo !empty($cast['photo']) ? 'display:inline-block;' : 'display:none;'; ?>">
              <button type="button" class="button button-small btn-upload-cast-photo">📷 <?php esc_html_e('Photo', 'big-screen-cinemas'); ?></button>
            </div>
            
            <button type="button" class="bsc-btn-remove-row" onclick="removeCastRow(this)">✕</button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:12px; font-weight:900; font-size:1rem; margin-top:1rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
      <?php echo $is_edit ? esc_html__('Update Movie in Multiplex Database', 'big-screen-cinemas') : esc_html__('Publish Movie to Multiplex Database', 'big-screen-cinemas'); ?>
    </button>
  </form>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Add Day Schedule Block
    const btnAddDay = document.getElementById('btn_add_day_block');
    const dayContainer = document.getElementById('bsc_day_schedule_container');

    if (btnAddDay && dayContainer) {
      btnAddDay.addEventListener('click', function() {
        const nextIndex = dayContainer.querySelectorAll('.bsc-day-schedule-card').length;
        const todayStr = new Date().toISOString().split('T')[0];
        
        const card = document.createElement('div');
        card.className = 'bsc-day-schedule-card';
        card.setAttribute('data-day-index', nextIndex);
        card.style.cssText = 'background:#fff; border:1px solid var(--cinema-border); border-radius:8px; padding:14px;';
        card.innerHTML = `
          <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px; flex:1;">
              <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:#0f766e; letter-spacing:0.04em;">Screening Day</span>
              <input type="date" name="schedule_days[${nextIndex}][date]" value="${todayStr}" required style="max-width:180px;">
            </div>
            <button type="button" class="bsc-btn-remove-day" onclick="removeDayBlock(this)" style="background:none; border:none; color:#ef4444; font-weight:700; cursor:pointer; font-size:0.85rem;">✕ Remove Day</button>
          </div>
          <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
              <label style="font-size:0.75rem; font-weight:700; color:#64748b; text-transform:uppercase; margin:0;">Showtime Slots for this Day</label>
              <button type="button" class="button button-small" onclick="addTimeSlotToDay(this)">+ Add Showtime</button>
            </div>
            <div class="bsc-time-slots-container" style="display:flex; flex-wrap:wrap; gap:8px;">
              <div class="bsc-time-slot-pill" style="display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; padding:4px 8px; border:1px solid #cbd5e1; border-radius:6px;">
                <input type="time" name="schedule_days[${nextIndex}][times][]" value="18:00" required style="border:1px solid #cbd5e1; border-radius:4px; padding:2px 4px; font-size:0.85rem;">
                <button type="button" onclick="removeTimeSlotPill(this)" style="background:none; border:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size:0.95rem; line-height:1;">✕</button>
              </div>
            </div>
          </div>
        `;
        dayContainer.appendChild(card);
      });
    }

    // Media Uploaders
    const btnPoster = document.getElementById('btn_upload_poster');
    const posterInput = document.getElementById('movie_poster_url');
    const posterImg = document.getElementById('poster_preview');
    const btnRemovePoster = document.getElementById('btn_remove_poster');

    if (btnPoster) {
      btnPoster.addEventListener('click', function(e) {
        e.preventDefault();
        const frame = wp.media({
          title: 'Select or Upload Movie Poster',
          button: { text: 'Use This Poster' },
          multiple: false,
          library: { type: 'image' }
        });
        frame.on('select', function() {
          const attachment = frame.state().get('selection').first().toJSON();
          posterInput.value = attachment.url;
          posterImg.src = attachment.url;
          posterImg.style.display = 'block';
          btnRemovePoster.style.display = 'inline-block';
        });
        frame.open();
      });

      btnRemovePoster.addEventListener('click', function() {
        posterInput.value = '';
        posterImg.src = '';
        posterImg.style.display = 'none';
        btnRemovePoster.style.display = 'none';
      });
    }

    const btnTrailer = document.getElementById('btn_upload_trailer');
    const trailerInput = document.getElementById('movie_trailer_url');
    const trailerVideo = document.getElementById('trailer_preview');
    const btnRemoveTrailer = document.getElementById('btn_remove_trailer');

    if (btnTrailer) {
      btnTrailer.addEventListener('click', function(e) {
        e.preventDefault();
        const frame = wp.media({
          title: 'Select or Upload 4K Trailer Video',
          button: { text: 'Use This Trailer' },
          multiple: false,
          library: { type: 'video' }
        });
        frame.on('select', function() {
          const attachment = frame.state().get('selection').first().toJSON();
          trailerInput.value = attachment.url;
          trailerVideo.src = attachment.url;
          trailerVideo.style.display = 'block';
          btnRemoveTrailer.style.display = 'inline-block';
        });
        frame.open();
      });

      btnRemoveTrailer.addEventListener('click', function() {
        trailerInput.value = '';
        trailerVideo.src = '';
        trailerVideo.style.display = 'none';
        btnRemoveTrailer.style.display = 'none';
      });
    }

    // Cast Member Repeater
    document.addEventListener('click', function(e) {
      if (e.target && e.target.classList.contains('btn-upload-cast-photo')) {
        e.preventDefault();
        const btn = e.target;
        const parent = btn.parentElement;
        const urlField = parent.querySelector('.cast-photo-url-field');
        const thumb = parent.querySelector('.bsc-cast-photo-thumb');

        const frame = wp.media({
          title: 'Select or Upload Actor Headshot',
          button: { text: 'Use Photo' },
          multiple: false,
          library: { type: 'image' }
        });

        frame.on('select', function() {
          const attachment = frame.state().get('selection').first().toJSON();
          urlField.value = attachment.url;
          thumb.src = attachment.url;
          thumb.style.display = 'inline-block';
        });
        frame.open();
      }
    });

    const btnAddCast = document.getElementById('btn_add_cast_row');
    const castContainer = document.getElementById('bsc_cast_repeater_container');

    if (btnAddCast && castContainer) {
      btnAddCast.addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'bsc-repeater-row';
        row.innerHTML = `
          <input type="text" name="cast_names[]" placeholder="Actor Name" required>
          <input type="text" name="cast_roles[]" placeholder="Role / Character" required>
          <div style="display:flex; align-items:center; gap:6px;">
            <input type="hidden" name="cast_photos[]" class="cast-photo-url-field" value="">
            <img src="" alt="" class="bsc-cast-photo-thumb" style="display:none;">
            <button type="button" class="button button-small btn-upload-cast-photo">📷 Photo</button>
          </div>
          <button type="button" class="bsc-btn-remove-row" onclick="removeCastRow(this)">✕</button>
        `;
        castContainer.appendChild(row);
      });
    }
  });

  function addTimeSlotToDay(btn) {
    const card = btn.closest('.bsc-day-schedule-card');
    const dayIndex = card.getAttribute('data-day-index');
    const container = card.querySelector('.bsc-time-slots-container');
    
    const pill = document.createElement('div');
    pill.className = 'bsc-time-slot-pill';
    pill.style.cssText = 'display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; padding:4px 8px; border:1px solid #cbd5e1; border-radius:6px;';
    pill.innerHTML = `
      <input type="time" name="schedule_days[${dayIndex}][times][]" value="18:00" required style="border:1px solid #cbd5e1; border-radius:4px; padding:2px 4px; font-size:0.85rem;">
      <button type="button" onclick="removeTimeSlotPill(this)" style="background:none; border:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size:0.95rem; line-height:1;">✕</button>
    `;
    container.appendChild(pill);
  }

  function removeTimeSlotPill(btn) {
    const container = btn.closest('.bsc-time-slots-container');
    if (container.querySelectorAll('.bsc-time-slot-pill').length > 1) {
      btn.closest('.bsc-time-slot-pill').remove();
    } else {
      alert('Each screening day requires at least one showtime slot.');
    }
  }

  function removeDayBlock(btn) {
    const container = document.getElementById('bsc_day_schedule_container');
    if (container.querySelectorAll('.bsc-day-schedule-card').length > 1) {
      btn.closest('.bsc-day-schedule-card').remove();
    } else {
      alert('At least one screening day schedule is required.');
    }
  }

  function removeCastRow(btn) {
    const row = btn.closest('.bsc-repeater-row');
    if (document.querySelectorAll('#bsc_cast_repeater_container .bsc-repeater-row').length > 1) {
      row.remove();
    } else {
      alert('At least one cast entry is required.');
    }
  }
</script>