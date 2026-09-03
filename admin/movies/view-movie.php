<?php
/**
 * Movie Module - Single Movie Dossier & Details View
 *
 * Path: admin/movies/view-movie.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
}

global $wpdb;
$table    = $wpdb->prefix . 'bsc_control_movies';
$movie_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$movie = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $movie_id ) );

if ( ! $movie ) {
    echo '<div class="cinema-panel" style="margin:20px;"><p>' . esc_html__( 'Movie record not found.', 'bsc-cinema-manager' ) . '</p></div>';
    return;
}

$cast_list = ! empty( $movie->cast_crew ) ? json_decode( $movie->cast_crew, true ) : array();

// Helper date formatter: e.g. "12 Sep 2026"
function bsc_format_view_date( $raw_date ) {
    if ( empty( $raw_date ) ) {
        return '--';
    }
    $ts = strtotime( $raw_date );
    return $ts ? date_i18n( 'd M Y', $ts ) : esc_html( $raw_date );
}

// Helper 12-Hour AM/PM Time Formatter
function bsc_format_view_time( $raw_time ) {
    $clean = trim( (string) $raw_time );
    if ( empty( $clean ) ) {
        return '';
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

// Parse official single theatrical release date
$official_release_date_str = '--';
if ( ! empty( $movie->release_date ) ) {
    $decoded_rd = json_decode( $movie->release_date, true );
    $raw_rd = ( is_array( $decoded_rd ) && ! empty( $decoded_rd[0] ) ) ? $decoded_rd[0] : $movie->release_date;
    $official_release_date_str = bsc_format_view_date( $raw_rd );
}

// Parse hierarchical screening schedule (Days & Time slots)
$day_schedules = array();
if ( ! empty( $movie->showtimes ) ) {
    $decoded_s = json_decode( $movie->showtimes, true );
    if ( is_array( $decoded_s ) && ! empty( $decoded_s ) ) {
        if ( isset( $decoded_s[0]['date'] ) && isset( $decoded_s[0]['times'] ) ) {
            $day_schedules = $decoded_s;
        } elseif ( isset( $decoded_s[0]['date'] ) && isset( $decoded_s[0]['time'] ) ) {
            $grouped = array();
            foreach ( $decoded_s as $item ) {
                $d = $item['date'] ?? current_time( 'Y-m-d' );
                $t = $item['time'] ?? '18:00';
                if ( ! isset( $grouped[ $d ] ) ) {
                    $grouped[ $d ] = array( 'date' => $d, 'times' => array() );
                }
                $grouped[ $d ]['times'][] = $t;
            }
            $day_schedules = array_values( $grouped );
        } else {
            $day_schedules = array(
                array(
                    'date'  => current_time( 'Y-m-d' ),
                    'times' => is_array( $decoded_s ) ? $decoded_s : array( '18:00' ),
                ),
            );
        }
    }
}
?>
<style>
  .bsc-movie-dossier-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.5rem;
    align-items: start;
    max-width: 1100px;
    margin: 10px auto 60px auto;
  }
  .bsc-poster-sidebar-card {
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: var(--cinema-radius-md, 12px);
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  }
  .bsc-poster-sidebar-card img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    border: 1px solid var(--cinema-border, #e2e8f0);
    display: block;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }
  .bsc-movie-content-main {
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: var(--cinema-radius-md, 12px);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
  }
  .bsc-movie-banner-head {
    background: #0f172a;
    padding: 1.75rem 2rem;
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .bsc-movie-body-wrap {
    padding: 1.75rem 2rem;
  }
  .bsc-metric-card-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  .bsc-metric-item {
    background: #f8fafc;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 8px;
    padding: 1rem 1.15rem;
  }
  .bsc-metric-item .label {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--cinema-text-muted, #64748b);
    letter-spacing: 0.04em;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
  }
  .bsc-metric-item .label svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
  }
  .bsc-metric-item .val {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--cinema-text-main, #0f172a);
  }
  .bsc-metric-item .sub {
    font-size: 0.76rem;
    color: var(--cinema-text-muted, #64748b);
    margin-top: 2px;
  }
  .bsc-synopsis-box {
    background: #f8fafc;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 8px;
    padding: 1.25rem;
  }
  .bsc-synopsis-box h4 {
    margin: 0 0 0.6rem 0;
    font-size: 0.82rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 0.04em;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .bsc-schedule-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 1.5rem;
    background: #ffffff;
  }
  .bsc-schedule-table th {
    padding: 0.75rem 1rem;
    background: #f8fafc;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    text-align: left;
    border-bottom: 1px solid var(--cinema-border, #e2e8f0);
  }
  .bsc-schedule-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--cinema-border-light, #f1f5f9);
    font-size: 0.88rem;
    color: var(--cinema-text-main, #0f172a);
  }
  .bsc-schedule-table tr:last-child td {
    border-bottom: none;
  }
  .bsc-weekday-badge {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
  }
  .bsc-time-pill-view {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f1f5f9;
    color: var(--cinema-text-main, #0f172a);
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 3px 8px;
    font-size: 0.8rem;
    font-weight: 700;
    font-family: var(--cinema-font-mono, monospace);
  }
  .bsc-cast-grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
  }
  .bsc-cast-card-mini {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8fafc;
    border: 1px solid var(--cinema-border, #e2e8f0);
    padding: 8px 12px;
    border-radius: 8px;
  }
  .bsc-cast-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #cbd5e1;
  }
  @media (max-width: 900px) {
    .bsc-movie-dossier-grid { grid-template-columns: 1fr; }
    .bsc-metric-card-grid { grid-template-columns: 1fr; }
    .bsc-movie-banner-head { flex-direction: column; gap: 1rem; }
  }
</style>

<div class="bsc-movie-dossier-grid">
  
  <!-- Left Column: Poster & Direct Media Links -->
  <div class="bsc-poster-sidebar-card">
    <img src="<?php echo esc_url( $movie->poster_url ?: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=700&auto=format&fit=crop&q=75' ); ?>" alt="<?php echo esc_attr( $movie->title ); ?>">
    
    <div style="margin-top: 1.25rem; background:#f8fafc; border:1px solid #e2e8f0; padding:1rem; border-radius:8px;">
      <span style="font-size:0.72rem; text-transform:uppercase; font-weight:800; color:#64748b; display:block; margin-bottom:8px;"><?php esc_html_e( 'Direct Assets', 'bsc-cinema-manager' ); ?></span>
      <div style="font-size:0.82rem; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
        <strong style="color:#0f172a;"><?php esc_html_e( 'Poster Image:', 'bsc-cinema-manager' ); ?></strong> 
        <a href="<?php echo esc_url( $movie->poster_url ); ?>" target="_blank" style="color:#0284c7; font-weight:700; text-decoration:none;"><?php esc_html_e( 'Open ↗', 'bsc-cinema-manager' ); ?></a>
      </div>
      <?php if ( ! empty( $movie->trailer_url ) ) : ?>
        <div style="font-size:0.82rem; display:flex; justify-content:space-between; align-items:center;">
          <strong style="color:#0f172a;"><?php esc_html_e( 'Trailer Stream:', 'bsc-cinema-manager' ); ?></strong> 
          <a href="<?php echo esc_url( $movie->trailer_url ); ?>" target="_blank" style="color:#0284c7; font-weight:700; text-decoration:none;"><?php esc_html_e( 'Open ↗', 'bsc-cinema-manager' ); ?></a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Content & Metadata -->
  <div class="bsc-movie-content-main">
    
    <!-- Top Header Banner -->
    <div class="bsc-movie-banner-head">
      <div>
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
          <span style="background:rgba(56,189,248,0.15); color:#38bdf8; border:1px solid rgba(56,189,248,0.3); padding:2px 8px; border-radius:4px; font-size:0.72rem; font-weight:800; letter-spacing:0.04em;">
            <?php echo esc_html( $movie->badge_tag ?: 'STANDARD' ); ?>
          </span>
          <span style="font-size:0.78rem; color:#94a3b8;">
            <?php echo esc_html( $movie->genre ); ?>
          </span>
        </div>
        <h2 style="font-size: 1.8rem; font-weight: 900; margin: 0 0 0.35rem 0; color: #ffffff;">
          <?php echo esc_html( $movie->title ); ?>
        </h2>
        <div style="font-size: 0.8rem; color: #94a3b8; font-family: var(--cinema-font-mono, monospace);">
          ID: #MOVIE-<?php echo intval( $movie->id ); ?> &bull; Registered: <?php echo esc_html( bsc_format_view_date( $movie->created_at ?? '' ) ); ?>
        </div>
      </div>

      <div style="display:flex; gap:8px;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-movies&subtab=edit&id=' . $movie->id ) ); ?>" class="button" style="background:#f1f5f9; border-color:#cbd5e1; color:#0f172a; font-weight:800; border-radius:6px;">
          ✏️ <?php esc_html_e( 'Edit', 'bsc-cinema-manager' ); ?>
        </a>
        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=bsc-movies&subtab=delete&id=' . $movie->id ), 'bsc_delete_movie_' . $movie->id ) ); ?>" class="button" style="background:#fef2f2; border-color:#fecaca; color:#ef4444; font-weight:800; border-radius:6px;" onclick="return confirm('<?php echo esc_js( __( 'Permanently delete this movie?', 'bsc-cinema-manager' ) ); ?>');">
          ✕ <?php esc_html_e( 'Delete', 'bsc-cinema-manager' ); ?>
        </a>
      </div>
    </div>

    <!-- Main Content Details -->
    <div class="bsc-movie-body-wrap">
      
      <!-- 3 Metrics: Rating, Release Date, Runtime -->
      <div class="bsc-metric-card-grid">
        <div class="bsc-metric-item">
          <div class="label">
            <svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
            <?php esc_html_e( 'Master Rating', 'bsc-cinema-manager' ); ?>
          </div>
          <div class="val" style="color:#d97706;">★ <?php echo esc_html( $movie->rating ); ?> / 10</div>
          <div class="sub"><?php echo esc_html( $movie->age_rating ); ?></div>
        </div>

        <div class="bsc-metric-item">
          <div class="label">
            <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
            <?php esc_html_e( 'Release Date', 'bsc-cinema-manager' ); ?>
          </div>
          <div class="val" style="color:#059669;"><?php echo esc_html( $official_release_date_str ); ?></div>
          <div class="sub"><?php esc_html_e( 'Official Theatrical Date', 'bsc-cinema-manager' ); ?></div>
        </div>

        <div class="bsc-metric-item">
          <div class="label">
            <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            <?php esc_html_e( 'Runtime & Audio', 'bsc-cinema-manager' ); ?>
          </div>
          <div class="val"><?php echo esc_html( $movie->duration ); ?></div>
          <div class="sub"><?php echo esc_html( $movie->audio_language ); ?></div>
        </div>
      </div>

      <!-- Director & Writers -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.9rem 1.15rem;">
          <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; color:#64748b; display:block; margin-bottom:3px;"><?php esc_html_e( 'Director', 'bsc-cinema-manager' ); ?></span>
          <strong style="font-size:0.98rem; color:#0f172a;"><?php echo esc_html( $movie->director ?: 'Not Specified' ); ?></strong>
        </div>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:0.9rem 1.15rem;">
          <span style="font-size:0.7rem; font-weight:800; text-transform:uppercase; color:#64748b; display:block; margin-bottom:3px;"><?php esc_html_e( 'Screenwriters & Story', 'bsc-cinema-manager' ); ?></span>
          <strong style="font-size:0.98rem; color:#0f172a;"><?php echo esc_html( $movie->writers ?: 'Not Specified' ); ?></strong>
        </div>
      </div>

      <!-- Screening Schedule Table (Formatted Dates & 12-Hour Times) -->
      <div style="margin-bottom:1.5rem;">
        <h4 style="margin:0 0 0.75rem 0; font-size:0.82rem; font-weight:800; text-transform:uppercase; color:#475569; letter-spacing:0.04em; display:flex; align-items:center; gap:6px;">
          🗓️ <?php esc_html_e( 'Screening Schedule (Multi-Day Showtimes)', 'bsc-cinema-manager' ); ?>
        </h4>

        <table class="bsc-schedule-table">
          <thead>
            <tr>
              <th style="width:40px;">#</th>
              <th style="width:160px;"><?php esc_html_e( 'Date', 'bsc-cinema-manager' ); ?></th>
              <th style="width:100px;"><?php esc_html_e( 'Day', 'bsc-cinema-manager' ); ?></th>
              <th><?php esc_html_e( 'Showtimes', 'bsc-cinema-manager' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php 
              if ( ! empty( $day_schedules ) ) :
                $row_idx = 1;
                foreach ( $day_schedules as $day_slot ) :
                  $d_raw = $day_slot['date'] ?? current_time( 'Y-m-d' );
                  $formatted_d = bsc_format_view_date( $d_raw );
                  $day_name = date( 'D', strtotime( $d_raw ) );
                  $times_arr = (array) ( $day_slot['times'] ?? array( '18:00' ) );
            ?>
              <tr>
                <td style="font-weight:700; color:#64748b;"><?php echo $row_idx++; ?></td>
                <td><strong style="color:#0f172a;"><?php echo esc_html( $formatted_d ); ?></strong></td>
                <td><span class="bsc-weekday-badge"><?php echo esc_html( strtoupper( $day_name ) ); ?></span></td>
                <td>
                  <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    <?php foreach ( $times_arr as $t ) : 
                      $formatted_t = bsc_format_view_time( $t );
                    ?>
                      <span class="bsc-time-pill-view">
                        ⏰ <?php echo esc_html( $formatted_t ); ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </td>
              </tr>
            <?php 
                endforeach;
              else : 
            ?>
              <tr><td colspan="4" style="text-align:center; color:#64748b; padding:1.5rem;"><?php esc_html_e( 'No screening schedules recorded.', 'bsc-cinema-manager' ); ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Short Summary & Full Storyline -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
        <div class="bsc-synopsis-box">
          <h4>📄 <?php esc_html_e( 'Short Summary', 'bsc-cinema-manager' ); ?></h4>
          <p style="font-size: 0.88rem; line-height: 1.6; color: #334155; margin: 0;">
            <?php echo nl2br( esc_html( $movie->short_description ?: 'No short summary provided.' ) ); ?>
          </p>
        </div>

        <div class="bsc-synopsis-box">
          <h4>📖 <?php esc_html_e( 'Full Storyline & Synopsis', 'bsc-cinema-manager' ); ?></h4>
          <p style="font-size: 0.88rem; line-height: 1.6; color: #334155; margin: 0;">
            <?php echo nl2br( esc_html( $movie->synopsis ?: 'No full synopsis provided.' ) ); ?>
          </p>
        </div>
      </div>

      <!-- Trailer Preview Video -->
      <?php if ( ! empty( $movie->trailer_url ) ) : ?>
        <div style="margin-bottom:1.5rem;">
          <h4 style="margin:0 0 0.65rem 0; font-size:0.82rem; font-weight:800; text-transform:uppercase; color:#475569; letter-spacing:0.04em;">
            ▶️ <?php esc_html_e( 'Trailer Preview', 'bsc-cinema-manager' ); ?>
          </h4>
          <video src="<?php echo esc_url( $movie->trailer_url ); ?>" controls style="width:100%; max-height:360px; border-radius:8px; background:#000; display:block;"></video>
        </div>
      <?php endif; ?>

      <!-- Lead Cast Roster -->
      <?php if ( ! empty( $cast_list ) ) : ?>
        <div style="margin-bottom:1.5rem;">
          <h4 style="margin:0 0 0.75rem 0; font-size:0.82rem; font-weight:800; text-transform:uppercase; color:#475569; letter-spacing:0.04em;">
            🎭 <?php esc_html_e( 'Lead Cast & Crew', 'bsc-cinema-manager' ); ?> (<?php echo count( $cast_list ); ?>)
          </h4>
          <div class="bsc-cast-grid-view">
            <?php foreach ( $cast_list as $cast ) : ?>
              <div class="bsc-cast-card-mini">
                <img src="<?php echo esc_url( ! empty( $cast['photo'] ) ? $cast['photo'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80' ); ?>" class="bsc-cast-avatar" alt="<?php echo esc_attr( $cast['name'] ); ?>">
                <div>
                  <strong style="font-size:0.88rem; color:#0f172a; display:block;"><?php echo esc_html( $cast['name'] ?: 'Unknown' ); ?></strong>
                  <span style="font-size:0.75rem; color:#64748b;"><?php echo esc_html( $cast['role'] ?: 'Cast Member' ); ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Back Navigation Link -->
      <div style="margin-top:1.5rem; border-top:1px solid #f1f5f9; padding-top:1.25rem;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-movies&subtab=all' ) ); ?>" class="button button-secondary" style="font-weight:700; border-radius:6px;">
          &larr; <?php esc_html_e( 'Back to All Movies', 'bsc-cinema-manager' ); ?>
        </a>
      </div>

    </div>

  </div>

</div>

<script>
  // Ensure media frames or interactive buttons work correctly if needed
</script>