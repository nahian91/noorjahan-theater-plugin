<?php
/**
 * Settings Module - Master Controller & Save Handler
 *
 * Path: admin/page-settings.php
 *
 * @package NahianMovieBookingPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// 1. RENDER SETTINGS PAGE UI
// =============================================================================
if ( ! function_exists( 'nmbp_render_settings_page' ) ) {
	function nmbp_render_settings_page() {
		$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
		if ( ! current_user_can( $admin_cap ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
		}

		$currency_symbol = sanitize_text_field( get_option( 'nmbp_currency_symbol', '৳' ) );

		// Cinema Identity & Contact Options.
		$site_name       = sanitize_text_field( get_option( 'nmbp_site_name', get_bloginfo( 'name' ) ) );
		$site_tagline    = sanitize_text_field( get_option( 'nmbp_site_tagline', get_bloginfo( 'description' ) ) );
		$site_logo       = esc_url_raw( get_option( 'nmbp_site_logo', '' ) );
		$contact_email   = sanitize_email( get_option( 'nmbp_contact_email', get_option( 'admin_email' ) ) );
		$contact_phone   = sanitize_text_field( get_option( 'nmbp_contact_phone', '' ) );
		$contact_address = sanitize_textarea_field( get_option( 'nmbp_contact_address', '' ) );

		// Fetch direct prices and descriptions with fallbacks.
		$standard_price = (float) get_option( 'nmbp_standard_price', 400.00 );
		$standard_desc  = sanitize_text_field( get_option( 'nmbp_standard_desc', __( 'Standard auditorium seats.', 'nahian-movie-booking-plugin' ) ) );

		$service_charge = (float) get_option( 'nmbp_service_charge_pct', 5.00 );
		$enable_tax     = ( 'yes' === get_option( 'nmbp_enable_tax', 'yes' ) );
		$seat_hold_time = absint( get_option( 'nmbp_seat_hold_timeout', 10 ) );

		$currency_presets = array( '৳', '$', '€', '£', '₹' );

		wp_enqueue_media();

		if ( function_exists( 'nmbp_render_page_shell_open' ) ) {
			nmbp_render_page_shell_open( 'nmbp-settings' );
		}
		?>
		<style id="nmbp-settings-page-css">
			.nmbp-settings-card--spaced {
				margin-bottom: 20px;
			}
			.nmbp-form-group--compact {
				margin-bottom: 0;
			}
			.nmbp-form-group--currency {
				margin-bottom: 18px;
			}
			.nmbp-logo-dropzone {
				background: #f8fafc;
				border: 1px dashed #cbd5e1;
				padding: 12px;
				border-radius: 8px;
				text-align: center;
			}
			.nmbp-logo-preview {
				max-height: 80px;
				margin: 0 auto 10px auto;
				border-radius: 6px;
				object-fit: contain;
			}
			.nmbp-logo-preview--hidden {
				display: none;
			}
			.nmbp-logo-preview--visible {
				display: block;
			}
			.nmbp-btn-icon-inline {
				vertical-align: -2px;
				margin-right: 4px;
			}
			.nmbp-btn-remove-logo {
				color: #b91c1c;
				margin-left: 8px;
			}
			.nmbp-btn-remove-logo--hidden {
				display: none;
			}
			.nmbp-btn-remove-logo--visible {
				display: inline-block;
			}
			.nmbp-currency-row {
				display: flex;
				align-items: center;
				gap: 8px;
				flex-wrap: wrap;
			}
			.nmbp-currency-input {
				width: 110px;
				font-weight: 800;
				text-align: center;
			}
			.nmbp-currency-preset-btn {
				font-weight: 700;
			}
			.nmbp-settings-submit-wrap {
				text-align: right;
			}
		</style>

		<div class="nmbp-settings-container">
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="nmbp-settings-alert-success">
					<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
					<span><?php esc_html_e( 'Cinema details, currency, ticket prices, and configuration saved successfully!', 'nahian-movie-booking-plugin' ); ?></span>
				</div>
			<?php endif; ?>

			<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'nmbp_save_settings_verify', 'nmbp_settings_nonce' ); ?>
				<input type="hidden" name="action" value="nmbp_save_settings">
				<input type="hidden" name="active_subtab" value="pricing">

				<!-- Cinema Identity & Contact Card -->
				<div class="nmbp-settings-card nmbp-settings-card--spaced">
					<div class="nmbp-settings-header-box">
						<h3 class="nmbp-settings-card-title">
							<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d9488" aria-hidden="true"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
							<span><?php esc_html_e( 'Cinema Identity & Contact Details', 'nahian-movie-booking-plugin' ); ?></span>
						</h3>
						<span class="nmbp-settings-card-desc">
							<?php esc_html_e( 'Configure your multiplex brand name, tagline, logo, and box-office contact details.', 'nahian-movie-booking-plugin' ); ?>
						</span>
					</div>

					<div class="nmbp-settings-grid-duo">
						<div class="nmbp-form-group">
							<label><?php esc_html_e( 'Site / Cinema Name', 'nahian-movie-booking-plugin' ); ?></label>
							<input type="text" name="nmbp_site_name" value="<?php echo esc_attr( $site_name ); ?>" placeholder="<?php esc_attr_e( 'e.g. Nahian Multiplex Cinemas', 'nahian-movie-booking-plugin' ); ?>">
						</div>

						<div class="nmbp-form-group">
							<label><?php esc_html_e( 'Tagline', 'nahian-movie-booking-plugin' ); ?></label>
							<input type="text" name="nmbp_site_tagline" value="<?php echo esc_attr( $site_tagline ); ?>" placeholder="<?php esc_attr_e( 'e.g. Experience Cinema Beyond Limits', 'nahian-movie-booking-plugin' ); ?>">
						</div>
					</div>

					<div class="nmbp-settings-grid-duo">
						<div class="nmbp-form-group">
							<label><?php esc_html_e( 'Contact Email', 'nahian-movie-booking-plugin' ); ?></label>
							<input type="email" name="nmbp_contact_email" value="<?php echo esc_attr( $contact_email ); ?>" placeholder="<?php esc_attr_e( 'e.g. bookings@example.com', 'nahian-movie-booking-plugin' ); ?>">
						</div>

						<div class="nmbp-form-group">
							<label><?php esc_html_e( 'Contact Phone', 'nahian-movie-booking-plugin' ); ?></label>
							<input type="text" name="nmbp_contact_phone" value="<?php echo esc_attr( $contact_phone ); ?>" placeholder="<?php esc_attr_e( 'e.g. +880 1700-000000', 'nahian-movie-booking-plugin' ); ?>">
						</div>
					</div>

					<div class="nmbp-form-group">
						<label><?php esc_html_e( 'Address', 'nahian-movie-booking-plugin' ); ?></label>
						<textarea name="nmbp_contact_address" rows="2" placeholder="<?php esc_attr_e( 'Street address, city, postal code...', 'nahian-movie-booking-plugin' ); ?>"><?php echo esc_textarea( $contact_address ); ?></textarea>
					</div>

					<div class="nmbp-form-group nmbp-form-group--compact">
						<label><?php esc_html_e( 'Cinema Logo', 'nahian-movie-booking-plugin' ); ?></label>
						<div class="nmbp-logo-dropzone">
							<input type="hidden" name="nmbp_site_logo" id="nmbp_site_logo_url" value="<?php echo esc_url( $site_logo ); ?>">
							<img id="nmbp_site_logo_preview" src="<?php echo esc_url( $site_logo ); ?>" alt="<?php esc_attr_e( 'Cinema Logo Preview', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-logo-preview <?php echo ! empty( $site_logo ) ? 'nmbp-logo-preview--visible' : 'nmbp-logo-preview--hidden'; ?>">
							<div>
								<button type="button" class="button" id="btn_upload_nmbp_logo">
									<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" class="nmbp-btn-icon-inline" aria-hidden="true"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
									<?php esc_html_e( 'Upload Logo', 'nahian-movie-booking-plugin' ); ?>
								</button>
								<button type="button" class="button nmbp-btn-remove-logo <?php echo ! empty( $site_logo ) ? 'nmbp-btn-remove-logo--visible' : 'nmbp-btn-remove-logo--hidden'; ?>" id="btn_remove_nmbp_logo">
									<?php esc_html_e( 'Remove Logo', 'nahian-movie-booking-plugin' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Pricing Card -->
				<div class="nmbp-settings-card">
					<div class="nmbp-settings-header-box">
						<h3 class="nmbp-settings-card-title">
							<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d9488" aria-hidden="true"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
							<span><?php esc_html_e( 'Currency, Seat Pricing & Descriptions', 'nahian-movie-booking-plugin' ); ?></span>
						</h3>
						<span class="nmbp-settings-card-desc">
							<?php esc_html_e( 'Set the currency symbol, base pricing, and ticket description for standard auditorium seating (Rows A–F).', 'nahian-movie-booking-plugin' ); ?>
						</span>
					</div>

					<!-- Currency Selection Block -->
					<div class="nmbp-form-group nmbp-form-group--currency">
						<label><?php esc_html_e( 'Currency Symbol', 'nahian-movie-booking-plugin' ); ?></label>
						<div class="nmbp-currency-row">
							<input type="text" name="nmbp_currency_symbol" id="nmbp_currency_symbol_input" value="<?php echo esc_attr( $currency_symbol ); ?>" maxlength="8" required class="nmbp-currency-input">
							<?php foreach ( $currency_presets as $preset ) : ?>
								<button type="button" class="button button-small nmbp-currency-preset-btn" data-symbol="<?php echo esc_attr( $preset ); ?>">
									<?php echo esc_html( $preset ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Standard Seating Block -->
					<div class="nmbp-settings-tier-block">
						<h4 class="nmbp-settings-tier-title standard"><?php esc_html_e( 'Standard Seating (Rows A–F)', 'nahian-movie-booking-plugin' ); ?></h4>
						<div class="nmbp-settings-grid-duo">
							<div class="nmbp-form-group">
								<label>
									<?php
									/* translators: %s: Currency symbol */
									printf( esc_html__( 'Price (%s)', 'nahian-movie-booking-plugin' ), esc_html( $currency_symbol ) );
									?>
								</label>
								<input type="number" step="1" min="0" name="nmbp_standard_price" value="<?php echo esc_attr( (string) $standard_price ); ?>" required>
							</div>
							<div class="nmbp-form-group">
								<label><?php esc_html_e( 'Description / Inclusions', 'nahian-movie-booking-plugin' ); ?></label>
								<input type="text" name="nmbp_standard_desc" value="<?php echo esc_attr( $standard_desc ); ?>" placeholder="<?php esc_attr_e( 'e.g. Standard auditorium seats.', 'nahian-movie-booking-plugin' ); ?>">
							</div>
						</div>
					</div>

					<hr class="nmbp-divider">

					<div class="nmbp-switch-box">
						<input type="checkbox" name="enable_tax" id="chk_tax" value="yes" <?php checked( $enable_tax, true ); ?>>
						<label for="chk_tax"><?php esc_html_e( 'Apply VAT / Online Service Surcharge to checkout balances', 'nahian-movie-booking-plugin' ); ?></label>
					</div>

					<div class="nmbp-settings-grid-equal">
						<div class="nmbp-form-group">
							<label><?php esc_html_e( 'VAT / Service Surcharge (%)', 'nahian-movie-booking-plugin' ); ?></label>
							<input type="number" step="0.1" min="0" max="100" name="service_charge_pct" value="<?php echo esc_attr( (string) $service_charge ); ?>">
						</div>

						<div class="nmbp-form-group">
							<label><?php esc_html_e( 'Seat Hold Expiration (Minutes)', 'nahian-movie-booking-plugin' ); ?></label>
							<input type="number" min="1" max="60" name="seat_hold_timeout" value="<?php echo esc_attr( (string) $seat_hold_time ); ?>">
						</div>
					</div>
				</div>

				<div class="nmbp-settings-submit-wrap">
					<button type="submit" class="button button-primary nmbp-btn-save-settings">
						💾 <?php esc_html_e( 'Save Configuration', 'nahian-movie-booking-plugin' ); ?>
					</button>
				</div>
			</form>
		</div>

		<script id="nmbp-settings-runtime-js">
			(function() {
				const i18n = {
					mediaTitle: <?php echo wp_json_encode( __( 'Select Cinema Logo', 'nahian-movie-booking-plugin' ) ); ?>,
					mediaBtn: <?php echo wp_json_encode( __( 'Use This Logo', 'nahian-movie-booking-plugin' ) ); ?>
				};

				document.addEventListener("DOMContentLoaded", function() {
					const btnUpload = document.getElementById("btn_upload_nmbp_logo");
					const inputUrl  = document.getElementById("nmbp_site_logo_url");
					const imgPrev   = document.getElementById("nmbp_site_logo_preview");
					const btnRemove = document.getElementById("btn_remove_nmbp_logo");

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
									imgPrev.classList.remove("nmbp-logo-preview--hidden");
									imgPrev.classList.add("nmbp-logo-preview--visible");
								}
								if (btnRemove) {
									btnRemove.classList.remove("nmbp-btn-remove-logo--hidden");
									btnRemove.classList.add("nmbp-btn-remove-logo--visible");
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
								imgPrev.classList.remove("nmbp-logo-preview--visible");
								imgPrev.classList.add("nmbp-logo-preview--hidden");
							}
							btnRemove.classList.remove("nmbp-btn-remove-logo--visible");
							btnRemove.classList.add("nmbp-btn-remove-logo--hidden");
						});
					}

					const currencyInput = document.getElementById("nmbp_currency_symbol_input");
					document.querySelectorAll(".nmbp-currency-preset-btn").forEach(function(btn) {
						btn.addEventListener("click", function() {
							const sym = btn.getAttribute("data-symbol") || "";
							if (currencyInput && sym) {
								currencyInput.value = sym;
								currencyInput.focus();
							}
						});
					});
				});
			})();
		</script>
		<?php
		if ( function_exists( 'nmbp_render_page_shell_close' ) ) {
			nmbp_render_page_shell_close();
		}
	}
}

