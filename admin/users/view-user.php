<?php
/**
 * Users / Staff Module - Single Staff Dossier View
 *
 * Path: admin/users/view-user.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$admin_cap = defined( 'BSC_ADMIN_CAP' ) ? BSC_ADMIN_CAP : 'manage_options';
if ( ! current_user_can( $admin_cap ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'bcs-screen-manager' ), 403 );
}

global $wpdb;
$table_staff     = $wpdb->prefix . 'bsc_staff_crew';
$staff_id        = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

$staff = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id ) );
if ( ! $staff ) {
    echo '<div class="bsc-panel" style="margin:20px;"><p>' . esc_html__( 'Staff record not found.', 'bcs-screen-manager' ) . '</p></div>';
    return;
}

$is_on_duty = ( 'On Duty' === ( $staff->duty_status ?? '' ) );
$staff_item_id = absint( $staff->id );
$staff_code = ! empty( $staff->staff_code ) ? $staff->staff_code : '#STAFF-' . $staff_item_id;
$delete_url = wp_nonce_url(
    admin_url( 'admin.php?page=bsc-users&subtab=delete&id=' . $staff_item_id ),
    'bsc_delete_user_' . $staff_item_id,
    '_bsc_nonce'
);
?>

<div class="bsc-staff-view-grid">
    
    <!-- Left: Master Dossier Table -->
    <div class="bsc-staff-main-card">
        
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid var(--bsc-border-light, #f1f5f9); padding-bottom:1.25rem; margin-bottom:1.5rem;">
            <div>
                <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.4rem;">
                    <span class="bsc-badge-pill <?php echo $is_on_duty ? 'active' : ''; ?>" style="<?php echo ! $is_on_duty ? 'background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3);' : ''; ?>">
                        ● <?php echo esc_html( $staff->duty_status ?? __( 'Off Duty', 'bcs-screen-manager' ) ); ?>
                    </span>
                    <span class="bsc-voucher-tag" style="font-family:var(--bsc-font-mono, monospace); background:#f8fafc; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px; font-size:0.75rem;">
                        <?php echo esc_html( $staff_code ); ?>
                    </span>
                </div>
                <h2 style="font-size: 1.75rem; font-weight: 900; margin: 0; color: var(--bsc-text-main, #0f172a);">
                    <?php echo esc_html( $staff->name ?? '' ); ?>
                </h2>
                <span style="font-size: 0.82rem; color: var(--bsc-text-muted, #64748b); font-family: var(--bsc-font-mono, monospace);">
                    <?php esc_html_e( 'Role:', 'bcs-screen-manager' ); ?> <strong><?php echo esc_html( $staff->role_title ?? '' ); ?></strong> &bull; <?php esc_html_e( 'Joined:', 'bcs-screen-manager' ); ?> <strong><?php echo esc_html( ! empty( $staff->created_at ) ? wp_date( 'M j, Y', strtotime( $staff->created_at ) ) : __( 'N/A', 'bcs-screen-manager' ) ); ?></strong>
                </span>
            </div>

            <div class="bsc-table-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-users&subtab=edit&id=' . $staff_item_id ) ); ?>" class="bsc-action-btn edit" style="padding:7px 12px; font-size:0.82rem;" title="<?php esc_attr_e( 'Edit Staff', 'bcs-screen-manager' ); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    <span><?php esc_html_e( 'Edit Staff', 'bcs-screen-manager' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $delete_url ); ?>" class="bsc-action-btn delete" style="padding:7px 12px; font-size:0.82rem;" onclick="return confirm('<?php echo esc_js( __( 'Permanently remove this staff record?', 'bcs-screen-manager' ) ); ?>');" title="<?php esc_attr_e( 'Delete', 'bcs-screen-manager' ); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    <span><?php esc_html_e( 'Delete', 'bcs-screen-manager' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Structured Key-Value Table -->
        <table class="bsc-staff-spec-table">
            <tbody>
                <tr>
                    <th>
                        <div class="bsc-table-cell-header">
                            <span><?php esc_html_e( 'Contact Information', 'bcs-screen-manager' ); ?></span>
                        </div>
                    </th>
                    <td>
                        <div style="font-weight: 800; font-size: 1rem; color: var(--bsc-text-main, #0f172a);">
                            📞 <?php echo esc_html( ! empty( $staff->phone ) ? $staff->phone : __( 'Not provided', 'bcs-screen-manager' ) ); ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--bsc-text-muted, #64748b); margin-top: 3px;">
                            ✉️ <?php echo esc_html( ! empty( $staff->email ) ? $staff->email : __( 'Not provided', 'bcs-screen-manager' ) ); ?>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>
                        <div class="bsc-table-cell-header">
                            <span><?php esc_html_e( 'Shift & Duty Status', 'bcs-screen-manager' ); ?></span>
                        </div>
                    </th>
                    <td>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="color: var(--bsc-accent-teal, #0d9488); font-size: 0.95rem;">
                                    <?php echo esc_html( ! empty( $staff->shift_schedule ) ? $staff->shift_schedule : __( 'Rotational Shift', 'bcs-screen-manager' ) ); ?>
                                </strong>
                            </div>
                            <span class="bsc-badge-pill <?php echo $is_on_duty ? 'active' : ''; ?>" style="<?php echo ! $is_on_duty ? 'background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3);' : ''; ?>">
                                ● <?php echo esc_html( $staff->duty_status ?? '' ); ?>
                            </span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>
                        <div class="bsc-table-cell-header">
                            <span><?php esc_html_e( 'Monthly Salary', 'bcs-screen-manager' ); ?></span>
                        </div>
                    </th>
                    <td>
                        <div style="font-size: 1.15rem; font-weight: 900; font-family: var(--bsc-font-mono, monospace); color: #059669;">
                            <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $staff->salary ?? 0.00 ), 2 ) ); ?>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>
                        <div class="bsc-table-cell-header">
                            <span><?php esc_html_e( 'Notes / Responsibilities', 'bcs-screen-manager' ); ?></span>
                        </div>
                    </th>
                    <td>
                        <p style="font-size:0.92rem; line-height:1.75; color: var(--bsc-text-muted, #475569); margin:0;">
                            <?php 
                            $notes = ! empty( $staff->responsibilities ) 
                                ? $staff->responsibilities 
                                : __( 'Standard cinema floor operations, guest welcoming, equipment readiness, and ticket turnstile check-in duties.', 'bcs-screen-manager' );
                            echo nl2br( esc_html( $notes ) ); 
                            ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top:2rem; border-top:1px solid var(--bsc-border-light, #f1f5f9); padding-top:1.25rem;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-users&subtab=all' ) ); ?>" class="button button-secondary" style="font-weight:700; border-radius:6px;">
                &larr; <?php esc_html_e( 'Back to Staff Roster', 'bcs-screen-manager' ); ?>
            </a>
        </div>

    </div>

    <!-- Right: ID Badge Visual -->
    <div>
        <div class="bsc-staff-id-card-sidebar">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <div style="font-size:0.75rem; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#99f6e4;">
                    <?php esc_html_e( 'MULTIPLEX CREW ID', 'bcs-screen-manager' ); ?>
                </div>
                <svg viewBox="0 0 24 24" width="22" height="22" fill="rgba(255,255,255,0.7)" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
            </div>

            <?php if ( ! empty( $staff->photo_url ) ) : ?>
                <img src="<?php echo esc_url( $staff->photo_url ); ?>" alt="<?php echo esc_attr( $staff->name ?? '' ); ?>" class="bsc-staff-badge-photo">
            <?php else : ?>
                <div class="bsc-staff-badge-photo" style="display:flex; align-items:center; justify-content:center; color:#fff; font-size:2.2rem; font-weight:900;">
                    <?php echo esc_html( strtoupper( substr( (string) ( $staff->name ?? 'SC' ), 0, 2 ) ) ); ?>
                </div>
            <?php endif; ?>

            <div style="font-size:1.35rem; font-weight:900; letter-spacing:0.02em; margin-bottom:0.25rem;">
                <?php echo esc_html( $staff->name ?? '' ); ?>
            </div>
            <div style="font-size:0.85rem; color:#99f6e4; font-weight:700;">
                <?php echo esc_html( $staff->role_title ?? '' ); ?>
            </div>
            <div style="font-size:0.8rem; color:#cbd5e1; font-family:var(--bsc-font-mono, monospace); margin-top:2px;">
                <?php echo esc_html( $staff_code ); ?>
            </div>

            <div style="display:flex; justify-content:flex-end; align-items:center; margin-top:1.75rem; border-top:1px solid rgba(255,255,255,0.15); padding-top:1rem;">
                <span class="bsc-badge-pill <?php echo $is_on_duty ? 'active' : ''; ?>" style="font-size:0.68rem; <?php echo ! $is_on_duty ? 'background:rgba(239,68,68,0.2); color:#fca5a5; border:1px solid rgba(239,68,68,0.4);' : ''; ?>">
                    ● <?php echo esc_html( $staff->duty_status ?? '' ); ?>
                </span>
            </div>
        </div>
    </div>

</div>