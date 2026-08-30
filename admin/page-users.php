<?php
if (!defined('ABSPATH')) exit;

function bsc_render_users_page() {
  global $wpdb;
  $table_staff = $wpdb->prefix . 'bsc_staff_crew';

  $subtab   = isset($_GET['subtab']) ? sanitize_key($_GET['subtab']) : 'all';
  $staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

  // Real-time metric computations
  $total_staff  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_staff");
  $on_duty_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_staff WHERE duty_status = 'On Duty'");
  $tech_count   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_staff WHERE role_title LIKE '%Projection%' OR role_title LIKE '%Sound%' OR role_title LIKE '%Laser%'");

  bsc_render_page_shell_open('bsc-users');
  ?>
  <style>
    .bsc-staff-kpi-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.25rem;
      margin-bottom: 1.75rem;
    }

    .bsc-staff-kpi-card {
      background: #ffffff;
      border: 1px solid var(--cinema-border);
      border-radius: var(--cinema-radius-md);
      padding: 1.25rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    }

    .bsc-staff-kpi-card .label {
      font-size: 0.72rem;
      text-transform: uppercase;
      font-weight: 800;
      color: var(--cinema-text-muted);
      letter-spacing: 0.05em;
    }

    .bsc-staff-kpi-card .val {
      font-size: 1.65rem;
      font-weight: 900;
      font-family: var(--cinema-font-mono);
      margin-top: 0.25rem;
      line-height: 1.1;
      color: var(--cinema-text-main);
    }

    .bsc-staff-avatar-cell {
      display: flex;
      align-items: center;
      gap: 0.85rem;
    }

    .bsc-staff-avatar-thumb {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--cinema-accent-teal);
      background: #0f1e24;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 800;
      font-size: 0.85rem;
    }

    .bsc-profile-hero-grid {
      display: grid;
      grid-template-columns: 220px 1fr;
      gap: 2rem;
      align-items: start;
    }

    .bsc-profile-avatar-large {
      width: 100%;
      height: 220px;
      border-radius: var(--cinema-radius-md);
      object-fit: cover;
      border: 2px solid var(--cinema-border);
    }
  </style>

  <!-- Subtabs Navigation -->
  <div class="bsc-subtabs-nav">
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-users&subtab=all')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'all') ? 'active' : ''; ?>">
      👤 <?php esc_html_e('All Staff & Crew', 'big-screen-cinemas'); ?>
    </a>
    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-users&subtab=add')); ?>" class="bsc-subtab-btn <?php echo ($subtab === 'add') ? 'active' : ''; ?>">
      ➕ <?php esc_html_e('Add Staff Member', 'big-screen-cinemas'); ?>
    </a>
    <?php if ($subtab === 'view'): ?>
      <span class="bsc-subtab-btn active"><?php esc_html_e('Staff Profile (Dossier)', 'big-screen-cinemas'); ?></span>
    <?php endif; ?>
    <?php if ($subtab === 'edit'): ?>
      <span class="bsc-subtab-btn active"><?php esc_html_e('Edit Staff Details', 'big-screen-cinemas'); ?></span>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['staff_added'])): ?>
    <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✓ <?php esc_html_e('Staff member added to multiplex personnel records!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['staff_updated'])): ?>
    <div style="background:#ecfdf5; color:#10b981; border:1px solid #a7f3d0; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✓ <?php esc_html_e('Staff profile & operational role updated!', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['staff_deleted'])): ?>
    <div style="background:#fef2f2; color:#ef4444; border:1px solid #fecaca; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:700;">
      ✕ <?php esc_html_e('Staff record removed from roster.', 'big-screen-cinemas'); ?>
    </div>
  <?php endif; ?>

  <!-- Staff Duty Overview KPI Deck -->
  <div class="bsc-staff-kpi-grid">
    <div class="bsc-staff-kpi-card">
      <div>
        <div class="label"><?php esc_html_e('Total Registered Staff', 'big-screen-cinemas'); ?></div>
        <div class="val"><?php echo number_format($total_staff); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted);">Crew</span></div>
      </div>
      <div class="bsc-metric-icon" style="background:#eff6ff; color:#3b82f6;">👥</div>
    </div>

    <div class="bsc-staff-kpi-card">
      <div>
        <div class="label"><?php esc_html_e('Currently Active On Duty', 'big-screen-cinemas'); ?></div>
        <div class="val" style="color:#059669;"><?php echo number_format($on_duty_count); ?> <span style="font-size:0.85rem; color:#10b981;">Present</span></div>
      </div>
      <div class="bsc-metric-icon" style="background:#ecfdf5; color:#10b981;">🟢</div>
    </div>

    <div class="bsc-staff-kpi-card">
      <div>
        <div class="label"><?php esc_html_e('Projection & Technical Crew', 'big-screen-cinemas'); ?></div>
        <div class="val" style="color:#d97706;"><?php echo number_format($tech_count); ?> <span style="font-size:0.85rem; color:var(--cinema-text-muted);">Techs</span></div>
      </div>
      <div class="bsc-metric-icon" style="background:#fef3c7; color:#d97706;">📽️</div>
    </div>
  </div>

  <?php
  /* ==========================================================================
     01. VIEW MODE: COMPLETE STAFF DOSSIER
     ========================================================================== */
  if ($subtab === 'view' && $staff_id > 0):
    $staff = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_staff WHERE id = %d", $staff_id));
    if (!$staff):
      echo '<div class="cinema-panel"><p>' . esc_html__('Staff record not found.', 'big-screen-cinemas') . '</p></div>';
    else:
      $is_on_duty = ($staff->duty_status === 'On Duty');
  ?>
    <div class="cinema-panel">
      <div class="cinema-panel-header" style="border-bottom:1px solid var(--cinema-border-light); padding-bottom:1.25rem;">
        <div>
          <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
            <span class="cinema-badge-pill active" style="<?php echo !$is_on_duty ? 'background:rgba(239,68,68,0.15); color:#ef4444; border-color:rgba(239,68,68,0.3);' : ''; ?>">
              ● <?php echo esc_html($staff->duty_status); ?>
            </span>
            <span class="cinema-voucher-tag"><?php echo esc_html($staff->staff_code); ?></span>
          </div>
          <h2 style="font-size:1.85rem; font-weight:900; margin:0 0 0.35rem 0; color:var(--cinema-text-main);">
            <?php echo esc_html($staff->name); ?>
          </h2>
          <span style="font-size:0.82rem; color:var(--cinema-text-muted); font-family:var(--cinema-font-mono);">
            Role: <strong><?php echo esc_html($staff->role_title); ?></strong> • Joined: <strong><?php echo esc_html($staff->joined_date ?: $staff->created_at); ?></strong>
          </span>
        </div>

        <div class="bsc-action-btn-group">
          <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-users&subtab=edit&id=' . $staff->id)); ?>" class="button button-primary" style="background:#ca8a04; border-color:#ca8a04; font-weight:700;">
            ✏️ <?php esc_html_e('Edit Staff Details', 'big-screen-cinemas'); ?>
          </a>
          <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=bsc_delete_staff&id=' . $staff->id), 'bsc_delete_staff_' . $staff->id)); ?>" class="button" style="color:#dc2626; border-color:#fca5a5; background:#fff;" onclick="return confirm('Permanently delete this staff member?');">
            🗑️ <?php esc_html_e('Delete', 'big-screen-cinemas'); ?>
          </a>
          <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-users&subtab=all')); ?>" class="button button-secondary">
            ← <?php esc_html_e('Back to Roster', 'big-screen-cinemas'); ?>
          </a>
        </div>
      </div>

      <div class="bsc-profile-hero-grid" style="margin-top:1.5rem;">
        <!-- Avatar Photo -->
        <div>
          <?php if (!empty($staff->photo_url)): ?>
            <img src="<?php echo esc_url($staff->photo_url); ?>" alt="<?php echo esc_attr($staff->name); ?>" class="bsc-profile-avatar-large">
          <?php else: ?>
            <div class="bsc-profile-avatar-large" style="background:#0f1e24; display:flex; align-items:center; justify-content:center; color:#fff; font-size:3rem; font-weight:900;">
              <?php echo esc_html(strtoupper(substr($staff->name, 0, 2))); ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Meta Grid -->
        <div>
          <div class="bsc-view-stats-row" style="grid-template-columns: repeat(3, 1fr); margin-bottom:1.25rem;">
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Operational Role', 'big-screen-cinemas'); ?></div>
              <div class="v" style="font-size:0.95rem;"><?php echo esc_html($staff->role_title); ?></div>
            </div>
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Assigned Area / Hall', 'big-screen-cinemas'); ?></div>
              <div class="v" style="font-size:0.95rem; color:var(--cinema-accent-teal);"><?php echo esc_html($staff->assigned_hall); ?></div>
            </div>
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Work Shift', 'big-screen-cinemas'); ?></div>
              <div class="v" style="font-size:0.95rem;"><?php echo esc_html($staff->shift_schedule ?: 'Full-Day (Rotational)'); ?></div>
            </div>
          </div>

          <div class="bsc-view-stats-row" style="grid-template-columns: repeat(3, 1fr); margin-bottom:1.25rem;">
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Contact Phone', 'big-screen-cinemas'); ?></div>
              <div class="v" style="font-size:0.9rem; font-family:var(--cinema-font-mono);"><?php echo esc_html($staff->phone ?: 'Not provided'); ?></div>
            </div>
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Email Address', 'big-screen-cinemas'); ?></div>
              <div class="v" style="font-size:0.85rem; word-break:break-all;"><?php echo esc_html($staff->email ?: 'Not provided'); ?></div>
            </div>
            <div class="bsc-stat-card-mini">
              <div class="k"><?php esc_html_e('Monthly Payroll (BDT)', 'big-screen-cinemas'); ?></div>
              <div class="v" style="color:#059669; font-family:var(--cinema-font-mono);">৳<?php echo number_format($staff->salary, 2); ?></div>
            </div>
          </div>

          <!-- Permissions & Operational Notes -->
          <div style="background:#f8fafc; border:1px solid var(--cinema-border); padding:1rem 1.25rem; border-radius:8px;">
            <span style="font-size:0.75rem; text-transform:uppercase; font-weight:800; color:var(--cinema-text-muted); display:block; margin-bottom:4px;">
              📝 <?php esc_html_e('Staff Privileges & Operational Responsibilities', 'big-screen-cinemas'); ?>
            </span>
            <p style="margin:0; font-size:0.92rem; line-height:1.7; color:var(--cinema-text-main);">
              <?php echo esc_html($staff->responsibilities ?: 'Standard cinema floor operations, guest welcoming, equipment readiness, and ticket turnstile check-in duties.'); ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  <?php
    endif;
  /* ==========================================================================
     02. ADD / EDIT STAFF FORM
     ========================================================================== */
  elseif ($subtab === 'add' || $subtab === 'edit'):
    $staff   = null;
    $is_edit = ($subtab === 'edit' && $staff_id > 0);

    if ($is_edit) {
      $staff = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_staff WHERE id = %d", $staff_id));
    }
  ?>
    <div class="cinema-panel" style="max-width:750px; margin:0 auto;">
      <h3 class="cinema-panel-title" style="margin-bottom:1.5rem;">
        👤 <?php echo $is_edit ? esc_html__('Edit Staff Personnel Profile', 'big-screen-cinemas') : esc_html__('Add New Multiplex Staff Member', 'big-screen-cinemas'); ?>
      </h3>

      <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php
        if ($is_edit) {
          wp_nonce_field('bsc_edit_staff_verify');
          echo '<input type="hidden" name="action" value="bsc_edit_staff">';
          echo '<input type="hidden" name="staff_id" value="' . esc_attr($staff->id) . '">';
        } else {
          wp_nonce_field('bsc_add_staff_verify');
          echo '<input type="hidden" name="action" value="bsc_add_staff">';
        }
        ?>

        <div class="bsc-form-grid-2">
          <div class="bsc-form-group">
            <label><?php esc_html_e('Full Legal Name', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="staff_name" required value="<?php echo esc_attr($staff->name ?? ''); ?>" placeholder="e.g. Tanvir Ahmed">
          </div>

          <div class="bsc-form-group">
            <label><?php esc_html_e('Operational Role / Job Title', 'big-screen-cinemas'); ?> *</label>
            <select name="staff_role" required>
              <?php
                $roles = array(
                  'Master Administrator',
                  'Chief Projectionist (4K Laser)',
                  'Audio & Sound Engineer (Dolby Atmos)',
                  'Concierge & Box Office Supervisor',
                  'Turnstile Gate Inspector',
                  'F&B / Concessions Lead',
                  'Auditorium Operations Steward'
                );
                $selected_role = $staff->role_title ?? 'Concierge & Box Office Supervisor';
                foreach ($roles as $r) {
                  echo '<option value="' . esc_attr($r) . '" ' . selected($selected_role, $r, false) . '>' . esc_html($r) . '</option>';
                }
              ?>
            </select>
          </div>
        </div>

        <div class="bsc-form-grid-3">
          <div class="bsc-form-group">
            <label><?php esc_html_e('Assigned Hall / Area', 'big-screen-cinemas'); ?> *</label>
            <select name="assigned_hall" required>
              <?php
                $halls = array(
                  'All Multiplex Facilities',
                  'Auditorium 1 (23 Seats)',
                  'Auditorium 2 (23 Seats)',
                  'Auditorium 3 (23 Seats)',
                  'Reception & Gate Turnstiles',
                  'VIP Lounge & Concessions'
                );
                $selected_hall = $staff->assigned_hall ?? 'All Multiplex Facilities';
                foreach ($halls as $h) {
                  echo '<option value="' . esc_attr($h) . '" ' . selected($selected_hall, $h, false) . '>' . esc_html($h) . '</option>';
                }
              ?>
            </select>
          </div>

          <div class="bsc-form-group">
            <label><?php esc_html_e('Duty Status', 'big-screen-cinemas'); ?> *</label>
            <select name="duty_status" required>
              <option value="On Duty" <?php selected($staff->duty_status ?? 'On Duty', 'On Duty'); ?>><?php esc_html_e('On Duty (Active)', 'big-screen-cinemas'); ?></option>
              <option value="Off Duty" <?php selected($staff->duty_status ?? 'On Duty', 'Off Duty'); ?>><?php esc_html_e('Off Duty (Standby)', 'big-screen-cinemas'); ?></option>
              <option value="On Leave" <?php selected($staff->duty_status ?? 'On Duty', 'On Leave'); ?>><?php esc_html_e('On Leave', 'big-screen-cinemas'); ?></option>
            </select>
          </div>

          <div class="bsc-form-group">
            <label><?php esc_html_e('Work Shift', 'big-screen-cinemas'); ?> *</label>
            <select name="shift_schedule" required>
              <option value="Day Shift (11:00 AM - 07:00 PM)" <?php selected($staff->shift_schedule ?? '', 'Day Shift (11:00 AM - 07:00 PM)'); ?>>Day (11 AM - 7 PM)</option>
              <option value="Evening Shift (03:00 PM - 11:30 PM)" <?php selected($staff->shift_schedule ?? '', 'Evening Shift (03:00 PM - 11:30 PM)'); ?>>Evening (3 PM - 11:30 PM)</option>
              <option value="Full Rotational" <?php selected($staff->shift_schedule ?? 'Full Rotational', 'Full Rotational'); ?>>Full Rotational</option>
            </select>
          </div>
        </div>

        <div class="bsc-form-grid-3">
          <div class="bsc-form-group">
            <label><?php esc_html_e('Contact Phone', 'big-screen-cinemas'); ?> *</label>
            <input type="text" name="staff_phone" required value="<?php echo esc_attr($staff->phone ?? '+880 '); ?>" placeholder="+880 1700-000000">
          </div>

          <div class="bsc-form-group">
            <label><?php esc_html_e('Email Address', 'big-screen-cinemas'); ?></label>
            <input type="email" name="staff_email" value="<?php echo esc_attr($staff->email ?? ''); ?>" placeholder="staff@example.com">
          </div>

          <div class="bsc-form-group">
            <label><?php esc_html_e('Monthly Salary (BDT)', 'big-screen-cinemas'); ?></label>
            <input type="number" step="0.01" name="staff_salary" value="<?php echo esc_attr($staff->salary ?? '25000.00'); ?>">
          </div>
        </div>

        <!-- Staff Headshot Photo Upload -->
        <div class="bsc-form-group">
          <label><?php esc_html_e('Profile Headshot Image (WP Media Upload)', 'big-screen-cinemas'); ?></label>
          <div class="bsc-media-uploader-box">
            <input type="hidden" name="photo_url" id="staff_photo_url" value="<?php echo esc_url($staff->photo_url ?? ''); ?>">
            <img id="staff_photo_preview" src="<?php echo esc_url($staff->photo_url ?? ''); ?>" alt="Preview" class="bsc-media-preview-img" style="<?php echo !empty($staff->photo_url) ? 'display:block;' : 'display:none;'; ?>">
            <div>
              <button type="button" class="button" id="btn_upload_staff_photo">📷 <?php esc_html_e('Select / Upload Photo', 'big-screen-cinemas'); ?></button>
              <button type="button" class="button" id="btn_remove_staff_photo" style="<?php echo !empty($staff->photo_url) ? 'display:inline-block;' : 'display:none;'; ?> color:red; margin-left:8px;"><?php esc_html_e('Remove', 'big-screen-cinemas'); ?></button>
            </div>
          </div>
        </div>

        <div class="bsc-form-group">
          <label><?php esc_html_e('Operational Privileges & Notes', 'big-screen-cinemas'); ?></label>
          <textarea name="responsibilities" rows="3" placeholder="<?php esc_attr_e('Assigned responsibilities, hall authorization keys, projection access...', 'big-screen-cinemas'); ?>"><?php echo esc_textarea($staff->responsibilities ?? ''); ?></textarea>
        </div>

        <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:10px; font-weight:800; font-size:0.95rem; margin-top:0.5rem;">
          <?php echo $is_edit ? esc_html__('Update Staff Personnel Details', 'big-screen-cinemas') : esc_html__('Save Staff Member to Roster', 'big-screen-cinemas'); ?>
        </button>
      </form>
    </div>

    <!-- Media Script -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const btnUpload = document.getElementById('btn_upload_staff_photo');
        const inputUrl  = document.getElementById('staff_photo_url');
        const imgPrev   = document.getElementById('staff_photo_preview');
        const btnRemove = document.getElementById('btn_remove_staff_photo');

        if (btnUpload) {
          btnUpload.addEventListener('click', function(e) {
            e.preventDefault();
            const frame = wp.media({
              title: 'Select Staff Member Photo',
              button: { text: 'Use This Photo' },
              multiple: false,
              library: { type: 'image' }
            });
            frame.on('select', function() {
              const attachment = frame.state().get('selection').first().toJSON();
              inputUrl.value = attachment.url;
              imgPrev.src = attachment.url;
              imgPrev.style.display = 'block';
              btnRemove.style.display = 'inline-block';
            });
            frame.open();
          });

          btnRemove.addEventListener('click', function() {
            inputUrl.value = '';
            imgPrev.src = '';
            imgPrev.style.display = 'none';
            btnRemove.style.display = 'none';
          });
        }
      });
    </script>

  <?php
  /* ==========================================================================
     03. ALL STAFF MEMBERS TABLE (WITH VIEW, EDIT, DELETE)
     ========================================================================== */
  else:
    $staff_members = $wpdb->get_results("SELECT * FROM $table_staff ORDER BY id DESC");
  ?>
    <div class="cinema-panel" style="margin:0;">
      <div class="cinema-panel-header">
        <h3 class="cinema-panel-title">👥 <?php echo esc_html(sprintf(__('Staff & Operational Crew (%d)', 'big-screen-cinemas'), count($staff_members))); ?></h3>
      </div>

      <div class="cinema-table-wrap">
        <table class="cinema-data-table">
          <thead>
            <tr>
              <th>STAFF MEMBER</th>
              <th>STAFF ID</th>
              <th>ROLE / TITLE</th>
              <th>ASSIGNED HALL</th>
              <th>CONTACT</th>
              <th>STATUS</th>
              <th style="text-align:center;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($staff_members)): foreach ($staff_members as $s):
              $view_url   = admin_url('admin.php?page=bsc-users&subtab=view&id=' . $s->id);
              $edit_url   = admin_url('admin.php?page=bsc-users&subtab=edit&id=' . $s->id);
              $delete_url = wp_nonce_url(admin_url('admin-post.php?action=bsc_delete_staff&id=' . $s->id), 'bsc_delete_staff_' . $s->id);
              $is_on_duty = ($s->duty_status === 'On Duty');
            ?>
              <tr>
                <td>
                  <div class="bsc-staff-avatar-cell">
                    <?php if (!empty($s->photo_url)): ?>
                      <img src="<?php echo esc_url($s->photo_url); ?>" alt="" class="bsc-staff-avatar-thumb">
                    <?php else: ?>
                      <div class="bsc-staff-avatar-thumb">
                        <?php echo esc_html(strtoupper(substr($s->name, 0, 2))); ?>
                      </div>
                    <?php endif; ?>
                    <div>
                      <strong><?php echo esc_html($s->name); ?></strong><br>
                      <small style="color:var(--cinema-text-muted);"><?php echo esc_html($s->shift_schedule ?: 'General Shift'); ?></small>
                    </div>
                  </div>
                </td>
                <td><code><?php echo esc_html($s->staff_code); ?></code></td>
                <td><strong><?php echo esc_html($s->role_title); ?></strong></td>
                <td><span style="color:var(--cinema-accent-teal); font-weight:700;"><?php echo esc_html($s->assigned_hall); ?></span></td>
                <td>
                  <span style="font-family:var(--cinema-font-mono); font-size:0.8rem;"><?php echo esc_html($s->phone); ?></span><br>
                  <small style="color:var(--cinema-text-muted);"><?php echo esc_html($s->email); ?></small>
                </td>
                <td>
                  <span class="cinema-badge-pill active" style="<?php echo !$is_on_duty ? 'background:rgba(239,68,68,0.15); color:#ef4444; border-color:rgba(239,68,68,0.3);' : ''; ?>">
                    ● <?php echo esc_html($s->duty_status); ?>
                  </span>
                </td>
                <td style="text-align:center;">
                  <div class="bsc-action-btn-group" style="justify-content:center;">
                    <a href="<?php echo esc_url($view_url); ?>" class="bsc-btn-action view" title="<?php esc_attr_e('View Full Dossier', 'big-screen-cinemas'); ?>">👁 <?php esc_html_e('View', 'big-screen-cinemas'); ?></a>
                    <a href="<?php echo esc_url($edit_url); ?>" class="bsc-btn-action edit" title="<?php esc_attr_e('Edit Details', 'big-screen-cinemas'); ?>">✏️ <?php esc_html_e('Edit', 'big-screen-cinemas'); ?></a>
                    <a href="<?php echo esc_url($delete_url); ?>" class="bsc-btn-action delete" onclick="return confirm('Permanently remove this staff member from the system?');" title="<?php esc_attr_e('Delete Staff', 'big-screen-cinemas'); ?>">🗑️ <?php esc_html_e('Delete', 'big-screen-cinemas'); ?></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="7" style="text-align:center; padding:2rem;"><?php esc_html_e('No staff members registered in the multiplex roster.', 'big-screen-cinemas'); ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
  <?php
  bsc_render_page_shell_close();
}