// =============================================================================
// 2. PROCESS & SAVE FORM SUBMISSION HANDLER
// =============================================================================
add_action( 'admin_post_nmbp_save_settings', 'nmbp_handle_save_settings_submission' );

function nmbp_handle_save_settings_submission() {
	$admin_cap = defined( 'NMBP_ADMIN_CAP' ) ? NMBP_ADMIN_CAP : 'manage_options';
	if ( ! current_user_can( $admin_cap ) ) {
		wp_die( esc_html__( 'Unauthorized access.', 'nahian-movie-booking-plugin' ), 403 );
	}

	check_admin_referer( 'nmbp_save_settings_verify', 'nmbp_settings_nonce' );

	// Cinema Identity & Contact Details.
	if ( isset( $_POST['nmbp_site_name'] ) ) {
		update_option( 'nmbp_site_name', sanitize_text_field( wp_unslash( $_POST['nmbp_site_name'] ) ) );
	}

	if ( isset( $_POST['nmbp_site_tagline'] ) ) {
		update_option( 'nmbp_site_tagline', sanitize_text_field( wp_unslash( $_POST['nmbp_site_tagline'] ) ) );
	}

	if ( isset( $_POST['nmbp_site_logo'] ) ) {
		update_option( 'nmbp_site_logo', esc_url_raw( wp_unslash( $_POST['nmbp_site_logo'] ) ) );
	}

	if ( isset( $_POST['nmbp_contact_email'] ) ) {
		update_option( 'nmbp_contact_email', sanitize_email( wp_unslash( $_POST['nmbp_contact_email'] ) ) );
	}

	if ( isset( $_POST['nmbp_contact_phone'] ) ) {
		update_option( 'nmbp_contact_phone', sanitize_text_field( wp_unslash( $_POST['nmbp_contact_phone'] ) ) );
	}

	if ( isset( $_POST['nmbp_contact_address'] ) ) {
		update_option( 'nmbp_contact_address', sanitize_textarea_field( wp_unslash( $_POST['nmbp_contact_address'] ) ) );
	}

	// Currency Symbol.
	if ( isset( $_POST['nmbp_currency_symbol'] ) ) {
		$symbol = sanitize_text_field( wp_unslash( $_POST['nmbp_currency_symbol'] ) );
		update_option( 'nmbp_currency_symbol', '' !== $symbol ? $symbol : '৳' );
	}

	// Standard Tier Data (non-negative).
	if ( isset( $_POST['nmbp_standard_price'] ) ) {
		$price = (float) sanitize_text_field( wp_unslash( $_POST['nmbp_standard_price'] ) );
		update_option( 'nmbp_standard_price', max( 0.0, $price ) );
	}

	if ( isset( $_POST['nmbp_standard_desc'] ) ) {
		update_option( 'nmbp_standard_desc', sanitize_text_field( wp_unslash( $_POST['nmbp_standard_desc'] ) ) );
	}

	// Switch & Percentage Controls (bounded 0-100% and 1-60 mins).
	$enable_tax = ( isset( $_POST['enable_tax'] ) && 'yes' === sanitize_key( wp_unslash( $_POST['enable_tax'] ) ) ) ? 'yes' : 'no';
	update_option( 'nmbp_enable_tax', $enable_tax );

	if ( isset( $_POST['service_charge_pct'] ) ) {
		$rate = (float) sanitize_text_field( wp_unslash( $_POST['service_charge_pct'] ) );
		update_option( 'nmbp_service_charge_pct', max( 0.0, min( 100.0, $rate ) ) );
	}

	if ( isset( $_POST['seat_hold_timeout'] ) ) {
		$hold = absint( sanitize_text_field( wp_unslash( $_POST['seat_hold_timeout'] ) ) );
		update_option( 'nmbp_seat_hold_timeout', max( 1, min( 60, $hold ) ) );
	}

	// Redirect back with success parameter.
	wp_safe_redirect( admin_url( 'admin.php?page=nmbp-settings&updated=1' ) );
	exit;
}