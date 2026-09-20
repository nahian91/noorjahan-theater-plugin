<?php
/**
 * Customer Module - Single Customer Dossier View
 *
 * Path: admin/customers/view-customer.php
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
$table_customers = $wpdb->prefix . 'bsc_customers';
$table_bookings  = $wpdb->prefix . 'bsc_bookings';
$customer_id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

$customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_customers} WHERE id = %d", $customer_id ) );
if ( ! $customer ) {
    echo '<div class="bsc-panel" style="margin:20px;"><p>' . esc_html__( 'Customer record not found.', 'bcs-screen-manager' ) . '</p></div>';
    return;
}

// Helper 12-Hour AM/PM Time Formatter
if ( ! function_exists( 'bsc_format_customer_time' ) ) {
    function bsc_format_customer_time( $raw_time ) {
        $clean = trim( (string) $raw_time );
        if ( empty( $clean ) ) {
            return '';
        }
        if ( false !== stripos( $clean, 'AM' ) || false !== stripos( $clean, 'PM' ) ) {
            return $clean;
        }
        if ( false !== strpos( $clean, ':' ) ) {
            $parts = explode( ':', $clean );
            $hour  = (int) $parts[0];
            $min   = isset( $parts[1] ) ? substr( $parts[1], 0, 2 ) : '00';
            $ampm  = $hour >= 12 ? 'PM' : 'AM';
            $h12   = ( $hour % 12 === 0 ) ? 12 : ( $hour % 12 );
            return sprintf( '%02d:%s %s', $h12, $min, $ampm );
        }
        return $clean;
    }
}

// Fetch matching historical bookings by customer email or phone
$customer_history = array();
$history_clauses  = array();
$history_args     = array();

if ( ! empty( $customer->email ) ) {
    $history_clauses[] = 'customer_email = %s';
    $history_args[]    = $customer->email;
}

if ( ! empty( $customer->phone ) ) {
    $history_clauses[] = 'customer_phone = %s';
    $history_args[]    = $customer->phone;
}

if ( ! empty( $history_clauses ) ) {
    $history_where = implode( ' OR ', $history_clauses );
    $customer_history = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table_bookings} WHERE ({$history_where}) ORDER BY id DESC",
            $history_args
        )
    );
}

if ( ! is_array( $customer_history ) ) {
    $customer_history = array();
}

// Calculate total tickets from actual history
$total_tickets_count = 0;
foreach ( $customer_history as $h ) {
    if ( ! empty( $h->seats ) ) {
        $seats_arr           = array_filter( array_map( 'trim', explode( ',', $h->seats ) ) );
        $total_tickets_count += count( $seats_arr );
    }
}

$total_visits_count = intval( $customer->total_visits ?? count( $customer_history ) );
$delete_url = wp_nonce_url(
    admin_url( 'admin.php?page=bsc-customers&subtab=delete&id=' . absint( $customer->id ) ),
    'bsc_delete_customer_' . absint( $customer->id ),
    '_bsc_nonce'
);
?>

<div class="bsc-customer-view-grid">
    
    <!-- Left: Dossier & Screening History -->
    <div class="bsc-customer-main-card">
        
        <!-- Cinematic Header Banner -->
        <div class="bsc-customer-banner-head">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                    <span class="bsc-badge-pill active"><?php esc_html_e( 'Club Member', 'bcs-screen-manager' ); ?></span>
                </div>
                <h2 style="font-size: 2.1rem; font-weight: 900; margin: 0 0 0.35rem 0; color: #ffffff; letter-spacing: -0.01em;">
                    <?php echo esc_html( $customer->name ?? '' ); ?>
                </h2>
                <div style="font-size: 0.82rem; color: #94a3b8; font-family: var(--bsc-font-mono, monospace);">
                    <?php esc_html_e( 'Registered:', 'bcs-screen-manager' ); ?> 
                    <strong style="color: #e2e8f0;">
                        <?php echo esc_html( ! empty( $customer->created_at ) ? wp_date( 'M j, Y', strtotime( $customer->created_at ) ) : __( 'N/A', 'bcs-screen-manager' ) ); ?>
                    </strong>
                </div>
            </div>

            <div class="bsc-table-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-customers&subtab=edit&id=' . absint( $customer->id ) ) ); ?>" class="bsc-action-btn edit" title="<?php esc_attr_e( 'Edit Customer', 'bcs-screen-manager' ); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                    <span><?php esc_html_e( 'Edit Customer', 'bcs-screen-manager' ); ?></span>
                </a>
                <a href="<?php echo esc_url( $delete_url ); ?>" class="bsc-action-btn delete" onclick="return confirm('<?php echo esc_js( __( 'Permanently remove this customer record?', 'bcs-screen-manager' ) ); ?>');" title="<?php esc_attr_e( 'Delete', 'bcs-screen-manager' ); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    <span><?php esc_html_e( 'Delete', 'bcs-screen-manager' ); ?></span>
                </a>
            </div>
        </div>

        <!-- Body Information -->
        <div class="bsc-customer-body-wrap">
            
            <!-- Structured Key-Value Spec Table -->
            <table class="bsc-customer-spec-table">
                <tbody>
                    <tr>
                        <th>
                            <div class="bsc-table-cell-header">
                                <span><?php esc_html_e( 'Contact Information', 'bcs-screen-manager' ); ?></span>
                            </div>
                        </th>
                        <td>
                            <div style="font-weight: 800; font-size: 1rem; color: var(--bsc-text-main, #0f172a);">
                                📞 <?php echo esc_html( ! empty( $customer->phone ) ? $customer->phone : __( 'Not provided', 'bcs-screen-manager' ) ); ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--bsc-text-muted, #64748b); margin-top: 3px;">
                                ✉️ <?php echo esc_html( ! empty( $customer->email ) ? $customer->email : __( 'Not provided', 'bcs-screen-manager' ) ); ?>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <div class="bsc-table-cell-header">
                                <span><?php esc_html_e( 'Screening Activity', 'bcs-screen-manager' ); ?></span>
                            </div>
                        </th>
                        <td>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong style="color: var(--bsc-text-main, #0f172a); font-size: 0.95rem;">
                                        <?php echo esc_html( number_format( $total_visits_count ) ); ?> <?php esc_html_e( 'Total Visits', 'bcs-screen-manager' ); ?>
                                    </strong>
                                    <div style="font-size: 0.8rem; color: var(--bsc-text-muted, #64748b); margin-top: 2px;">
                                        <?php esc_html_e( 'Member Screening Record', 'bcs-screen-manager' ); ?>
                                    </div>
                                </div>
                                <strong style="color: var(--bsc-accent-teal, #0d9488); font-family: var(--bsc-font-mono, monospace); font-size: 1.1rem;">
                                    <?php echo esc_html( number_format( $total_tickets_count ) ); ?> <?php esc_html_e( 'Tickets', 'bcs-screen-manager' ); ?>
                                </strong>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Customer Ticket Purchase History -->
            <div style="border-top: 1px solid var(--bsc-border-light, #f1f5f9); padding-top: 1.5rem; margin-top: 1.5rem;">
                <h4 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V10zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
                    <?php 
                    /* translators: %d: Historical booking count */
                    printf( esc_html__( 'Screening Passes & Ticket Log (%d)', 'bcs-screen-manager' ), count( $customer_history ) ); 
                    ?>
                </h4>

                <div class="bsc-table-wrap" style="overflow-x: auto;">
                    <table class="bsc-data-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--bsc-border, #e2e8f0); background: #f8fafc;">
                                <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'TOKEN', 'bcs-screen-manager' ); ?></th>
                                <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'MOVIE', 'bcs-screen-manager' ); ?></th>
                                <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'SHOWTIME', 'bcs-screen-manager' ); ?></th>
                                <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'SEATS', 'bcs-screen-manager' ); ?></th>
                                <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'AMOUNT', 'bcs-screen-manager' ); ?></th>
                                <th style="padding: 10px 12px; text-align: left;"><?php esc_html_e( 'DATE', 'bcs-screen-manager' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $customer_history ) ) : foreach ( $customer_history as $h ) : 
                                $formatted_h_time = bsc_format_customer_time( $h->showtime ?? '' );
                            ?>
                                <tr style="border-bottom: 1px solid var(--bsc-border-light, #f1f5f9);">
                                    <td style="padding: 12px;">
                                        <span class="bsc-voucher-tag"><?php echo esc_html( $h->booking_token ?? '' ); ?></span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <strong><?php echo esc_html( $h->movie_title ?? '' ); ?></strong>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span class="bsc-badge-pill active" style="font-size: 0.7rem;">
                                            <?php echo esc_html( ! empty( $formatted_h_time ) ? $formatted_h_time : __( 'Counter', 'bcs-screen-manager' ) ); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <code style="font-family: var(--bsc-font-mono, monospace);"><?php echo esc_html( $h->seats ?? '' ); ?></code>
                                    </td>
                                    <td style="padding: 12px;">
                                        <strong style="color: var(--bsc-accent-teal, #0d9488); font-family: var(--bsc-font-mono, monospace);">
                                            <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) ( $h->total_amount ?? 0.00 ), 2 ) ); ?>
                                        </strong>
                                    </td>
                                    <td style="padding: 12px;">
                                        <small style="color: var(--bsc-text-muted, #64748b); font-weight: 700;">
                                            <?php echo esc_html( ! empty( $h->booking_date ) ? wp_date( 'M j, Y', strtotime( $h->booking_date ) ) : '' ); ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--bsc-text-muted, #64748b);">
                                        <?php esc_html_e( 'No ticket bookings linked to this customer yet.', 'bcs-screen-manager' ); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top: 2rem; border-top: 1px solid var(--bsc-border-light, #f1f5f9); padding-top: 1.25rem;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-customers&subtab=all' ) ); ?>" class="button button-secondary" style="font-weight: 700; border-radius: 6px;">
                    &larr; <?php esc_html_e( 'Back to All Customers', 'bcs-screen-manager' ); ?>
                </a>
            </div>

        </div>

    </div>

    <!-- Right: Member Card Visual -->
    <div>
        <div class="bsc-staff-id-card-sidebar">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #99f6e4;">
                    <?php esc_html_e( 'CLUB MEMBER', 'bcs-screen-manager' ); ?>
                </div>
                <svg viewBox="0 0 24 24" width="28" height="28" fill="rgba(255,255,255,0.7)" aria-hidden="true"><path d="M12 2L1 21h22L12 2zm0 3.5l7.5 13H4.5L12 5.5z"/></svg>
            </div>

            <div style="font-size: 1.35rem; font-weight: 900; letter-spacing: 0.02em; margin-bottom: 0.25rem;">
                <?php echo esc_html( $customer->name ?? '' ); ?>
            </div>
            <div style="font-size: 0.85rem; color: #cbd5e1; font-family: var(--bsc-font-mono, monospace);">
                <?php echo esc_html( $customer->phone ?? '' ); ?>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 2.5rem; border-top: 1px solid rgba(255,255,255,0.15); padding-top: 1rem;">
                <div>
                    <div style="font-size: 0.68rem; text-transform: uppercase; color: #99f6e4; font-weight: 800;"><?php esc_html_e( 'Visits', 'bcs-screen-manager' ); ?></div>
                    <div style="font-size: 1.1rem; font-weight: 900;"><?php echo esc_html( number_format( $total_visits_count ) ); ?></div>
                </div>
                <div>
                    <div style="font-size: 0.68rem; text-transform: uppercase; color: #99f6e4; font-weight: 800; text-align: right;"><?php esc_html_e( 'Tickets', 'bcs-screen-manager' ); ?></div>
                    <div style="font-size: 1.1rem; font-weight: 900; font-family: var(--bsc-font-mono, monospace); color: #fde68a; text-align: right;">
                        <?php echo esc_html( number_format( $total_tickets_count ) ); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>