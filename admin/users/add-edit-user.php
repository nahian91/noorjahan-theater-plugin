<?php
/**
 * Users / Staff Module - Add & Edit Staff Views & Submission Handlers
 *
 * Path: admin/users/add-edit-user.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -------------------------------------------------------------
// 1. PROCESS FORM SUBMISSIONS ON admin_init (PREVENTS HEADER CONFLICTS)
// -------------------------------------------------------------
function bsc_handle_staff_form_submissions() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['bsc_action'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
    }

    global $wpdb;
    $table_staff = $wpdb->prefix . 'bsc_staff_crew';

    $action       = sanitize_text_field( wp_unslash( $_POST['bsc_action'] ?? '' ) );
    $name         = sanitize_text_field( wp_unslash( $_POST['staff_name'] ?? '' ) );
    $role_title   = sanitize_text_field( wp_unslash( $_POST['staff_role'] ?? '' ) );
    $duty_status  = sanitize_text_field( wp_unslash( $_POST['duty_status'] ?? 'On Duty' ) );
    $shift        = sanitize_text_field( wp_unslash( $_POST['shift_schedule'] ?? '' ) );
    $phone        = sanitize_text_field( wp_unslash( $_POST['staff_phone'] ?? '' ) );
    $email        = sanitize_email( wp_unslash( $_POST['staff_email'] ?? '' ) );
    $salary       = floatval( $_POST['staff_salary'] ?? 0.00 );
    $photo_url    = esc_url_raw( wp_unslash( $_POST['photo_url'] ?? '' ) );
    $responsibles = sanitize_textarea_field( wp_unslash( $_POST['responsibilities'] ?? '' ) );

    // 1. Add Staff Member
    if ( 'add_staff' === $action ) {
        check_admin_referer( 'bsc_add_staff_verify' );

        $inserted = $wpdb->insert(
            $table_staff,
            array(
                'name'             => $name,
                'role_title'       => $role_title,
                'duty_status'      => $duty_status,
                'shift_schedule'   => $shift,
                'phone'            => $phone,
                'email'            => $email,
                'salary'           => $salary,
                'photo_url'        => $photo_url,
                'responsibilities' => $responsibles,
                'created_at'       => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
        );

        if ( false !== $inserted ) {
            wp_safe_redirect( admin_url( 'admin.php?page=bsc-users&subtab=all&staff_added=true' ) );
            exit;
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=bsc-users&subtab=add&staff_error=db_failed' ) );
            exit;
        }
    }

    // 2. Edit Staff Member
    if ( 'edit_staff' === $action ) {
        check_admin_referer( 'bsc_edit_staff_verify' );
        $staff_id = absint( $_POST['staff_id'] ?? 0 );

        if ( $staff_id > 0 ) {
            $updated = $wpdb->update(
                $table_staff,
                array(
                    'name'             => $name,
                    'role_title'       => $role_title,
                    'duty_status'      => $duty_status,
                    'shift_schedule'   => $shift,
                    'phone'            => $phone,
                    'email'            => $email,
                    'salary'           => $salary,
                    'photo_url'        => $photo_url,
                    'responsibilities' => $responsibles,
                ),
                array( 'id' => $staff_id ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' ),
                array( '%d' )
            );

            if ( false !== $updated ) {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-users&subtab=all&staff_updated=true' ) );
                exit;
            } else {
                wp_safe_redirect( admin_url( 'admin.php?page=bsc-users&subtab=edit&id=' . $staff_id . '&staff_error=db_failed' ) );
                exit;
            }
        }
    }
}
add_action( 'admin_init', 'bsc_handle_staff_form_submissions' );

// -------------------------------------------------------------
// 2. UI VIEW RENDERING
// -------------------------------------------------------------
global $wpdb;
$table_staff = $wpdb->prefix . 'bsc_staff_crew';

$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( $_GET['subtab'] ) : 'add';
$staff_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$staff   = null;
$is_edit = ( 'edit' === $subtab && $staff_id > 0 );

if ( $is_edit ) {
    $staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id ) );
}

$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );
?>
<div class="cinema-panel" style="max-width:750px; margin:10px auto 60px auto; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
  <h3 class="cinema-panel-title" style="margin-bottom:1.5rem; display:flex; align-items:center; gap:8px;">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
    <?php echo $is_edit ? esc_html__( 'Edit Staff Profile', 'bsc-cinema-manager' ) : esc_html__( 'Add Staff Member', 'bsc-cinema-manager' ); ?>
  </h3>

  <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-users' . ( $is_edit ? '&subtab=edit&id=' . $staff->id : '' ) ) ); ?>">
    <?php
    if ( $is_edit ) {
        wp_nonce_field( 'bsc_edit_staff_verify' );
        echo '<input type="hidden" name="bsc_action" value="edit_staff">';
        echo '<input type="hidden" name="staff_id" value="' . esc_attr( $staff->id ) . '">';
    } else {
        wp_nonce_field( 'bsc_add_staff_verify' );
        echo '<input type="hidden" name="bsc_action" value="add_staff">';
    }
    ?>

    <div class="bsc-form-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:1rem;">
      <div class="bsc-form-group" style="margin-bottom:0;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Full Name', 'bsc-cinema-manager' ); ?> *</label>
        <input type="text" name="staff_name" required value="<?php echo esc_attr( $staff->name ?? '' ); ?>" placeholder="e.g. Tanvir Ahmed" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
      </div>

      <div class="bsc-form-group" style="margin-bottom:0;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Job Title', 'bsc-cinema-manager' ); ?> *</label>
        <select name="staff_role" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
          <?php
            $roles = array(
                'Master Administrator',
                'Chief Projectionist',
                'Audio & Sound Engineer',
                'Box Office Supervisor',
                'Gate Inspector',
                'Concessions Lead',
                'Operations Steward',
            );
            $selected_role = $staff->role_title ?? 'Box Office Supervisor';
            foreach ( $roles as $r ) {
                echo '<option value="' . esc_attr( $r ) . '" ' . selected( $selected_role, $r, false ) . '>' . esc_html( $r ) . '</option>';
            }
          ?>
        </select>
      </div>
    </div>

    <div class="bsc-form-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:1rem;">
      <div class="bsc-form-group" style="margin-bottom:0;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Status', 'bsc-cinema-manager' ); ?> *</label>
        <select name="duty_status" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
          <option value="On Duty" <?php selected( $staff->duty_status ?? 'On Duty', 'On Duty' ); ?>><?php esc_html_e( 'On Duty', 'bsc-cinema-manager' ); ?></option>
          <option value="Off Duty" <?php selected( $staff->duty_status ?? 'On Duty', 'Off Duty' ); ?>><?php esc_html_e( 'Off Duty', 'bsc-cinema-manager' ); ?></option>
          <option value="On Leave" <?php selected( $staff->duty_status ?? 'On Duty', 'On Leave' ); ?>><?php esc_html_e( 'On Leave', 'bsc-cinema-manager' ); ?></option>
        </select>
      </div>

      <div class="bsc-form-group" style="margin-bottom:0;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Work Shift', 'bsc-cinema-manager' ); ?> *</label>
        <select name="shift_schedule" required style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
          <option value="Day Shift (11:00 AM - 07:00 PM)" <?php selected( $staff->shift_schedule ?? '', 'Day Shift (11:00 AM - 07:00 PM)' ); ?>><?php esc_html_e( 'Day (11 AM - 7 PM)', 'bsc-cinema-manager' ); ?></option>
          <option value="Evening Shift (03:00 PM - 11:30 PM)" <?php selected( $staff->shift_schedule ?? '', 'Evening Shift (03:00 PM - 11:30 PM)' ); ?>><?php esc_html_e( 'Evening (3 PM - 11:30 PM)', 'bsc-cinema-manager' ); ?></option>
          <option value="Full Rotational" <?php selected( $staff->shift_schedule ?? 'Full Rotational', 'Full Rotational' ); ?>><?php esc_html_e( 'Rotational', 'bsc-cinema-manager' ); ?></option>
        </select>
      </div>
    </div>

    <div class="bsc-form-grid-3" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:1rem;">
      <div class="bsc-form-group" style="margin-bottom:0;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Phone', 'bsc-cinema-manager' ); ?> *</label>
        <input type="text" name="staff_phone" required value="<?php echo esc_attr( $staff->phone ?? '+880 ' ); ?>" placeholder="+880 1700-000000" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
      </div>

      <div class="bsc-form-group" style="margin-bottom:0;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Email', 'bsc-cinema-manager' ); ?></label>
        <input type="email" name="staff_email" value="<?php echo esc_attr( $staff->email ?? '' ); ?>" placeholder="staff@example.com" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
      </div>

      <div class="bsc-form-group" style="margin-bottom:0;">
        <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php echo esc_html( sprintf( __( 'Monthly Salary (%s)', 'bsc-cinema-manager' ), $currency_symbol ) ); ?></label>
        <input type="number" step="0.01" name="staff_salary" value="<?php echo esc_attr( $staff->salary ?? '25000.00' ); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
      </div>
    </div>

    <!-- Staff Headshot Photo Upload -->
    <div class="bsc-form-group" style="margin-bottom:1rem;">
      <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Profile Photo', 'bsc-cinema-manager' ); ?></label>
      <div class="bsc-media-uploader-box" style="background:#f8fafc; border:1px dashed #cbd5e1; padding:12px; border-radius:8px; text-align:center;">
        <input type="hidden" name="photo_url" id="staff_photo_url" value="<?php echo esc_url( $staff->photo_url ?? '' ); ?>">
        <img id="staff_photo_preview" src="<?php echo esc_url( $staff->photo_url ?? '' ); ?>" alt="Preview" class="bsc-media-preview-img" style="<?php echo ! empty( $staff->photo_url ) ? 'display:block;' : 'display:none;'; ?> max-height:100px; margin:0 auto 8px auto; border-radius:6px; object-fit:cover;">
        <div>
          <button type="button" class="button" id="btn_upload_staff_photo">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align:-2px; margin-right:4px;"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
            <?php esc_html_e( 'Upload Photo', 'bsc-cinema-manager' ); ?>
          </button>
          <button type="button" class="button" id="btn_remove_staff_photo" style="<?php echo ! empty( $staff->photo_url ) ? 'display:inline-block;' : 'display:none;'; ?> color:red; margin-left:8px;"><?php esc_html_e( 'Remove', 'bsc-cinema-manager' ); ?></button>
        </div>
      </div>
    </div>

    <div class="bsc-form-group" style="margin-bottom:1.5rem;">
      <label style="display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:5px;"><?php esc_html_e( 'Notes / Responsibilities', 'bsc-cinema-manager' ); ?></label>
      <textarea name="responsibilities" rows="3" placeholder="<?php esc_attr_e( 'Assigned responsibilities, duties...', 'bsc-cinema-manager' ); ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;"><?php echo esc_textarea( $staff->responsibilities ?? '' ); ?></textarea>
    </div>

    <button type="submit" class="button button-primary" style="background:#064e3b; border-color:#064e3b; width:100%; padding:11px; font-weight:800; font-size:0.95rem; border-radius:8px; display:flex; align-items:center; justify-content:center; gap:8px;">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
      <span><?php echo $is_edit ? esc_html__( 'Update Staff', 'bsc-cinema-manager' ) : esc_html__( 'Save Staff', 'bsc-cinema-manager' ); ?></span>
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