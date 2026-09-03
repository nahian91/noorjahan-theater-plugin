<?php
/**
 * Accounts Module - Comprehensive Financial & Box-Office Report View
 *
 * Path: admin/accounts/report-accounts.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_bookings = isset( $table_bookings ) ? $table_bookings : $wpdb->prefix . 'bsc_bookings';
$table_movies   = $wpdb->prefix . 'bsc_control_movies';
$table_ledger   = $wpdb->prefix . 'bsc_ledger';

$currency_symbol = sanitize_text_field( get_option( 'bsc_currency_symbol', '৳' ) );

// Determine active sub-report tab ('ticket', 'lifetime', 'income', or 'expense')
$report_subtab = isset( $_GET['report_tab'] ) ? sanitize_key( $_GET['report_tab'] ) : 'ticket';

// Custom Date Range Inputs from URL
$start_date_val = isset( $_GET['report_start'] ) ? sanitize_text_field( wp_unslash( $_GET['report_start'] ) ) : '';
$end_date_val   = isset( $_GET['report_end'] ) ? sanitize_text_field( wp_unslash( $_GET['report_end'] ) ) : '';

// Build custom date WHERE clause for bookings
$custom_date_where = '1=1';
if ( ! empty( $start_date_val ) && ! empty( $end_date_val ) ) {
    $custom_date_where = $wpdb->prepare( 'DATE(booking_date) BETWEEN %s AND %s', $start_date_val, $end_date_val );
} elseif ( ! empty( $start_date_val ) ) {
    $custom_date_where = $wpdb->prepare( 'DATE(booking_date) >= %s', $start_date_val );
} elseif ( ! empty( $end_date_val ) ) {
    $custom_date_where = $wpdb->prepare( 'DATE(booking_date) <= %s', $end_date_val );
}

// Build custom date WHERE clause for ledger (expenses and manual income)
$ledger_date_where = '1=1';
if ( ! empty( $start_date_val ) && ! empty( $end_date_val ) ) {
    $ledger_date_where = $wpdb->prepare( 'DATE(transaction_date) BETWEEN %s AND %s', $start_date_val, $end_date_val );
} elseif ( ! empty( $start_date_val ) ) {
    $ledger_date_where = $wpdb->prepare( 'DATE(transaction_date) >= %s', $start_date_val );
} elseif ( ! empty( $end_date_val ) ) {
    $ledger_date_where = $wpdb->prepare( 'DATE(transaction_date) <= %s', $end_date_val );
}

// Fetch filter options dynamically
$filter_movies = $wpdb->get_col( "SELECT DISTINCT movie_title FROM {$table_bookings} WHERE movie_title != '' ORDER BY movie_title ASC" );
$filter_times  = $wpdb->get_col( "SELECT DISTINCT showtime FROM {$table_bookings} WHERE showtime != '' ORDER BY showtime ASC" );

// Helper 12-Hour AM/PM Time Formatter
function bsc_format_report_time( $raw_time ) {
    $clean = trim( (string) $raw_time );
    if ( empty( $clean ) ) {
        return 'Counter';
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

// Fetch detailed movie-based box-office breakdown for the ticket tab
$movie_income_breakdown = $wpdb->get_results( "
    SELECT 
        movie_title, 
        showtime,
        DATE(booking_date) as b_date,
        COUNT(id) as total_passes, 
        SUM(total_amount) as gross_revenue,
        SUM(CASE WHEN payment_method = 'Cash' THEN total_amount ELSE 0 END) as cash_revenue,
        SUM(CASE WHEN payment_method != 'Cash' THEN total_amount ELSE 0 END) as online_revenue,
        GROUP_CONCAT(seats SEPARATOR ', ') as all_booked_seats
    FROM {$table_bookings} 
    WHERE ({$custom_date_where}) AND payment_status != 'Cancelled' 
    GROUP BY movie_title, showtime, DATE(booking_date) 
    ORDER BY gross_revenue DESC
" );

// Fetch lifetime movie progress statistics with collation fix
$lifetime_movies_stats = $wpdb->get_results( "
    SELECT 
        m.title as movie_title,
        m.poster_url,
        COALESCE(SUM(b.total_amount), 0) as lifetime_revenue,
        COUNT(b.id) as lifetime_passes
    FROM {$table_movies} m
    LEFT JOIN {$table_bookings} b ON m.title COLLATE utf8mb4_unicode_520_ci = b.movie_title COLLATE utf8mb4_unicode_520_ci AND b.payment_status != 'Cancelled'
    GROUP BY m.id, m.title, m.poster_url
    ORDER BY lifetime_revenue DESC
" );

// Fetch manual incomes for the Income tab
$report_incomes = $wpdb->get_results( "SELECT * FROM {$table_ledger} WHERE ({$ledger_date_where}) AND (txn_type = 'Income' OR txn_type IS NULL OR txn_type = '') ORDER BY id DESC" );

// Fetch expenses for the Expense tab
$report_expenses = $wpdb->get_results( "SELECT * FROM {$table_ledger} WHERE ({$ledger_date_where}) AND txn_type = 'Expense' ORDER BY id DESC" );

// Find max lifetime revenue for progress bar percentage calculation
$max_lifetime_rev = 1;
foreach ( $lifetime_movies_stats as $stat ) {
    if ( $stat->lifetime_revenue > $max_lifetime_rev ) {
        $max_lifetime_rev = $stat->lifetime_revenue;
    }
}
?>
<style>
  .bsc-report-filter-bar {
    background: #ffffff;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: var(--cinema-radius-md, 8px);
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
  }
  .bsc-report-filter-bar select,
  .bsc-report-filter-bar input {
    padding: 6px 10px;
    font-size: 0.85rem;
    border: 1px solid var(--cinema-border, #e2e8f0);
    border-radius: 6px;
    background: #ffffff;
    color: var(--cinema-text-main, #0f172a);
    outline: none;
  }
  .bsc-report-tabs-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 1.25rem;
    border-bottom: 2px solid var(--cinema-border, #e2e8f0);
    padding-bottom: 0.75rem;
    flex-wrap: wrap;
  }
  .bsc-report-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    font-size: 0.85rem;
    font-weight: 800;
    border-radius: 6px;
    text-decoration: none;
    background: #f1f5f9;
    color: var(--cinema-text-muted, #64748b);
    border: 1px solid var(--cinema-border, #e2e8f0);
    transition: all 0.2s ease;
  }
  .bsc-report-tab-btn.active, .bsc-report-tab-btn:hover {
    background: var(--cinema-accent-teal, #0d9488);
    color: #ffffff;
    border-color: var(--cinema-accent-teal, #0d9488);
  }
  .bsc-lifetime-progress-wrap {
    background: #f1f5f9;
    height: 10px;
    border-radius: 999px;
    overflow: hidden;
    margin-top: 6px;
    width: 100%;
  }
  .bsc-lifetime-progress-fill {
    background: #0d9488;
    height: 100%;
    border-radius: 999px;
  }
</style>

<div id="bscPrintArea">
  <!-- Internal Report Subtabs with SVG Icons -->
  <div class="bsc-report-tabs-nav">
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=report&report_tab=ticket' ) ); ?>" class="bsc-report-tab-btn <?php echo ( 'ticket' === $report_subtab ) ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
      <?php esc_html_e( 'Ticket', 'bsc-cinema-manager' ); ?>
    </a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=report&report_tab=lifetime' ) ); ?>" class="bsc-report-tab-btn <?php echo ( 'lifetime' === $report_subtab ) ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
      <?php esc_html_e( 'Movie Performance', 'bsc-cinema-manager' ); ?>
    </a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=report&report_tab=income' ) ); ?>" class="bsc-report-tab-btn <?php echo ( 'income' === $report_subtab ) ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
      <?php esc_html_e( 'Income', 'bsc-cinema-manager' ); ?>
    </a>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=report&report_tab=expense' ) ); ?>" class="bsc-report-tab-btn <?php echo ( 'expense' === $report_subtab ) ? 'active' : ''; ?>">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
      <?php esc_html_e( 'Expense', 'bsc-cinema-manager' ); ?>
    </a>
  </div>

  <?php if ( 'ticket' === $report_subtab || 'income' === $report_subtab || 'expense' === $report_subtab ) : ?>
    <!-- Shared Date Range & Filter Bar -->
    <form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="bsc-report-filter-bar">
      <input type="hidden" name="page" value="bsc-accounts">
      <input type="hidden" name="subtab" value="report">
      <input type="hidden" name="report_tab" value="<?php echo esc_attr( $report_subtab ); ?>">

      <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:6px;">
          <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted, #64748b);">From:</span>
          <input type="date" name="report_start" value="<?php echo esc_attr( $start_date_val ); ?>">
        </div>

        <div style="display:flex; align-items:center; gap:6px;">
          <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted, #64748b);">To:</span>
          <input type="date" name="report_end" value="<?php echo esc_attr( $end_date_val ); ?>">
        </div>

        <button type="submit" class="button button-small" style="font-weight:800; background:#0f172a; color:#fff; border-color:#0f172a;"><?php esc_html_e( 'Apply Range', 'bsc-cinema-manager' ); ?></button>
      </div>

      <?php if ( 'ticket' === $report_subtab ) : ?>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
          <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted, #64748b);">Movie:</span>
            <select id="reportFilterMovie" onchange="filterReportTable()">
              <option value="">-- All Movies --</option>
              <?php foreach ( $filter_movies as $fm ) : ?>
                <option value="<?php echo esc_attr( $fm ); ?>"><?php echo esc_html( $fm ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:var(--cinema-text-muted, #64748b);">Showtime:</span>
            <select id="reportFilterTime" onchange="filterReportTable()">
              <option value="">-- All Times --</option>
              <?php foreach ( $filter_times as $ft ) : ?>
                <option value="<?php echo esc_attr( $ft ); ?>"><?php echo esc_html( bsc_format_report_time( $ft ) ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endif; ?>

      <div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts&subtab=report&report_tab=' . $report_subtab ) ); ?>" class="button button-small" style="font-weight:700;"><?php esc_html_e( 'Reset', 'bsc-cinema-manager' ); ?></a>
      </div>
    </form>
  <?php endif; ?>

  <?php if ( 'ticket' === $report_subtab ) : ?>
    <!-- Ticket Tab Content -->
    <div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
      <div class="cinema-table-wrap" style="overflow-x:auto;">
        <table class="cinema-data-table" id="bscDataTable" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'FEATURE MOVIE', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'SHOWTIME', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'SCREENING DATE', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:center;"><?php esc_html_e( 'PASSES', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:center;"><?php esc_html_e( 'SEATS', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'CASH', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'ONLINE / bKash', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:right;"><?php esc_html_e( 'GROSS REVENUE', 'bsc-cinema-manager' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if ( ! empty( $movie_income_breakdown ) ) : foreach ( $movie_income_breakdown as $item ) : 
              $seats_array = array_filter( array_map( 'trim', explode( ',', $item->all_booked_seats ) ) );
              $ticket_count = count( $seats_array );
              $formatted_st = bsc_format_report_time( $item->showtime );
            ?>
              <tr data-movie="<?php echo esc_attr( strtolower( $item->movie_title ) ); ?>" data-time="<?php echo esc_attr( strtolower( trim( $item->showtime ) ) ); ?>" style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:12px;"><strong><?php echo esc_html( $item->movie_title ); ?></strong></td>
                <td style="padding:12px;"><span class="cinema-badge-pill active" style="font-size:0.7rem; background:rgba(13,148,136,0.1); color:#0d9488; padding:3px 8px; border-radius:999px; font-weight:700;"><?php echo esc_html( $formatted_st ); ?></span></td>
                <td style="padding:12px;"><small style="color:var(--cinema-text-muted, #64748b); font-weight:700;"><?php echo esc_html( $item->b_date ); ?></small></td>
                <td style="padding:12px; text-align:center; font-weight:700;"><?php echo esc_html( $item->total_passes ); ?></td>
                <td style="padding:12px; text-align:center;"><code style="font-family:var(--cinema-font-mono, monospace);"><?php echo esc_html( $ticket_count ); ?></code></td>
                <td style="padding:12px;">
                  <span style="color:#059669; font-weight:700; font-family:var(--cinema-font-mono, monospace);">
                    <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $item->cash_revenue, 2 ) ); ?>
                  </span>
                </td>
                <td style="padding:12px;">
                  <span style="color:#d946ef; font-weight:700; font-family:var(--cinema-font-mono, monospace);">
                    <?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $item->online_revenue, 2 ) ); ?>
                  </span>
                </td>
                <td style="padding:12px; text-align:right; font-weight:900; color:#0d9488; font-family:var(--cinema-font-mono, monospace); font-size:1.05rem;">
                  +<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $item->gross_revenue, 2 ) ); ?>
                </td>
              </tr>
            <?php endforeach; else : ?>
              <tr id="noReportRow">
                <td colspan="8" style="text-align:center; padding:3rem; color:var(--cinema-text-muted, #64748b);">
                  <?php esc_html_e( 'No movie ticket income recorded for this date range.', 'bsc-cinema-manager' ); ?>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php elseif ( 'lifetime' === $report_subtab ) : ?>
    <!-- Catalog Performance Tab -->
    <div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
      <div class="cinema-panel-header" style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0;">
        <div>
          <h3 class="cinema-panel-title" style="margin:0; font-size:1.15rem; font-weight:900; color:#0f172a;">
            <?php esc_html_e( 'All Movies Lifetime Box-Office Performance', 'bsc-cinema-manager' ); ?>
          </h3>
          <span style="font-size:0.78rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'Comprehensive historical revenue comparison across the entire movie catalog.', 'bsc-cinema-manager' ); ?></span>
        </div>
      </div>

      <div class="cinema-table-wrap" style="overflow-x:auto;">
        <table class="cinema-data-table" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
              <th style="padding:10px 12px; width:60px; text-align:left;"><?php esc_html_e( 'POSTER', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'MOVIE TITLE', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:center;"><?php esc_html_e( 'LIFETIME PASSES', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; width:30%; text-align:left;"><?php esc_html_e( 'LIFETIME REVENUE PROGRESS', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:right;"><?php esc_html_e( 'TOTAL GROSS EARNINGS', 'bsc-cinema-manager' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if ( ! empty( $lifetime_movies_stats ) ) : foreach ( $lifetime_movies_stats as $stat ) : 
              $pct = round( ( (float) $stat->lifetime_revenue / (float) $max_lifetime_rev ) * 100, 1 );
            ?>
              <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:12px;">
                  <img src="<?php echo esc_url( $stat->poster_url ?: 'https://via.placeholder.com/44x60?text=Film' ); ?>" alt="" style="width:40px; height:55px; border-radius:4px; object-fit:cover;">
                </td>
                <td style="padding:12px;"><strong><?php echo esc_html( $stat->movie_title ); ?></strong></td>
                <td style="padding:12px; text-align:center;">
                  <span class="cinema-badge-pill active" style="font-size:0.7rem; background:rgba(13,148,136,0.1); color:#0d9488; padding:3px 8px; border-radius:999px; font-weight:700;"><?php echo esc_html( $stat->lifetime_passes ); ?> Passes</span>
                </td>
                <td style="padding:12px;">
                  <div style="display:flex; justify-content:space-between; font-size:0.78rem; font-weight:800; color:var(--cinema-text-muted, #64748b);">
                    <span><?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $stat->lifetime_revenue, 2 ) ); ?></span>
                    <span><?php echo esc_html( $pct ); ?>%</span>
                  </div>
                  <div class="bsc-lifetime-progress-wrap">
                    <div class="bsc-lifetime-progress-fill" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
                  </div>
                </td>
                <td style="padding:12px; text-align:right; font-weight:900; color:#059669; font-family:var(--cinema-font-mono, monospace); font-size:1.1rem;">
                  +<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $stat->lifetime_revenue, 2 ) ); ?>
                </td>
              </tr>
            <?php endforeach; else : ?>
              <tr>
                <td colspan="5" style="text-align:center; padding:3rem; color:var(--cinema-text-muted, #64748b);">
                  <?php esc_html_e( 'No movies found in catalog.', 'bsc-cinema-manager' ); ?>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php elseif ( 'income' === $report_subtab ) : ?>
    <!-- Income Tab Report -->
    <div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
      <div class="cinema-panel-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0;">
        <h3 class="cinema-panel-title" style="margin:0; font-size:1.15rem; font-weight:900; color:#0f172a; display:flex; align-items:center; gap:8px;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          <?php esc_html_e( 'Direct Revenue & Concessions Report', 'bsc-cinema-manager' ); ?>
        </h3>
      </div>
      <div class="cinema-table-wrap" style="overflow-x:auto;">
        <table class="cinema-data-table" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'REF TOKEN', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'PARTICULARS', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'CHANNEL', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'DATE', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:right;"><?php esc_html_e( 'CREDIT AMOUNT', 'bsc-cinema-manager' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if ( ! empty( $report_incomes ) ) : foreach ( $report_incomes as $inc ) : ?>
              <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:12px;"><span class="cinema-voucher-tag" style="font-family:var(--cinema-font-mono, monospace); background:#f8fafc; padding:2px 6px; border:1px solid #cbd5e1; border-radius:4px;"><?php echo esc_html( $inc->reference_no ); ?></span></td>
                <td style="padding:12px;"><strong><?php echo esc_html( $inc->particulars ); ?></strong></td>
                <td style="padding:12px;"><span class="cinema-badge-payment" style="font-weight:700;"><?php echo esc_html( $inc->method ); ?></span></td>
                <td style="padding:12px;"><small style="color:var(--cinema-text-muted, #64748b); font-weight:700;"><?php echo esc_html( $inc->transaction_date ); ?></small></td>
                <td style="padding:12px; text-align:right; color:#059669; font-weight:900; font-family:var(--cinema-font-mono, monospace);">
                  +<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $inc->amount, 2 ) ); ?>
                </td>
              </tr>
            <?php endforeach; else : ?>
              <tr><td colspan="5" style="text-align:center; padding:3rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'No manual income entries found for this period.', 'bsc-cinema-manager' ); ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <?php elseif ( 'expense' === $report_subtab ) : ?>
    <!-- Expense Tab Report -->
    <div class="cinema-panel" style="margin:0; box-shadow:0 4px 16px rgba(15,23,42,0.03);">
      <div class="cinema-panel-header" style="padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0;">
        <h3 class="cinema-panel-title" style="margin:0; font-size:1.15rem; font-weight:900; color:#0f172a; display:flex; align-items:center; gap:8px;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19 14V6c0-1.1-.9-2-2-2H3c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zm-9-1c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm13-6v11c0 1.1-.9 2-2 2H4v-2h17V7h2z"/></svg>
          <?php esc_html_e( 'Operating Expenses & Payroll Report', 'bsc-cinema-manager' ); ?>
        </h3>
      </div>
      <div class="cinema-table-wrap" style="overflow-x:auto;">
        <table class="cinema-data-table" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1px solid #e2e8f0; background:#f8fafc;">
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'REF TOKEN', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'PARTICULARS', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'CHANNEL', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:left;"><?php esc_html_e( 'DATE', 'bsc-cinema-manager' ); ?></th>
              <th style="padding:10px 12px; text-align:right;"><?php esc_html_e( 'DEBIT AMOUNT', 'bsc-cinema-manager' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if ( ! empty( $report_expenses ) ) : foreach ( $report_expenses as $exp ) : ?>
              <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:12px;"><span class="cinema-voucher-tag" style="font-family:var(--cinema-font-mono, monospace); background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:2px 6px; border-radius:4px;"><?php echo esc_html( $exp->reference_no ); ?></span></td>
                <td style="padding:12px;"><strong><?php echo esc_html( $exp->particulars ); ?></strong></td>
                <td style="padding:12px;"><span class="cinema-badge-payment" style="font-weight:700;"><?php echo esc_html( $exp->method ); ?></span></td>
                <td style="padding:12px;"><small style="color:var(--cinema-text-muted, #64748b); font-weight:700;"><?php echo esc_html( $exp->transaction_date ); ?></small></td>
                <td style="padding:12px; text-align:right; color:#ef4444; font-weight:900; font-family:var(--cinema-font-mono, monospace);">
                  -<?php echo esc_html( $currency_symbol . ' ' . number_format( (float) $exp->amount, 2 ) ); ?>
                </td>
              </tr>
            <?php endforeach; else : ?>
              <tr><td colspan="5" style="text-align:center; padding:3rem; color:var(--cinema-text-muted, #64748b);"><?php esc_html_e( 'No operational expenses found for this period.', 'bsc-cinema-manager' ); ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
  function filterReportTable() {
    const selMovie = document.getElementById('reportFilterMovie') ? document.getElementById('reportFilterMovie').value.toLowerCase().trim() : '';
    const selTime  = document.getElementById('reportFilterTime') ? document.getElementById('reportFilterTime').value.toLowerCase().trim() : '';

    const rows = document.querySelectorAll("#bscDataTable tbody tr");
    let visibleCount = 0;

    rows.forEach(row => {
      if (row.id === 'noReportRow') return;

      const rMovie = (row.getAttribute('data-movie') || '').toLowerCase().trim();
      const rTime  = (row.getAttribute('data-time') || '').toLowerCase().trim();

      let matchMovie = !selMovie || rMovie.indexOf(selMovie) !== -1;
      let matchTime  = !selTime || rTime === selTime;

      if (matchMovie && matchTime) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });
  }
</script>