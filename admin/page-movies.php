<?php
if (!defined('ABSPATH')) exit;

function bsc_render_movies_page() {
  global $wpdb;
  $table = $wpdb->prefix . 'bsc_control_movies';
  $subtab = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'all';
  $movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

  bsc_render_page_shell_open('bsc-movies');
  ?>
  <div class="bsc-subtabs-nav">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-movies&subtab=all')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'all') ? 'active' : ''; ?>"><?php esc_html_e('All Movies', 'big-screen-cinemas'); ?></a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-movies&subtab=add')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'add') ? 'active' : ''; ?>"><?php esc_html_e('Add Movie', 'big-screen-cinemas'); ?></a>
    <?php if ($subtab === 'view'): ?>
      <span class="bsc-subtab-btn active"><?php esc_html_e('Movie Details (Admin View)', 'big-screen-cinemas'); ?></span>
    <?php endif; ?>
    <?php if ($subtab === 'edit'): ?>
      <span class="bsc-subtab-btn active"><?php esc_html_e('Edit Movie', 'big-screen-cinemas'); ?></span>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['movie_added'])): ?>
    <div style="background:#ecfdf5; color:#10b981; padding:12px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      <?php esc_html_e('Movie published successfully to database!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['movie_updated'])): ?>
    <div style="background:#ecfdf5; color:#10b981; padding:12px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      <?php esc_html_e('Movie details updated successfully!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['movie_deleted'])): ?>
    <div style="background:#fef2f2; color:#ef4444; padding:12px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      <?php esc_html_e('Movie deleted successfully from database!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php 
  /* --------------------------------------------------------------------------
     VIEW MODE: IN-ADMIN COMPLETE PREVIEW
     -------------------------------------------------------------------------- */
  if ($subtab === 'view' && $movie_id > 0): 
    $movie = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $movie_id));
    if (!$movie):
      echo '<div class="cinema-panel"><p>' . esc_html__('Movie record not found.', 'big-screen-cinemas') . '</p></div>';
    else:
      $cast_list = !empty($movie->cast_crew) ? json_decode($movie->cast_crew, true) : array();
      
      $showtimes_list = array();
      if (!empty($movie->showtimes)) {
        $decoded_times = json_decode($movie->showtimes, true);
        if (is_array($decoded_times)) {
          $showtimes_list = $decoded_times;
        } else {
          $showtimes_list = array_map('trim', explode(',', $movie->showtimes));
        }
      }

      $screening_dates = array();
      if (!empty($movie->release_date)) {
        $decoded_dates = json_decode($movie->release_date, true);
        if (is_array($decoded_dates)) {
          $screening_dates = $decoded_dates;
        } else {
          $screening_dates = array_map('trim', explode(',', $movie->release_date));
        }
      }
  ?>
    <div class="cinema-panel">
      <div class="cinema-panel-header" style="border-bottom: 1px solid var(--cinema-border-light); padding-bottom: 1.25rem;">
        <div>
          <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
            <span class="cinema-badge-pill active"><?php echo esc_html($movie->category ?: 'Now Showing'); ?></span>
            <span class="cinema-voucher-tag"><?php echo esc_html($movie->badge_tag); ?></span>
          </div>
          <h2 style="font-size: 1.85rem; font-weight: 900; margin: 0 0 0.35rem 0; color: var(--cinema-text-main);">
            <?php echo esc_html($movie->title); ?>
          </h2>
          <span style="font-size: 0.82rem; color: var(--cinema-text-muted); font-family: var(--cinema-font-mono);">
            Database Record ID: <strong>#MOVIE-<?php echo intval($movie->id); ?></strong> • Registered: <strong><?php echo esc_html($movie->created_at ?? 'N/A'); ?></strong>
          </span>
        </div>

        <div class="bsc-action-btn-group">
          <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-movies&subtab=edit&id=' . $movie->id)); ?>" class="button button-primary" style="background: #ca8a04; border-color: #ca8a04; font-weight:700;">
            ✏️ <?php esc_html_e('Edit Movie', 'big-screen-cinemas'); ?>
          </a>
          <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bsc_delete_movie&id=' . $movie->id), 'bsc_delete_movie_' . $movie->id)); ?>" class="button" style="color:#dc2626; border-color:#fca5a5; background:#fff;" onclick="return confirm('Permanently delete this movie?');">
            🗑️ <?php esc_html_e('Delete', 'big-screen-cinemas'); ?>
          </a>
          <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-movies&subtab=all')); ?>" class="button button-secondary">
            ← <?php esc_html_e('Back to All Movies', 'big-screen-cinemas'); ?>
          </a>
        </div>
      </div>

      <div class="bsc-view-hero-grid" style="margin-top: 1.5rem;">
        <div class="bsc-view-poster-wrap">
          <img src="<?php echo esc_url($movie->poster_url ?: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=700&auto=format&fit=crop&q=75'); ?>" alt="<?php echo esc_attr($movie->title); ?>">
          
          <div style="margin-top: 1rem; background:#f8fafc; border:1px solid var(--cinema-border); padding:0.85rem; border-radius:8px;">
            <span style="font-size:0.72rem; text-transform:uppercase; font-weight:800; color:var(--cinema-text-muted); display:block; margin-bottom:4px;">Direct Media Asset URLs</span>
            <div style="font-size:0.78rem; word-break:break-all; margin-bottom:6px;">
              <strong>Poster:</strong> <a href="<?php echo esc_url($movie->poster_url); ?>" target="_blank" style="color:var(--cinema-accent-teal);">Open Image File ↗</a>
            </div>
            <?php if (!empty($movie->trailer_url)): ?>
              <div style="font-size:0.78rem; word-break:break-all;">
                <strong>Trailer:</strong> <a href="<?php echo esc_url($movie->trailer_url); ?>" target="_blank" style="color:var(--cinema-accent-teal);">Open Video Stream ↗</a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div>
          <div class="bsc-spec-pill-deck">
            <span class="bsc-spec-pill" style="background:#fef3c7; color:#b45309; border-color:#fde68a;">★ <?php echo esc_html($movie->rating); ?> Master Rating</span>
            <span class="bsc-spec-pill">⏱ <?php echo esc_html($movie->duration); ?></span>
            <span class="bsc-spec-pill">🎭 <?php echo esc_html($movie->genre); ?></span>
            <span class="bsc-spec-pill" style="background:#fef2f2; color:#dc2626; border-color:#fecaca;">🔞 <?php echo esc_html($movie->age_rating); ?></span>
            <span class="bsc-spec-pill">🔊 <?php echo esc_html($movie->audio_language); ?></span>
          </div>

          <div class="bsc-view-stats-row" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 1.25rem;">
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Director', 'big-screen-cinemas'); ?></div>
              <div class="v"><?php echo esc_html($movie->director ?: 'Not Specified'); ?></div>
            </div>
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Screenwriters / Story', 'big-screen-cinemas'); ?></div>
              <div class="v"><?php echo esc_html($movie->writers ?: 'Not Specified'); ?></div>
            </div>
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Auditorium Allocation', 'big-screen-cinemas'); ?></div>
              <div class="v"><?php echo esc_html($movie->auditorium); ?></div>
            </div>
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Format Badge Tag', 'big-screen-cinemas'); ?></div>
              <div class="v" style="color:var(--cinema-accent-teal);"><?php echo esc_html($movie->badge_tag); ?></div>
            </div>
          </div>

          <div style="background:#f8fafc; border:1px solid var(--cinema-border); padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.25rem;">
            <span style="font-size:0.75rem; text-transform:uppercase; font-weight:800; color:var(--cinema-text-muted); display:block; margin-bottom:4px;">
              📅 <?php esc_html_e('Selected Screening Dates', 'big-screen-cinemas'); ?>:
            </span>
            <div style="font-size:0.95rem; font-weight:700; color:var(--cinema-text-main);">
              <?php echo esc_html(implode(', ', $screening_dates)); ?>
            </div>
          </div>

          <div style="background:#f8fafc; border:1px solid var(--cinema-border); padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.25rem;">
            <span style="font-size:0.75rem; text-transform:uppercase; font-weight:800; color:var(--cinema-text-muted); display:block; margin-bottom:4px;">
              🕒 <?php esc_html_e('Daily Scheduled Showtimes', 'big-screen-cinemas'); ?>:
            </span>
            <div style="font-size:1.1rem; font-weight:800; font-family:var(--cinema-font-mono); color:var(--cinema-text-main);">
              <?php echo esc_html(implode(' • ', $showtimes_list)); ?>
            </div>
          </div>

          <div style="background:#fff; border:1px solid var(--cinema-border); padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.25rem;">
            <h4 style="margin: 0 0 0.35rem 0; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; color: var(--cinema-text-muted);">
              📝 <?php esc_html_e('Short Summary (Card Excerpt)', 'big-screen-cinemas'); ?>
            </h4>
            <p style="font-size: 0.95rem; line-height: 1.7; color: var(--cinema-text-main); margin: 0;">
              <?php echo esc_html($movie->short_description ?: 'No short description provided.'); ?>
            </p>
          </div>

          <div style="background:#fff; border:1px solid var(--cinema-border); padding:1rem 1.25rem; border-radius:8px;">
            <h4 style="margin: 0 0 0.35rem 0; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; color: var(--cinema-text-muted);">
              📖 <?php esc_html_e('Full Storyline & Synopsis', 'big-screen-cinemas'); ?>
            </h4>
            <p style="font-size: 0.95rem; line-height: 1.85; color: var(--cinema-text-secondary); margin: 0;">
              <?php echo nl2br(esc_html($movie->synopsis ?: 'No full synopsis provided.')); ?>
            </p>
          </div>
        </div>
      </div>

      <div style="border-top:1px solid var(--cinema-border-light); padding-top:1.5rem; margin-top:1.75rem;">
        <h4 style="margin:0 0 0.85rem 0; font-size:1.1rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;">
          🎬 <?php esc_html_e('Official 4K Trailer Video Preview', 'big-screen-cinemas'); ?>
        </h4>
        <?php if (!empty($movie->trailer_url)): ?>
          <video src="<?php echo esc_url($movie->trailer_url); ?>" controls style="width:100%; max-height:450px; border-radius:var(--cinema-radius-md); background:#000; display:block; box-shadow:0 10px 25px rgba(0,0,0,0.1);"></video>
        <?php else: ?>
          <div style="background:#f8fafc; border:1px dashed var(--cinema-border); padding:2rem; text-align:center; border-radius:8px; color:var(--cinema-text-muted);">
            <?php esc_html_e('No trailer video uploaded for this movie.', 'big-screen-cinemas'); ?>
          </div>
        <?php endif; ?>
      </div>

      <div style="border-top:1px solid var(--cinema-border-light); padding-top:1.5rem; margin-top:1.75rem;">
        <h4 style="margin:0 0 0.85rem 0; font-size:1.1rem; font-weight:800; display:flex; align-items:center; gap:0.5rem;">
          👥 <?php esc_html_e('Lead Cast & Production Team', 'big-screen-cinemas'); ?> 
          <span style="font-size:0.8rem; font-weight:700; color:var(--cinema-text-muted);">(<?php echo count($cast_list); ?> Members)</span>
        </h4>
        <?php if (!empty($cast_list)): ?>
          <div class="bsc-cast-grid-view">
            <?php foreach ($cast_list as $cast): ?>
              <div class="bsc-cast-card-mini">
                <img src="<?php echo esc_url(!empty($cast['photo']) ? $cast['photo'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80'); ?>" class="bsc-cast-avatar" alt="<?php echo esc_attr($cast['name']); ?>">
                <div>
                  <strong style="font-size:0.92rem; color:var(--cinema-text-main); display:block;"><?php echo esc_html($cast['name'] ?: 'Unknown'); ?></strong>
                  <span style="font-size:0.78rem; color:var(--cinema-text-muted);"><?php echo esc_html($cast['role'] ?: 'Role not specified'); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div style="background:#f8fafc; border:1px dashed var(--cinema-border); padding:1.5rem; text-align:center; border-radius:8px; color:var(--cinema-text-muted);">
            <?php esc_html_e('No cast or crew members entered for this film.', 'big-screen-cinemas'); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php 
    endif;
  /* --------------------------------------------------------------------------
     FORM: ADD / EDIT MOVIE
     -------------------------------------------------------------------------- */
  elseif ($subtab === 'add' || $subtab === 'edit'): 
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

    // Parse showtimes data
    $showtimes_list = array('18:00');
    if ($movie && !empty($movie->showtimes)) {
      $decoded_times = json_decode($movie->showtimes, true);
      if (is_array($decoded_times)) {
        $showtimes_list = $decoded_times;
      } else {
        $showtimes_list = array_map('trim', explode(',', $movie->showtimes));
      }
    }

    // Parse dates data
    $screening_dates_list = array(current_time('Y-m-d'));
    if ($movie && !empty($movie->release_date)) {
      $decoded_dates = json_decode($movie->release_date, true);
      if (is_array($decoded_dates)) {
        $screening_dates_list = $decoded_dates;
      } else {
        $screening_dates_list = array_map('trim', explode(',', $movie->release_date));
      }
    }
  ?>
    <div class="cinema-panel" style="max-width:900px; margin:0 auto;">
      <h3 class="cinema-panel-title" style="margin-bottom:1.5rem;">
        🎬 <?php echo $is_edit ? esc_html__('Edit Feature Movie', 'big-screen-cinemas') : esc_html__('Add New Feature Movie (Full Spec)', 'big-screen-cinemas'); ?>
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
            <input type="text" name="movie_title" required value="<?php echo esc_attr($movie->title ?? ''); ?>" placeholder="e.g. FOOD COMBO (EVIL DEAD BURN)">
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
            <input type="text" name="movie_genre" required value="<?php echo esc_attr($movie->genre ?? ''); ?>" placeholder="e.g. Horror / Thriller">
          </div>
          <div class="bsc-form-group">
            <label><?php esc_html_e('Rating (IMDb / Master)', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="movie_rating" required value="<?php echo esc_attr($movie->rating ?? ''); ?>" placeholder="e.g. 8.6">
          </div>
          <div class="bsc-form-group">
            <label><?php esc_html_e('Age / Censor Rating', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="movie_age_rating" required value="<?php echo esc_attr($movie->age_rating ?? 'R-Rated (18+)'); ?>" placeholder="e.g. R-Rated (18+), PG-13">
          </div>
        </div>

        <div class="bsc-form-grid-3">
          <div class="bsc-form-group">
            <label><?php esc_html_e('Duration', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="movie_duration" required value="<?php echo esc_attr($movie->duration ?? ''); ?>" placeholder="e.g. 01h 50m">
          </div>
          <div class="bsc-form-group">
            <label><?php esc_html_e('Language & Audio Mastering', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="movie_audio_lang" required value="<?php echo esc_attr($movie->audio_language ?? 'English (Dolby Atmos 128-Ch)'); ?>" placeholder="e.g. English (Dolby Atmos 128-Ch)">
          </div>
          <div class="bsc-form-group">
            <label><?php esc_html_e('Format Badge Tag', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="movie_badge" required value="<?php echo esc_attr($movie->badge_tag ?? '4K DUAL LASER'); ?>" placeholder="e.g. 4K DUAL LASER, IMAX LASER">
          </div>
        </div>

        <div class="bsc-form-grid-2">
          <div class="bsc-form-group">
            <label><?php esc_html_e('Director', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="movie_director" required value="<?php echo esc_attr($movie->director ?? ''); ?>" placeholder="e.g. Darren Aron">
          </div>
          <div class="bsc-form-group">
            <label><?php esc_html_e('Screenwriters / Story', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="movie_writers" required value="<?php echo esc_attr($movie->writers ?? ''); ?>" placeholder="e.g. Sam Raimi, James Wan">
          </div>
        </div>

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

        <!-- UNIFIED SCREENING DATE & TIME PICKER REPEATER -->
        <div class="bsc-form-group" style="background:#f8fafc; border:1px solid var(--cinema-border); padding:1.25rem; border-radius:var(--cinema-radius-sm); margin-bottom: 1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <label style="margin:0; font-weight:800;">📅 <?php esc_html_e('Screening Dates & Daily Showtimes', 'big-screen-cinemas'); ?> *</label>
            <button type="button" class="button button-secondary" id="btn_add_schedule_row">+ <?php esc_html_e('Add Screening Slot', 'big-screen-cinemas'); ?></button>
          </div>

          <div id="bsc_schedule_repeater_container" style="display:flex; flex-direction:column; gap:10px;">
            <?php 
              $max_count = max(count($screening_dates_list), count($showtimes_list));
              for ($i = 0; $i < $max_count; $i++) {
                $row_date = $screening_dates_list[$i] ?? $screening_dates_list[0] ?? current_time('Y-m-d');
                $row_time = $showtimes_list[$i] ?? $showtimes_list[0] ?? '18:00';
            ?>
              <div class="bsc-repeater-row" style="display:flex; gap:10px; align-items:center; background:#fff; padding:8px 12px; border:1px solid var(--cinema-border); border-radius:6px;">
                <input type="date" name="screening_dates[]" value="<?php echo esc_attr($row_date); ?>" required style="flex:1;">
                
                <input type="time" name="screening_times[]" value="<?php echo esc_attr($row_time); ?>" required style="flex:1;">

                <button type="button" class="bsc-btn-remove-row" onclick="removeScheduleRow(this)" style="background:none; border:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size:1.1rem;">✕</button>
              </div>
            <?php } ?>
          </div>
        </div>

        <div class="bsc-form-group">
          <label><?php esc_html_e('Movie Poster Image (WP Media Upload)', 'big-screen-cinemas'); ?> *</label>
          <div class="bsc-media-uploader-box">
            <input type="hidden" name="movie_poster" id="movie_poster_url" required value="<?php echo esc_url($movie->poster_url ?? ''); ?>">
            <img id="poster_preview" src="<?php echo esc_url($movie->poster_url ?? ''); ?>" alt="Preview" class="bsc-media-preview-img" style="<?php echo !empty($movie->poster_url) ? 'display:block;' : 'display:none;'; ?>">
            <div>
              <button type="button" class="button" id="btn_upload_poster">📷 <?php esc_html_e('Choose / Upload Poster Image', 'big-screen-cinemas'); ?></button>
              <button type="button" class="button" id="btn_remove_poster" style="<?php echo !empty($movie->poster_url) ? 'display:inline-block;' : 'display:none;'; ?> color:red; margin-left:8px;"><?php esc_html_e('Remove', 'big-screen-cinemas'); ?></button>
            </div>
          </div>
        </div>

        <div class="bsc-form-group">
          <label><?php esc_html_e('Official 4K Trailer Preview (WP Video Upload / MP4)', 'big-screen-cinemas'); ?></label>
          <div class="bsc-media-uploader-box">
            <input type="hidden" name="movie_trailer" id="movie_trailer_url" value="<?php echo esc_url($movie->trailer_url ?? ''); ?>">
            <video id="trailer_preview" src="<?php echo esc_url($movie->trailer_url ?? ''); ?>" controls class="bsc-media-preview-video" style="<?php echo !empty($movie->trailer_url) ? 'display:block;' : 'display:none;'; ?>"></video>
            <div style="margin-top:8px;">
              <button type="button" class="button" id="btn_upload_trailer">🎬 <?php esc_html_e('Choose / Upload Trailer Video', 'big-screen-cinemas'); ?></button>
              <button type="button" class="button" id="btn_remove_trailer" style="<?php echo !empty($movie->trailer_url) ? 'display:inline-block;' : 'display:none;'; ?> color:red; margin-left:8px;"><?php esc_html_e('Remove', 'big-screen-cinemas'); ?></button>
            </div>
          </div>
        </div>

        <div class="bsc-form-group">
          <label><?php esc_html_e('Short Description (Card Summary)', 'big-screen-cinemas'); ?> *</label>
          <textarea name="movie_short_desc" rows="2" required placeholder="<?php esc_attr_e('Brief 1-2 sentence overview for movie grid cards...', 'big-screen-cinemas'); ?>"><?php echo esc_textarea($movie->short_description ?? ''); ?></textarea>
        </div>

        <div class="bsc-form-group">
          <label><?php esc_html_e('Long Description / Full Synopsis', 'big-screen-cinemas'); ?> *</label>
          <textarea name="movie_synopsis" rows="5" required placeholder="<?php esc_attr_e('Detailed storyline, director notes, hall experience details...', 'big-screen-cinemas'); ?>"><?php echo esc_textarea($movie->synopsis ?? ''); ?></textarea>
        </div>

        <div class="bsc-form-group" style="background:#f8fafc; border:1px solid var(--cinema-border); padding:1.25rem; border-radius:var(--cinema-radius-sm);">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <label style="margin:0; font-size:0.9rem;">👥 <?php esc_html_e('Lead Cast & Production Team (With Photos)', 'big-screen-cinemas'); ?></label>
            <button type="button" class="button button-secondary" id="btn_add_cast_row">+ <?php esc_html_e('Add Cast Member', 'big-screen-cinemas'); ?></button>
          </div>
          
          <div id="bsc_cast_repeater_container">
            <?php foreach ($cast_list as $cast): ?>
              <div class="bsc-repeater-row">
                <input type="text" name="cast_names[]" placeholder="<?php esc_attr_e('Actor Name (e.g. Mia Goth)', 'big-screen-cinemas'); ?>" value="<?php echo esc_attr($cast['name'] ?? ''); ?>" required>
                <input type="text" name="cast_roles[]" placeholder="<?php esc_attr_e('Role / Character (e.g. Maxine Minx)', 'big-screen-cinemas'); ?>" value="<?php echo esc_attr($cast['role'] ?? ''); ?>" required>
                
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

        <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:12px; font-weight:900; font-size:1rem; margin-top:1rem;">
          <?php echo $is_edit ? esc_html__('Update Movie in Multiplex Database', 'big-screen-cinemas') : esc_html__('Publish Movie to Multiplex Database', 'big-screen-cinemas'); ?>
        </button>
      </form>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const btnAddSchedule = document.getElementById('btn_add_schedule_row');
        const scheduleContainer = document.getElementById('bsc_schedule_repeater_container');

        if (btnAddSchedule && scheduleContainer) {
          btnAddSchedule.addEventListener('click', function() {
            const todayStr = new Date().toISOString().split('T')[0];
            const row = document.createElement('div');
            row.className = 'bsc-repeater-row';
            row.style.cssText = 'display:flex; gap:10px; align-items:center; background:#fff; padding:8px 12px; border:1px solid var(--cinema-border); border-radius:6px;';
            row.innerHTML = `
              <input type="date" name="screening_dates[]" value="${todayStr}" required style="flex:1;">
              <input type="time" name="screening_times[]" value="18:00" required style="flex:1;">
              <button type="button" class="bsc-btn-remove-row" onclick="removeScheduleRow(this)" style="background:none; border:none; color:#ef4444; font-weight:bold; cursor:pointer; font-size:1.1rem;">✕</button>
            `;
            scheduleContainer.appendChild(row);
          });
        }

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

      function removeScheduleRow(btn) {
        const row = btn.closest('.bsc-repeater-row');
        if (document.querySelectorAll('#bsc_schedule_repeater_container .bsc-repeater-row').length > 1) {
          row.remove();
        } else {
          alert('At least one screening date and time slot is required.');
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

  <?php 
  /* --------------------------------------------------------------------------
     TABLE: ALL MOVIES CATALOG
     -------------------------------------------------------------------------- */
  else: 
    $movies = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
  ?>
    <div class="cinema-panel" style="margin:0;">
      <h3 class="cinema-panel-title" style="margin-bottom:1.25rem;">🎬 <?php echo esc_html(sprintf(__('All Active Movies (%d)', 'big-screen-cinemas'), count($movies))); ?></h3>
      <div class="cinema-table-wrap">
        <table class="cinema-data-table">
          <thead>
            <tr>
              <th>POSTER</th>
              <th>TITLE &amp; CATEGORY</th>
              <th>DIRECTOR &amp; GENRE</th>
              <th>RATING &amp; CENSOR</th>
              <th>HALL &amp; TIMES</th>
              <th>TRAILER</th>
              <th style="text-align:center;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($movies)): foreach ($movies as $m): 
              $view_admin_url = admin_url('admin.php?page=bsc-movies&subtab=view&id=' . $m->id);
              $edit_url       = admin_url('admin.php?page=bsc-movies&subtab=edit&id=' . $m->id);
              $delete_url     = wp_nonce_url(admin_url('admin-post.php?action=bsc_delete_movie&id=' . $m->id), 'bsc_delete_movie_' . $m->id);
              
              $showtimes_disp = $m->showtimes;
              $decoded_times = json_decode($m->showtimes, true);
              if (is_array($decoded_times)) { $showtimes_disp = implode(', ', $decoded_times); }
            ?>
              <tr>
                <td><img src="<?php echo esc_url($m->poster_url); ?>" style="width:48px; height:68px; border-radius:4px; object-fit:cover;"></td>
                <td>
                  <strong><?php echo esc_html($m->title); ?></strong><br>
                  <span class="cinema-badge-pill active" style="font-size:0.68rem; margin-top:3px;"><?php echo esc_html($m->category ?: 'Now Showing'); ?></span>
                </td>
                <td>
                  <strong><?php echo esc_html($m->director); ?></strong><br>
                  <small style="color:var(--cinema-text-muted);"><?php echo esc_html($m->genre); ?></small>
                </td>
                <td>
                  <strong style="color:var(--cinema-accent-amber);">★ <?php echo esc_html($m->rating); ?></strong><br>
                  <small style="color:var(--cinema-text-muted);"><?php echo esc_html($m->age_rating); ?></small>
                </td>
                <td>
                  <span><?php echo esc_html($m->auditorium); ?></span><br>
                  <small style="color:var(--cinema-accent-teal); font-family:var(--cinema-font-mono);"><?php echo esc_html($showtimes_disp); ?></small>
                </td>
                <td>
                  <?php if (!empty($m->trailer_url)): ?>
                    <a href="<?php echo esc_url($m->trailer_url); ?>" target="_blank" class="button button-small">▶ 4K Video</a>
                  <?php else: ?>
                    <span style="color:var(--cinema-text-muted); font-size:0.8rem;">None</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;">
                  <div class="bsc-action-btn-group" style="justify-content:center;">
                    <a href="<?php echo esc_url($view_admin_url); ?>" class="bsc-btn-action view" title="<?php esc_attr_e('View in Admin Details', 'big-screen-cinemas'); ?>">👁 <?php esc_html_e('View', 'big-screen-cinemas'); ?></a>
                    <a href="<?php echo esc_url($edit_url); ?>" class="bsc-btn-action edit" title="<?php esc_attr_e('Edit Movie Details', 'big-screen-cinemas'); ?>">✏️ <?php esc_html_e('Edit', 'big-screen-cinemas'); ?></a>
                    <a href="<?php echo esc_url($delete_url); ?>" class="bsc-btn-action delete" onclick="return confirm('Are you sure you want to permanently delete this movie from the database?');" title="<?php esc_attr_e('Delete Movie', 'big-screen-cinemas'); ?>">🗑️ <?php esc_html_e('Delete', 'big-screen-cinemas'); ?></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="7"><?php esc_html_e('No movies published in the catalog.', 'big-screen-cinemas'); ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
  <?php
  bsc_render_page_shell_close();
}