<?php
/**
 * Users / Staff Module - Add & Edit Staff Views & Submission Handlers
 *
 * Path: admin/users/add-edit-user.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------
// 1. EARLY SUBMISSION HANDLER
// -------------------------------------------------------------
if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['nmbp_action'] ) ) {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
	}

	global $wpdb;
	$table_staff = $wpdb->prefix . 'nmbp_staff_crew';

	$action       = sanitize_key( wp_unslash( $_POST['nmbp_action'] ) );
	$name         = isset( $_POST['staff_name'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_name'] ) ) : '';
	$role_title   = isset( $_POST['staff_role'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_role'] ) ) : '';
	$duty_status  = isset( $_POST['duty_status'] ) ? sanitize_text_field( wp_unslash( $_POST['duty_status'] ) ) : 'On Duty';
	$shift        = isset( $_POST['shift_schedule'] ) ? sanitize_text_field( wp_unslash( $_POST['shift_schedule'] ) ) : '';
	$phone        = isset( $_POST['staff_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['staff_phone'] ) ) : '';
	$email        = isset( $_POST['staff_email'] ) ? sanitize_email( wp_unslash( $_POST['staff_email'] ) ) : '';
	$salary       = isset( $_POST['staff_salary'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['staff_salary'] ) ) : 0.00;
	$photo_url    = isset( $_POST['photo_url'] ) ? esc_url_raw( wp_unslash( $_POST['photo_url'] ) ) : '';
	$responsibles = isset( $_POST['responsibilities'] ) ? sanitize_textarea_field( wp_unslash( $_POST['responsibilities'] ) ) : '';

	// Handle Add.
	if ( 'add_user' === $action || 'add_staff' === $action ) {
		check_admin_referer( 'nmbp_user_form_action', 'nmbp_user_nonce' );

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
			wp_safe_redirect( admin_url( 'admin.php?page=nmbp-users&subtab=all&staff_added=true' ) );
			exit;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=nmbp-users&subtab=add&staff_error=db_failed' ) );
		exit;
	}

	// Handle Edit.
	if ( 'edit_user' === $action || 'edit_staff' === $action ) {
		check_admin_referer( 'nmbp_user_form_action', 'nmbp_user_nonce' );
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
				wp_safe_redirect( admin_url( 'admin.php?page=nmbp-users&subtab=all&staff_updated=true' ) );
				exit;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=nmbp-users&subtab=edit&id=' . $staff_id . '&staff_error=db_failed' ) );
			exit;
		}
	}
}

// -------------------------------------------------------------
// 2. UI VIEW RENDERING (Only runs during page render callback)
// -------------------------------------------------------------
global $wpdb;
$table_staff = $wpdb->prefix . 'nmbp_staff_crew';

$subtab   = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'add';
$staff_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$staff   = null;
$is_edit = ( 'edit' === $subtab && $staff_id > 0 );

if ( $is_edit ) {
	$staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id ) );
}

$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );
wp_enqueue_media();
?>

<style id="nmbp-add-edit-user-css">
	.nmbp-staff-form-panel {
		max-width: 750px;
		margin: 10px auto 60px auto;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
	}
	.nmbp-staff-form-title {
		margin-bottom: 1.5rem;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.nmbp-staff-grid-2 {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 14px;
		margin-bottom: 1rem;
	}
	.nmbp-staff-grid-3 {
		display: grid;
		grid-template-columns: 1fr 1fr 1fr;
		gap: 14px;
		margin-bottom: 1rem;
	}
	.nmbp-staff-field-group {
		margin-bottom: 0;
	}
	.nmbp-staff-field-group--photo {
		margin-bottom: 1rem;
	}
	.nmbp-staff-field-group--notes {
		margin-bottom: 1.5rem;
	}
	.nmbp-staff-label {
		display: block;
		font-size: 0.8rem;
		font-weight: 700;
		color: #334155;
		margin-bottom: 5px;
	}
	.nmbp-staff-input,
	.nmbp-staff-select,
	.nmbp-staff-textarea {
		width: 100%;
		padding: 8px 12px;
		border: 1px solid #cbd5e1;
		border-radius: 6px;
		background: #ffffff;
	}
	.nmbp-staff-uploader-box {
		background: #f8fafc;
		border: 1px dashed #cbd5e1;
		padding: 12px;
		border-radius: 8px;
		text-align: center;
	}
	.nmbp-staff-preview-img {
		max-height: 100px;
		margin: 0 auto 8px auto;
		border-radius: 6px;
		object-fit: cover;
	}
	.nmbp-staff-preview-img--hidden {
		display: none;
	}
	.nmbp-staff-preview-img--visible {
		display: block;
	}
	.nmbp-staff-btn-icon {
		vertical-align: -2px;
		margin-right: 4px;
	}
	.nmbp-staff-btn-remove {
		color: #b91c1c;
		margin-left: 8px;
	}
	.nmbp-staff-btn-remove--hidden {
		display: none;
	}
	.nmbp-staff-btn-remove--visible {
		display: inline-block;
	}
	.nmbp-staff-btn-submit {
		background: #064e3b;
		border-color: #064e3b;
		width: 100%;
		padding: 11px;
		font-weight: 800;
		font-size: 0.95rem;
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
	}
</style>

<div class="nmbp-panel nmbp-staff-form-panel">
	<h3 class="nmbp-panel-title nmbp-staff-form-title">
		<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
		<?php echo $is_edit ? esc_html__( 'Edit Staff Profile', 'nahian-movie-booking-plugin' ) : esc_html__( 'Add Staff Member', 'nahian-movie-booking-plugin' ); ?>
	</h3>

	<form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-users' ) ); ?>">
		<?php
		wp_nonce_field( 'nmbp_user_form_action', 'nmbp_user_nonce' );

		if ( $is_edit && $staff ) {
			echo '<input type="hidden" name="nmbp_action" value="edit_user">';
			echo '<input type="hidden" name="staff_id" value="' . esc_attr( (string) $staff->id ) . '">';
		} else {
			echo '<input type="hidden" name="nmbp_action" value="add_user">';
		}
		?>

		<div class="nmbp-form-grid-2 nmbp-staff-grid-2">
			<div class="nmbp-form-group nmbp-staff-field-group">
				<label class="nmbp-staff-label"><?php esc_html_e( 'Full Name', 'nahian-movie-booking-plugin' ); ?> *</label>
				<input type="text" name="staff_name" required value="<?php echo esc_attr( $staff->name ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Tanvir Ahmed', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-staff-input">
			</div>

			<div class="nmbp-form-group nmbp-staff-field-group">
				<label class="nmbp-staff-label"><?php esc_html_e( 'Job Title', 'nahian-movie-booking-plugin' ); ?> *</label>
				<select name="staff_role" required class="nmbp-staff-select">
					<?php
					$roles = array(
						__( 'Master Administrator', 'nahian-movie-booking-plugin' ),
						__( 'Chief Projectionist', 'nahian-movie-booking-plugin' ),
						__( 'Audio & Sound Engineer', 'nahian-movie-booking-plugin' ),
						__( 'Box Office Supervisor', 'nahian-movie-booking-plugin' ),
						__( 'Gate Inspector', 'nahian-movie-booking-plugin' ),
						__( 'Concessions Lead', 'nahian-movie-booking-plugin' ),
						__( 'Operations Steward', 'nahian-movie-booking-plugin' ),
					);
					$selected_role = $staff->role_title ?? 'Box Office Supervisor';
					foreach ( $roles as $r ) {
						echo '<option value="' . esc_attr( $r ) . '" ' . selected( $selected_role, $r, false ) . '>' . esc_html( $r ) . '</option>';
					}
					?>
				</select>
			</div>
		</div>

		<div class="nmbp-form-grid-2 nmbp-staff-grid-2">
			<div class="nmbp-form-group nmbp-staff-field-group">
				<label class="nmbp-staff-label"><?php esc_html_e( 'Status', 'nahian-movie-booking-plugin' ); ?> *</label>
				<select name="duty_status" required class="nmbp-staff-select">
					<option value="On Duty" <?php selected( $staff->duty_status ?? 'On Duty', 'On Duty' ); ?>><?php esc_html_e( 'On Duty', 'nahian-movie-booking-plugin' ); ?></option>
					<option value="Off Duty" <?php selected( $staff->duty_status ?? '', 'Off Duty' ); ?>><?php esc_html_e( 'Off Duty', 'nahian-movie-booking-plugin' ); ?></option>
					<option value="On Leave" <?php selected( $staff->duty_status ?? '', 'On Leave' ); ?>><?php esc_html_e( 'On Leave', 'nahian-movie-booking-plugin' ); ?></option>
				</select>
			</div>

			<div class="nmbp-form-group nmbp-staff-field-group">
				<label class="nmbp-staff-label"><?php esc_html_e( 'Work Shift', 'nahian-movie-booking-plugin' ); ?> *</label>
				<select name="shift_schedule" required class="nmbp-staff-select">
					<option value="Day Shift (11:00 AM - 07:00 PM)" <?php selected( $staff->shift_schedule ?? '', 'Day Shift (11:00 AM - 07:00 PM)' ); ?>><?php esc_html_e( 'Day (11 AM - 7 PM)', 'nahian-movie-booking-plugin' ); ?></option>
					<option value="Evening Shift (03:00 PM - 11:30 PM)" <?php selected( $staff->shift_schedule ?? '', 'Evening Shift (03:00 PM - 11:30 PM)' ); ?>><?php esc_html_e( 'Evening (3 PM - 11:30 PM)', 'nahian-movie-booking-plugin' ); ?></option>
					<option value="Full Rotational" <?php selected( $staff->shift_schedule ?? 'Full Rotational', 'Full Rotational' ); ?>><?php esc_html_e( 'Rotational', 'nahian-movie-booking-plugin' ); ?></option>
				</select>
			</div>
		</div>

		<div class="nmbp-form-grid-3 nmbp-staff-grid-3">
			<div class="nmbp-form-group nmbp-staff-field-group">
				<label class="nmbp-staff-label"><?php esc_html_e( 'Phone', 'nahian-movie-booking-plugin' ); ?> *</label>
				<input type="text" name="staff_phone" required value="<?php echo esc_attr( $staff->phone ?? '+880 ' ); ?>" placeholder="+880 1700-000000" class="nmbp-staff-input">
			</div>

			<div class="nmbp-form-group nmbp-staff-field-group">
				<label class="nmbp-staff-label"><?php esc_html_e( 'Email', 'nahian-movie-booking-plugin' ); ?></label>
				<input type="email" name="staff_email" value="<?php echo esc_attr( $staff->email ?? '' ); ?>" placeholder="staff@example.com" class="nmbp-staff-input">
			</div>

			<div class="nmbp-form-group nmbp-staff-field-group">
				<label class="nmbp-staff-label">
					<?php
					/* translators: %s: currency symbol */
					printf( esc_html__( 'Monthly Salary (%s)', 'nahian-movie-booking-plugin' ), esc_html( $currency_symbol ) );
					?>
				</label>
				<input type="number" step="0.01" name="staff_salary" value="<?php echo esc_attr( (string) ( $staff->salary ?? '25000.00' ) ); ?>" class="nmbp-staff-input">
			</div>
		</div>

		<!-- Staff Headshot Photo Upload -->
		<div class="nmbp-form-group nmbp-staff-field-group--photo">
			<label class="nmbp-staff-label"><?php esc_html_e( 'Profile Photo', 'nahian-movie-booking-plugin' ); ?></label>
			<div class="nmbp-media-uploader-box nmbp-staff-uploader-box">
				<input type="hidden" name="photo_url" id="staff_photo_url" value="<?php echo esc_url( $staff->photo_url ?? '' ); ?>">
				<img id="staff_photo_preview" src="<?php echo esc_url( $staff->photo_url ?? '' ); ?>" alt="<?php esc_attr_e( 'Preview', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-media-preview-img nmbp-staff-preview-img <?php echo ! empty( $staff->photo_url ) ? 'nmbp-staff-preview-img--visible' : 'nmbp-staff-preview-img--hidden'; ?>">
				<div>
					<button type="button" class="button" id="btn_upload_staff_photo">
						<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true" class="nmbp-staff-btn-icon"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
						<?php esc_html_e( 'Upload Photo', 'nahian-movie-booking-plugin' ); ?>
					</button>
					<button type="button" class="button nmbp-staff-btn-remove <?php echo ! empty( $staff->photo_url ) ? 'nmbp-staff-btn-remove--visible' : 'nmbp-staff-btn-remove--hidden'; ?>" id="btn_remove_staff_photo"><?php esc_html_e( 'Remove', 'nahian-movie-booking-plugin' ); ?></button>
				</div>
			</div>
		</div>

		<div class="nmbp-form-group nmbp-staff-field-group--notes">
			<label class="nmbp-staff-label"><?php esc_html_e( 'Notes / Responsibilities', 'nahian-movie-booking-plugin' ); ?></label>
			<textarea name="responsibilities" rows="3" placeholder="<?php esc_attr_e( 'Assigned responsibilities, duties...', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-staff-textarea"><?php echo esc_textarea( $staff->responsibilities ?? '' ); ?></textarea>
		</div>

		<button type="submit" class="button button-primary nmbp-staff-btn-submit">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
			<span><?php echo $is_edit ? esc_html__( 'Update Staff', 'nahian-movie-booking-plugin' ) : esc_html__( 'Save Staff', 'nahian-movie-booking-plugin' ); ?></span>
		</button>
	</form>
