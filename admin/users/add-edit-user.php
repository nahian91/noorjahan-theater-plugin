<?php
/**
 * Users / Staff Module - Add & Edit Staff Views & Submission Handlers
 *
 * Path: admin/users/add-edit-user.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -------------------------------------------------------------
// 1. EARLY SUBMISSION HANDLER
// -------------------------------------------------------------
if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['bsc_action'] ) ) {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
    }

    global $wpdb;
    $table_staff = $wpdb->prefix . 'bsc_staff_crew';

    $action       = sanitize_key( wp_unslash( $_POST['bsc_action'] ) );
    $name         = isset( $_POST['staff_name'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_name'] ) ) : '';
    $role_title   = isset( $_POST['staff_role'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_role'] ) ) : '';
    $duty_status  = isset( $_POST['duty_status'] ) ? sanitize_text_field( wp_unslash( $_POST['duty_status'] ) ) : 'On Duty';
    $shift        = isset( $_POST['shift_schedule'] ) ? sanitize_text_field( wp_unslash( $_POST['shift_schedule'] ) ) : '';
    $phone        = isset( $_POST['staff_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_phone'] ) ) : '';
    $email        = isset( $_POST['staff_email'] ) ? sanitize_email( wp_unslash( $_POST['staff_email'] ) ) : '';
    $salary       = isset( $_POST['staff_salary'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['staff_salary'] ) ) : 0.00;
    $photo_url    = isset( $_POST['photo_url'] ) ? esc_url_raw( wp_unslash( $_POST['photo_url'] ) ) : '';
    $responsibles = isset( $_POST['responsibilities'] ) ? sanitize_textarea_field( wp_unslash( $_POST['responsibilities'] ) ) : '';

    // Handle Add
    if ( 'add_user' === $action || 'add_staff' === $action ) {
        check_admin_referer( 'bsc_user_form_action', 'bsc_user_nonce' );

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
        }

        wp_safe_redirect( admin_url( 'admin.php?page=bsc-users&subtab=add&staff_error=db_failed' ) );
        exit;
    }

    // Handle Edit
    if ( 'edit_user' === $action || 'edit_staff' === $action ) {
        check_admin_referer( 'bsc_user_form_action', 'bsc_user_nonce' );
        $staff_id = isset( $_POST['staff_id'] ) ? absint( $_POST['staff_id'] ) : 0;

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
            }

            wp_safe_redirect( admin_url( 'admin.php?page=bsc-users&subtab=edit&id=' . $staff_id . '&staff_error=db_failed' ) );
            exit;
        }
    }
}

// -------------------------------------------------------------
// 2. UI VIEW RENDERING (Only runs during page render callback)
// -------------------------------------------------------------
global $wpdb;
$table_staff = $wpdb->prefix . 'bsc_staff_crew';

$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'add';
$staff_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$staff   = null;
$is_edit = ( 'edit' === $subtab && $staff_id > 0 );

if ( $is_edit ) {
    $staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id ) );
}

$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );
wp_enqueue_media();
?>

<div class="bsc-panel" style="max-width: 750px; margin: 10px auto 60px auto; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
    <h3 class="bsc-panel-title" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        <?php echo $is_edit ? esc_html__( 'Edit Staff Profile', 'bcs-screen-manager' ) : esc_html__( 'Add Staff Member', 'bcs-screen-manager' ); ?>
    </h3>

    <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=bsc-users' ) ); ?>">
        <?php
        wp_nonce_field( 'bsc_user_form_action', 'bsc_user_nonce' );

        if ( $is_edit && $staff ) {
            echo '<input type="hidden" name="bsc_action" value="edit_user">';
            echo '<input type="hidden" name="staff_id" value="' . esc_attr( (string) $staff->id ) . '">';
        } else {
            echo '<input type="hidden" name="bsc_action" value="add_user">';
        }
        ?>

        <div class="bsc-form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 1rem;">
            <div class="bsc-form-group" style="margin-bottom: 0;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;"><?php esc_html_e( 'Full Name', 'bcs-screen-manager' ); ?> *</label>
                <input type="text" name="staff_name" required value="<?php echo esc_attr( $staff->name ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Tanvir Ahmed', 'bcs-screen-manager' ); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div class="bsc-form-group" style="margin-bottom: 0;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;"><?php esc_html_e( 'Job Title', 'bcs-screen-manager' ); ?> *</label>
                <select name="staff_role" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                    <?php
                    $roles = array(
                        __( 'Master Administrator', 'bcs-screen-manager' ),
                        __( 'Chief Projectionist', 'bcs-screen-manager' ),
                        __( 'Audio & Sound Engineer', 'bcs-screen-manager' ),
                        __( 'Box Office Supervisor', 'bcs-screen-manager' ),
                        __( 'Gate Inspector', 'bcs-screen-manager' ),
                        __( 'Concessions Lead', 'bcs-screen-manager' ),
                        __( 'Operations Steward', 'bcs-screen-manager' ),
                    );
                    $selected_role = $staff->role_title ?? 'Box Office Supervisor';
                    foreach ( $roles as $r ) {
                        echo '<option value="' . esc_attr( $r ) . '" ' . selected( $selected_role, $r, false ) . '>' . esc_html( $r ) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="bsc-form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 1rem;">
            <div class="bsc-form-group" style="margin-bottom: 0;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;"><?php esc_html_e( 'Status', 'bcs-screen-manager' ); ?> *</label>
                <select name="duty_status" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                    <option value="On Duty" <?php selected( $staff->duty_status ?? 'On Duty', 'On Duty' ); ?>><?php esc_html_e( 'On Duty', 'bcs-screen-manager' ); ?></option>
                    <option value="Off Duty" <?php selected( $staff->duty_status ?? '', 'Off Duty' ); ?>><?php esc_html_e( 'Off Duty', 'bcs-screen-manager' ); ?></option>
                    <option value="On Leave" <?php selected( $staff->duty_status ?? '', 'On Leave' ); ?>><?php esc_html_e( 'On Leave', 'bcs-screen-manager' ); ?></option>
                </select>
            </div>

            <div class="bsc-form-group" style="margin-bottom: 0;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;"><?php esc_html_e( 'Work Shift', 'bcs-screen-manager' ); ?> *</label>
                <select name="shift_schedule" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                    <option value="Day Shift (11:00 AM - 07:00 PM)" <?php selected( $staff->shift_schedule ?? '', 'Day Shift (11:00 AM - 07:00 PM)' ); ?>><?php esc_html_e( 'Day (11 AM - 7 PM)', 'bcs-screen-manager' ); ?></option>
                    <option value="Evening Shift (03:00 PM - 11:30 PM)" <?php selected( $staff->shift_schedule ?? '', 'Evening Shift (03:00 PM - 11:30 PM)' ); ?>><?php esc_html_e( 'Evening (3 PM - 11:30 PM)', 'bcs-screen-manager' ); ?></option>
                    <option value="Full Rotational" <?php selected( $staff->shift_schedule ?? 'Full Rotational', 'Full Rotational' ); ?>><?php esc_html_e( 'Rotational', 'bcs-screen-manager' ); ?></option>
                </select>
            </div>
        </div>

        <div class="bsc-form-grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 1rem;">
            <div class="bsc-form-group" style="margin-bottom: 0;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;"><?php esc_html_e( 'Phone', 'bcs-screen-manager' ); ?> *</label>
                <input type="text" name="staff_phone" required value="<?php echo esc_attr( $staff->phone ?? '+880 ' ); ?>" placeholder="+880 1700-000000" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div class="bsc-form-group" style="margin-bottom: 0;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;"><?php esc_html_e( 'Email', 'bcs-screen-manager' ); ?></label>
                <input type="email" name="staff_email" value="<?php echo esc_attr( $staff->email ?? '' ); ?>" placeholder="staff@example.com" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div class="bsc-form-group" style="margin-bottom: 0;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;">
                    <?php 
                    /* translators: %s: currency symbol */
                    printf( esc_html__( 'Monthly Salary (%s)', 'bcs-screen-manager' ), esc_html( $currency_symbol ) ); 
                    ?>
                </label>
                <input type="number" step="0.01" name="staff_salary" value="<?php echo esc_attr( (string) ( $staff->salary ?? '25000.00' ) ); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
        </div>

        <!-- Staff Headshot Photo Upload -->
        <div class="bsc-form-group" style="margin-bottom: 1rem;">
            <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;"><?php esc_html_e( 'Profile Photo', 'bcs-screen-manager' ); ?></label>
            <div class="bsc-media-uploader-box" style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 12px; border-radius: 8px; text-align: center;">
                <input type="hidden" name="photo_url" id="staff_photo_url" value="<?php echo esc_url( $staff->photo_url ?? '' ); ?>">
                <img id="staff_photo_preview" src="<?php echo esc_url( $staff->photo_url ?? '' ); ?>" alt="<?php esc_attr_e( 'Preview', 'bcs-screen-manager' ); ?>" class="bsc-media-preview-img" style="<?php echo ! empty( $staff->photo_url ) ? 'display: block;' : 'display: none;'; ?> max-height: 100px; margin: 0 auto 8px auto; border-radius: 6px; object-fit: cover;">
                <div>
                    <button type="button" class="button" id="btn_upload_staff_photo">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" style="vertical-align: -2px; margin-right: 4px;" aria-hidden="true"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        <?php esc_html_e( 'Upload Photo', 'bcs-screen-manager' ); ?>
                    </button>
                    <button type="button" class="button" id="btn_remove_staff_photo" style="<?php echo ! empty( $staff->photo_url ) ? 'display: inline-block;' : 'display: none;'; ?> color: #b91c1c; margin-left: 8px;"><?php esc_html_e( 'Remove', 'bcs-screen-manager' ); ?></button>
                </div>
            </div>
        </div>

        <div class="bsc-form-group" style="margin-bottom: 1.5rem;">
            <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 5px;"><?php esc_html_e( 'Notes / Responsibilities', 'bcs-screen-manager' ); ?></label>
            <textarea name="responsibilities" rows="3" placeholder="<?php esc_attr_e( 'Assigned responsibilities, duties...', 'bcs-screen-manager' ); ?>" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;"><?php echo esc_textarea( $staff->responsibilities ?? '' ); ?></textarea>
        </div>

        <button type="submit" class="button button-primary" style="background: #064e3b; border-color: #064e3b; width: 100%; padding: 11px; font-weight: 800; font-size: 0.95rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
            <span><?php echo $is_edit ? esc_html__( 'Update Staff', 'bcs-screen-manager' ) : esc_html__( 'Save Staff', 'bcs-screen-manager' ); ?></span>
        </button>
    </form>
