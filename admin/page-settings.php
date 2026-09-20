<?php
/**
 * Settings Module - Master Controller & Save Handler
 *
 * Path: admin/page-settings.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// 1. RENDER SETTINGS PAGE UI
// =============================================================================
if ( ! function_exists( 'bsc_render_settings_page' ) ) {
    function bsc_render_settings_page() {
        $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
        if ( ! current_user_can( $admin_cap ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
        }

        $currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

        // Fetch direct prices and descriptions with fallbacks
        $standard_price = (float) get_option( 'bsc_standard_price', 400.00 );
        $standard_desc  = sanitize_text_field( get_option( 'bsc_standard_desc', 'Standard auditorium seats.' ) );

        $service_charge = (float) get_option( 'bsc_service_charge_pct', 5.00 );
        $enable_tax     = ( 'yes' === get_option( 'bsc_enable_tax', 'yes' ) );
        $seat_hold_time = absint( get_option( 'bsc_seat_hold_timeout', 10 ) );

        if ( function_exists( 'bsc_render_page_shell_open' ) ) {
            bsc_render_page_shell_open( 'bsc-settings' );
        }
        ?>
        <div class="bsc-settings-container">
            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="bsc-settings-alert-success">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    <span><?php esc_html_e( 'Ticket prices, descriptions, and configuration saved successfully!', 'bcs-screen-manager' ); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'bsc_save_settings_verify', 'bsc_settings_nonce' ); ?>
                <input type="hidden" name="action" value="bsc_save_settings">
                <input type="hidden" name="active_subtab" value="pricing">

                <!-- Pricing Card -->
                <div class="bsc-settings-card">
                    <div class="bsc-settings-header-box">
                        <h3 class="bsc-settings-card-title">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="#0d9488" aria-hidden="true"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                            <span><?php esc_html_e( 'Auditorium Seat Pricing & Descriptions', 'bcs-screen-manager' ); ?></span>
                        </h3>
                        <span class="bsc-settings-card-desc">
                            <?php esc_html_e( 'Configure the base pricing and ticket description for standard auditorium seating (Rows A–F).', 'bcs-screen-manager' ); ?>
                        </span>
                    </div>

                    <!-- Standard Seating Block -->
                    <div class="bsc-settings-tier-block">
                        <h4 class="bsc-settings-tier-title standard"><?php esc_html_e( 'Standard Seating (Rows A–F)', 'bcs-screen-manager' ); ?></h4>
                        <div class="bsc-settings-grid-duo">
                            <div class="bsc-form-group">
                                <label>
                                    <?php 
                                    /* translators: %s: Currency symbol */
                                    printf( esc_html__( 'Price (%s)', 'bcs-screen-manager' ), esc_html( $currency_symbol ) ); 
                                    ?>
                                </label>
                                <input type="number" step="1" min="0" name="bsc_standard_price" value="<?php echo esc_attr( (string) $standard_price ); ?>" required>
                            </div>
                            <div class="bsc-form-group">
                                <label><?php esc_html_e( 'Description / Inclusions', 'bcs-screen-manager' ); ?></label>
                                <input type="text" name="bsc_standard_desc" value="<?php echo esc_attr( $standard_desc ); ?>" placeholder="<?php esc_attr_e( 'e.g. Standard auditorium seats.', 'bcs-screen-manager' ); ?>">
                            </div>
                        </div>
                    </div>

                    <hr class="bsc-divider">

                    <div class="bsc-switch-box">
                        <input type="checkbox" name="enable_tax" id="chk_tax" value="yes" <?php checked( $enable_tax, true ); ?>>
                        <label for="chk_tax"><?php esc_html_e( 'Apply VAT / Online Service Surcharge to checkout balances', 'bcs-screen-manager' ); ?></label>
                    </div>

                    <div class="bsc-settings-grid-equal">
                        <div class="bsc-form-group">
                            <label><?php esc_html_e( 'VAT / Service Surcharge (%)', 'bcs-screen-manager' ); ?></label>
                            <input type="number" step="0.1" min="0" max="100" name="service_charge_pct" value="<?php echo esc_attr( (string) $service_charge ); ?>">
                        </div>

                        <div class="bsc-form-group">
                            <label><?php esc_html_e( 'Seat Hold Expiration (Minutes)', 'bcs-screen-manager' ); ?></label>
                            <input type="number" min="1" max="60" name="seat_hold_timeout" value="<?php echo esc_attr( (string) $seat_hold_time ); ?>">
                        </div>
                    </div>
                </div>

                <div style="text-align:right;">
                    <button type="submit" class="button button-primary bsc-btn-save-settings">
                        💾 <?php esc_html_e( 'Save Configuration', 'bcs-screen-manager' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        if ( function_exists( 'bsc_render_page_shell_close' ) ) {
            bsc_render_page_shell_close();
        }
    }
}

// =============================================================================
// 2. PROCESS & SAVE FORM SUBMISSION HANDLER
// =============================================================================
add_action( 'admin_post_bsc_save_settings', 'bsc_handle_save_settings_submission' );

function bsc_handle_save_settings_submission() {
    $admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
    if ( ! current_user_can( $admin_cap ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
    }

    check_admin_referer( 'bsc_save_settings_verify', 'bsc_settings_nonce' );

    // Standard Tier Data (non-negative)
    if ( isset( $_POST['bsc_standard_price'] ) ) {
        $price = (float) sanitize_text_field( wp_unslash( $_POST['bsc_standard_price'] ) );
        update_option( 'bsc_standard_price', max( 0.0, $price ) );
    }

    if ( isset( $_POST['bsc_standard_desc'] ) ) {
        update_option( 'bsc_standard_desc', sanitize_text_field( wp_unslash( $_POST['bsc_standard_desc'] ) ) );
    }

    // Switch & Percentage Controls (bounded 0-100% and 1-60 mins)
    $enable_tax = ( isset( $_POST['enable_tax'] ) && 'yes' === sanitize_key( wp_unslash( $_POST['enable_tax'] ) ) ) ? 'yes' : 'no';
    update_option( 'bsc_enable_tax', $enable_tax );

    if ( isset( $_POST['service_charge_pct'] ) ) {
        $rate = (float) sanitize_text_field( wp_unslash( $_POST['service_charge_pct'] ) );
        update_option( 'bsc_service_charge_pct', max( 0.0, min( 100.0, $rate ) ) );
    }

    if ( isset( $_POST['seat_hold_timeout'] ) ) {
        $hold = absint( sanitize_text_field( wp_unslash( $_POST['seat_hold_timeout'] ) ) );
        update_option( 'bsc_seat_hold_timeout', max( 1, min( 60, $hold ) ) );
    }

    // Redirect back with success parameter
    wp_safe_redirect( admin_url( 'admin.php?page=bsc-settings&updated=1' ) );
    exit;
}