</div>

<script id="nmbp-staff-builder-runtime-js">
	(function() {
		const i18n = {
			mediaTitle: <?php echo wp_json_encode( __( 'Select Staff Member Photo', 'nahian-movie-booking-plugin' ) ); ?>,
			mediaBtn: <?php echo wp_json_encode( __( 'Use This Photo', 'nahian-movie-booking-plugin' ) ); ?>
		};

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
							imgPrev.classList.remove("nmbp-staff-preview-img--hidden");
							imgPrev.classList.add("nmbp-staff-preview-img--visible");
						}
						if (btnRemove) {
							btnRemove.classList.remove("nmbp-staff-btn-remove--hidden");
							btnRemove.classList.add("nmbp-staff-btn-remove--visible");
						}
					});
					frame.open();
				});
			}

			if (btnRemove) {
				btnRemove.addEventListener("click", function() {
					if (inputUrl) inputUrl.value = "";
					if (imgPrev) {
						imgPrev.src = "";
						imgPrev.classList.remove("nmbp-staff-preview-img--visible");
						imgPrev.classList.add("nmbp-staff-preview-img--hidden");
					}
					btnRemove.classList.remove("nmbp-staff-btn-remove--visible");
					btnRemove.classList.add("nmbp-staff-btn-remove--hidden");
				});
			}
		});
	})();
</script>