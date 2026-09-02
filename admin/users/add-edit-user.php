<?php
if (!defined('ABSPATH')) exit;

$staff   = null;
$is_edit = ($subtab === 'edit' && $staff_id > 0);

if ($is_edit) {
  $staff = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_staff WHERE id = %d", $staff_id));
}
?>
<div class="cinema-panel" style="max-width:750px; margin:0 auto; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
  <h3 class="cinema-panel-title" style="margin-bottom:1.5rem;">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
    <?php echo $is_edit ? esc_html__('Edit Staff Personnel Profile', 'big-screen-cinemas') : esc_html__('Register New Staff Member', 'big-screen-cinemas'); ?>
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
        <label><?php esc_html_e('Monthly Salary', 'big-screen-cinemas'); ?> (<?php echo esc_html($currency_symbol); ?>)</label>
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
          <button type="button" class="button" id="btn_upload_staff_photo">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
            <?php esc_html_e('Select / Upload Photo', 'big-screen-cinemas'); ?>
          </button>
          <button type="button" class="button" id="btn_remove_staff_photo" style="<?php echo !empty($staff->photo_url) ? 'display:inline-block;' : 'display:none;'; ?> color:red; margin-left:8px;"><?php esc_html_e('Remove', 'big-screen-cinemas'); ?></button>
        </div>
      </div>
    </div>

    <div class="bsc-form-group">
      <label><?php esc_html_e('Operational Privileges & Notes', 'big-screen-cinemas'); ?></label>
      <textarea name="responsibilities" rows="3" placeholder="<?php esc_attr_e('Assigned responsibilities, hall authorization keys, projection access...', 'big-screen-cinemas'); ?>"><?php echo esc_textarea($staff->responsibilities ?? ''); ?></textarea>
    </div>

    <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:12px; font-weight:800; font-size:0.95rem; border-radius:8px; margin-top:0.75rem; display:flex; align-items:center; justify-content:center; gap:8px;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
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