</div>

<?php
$version = defined( 'BSC_VERSION' ) ? BSC_VERSION : '1.0.0';
wp_register_script( 'bsc-staff-builder-runtime', false, array(), $version, true );
wp_enqueue_script( 'bsc-staff-builder-runtime' );

$staff_i18n = array(
    'mediaTitle' => __( 'Select Staff Member Photo', 'bcs-screen-manager' ),
    'mediaBtn'   => __( 'Use This Photo', 'bcs-screen-manager' ),
);

$js_script = '
    (function() {
        const i18n = ' . wp_json_encode( $staff_i18n ) . ';
        document.addEventListener("DOMContentLoaded", function() {
            const btnUpload = document.getElementById("btn_upload_staff_photo");
            const inputUrl  = document.getElementById("staff_photo_url");
            const imgPrev   = document.getElementById("staff_photo_preview");
            const btnRemove = document.getElementById("btn_remove_staff_photo");

            if (btnUpload) {
                btnUpload.addEventListener("click", function(e) {
                    e.preventDefault();
                    if (typeof wp === "undefined" || !wp.media) {
                        return;
                    }
                    const frame = wp.media({
                        title: i18n.mediaTitle,
                        button: { text: i18n.mediaBtn },
                        multiple: false,
                        library: { type: "image" }
                    });
                    frame.on("select", function() {
                        const attachment = frame.state().get("selection").first().toJSON();
                        if (inputUrl) inputUrl.value = attachment.url;
                        if (imgPrev) {
                            imgPrev.src = attachment.url;
                            imgPrev.style.display = "block";
                        }
                        if (btnRemove) btnRemove.style.display = "inline-block";
                    });
                    frame.open();
                });
            }

            if (btnRemove) {
                btnRemove.addEventListener("click", function() {
                    if (inputUrl) inputUrl.value = "";
                    if (imgPrev) {
                        imgPrev.src = "";
                        imgPrev.style.display = "none";
                    }
                    btnRemove.style.display = "none";
                });
            }
        });
    })();
';

wp_add_inline_script( 'bsc-staff-builder-runtime', $js_script );