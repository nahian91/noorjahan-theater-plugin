<?php
/**
 * Customer Module - Add & Edit Customer Views & Submission Handlers
 *
 * Path: admin/customers/add-edit-customer.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------
// 1. PROCESS FORM SUBMISSIONS (Triggered during early action handling)
// -------------------------------------------------------------
if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['nmbp_action'] ) ) {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
	}

	check_admin_referer( 'nmbp_customer_form_action', '_nmbp_nonce' );

	global $wpdb;
	$table_customers = $wpdb->prefix . 'nmbp_customers';

	$action = sanitize_key( wp_unslash( $_POST['nmbp_action'] ) );
	$name   = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
	$phone  = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
	$email  = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';

	// 1. Add Customer.
	if ( 'add_customer' === $action ) {
		$inserted = $wpdb->insert(
			$table_customers,
			array(
				'name'       => $name,
				'phone'      => $phone,
				'email'      => $email,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		if ( false !== $inserted ) {
			wp_safe_redirect( admin_url( 'admin.php?page=nmbp-customers&subtab=all&customer_added=true' ) );
			exit;
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=nmbp-customers&subtab=add&customer_error=db_failed' ) );
			exit;
		}
	}

	// 2. Edit Customer.
	if ( 'edit_customer' === $action ) {
		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;

		if ( $customer_id > 0 ) {
			$updated = $wpdb->update(
				$table_customers,
				array(
					'name'  => $name,
					'phone' => $phone,
					'email' => $email,
				),
				array( 'id' => $customer_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				wp_safe_redirect( admin_url( 'admin.php?page=nmbp-customers&subtab=all&customer_updated=true' ) );
				exit;
			} else {
				wp_safe_redirect( admin_url( 'admin.php?page=nmbp-customers&subtab=edit&id=' . $customer_id . '&customer_error=db_failed' ) );
				exit;
			}
		}
	}
}

// -------------------------------------------------------------
// 2. UI VIEW RENDERING (Executes only during render pass)
// -------------------------------------------------------------
$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
	wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
}

global $wpdb;
$table_customers = $wpdb->prefix . 'nmbp_customers';

$subtab      = isset( $_GET['subtab'] ) ? sanitize_key( wp_unslash( $_GET['subtab'] ) ) : 'add';
$customer_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

$customer = null;
$is_edit  = ( 'edit' === $subtab && $customer_id > 0 );

if ( $is_edit ) {
	$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $customer_id ) );
}
?>
<style id="nmbp-add-edit-customer-css">
	.nmbp-customer-form-panel {
		max-width: 680px;
		margin: 10px auto;
		box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
	}
	.nmbp-customer-form-title {
		margin-bottom: 1.5rem;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.nmbp-customer-field-group {
		margin-bottom: 1.25rem;
	}
	.nmbp-customer-field-group--compact {
		margin-bottom: 0;
	}
	.nmbp-customer-grid-2 {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 14px;
		margin-bottom: 1.5rem;
	}
	.nmbp-customer-label {
		display: block;
		font-size: 0.8rem;
		font-weight: 700;
		color: #334155;
		margin-bottom: 5px;
	}
	.nmbp-customer-input {
		width: 100%;
		padding: 8px 12px;
		border: 1px solid #cbd5e1;
		border-radius: 6px;
	}
	.nmbp-customer-btn-submit {
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
		box-shadow: 0 2px 6px rgba(6, 78, 59, 0.2);
	}
</style>

<div class="nmbp-panel nmbp-customer-form-panel">
	<h3 class="nmbp-panel-title nmbp-customer-form-title">
		<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
		<?php echo $is_edit ? esc_html__( 'Edit Customer Profile', 'nahian-movie-booking-plugin' ) : esc_html__( 'Register New Club Member', 'nahian-movie-booking-plugin' ); ?>
	</h3>

	<form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-customers' ) ); ?>">
		<?php
		wp_nonce_field( 'nmbp_customer_form_action', '_nmbp_nonce' );

		if ( $is_edit && $customer ) {
			echo '<input type="hidden" name="nmbp_action" value="edit_customer">';
			echo '<input type="hidden" name="customer_id" value="' . esc_attr( (string) $customer->id ) . '">';
		} else {
			echo '<input type="hidden" name="nmbp_action" value="add_customer">';
		}
		?>

		<div class="nmbp-form-group nmbp-customer-field-group">
			<label class="nmbp-customer-label">
				<?php esc_html_e( 'Customer Full Name', 'nahian-movie-booking-plugin' ); ?> *
			</label>
			<input type="text" name="customer_name" required value="<?php echo esc_attr( $customer->name ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Mahfuzur Rahman', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-customer-input">
		</div>

		<div class="nmbp-form-grid-2 nmbp-customer-grid-2">
			<div class="nmbp-form-group nmbp-customer-field-group--compact">
				<label class="nmbp-customer-label">
					<?php esc_html_e( 'Phone Number', 'nahian-movie-booking-plugin' ); ?> *
				</label>
				<input type="text" name="customer_phone" required value="<?php echo esc_attr( $customer->phone ?? '+880 ' ); ?>" placeholder="+880 1700-000000" class="nmbp-customer-input">
			</div>
			<div class="nmbp-form-group nmbp-customer-field-group--compact">
				<label class="nmbp-customer-label">
					<?php esc_html_e( 'Email Address', 'nahian-movie-booking-plugin' ); ?>
				</label>
				<input type="email" name="customer_email" value="<?php echo esc_attr( $customer->email ?? '' ); ?>" placeholder="customer@example.com" class="nmbp-customer-input">
			</div>
		</div>

		<button type="submit" class="button button-primary nmbp-customer-btn-submit">
			<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
			<span><?php echo $is_edit ? esc_html__( 'Update Customer Profile', 'nahian-movie-booking-plugin' ) : esc_html__( 'Save Member', 'nahian-movie-booking-plugin' ); ?></span>
		</button>
	</form>
</div>