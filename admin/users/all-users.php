<?php
/**
 * Users / Staff Module - All Staff Roster View
 *
 * Path: admin/users/all-users.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$staff_count = ( isset( $staff_members ) && is_array( $staff_members ) ) ? count( $staff_members ) : 0;
?>

<div class="bsc-panel" style="margin: 0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);">
    <div class="bsc-panel-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--bsc-border, #e2e8f0);">
        <div>
            <h3 class="bsc-panel-title" style="margin: 0 0 4px 0; font-size: 1.15rem; font-weight: 800; color: var(--bsc-text-main, #0f172a);">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" style="vertical-align: -4px; margin-right: 4px;">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
                <?php 
                /* translators: %d: number of staff members */
                printf( esc_html__( 'Staff & Operational Crew (%d)', 'bcs-screen-manager' ), absint( $staff_count ) ); 
                ?>
            </h3>
            <span style="font-size: 0.78rem; color: var(--bsc-text-muted, #64748b);">
                <?php esc_html_e( 'Manage multiplex staff roster, duty status, and payroll.', 'bcs-screen-manager' ); ?>
            </span>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-users&subtab=add' ) ); ?>" class="button button-primary" style="background: #064e3b; border-color: #064e3b; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            <?php esc_html_e( 'Add Staff Member', 'bcs-screen-manager' ); ?>
        </a>
    </div>

    <div class="bsc-table-wrap" style="overflow-x: auto;">
        <table class="bsc-data-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--bsc-border, #e2e8f0); background: #f8fafc;">
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'STAFF MEMBER', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'ROLE / TITLE', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'CONTACT', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'STATUS', 'bcs-screen-manager' ); ?></th>
                    <th style="padding: 10px 12px; text-align: right;"><?php esc_html_e( 'ACTIONS', 'bcs-screen-manager' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $staff_members ) && is_array( $staff_members ) ) : foreach ( $staff_members as $s ) :
                    $item_id    = absint( $s->id );
                    $view_url   = admin_url( 'admin.php?page=bsc-users&subtab=view&id=' . $item_id );
                    $edit_url   = admin_url( 'admin.php?page=bsc-users&subtab=edit&id=' . $item_id );
                    $delete_url = wp_nonce_url(
                        admin_url( 'admin.php?page=bsc-users&subtab=delete&id=' . $item_id ),
                        'bsc_delete_user_' . $item_id,
                        '_bsc_nonce'
                    );
                    $is_on_duty = ( 'On Duty' === ( $s->duty_status ?? '' ) );
                ?>
                    <tr style="border-bottom: 1px solid var(--bsc-border-light, #f1f5f9);">
                        <td style="padding: 12px;">
                            <div class="bsc-staff-avatar-cell">
                                <?php if ( ! empty( $s->photo_url ) ) : ?>
                                    <img src="<?php echo esc_url( $s->photo_url ); ?>" alt="<?php echo esc_attr( $s->name ?? '' ); ?>" class="bsc-staff-avatar-thumb">
                                <?php else : ?>
                                    <div class="bsc-staff-avatar-thumb">
                                        <?php echo esc_html( strtoupper( substr( (string) ( $s->name ?? 'SC' ), 0, 2 ) ) ); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong><?php echo esc_html( $s->name ?? '' ); ?></strong><br>
                                    <small style="color: var(--bsc-text-muted, #64748b);">
                                        <?php echo esc_html( ! empty( $s->shift_schedule ) ? $s->shift_schedule : __( 'General Shift', 'bcs-screen-manager' ) ); ?>
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html( $s->role_title ?? '' ); ?></strong>
                        </td>
                        <td style="padding: 12px;">
                            <span style="font-family: var(--bsc-font-mono, monospace); font-size: 0.85rem; color: #334155; font-weight: 700;">
                                <?php echo esc_html( $s->phone ?? '' ); ?>
                            </span><br>
                            <small style="color: var(--bsc-text-muted, #64748b);">
                                <?php echo esc_html( ! empty( $s->email ) ? $s->email : __( 'No email provided', 'bcs-screen-manager' ) ); ?>
                            </small>
                        </td>
                        <td style="padding: 12px;">
                            <span class="bsc-badge-pill <?php echo $is_on_duty ? 'active' : ''; ?>" style="<?php echo ! $is_on_duty ? 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);' : ''; ?>">
                                ● <?php echo esc_html( $s->duty_status ?? __( 'Off Duty', 'bcs-screen-manager' ) ); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: right; white-space: nowrap;">
                            <div class="bsc-table-actions">
                                <a href="<?php echo esc_url( $view_url ); ?>" class="bsc-action-btn view" title="<?php esc_attr_e( 'View', 'bcs-screen-manager' ); ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    <span><?php esc_html_e( 'View', 'bcs-screen-manager' ); ?></span>
                                </a>
                                <a href="<?php echo esc_url( $edit_url ); ?>" class="bsc-action-btn edit" title="<?php esc_attr_e( 'Edit', 'bcs-screen-manager' ); ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    <span><?php esc_html_e( 'Edit', 'bcs-screen-manager' ); ?></span>
                                </a>
                                <a href="<?php echo esc_url( $delete_url ); ?>" class="bsc-action-btn delete" onclick="return confirm('<?php echo esc_js( __( 'Permanently remove this staff member from the system?', 'bcs-screen-manager' ) ); ?>');" title="<?php esc_attr_e( 'Delete', 'bcs-screen-manager' ); ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    <span><?php esc_html_e( 'Delete', 'bcs-screen-manager' ); ?></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--bsc-text-muted, #64748b);">
                            <?php esc_html_e( 'No staff members registered in the multiplex roster.', 'bcs-screen-manager' ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>