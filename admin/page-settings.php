<?php
/**
 * Plugin Name:   Big Screen Cinemas - Master Controller Suite (Settings Module)
 * Description:   Simplified Admin Settings Renderer (Standard & VIP Prices with Descriptions).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'bsc_render_settings_page' ) ) {
    function bsc_render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access.', 'bsc-cinema-manager' ) );
        }

        $currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

        // Fetch direct prices and descriptions with fallbacks
        $standard_price  = floatval( get_option( 'bsc_standard_price', 400.00 ) );
        $standard_desc   = sanitize_text_field( get_option( 'bsc_standard_desc', 'Standard auditorium seats.' ) );
        
        $vip_price       = floatval( get_option( 'bsc_vip_price', 500.00 ) );
        $vip_desc        = sanitize_text_field( get_option( 'bsc_vip_desc', 'Premium luxury recliners with free beverage.' ) );

        $service_charge  = floatval( get_option( 'bsc_service_charge_pct', 5.00 ) );
        $enable_tax      = ( 'yes' === get_option( 'bsc_enable_tax', 'yes' ) );
        $seat_hold_time  = absint( get_option( 'bsc_seat_hold_timeout', 10 ) );

        if ( function_exists( 'bsc_render_page_shell_open' ) ) {
            bsc_render_page_shell_open( 'bsc-settings' );
        }
        ?>
        <style>
            .bsc-settings-container {
                max-width: 800px;
                margin: 20px auto;
            }
            .bsc-settings-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
                margin-bottom: 1.5rem;
            }
            .bsc-settings-card-title {
                font-size: 1.1rem;
                font-weight: 800;
                color: #0f172a;
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0;
            }
            .bsc-form-group {
                margin-bottom: 16px;
            }
            .bsc-form-group label {
                display: block;
                font-size: 0.85rem;
                font-weight: 700;
                color: #334155;
                margin-bottom: 6px;
            }
            .bsc-form-group input[type="number"],
            .bsc-form-group input[type="text"] {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                font-size: 0.95rem;
                box-sizing: border-box;
            }
            .bsc-switch-box {
                display: flex;
                align-items: center;
                gap: 10px;
                background: #f8fafc;
                padding: 14px 16px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                margin-top: 1rem;
                margin-bottom: 1.5rem;
            }
            .bsc-switch-box input[type="checkbox"] {
                width: 18px;
                height: 18px;
                cursor: pointer;
                accent-color: #059669;
            }
            .bsc-switch-box label {
                font-weight: 700;
                font-size: 0.9rem;
                color: #1e293b;
                cursor: pointer;
            }
        </style>

        <div class="bsc-settings-container">
            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    <span><?php esc_html_e( 'Ticket prices, descriptions, and configuration saved successfully!', 'bsc-cinema-manager' ); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'bsc_save_settings_verify' ); ?>
                <input type="hidden" name="action" value="bsc_save_settings">
                <input type="hidden" name="active_subtab" value="pricing">

                <!-- Simple Pricing Card -->
                <div class="bsc-settings-card">
                    <div style="border-bottom:1px solid #f1f5f9; padding-bottom:1rem; margin-bottom:1.25rem;">
                        <h3 class="bsc-settings-card-title">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="#0d9488"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>
                            <span><?php esc_html_e( 'Auditorium Seat Pricing & Descriptions', 'bsc-cinema-manager' ); ?></span>
                        </h3>
                        <span style="font-size:0.8rem; color:#64748b; display:block; margin-top:4px;">
                            <?php esc_html_e( 'Configure the base pricing and description for Standard seating (Rows B, C, D) and VIP Recliners (Row A).', 'bsc-cinema-manager' ); ?>
                        </span>
                    </div>

                    <!-- Standard Tier Block -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-bottom:16px;">
                        <h4 style="margin:0 0 12px 0; font-size:0.95rem; font-weight:800; color:#0f172a;"><?php esc_html_e( 'Standard Seating (Rows B, C, D)', 'bsc-cinema-manager' ); ?></h4>
                        <div style="display:grid; grid-template-columns:180px 1fr; gap:16px;">
                            <div class="bsc-form-group" style="margin-bottom:0;">
                                <label><?php echo esc_html( sprintf( __( 'Price (%s)', 'bsc-cinema-manager' ), $currency_symbol ) ); ?></label>
                                <input type="number" step="1" min="0" name="bsc_standard_price" value="<?php echo esc_attr( $standard_price ); ?>" required>
                            </div>
                            <div class="bsc-form-group" style="margin-bottom:0;">
                                <label><?php esc_html_e( 'Description / Inclusions', 'bsc-cinema-manager' ); ?></label>
                                <input type="text" name="bsc_standard_desc" value="<?php echo esc_attr( $standard_desc ); ?>" placeholder="e.g. Standard auditorium seats.">
                            </div>
                        </div>
                    </div>

                    <!-- VIP Tier Block -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin-bottom:16px;">
                        <h4 style="margin:0 0 12px 0; font-size:0.95rem; font-weight:800; color:#b45309;"><?php esc_html_e( 'VIP Recliners (Row A)', 'bsc-cinema-manager' ); ?></h4>
                        <div style="display:grid; grid-template-columns:180px 1fr; gap:16px;">
                            <div class="bsc-form-group" style="margin-bottom:0;">
                                <label><?php echo esc_html( sprintf( __( 'Price (%s)', 'bsc-cinema-manager' ), $currency_symbol ) ); ?></label>
                                <input type="number" step="1" min="0" name="bsc_vip_price" value="<?php echo esc_attr( $vip_price ); ?>" required>
                            </div>
                            <div class="bsc-form-group" style="margin-bottom:0;">
                                <label><?php esc_html_e( 'Description / Inclusions', 'bsc-cinema-manager' ); ?></label>
                                <input type="text" name="bsc_vip_desc" value="<?php echo esc_attr( $vip_desc ); ?>" placeholder="e.g. Premium luxury recliners with free beverage.">
                            </div>
                        </div>
                    </div>

                    <hr style="border:0; border-top:1px solid #f1f5f9; margin: 1.5rem 0;">

                    <div class="bsc-switch-box">
                        <input type="checkbox" name="enable_tax" id="chk_tax" value="yes" <?php checked( $enable_tax, true ); ?>>
                        <label for="chk_tax"><?php esc_html_e( 'Apply VAT / Online Service Surcharge to checkout balances', 'bsc-cinema-manager' ); ?></label>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div class="bsc-form-group" style="margin-bottom:0;">
                            <label><?php esc_html_e( 'VAT / Service Surcharge (%)', 'bsc-cinema-manager' ); ?></label>
                            <input type="number" step="0.1" min="0" name="service_charge_pct" value="<?php echo esc_attr( $service_charge ); ?>">
                        </div>

                        <div class="bsc-form-group" style="margin-bottom:0;">
                            <label><?php esc_html_e( 'Seat Hold Expiration (Minutes)', 'bsc-cinema-manager' ); ?></label>
                            <input type="number" min="1" max="60" name="seat_hold_timeout" value="<?php echo esc_attr( $seat_hold_time ); ?>">
                        </div>
                    </div>
                </div>

                <div style="text-align:right;">
                    <button type="submit" class="button button-primary" style="background:#059669; border-color:#059669; padding:10px 24px; font-weight:800; font-size:0.95rem; border-radius:8px;">
                        💾 <?php esc_html_e( 'Save Configuration', 'bsc-cinema-manager' ); ?>
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