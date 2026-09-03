<?php
/**
 * Plugin Name:     Big Screen Cinemas - Master Controller Suite
 * Plugin URI:      https://bigscreencinemas.com
 * Description:     Cinema Multiplex Management Suite with 4K Media Uploads, CRUD Movie Management, Dynamic Scheduling, and Turnstile Booking Engine.
 * Version:         12.1.0
 * Author:          Abdullah Nahian
 * Text Domain:     bcs-screen-manager
 * Domain Path:     /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BSC_VERSION', '12.1.0' );
define( 'BSC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BSC_ADMIN_CAP', 'manage_options' );

// Load Database Layer exclusively from the inc folder
$bsc_db_file = BSC_PLUGIN_PATH . 'inc/database.php';
if ( is_readable( $bsc_db_file ) ) {
    require_once $bsc_db_file;
}

// Load all admin page modules
$bsc_modules = array(
    'admin/page-dashboard.php',
    'admin/page-booking.php',
    'admin/page-movies.php',
    'admin/page-gallery.php',
    'admin/page-customers.php',
    'admin/page-accounts.php',
    'admin/page-users.php',
    'admin/page-settings.php',
);

foreach ( $bsc_modules as $bsc_file ) {
    $bsc_path = BSC_PLUGIN_PATH . $bsc_file;
    if ( is_readable( $bsc_path ) ) {
        require_once $bsc_path;
    }
}

// Plugin Activation Hook
register_activation_hook( __FILE__, 'bsc_activate_plugin' );
function bsc_activate_plugin() {
    if ( function_exists( 'bsc_create_database_tables' ) ) {
        bsc_create_database_tables();
    }
}

function bsc_generate_secure_token( $prefix = 'BSC' ) {
    return sprintf( '#%s-%s-TX', sanitize_key( $prefix ), strtoupper( wp_generate_password( 8, false, false ) ) );
}

// Shell Layout: Open Wrapper & Sidebar
function bsc_render_page_shell_open( $current_page ) {
    $logout_url = wp_logout_url( home_url() );
    ?>
    <style>
        #wpcontent { padding-left: 0 !important; }
        #wpbody-content { padding-bottom: 0 !important; }
        .notice, #wpfooter { display: none !important; }

        :root {
            --cinema-bg-canvas: #f0f4f8;
            --cinema-sidebar-bg: #ffffff;
            --cinema-card-bg: #ffffff;
            --cinema-primary-dark: #0f1e24;
            --cinema-accent-teal: #0d9488;
            --cinema-accent-green: #10b981;
            --cinema-accent-amber: #f59e0b;
            --cinema-accent-red: #ef4444;
            --cinema-accent-blue: #3b82f6;
            --cinema-border: #e2e8f0;
            --cinema-border-light: #f1f5f9;
            --cinema-text-main: #0f172a;
            --cinema-text-muted: #64748b;
            --cinema-radius-sm: 8px;
            --cinema-radius-md: 14px;
            --cinema-radius-lg: 20px;
            --cinema-font-main: 'Plus Jakarta Sans', system-ui, sans-serif;
            --cinema-font-mono: 'Space Grotesk', monospace;
        }

        .bsc-app-wrap {
            display: flex;
            min-height: calc(100vh - 32px);
            background-color: var(--cinema-bg-canvas);
            font-family: var(--cinema-font-main);
            color: var(--cinema-text-main);
            box-sizing: border-box;
        }

        .bsc-sidebar {
            width: 260px;
            background: var(--cinema-sidebar-bg);
            border-right: 1px solid var(--cinema-border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .bsc-sidebar-header {
            padding: 1.5rem 1.25rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--cinema-border-light);
        }

        .bsc-admin-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .bsc-avatar-sm {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--cinema-primary-dark);
            border: 2px solid var(--cinema-accent-teal);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 0.85rem;
        }

        .bsc-admin-name {
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--cinema-text-main);
            line-height: 1.2;
        }

        .bsc-admin-role {
            font-size: 0.75rem;
            color: var(--cinema-text-muted);
        }

        .bsc-nav-list {
            list-style: none;
            padding: 1rem 0.75rem;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .bsc-nav-item a {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.75rem 1rem;
            color: var(--cinema-text-muted);
            font-weight: 700;
            font-size: 0.88rem;
            border-radius: var(--cinema-radius-sm);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .bsc-nav-item a:hover {
            background: #f8fafc;
            color: var(--cinema-text-main);
        }

        .bsc-nav-item.active a {
            background: #064e3b;
            color: #ffffff;
        }

        .bsc-nav-item svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        .bsc-sidebar-bottom {
            padding: 1rem 0.75rem;
            border-top: 1px solid var(--cinema-border-light);
            margin-top: auto;
        }

        .bsc-canvas {
            flex: 1;
            padding: 2rem 2.5rem;
            overflow-y: auto;
        }

        .cinema-hero-banner {
            background: linear-gradient(135deg, #0b2228 0%, #063d35 100%);
            color: #ffffff;
            border-radius: var(--cinema-radius-lg);
            padding: 1.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px -5px rgba(6, 61, 53, 0.2);
        }

        .cinema-hero-user-block {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .cinema-hero-avatar-large {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            border: 3px solid #14b8a6;
            object-fit: cover;
        }

        .cinema-hero-meta h1 {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            margin: 0.2rem 0;
            color: #fff;
        }

        .cinema-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cinema-badge-pill.active {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }

        .cinema-hero-stats-strip {
            display: flex;
            gap: 1.25rem;
            font-size: 0.85rem;
            color: #cbd5e1;
            margin-top: 0.25rem;
        }

        .cinema-clock-card {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.85rem 1.25rem;
            border-radius: var(--cinema-radius-md);
            text-align: right;
        }

        .cinema-clock-card .date {
            font-size: 0.8rem;
            color: #94a3b8;
            font-weight: 700;
        }

        .cinema-clock-card .time {
            font-size: 1.45rem;
            font-weight: 900;
            font-family: var(--cinema-font-mono);
            color: #ffffff;
        }

        .cinema-panel {
            background: var(--cinema-card-bg);
            border: 1px solid var(--cinema-border);
            border-radius: var(--cinema-radius-md);
            padding: 1.75rem;
            margin-bottom: 2rem;
        }

        .cinema-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .cinema-panel-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--cinema-text-main);
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0;
        }

        .cinema-table-wrap { overflow-x: auto; }
        .cinema-data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }
        .cinema-data-table th {
            padding: 0.85rem 1rem;
            background: #f8fafc;
            color: var(--cinema-text-muted);
            font-size: 0.72rem;
            text-transform: uppercase;
            border-bottom: 1px solid var(--cinema-border);
        }
        .cinema-data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--cinema-border-light);
            vertical-align: middle;
        }

        .cinema-voucher-tag {
            background: #fdf2f8;
            border: 1px solid #fbcfe8;
            color: #db2777;
            font-family: var(--cinema-font-mono);
            font-size: 0.75rem;
            font-weight: 800;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }

        .cinema-badge-payment {
            background: #f1f5f9;
            padding: 0.2rem 0.55rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--cinema-text-muted);
        }

        .bsc-form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .bsc-form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .bsc-form-group { margin-bottom: 1.15rem; }
        .bsc-form-group label { display: block; font-weight: 800; font-size: 0.82rem; color: var(--cinema-text-main); margin-bottom: 0.4rem; }
        .bsc-form-group input, .bsc-form-group textarea, .bsc-form-group select {
            width: 100%; padding: 0.7rem 0.85rem; border: 1px solid var(--cinema-border); border-radius: var(--cinema-radius-sm); font-size: 0.9rem; background: #fff; font-family: inherit;
        }

        .bsc-media-uploader-box {
            border: 2px dashed var(--cinema-border);
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: var(--cinema-radius-sm);
            text-align: center;
        }

        .bsc-media-preview-img {
            max-width: 120px;
            height: auto;
            border-radius: 6px;
            margin: 0.5rem auto;
            border: 1px solid var(--cinema-border);
        }

        .bsc-media-preview-video {
            max-width: 100%;
            max-height: 160px;
            margin-top: 0.75rem;
            border-radius: 6px;
        }

        .bsc-repeater-row {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 1.5fr auto;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            align-items: center;
            background: #fff;
            padding: 0.75rem;
            border: 1px solid var(--cinema-border);
            border-radius: 6px;
        }

        .bsc-cast-photo-thumb {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--cinema-border);
        }

        .bsc-btn-remove-row {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            padding: 0.6rem 0.8rem;
            border-radius: 6px;
            font-weight: 800;
            cursor: pointer;
        }

        .bsc-subtabs-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--cinema-border);
            padding-bottom: 10px;
        }
        .bsc-subtab-btn {
            padding: 8px 18px;
            background: #e2e8f0;
            color: #334155;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .bsc-subtab-btn.active {
            background: #0d9488;
            color: #fff;
        }

        .bsc-action-btn-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .bsc-btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 10px;
            font-size: 0.75rem;
            font-weight: 800;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .bsc-btn-action.view { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .bsc-btn-action.view:hover { background: #2563eb; color: #fff; }

        .bsc-btn-action.edit { background: #fefce8; color: #ca8a04; border: 1px solid #fef08a; }
        .bsc-btn-action.edit:hover { background: #ca8a04; color: #fff; }

        .bsc-btn-action.delete { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .bsc-btn-action.delete:hover { background: #dc2626; color: #fff; }

        .bsc-view-hero-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .bsc-view-poster-wrap img {
            width: 100%;
            border-radius: var(--cinema-radius-md);
            border: 2px solid var(--cinema-border);
            box-shadow: 0 10px 20px rgba(0,0,0,0.06);
            display: block;
        }

        .bsc-spec-pill-deck {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 0.85rem 0 1.25rem 0;
        }

        .bsc-spec-pill {
            background: #f1f5f9;
            border: 1px solid var(--cinema-border);
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--cinema-text-main);
        }

        .bsc-view-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .bsc-stat-card-mini {
            background: #f8fafc;
            border: 1px solid var(--cinema-border);
            padding: 0.85rem 1rem;
            border-radius: 8px;
        }

        .bsc-stat-card-mini .k {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--cinema-text-muted);
        }

        .bsc-stat-card-mini .v {
            font-size: 1.05rem;
            font-weight: 800;
            margin-top: 2px;
            color: var(--cinema-text-main);
        }

        .bsc-cast-grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .bsc-cast-card-mini {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            background: #f8fafc;
            border: 1px solid var(--cinema-border);
            padding: 0.75rem;
            border-radius: 8px;
        }

        .bsc-cast-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--cinema-accent-teal);
        }
    </style>

    <div class="bsc-app-wrap">
        <!-- SIDEBAR -->
        <aside class="bsc-sidebar">
            <div>
                <div class="bsc-sidebar-header">
                    <div class="bsc-admin-profile">
                        <div class="bsc-avatar-sm">AN</div>
                        <div>
                            <div class="bsc-admin-name">Abdullah Nahian</div>
                            <div class="bsc-admin-role"><?php esc_html_e( 'Theater Administrator', 'bsc-cinema-manager' ); ?></div>
                        </div>
                    </div>
                </div>

                <ul class="bsc-nav-list">
                    <li class="bsc-nav-item <?php echo ( 'bsc-dashboard' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-dashboard' ) ); ?>"><svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg><span><?php esc_html_e( 'Dashboard', 'bsc-cinema-manager' ); ?></span></a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-booking' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking' ) ); ?>"><svg viewBox="0 0 24 24"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg><span><?php esc_html_e( 'Booking', 'bsc-cinema-manager' ); ?></span></a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-movies' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-movies' ) ); ?>"><svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg><span><?php esc_html_e( 'Movies', 'bsc-cinema-manager' ); ?></span></a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-gallery' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-gallery' ) ); ?>"><svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg><span><?php esc_html_e( 'Gallery', 'bsc-cinema-manager' ); ?></span></a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-customers' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-customers' ) ); ?>"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg><span><?php esc_html_e( 'Customers', 'bsc-cinema-manager' ); ?></span></a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-accounts' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts' ) ); ?>"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm0 16H5V5h14v14z"/></svg><span><?php esc_html_e( 'Accounts', 'bsc-cinema-manager' ); ?></span></a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-users' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-users' ) ); ?>"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg><span><?php esc_html_e( 'Users', 'bsc-cinema-manager' ); ?></span></a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-settings' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-settings' ) ); ?>"><svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg><span><?php esc_html_e( 'Settings', 'bsc-cinema-manager' ); ?></span></a>
                    </li>
                </ul>
            </div>
            <div class="bsc-sidebar-bottom">
                <ul class="bsc-nav-list" style="padding:0;">
                    <li class="bsc-nav-item"><a href="<?php echo esc_url( $logout_url ); ?>" style="color:var(--cinema-accent-red);"><svg viewBox="0 0 24 24"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg><span><?php esc_html_e( 'Logout', 'bsc-cinema-manager' ); ?></span></a></li>
                </ul>
            </div>
        </aside>

        <!-- CANVAS BODY -->
        <main class="bsc-canvas">
    <?php
}

// Shell Layout: Close Wrapper
function bsc_render_page_shell_close() {
    ?>
        </main>
    </div>
    <?php
}

// Master Admin Menu Registration
add_action( 'admin_menu', 'bsc_register_master_menu', 99 );
function bsc_register_master_menu() {
    $parent_slug = 'bsc-dashboard';

    add_menu_page(
        __( 'Movie Theater', 'bsc-cinema-manager' ),
        __( 'Movie Theater', 'bsc-cinema-manager' ),
        BSC_ADMIN_CAP,
        $parent_slug,
        'bsc_render_dashboard_page',
        'dashicons-video-alt3',
        6
    );

    add_submenu_page( $parent_slug, __( 'Dashboard', 'bsc-cinema-manager' ), __( 'Dashboard', 'bsc-cinema-manager' ), BSC_ADMIN_CAP, 'bsc-dashboard', 'bsc_render_dashboard_page' );
    add_submenu_page( $parent_slug, __( 'Booking Manager', 'bsc-cinema-manager' ), __( 'Booking', 'bsc-cinema-manager' ), BSC_ADMIN_CAP, 'bsc-booking', 'bsc_render_booking_page' );
    add_submenu_page( $parent_slug, __( 'Movies & Shows', 'bsc-cinema-manager' ), __( 'Movies', 'bsc-cinema-manager' ), BSC_ADMIN_CAP, 'bsc-movies', 'bsc_render_movies_page' );
    add_submenu_page( $parent_slug, __( 'Gallery Manager', 'bsc-cinema-manager' ), __( 'Gallery', 'bsc-cinema-manager' ), BSC_ADMIN_CAP, 'bsc-gallery', 'bsc_render_gallery_page' );
    add_submenu_page( $parent_slug, __( 'Customers Directory', 'bsc-cinema-manager' ), __( 'Customers', 'bsc-cinema-manager' ), BSC_ADMIN_CAP, 'bsc-customers', 'bsc_render_customers_page' );
    add_submenu_page( $parent_slug, __( 'Financial Accounts', 'bsc-cinema-manager' ), __( 'Accounts', 'bsc-cinema-manager' ), BSC_ADMIN_CAP, 'bsc-accounts', 'bsc_render_accounts_page' );
    add_submenu_page( $parent_slug, __( 'Staff & Crew', 'bsc-cinema-manager' ), __( 'Users', 'bsc-cinema-manager' ), BSC_ADMIN_CAP, 'bsc-users', 'bsc_render_users_page' );
    add_submenu_page( $parent_slug, __( 'Theater Settings', 'bsc-cinema-manager' ), __( 'Settings', 'bsc-cinema-manager' ), BSC_ADMIN_CAP, 'bsc-settings', 'bsc_render_settings_page' );
    add_submenu_page( $parent_slug, __( 'Sign Out', 'bsc-cinema-manager' ), '<span style="color:#ef4444;">🚪 ' . esc_html__( 'Logout', 'bsc-cinema-manager' ) . '</span>', BSC_ADMIN_CAP, 'bsc-logout', 'bsc_handle_logout_screen' );
}

function bsc_handle_logout_screen() {
    $logout_url = wp_logout_url( home_url() );
    ?>
    <script type="text/javascript">
        window.location.href = <?php echo wp_json_encode( $logout_url ); ?>;
    </script>
    <div class="wrap" style="padding: 40px; color: #fff; font-family: sans-serif;">
        <p><?php esc_html_e( 'Logging out of Big Screen Cinemas...', 'bsc-cinema-manager' ); ?></p>
        <p><a href="<?php echo esc_url( $logout_url ); ?>" class="button button-primary"><?php esc_html_e( 'Click here if you are not redirected automatically', 'bsc-cinema-manager' ); ?></a></p>
    </div>
    <?php
}

function bsc_is_cinema_screen() {
    if ( ! is_admin() ) {
        return false;
    }
    $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
    return ( 0 === strpos( $page, 'bsc-' ) );
}

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_admin_assets' );
function bsc_enqueue_admin_assets( $hook ) {
    if ( ! bsc_is_cinema_screen() ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style(
        'bsc-admin-fonts',
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap',
        array(),
        null
    );

    wp_register_style( 'bsc-unified-admin', false );
    wp_enqueue_style( 'bsc-unified-admin' );
    wp_add_inline_style( 'bsc-unified-admin', '
        .bsc-admin-wrap { margin-top: 20px; font-family: "Plus Jakarta Sans", sans-serif; }
        .bsc-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .bsc-card h2 { margin-top: 0; font-family: "Space Grotesk", sans-serif; font-size: 1.5rem; color: #0f172a; }
        .bsc-empty-state { background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 6px; padding: 40px; text-align: center; }
    ' );
}

add_action( 'admin_head', 'bsc_apply_cinema_mode_css' );
function bsc_apply_cinema_mode_css() {
    if ( ! bsc_is_cinema_screen() ) {
        return;
    }
    ?>
    <style id="bsc-cinema-fullscreen-styles">
        #adminmenumain,
        #adminmenuback,
        #adminmenuwrap,
        #wpadminbar,
        #wpfooter {
            display: none !important;
        }
        html.wp-toolbar {
            padding-top: 0 !important;
        }
        #wpcontent,
        #wpbody-content {
            margin-left: 0 !important;
            padding: 0 !important;
            min-height: 100vh !important;
        }
        body.wp-admin {
            background: #070e11 !important;
            overflow-x: hidden;
        }
        .bsc-floating-escape-btn {
            position: fixed !important;
            bottom: 18px !important;
            left: 18px !important;
            right: auto !important;
            z-index: 99999 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            background: #0f172a !important;
            color: #f8fafc !important;
            padding: 8px 14px !important;
            border-radius: 8px !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.6) !important;
            backdrop-filter: blur(8px) !important;
            cursor: pointer !important;
            transition: all 0.2s ease-in-out !important;
        }
        .bsc-floating-escape-btn:hover {
            background: #1e293b !important;
            color: #38bdf8 !important;
            border-color: #38bdf8 !important;
            transform: translateY(-2px) !important;
        }
        .bsc-floating-escape-btn svg {
            width: 15px;
            height: 15px;
            fill: currentColor;
        }
    </style>
    <?php
}

// Custom /a/admin routing
add_action( 'init', 'bsc_handle_custom_admin_slug' );
function bsc_handle_custom_admin_slug() {
    $request_uri = untrailingslashit( strtok( $_SERVER['REQUEST_URI'] ?? '', '?' ) );

    if ( '/a/admin' === $request_uri ) {
        if ( is_user_logged_in() ) {
            wp_safe_redirect( admin_url( 'admin.php?page=bsc-dashboard' ) );
        } else {
            wp_safe_redirect( wp_login_url( admin_url( 'admin.php?page=bsc-dashboard' ) ) );
        }
        exit;
    }
}

// Authentication & Profile Hooks
add_action( 'admin_post_nopriv_bsc_custom_login', 'bsc_handle_custom_login' );
add_action( 'admin_post_bsc_custom_login', 'bsc_handle_custom_login' );
function bsc_handle_custom_login() {
    $creds = array(
        'user_login'    => sanitize_text_field( wp_unslash( $_POST['log'] ?? '' ) ),
        'user_password' => $_POST['pwd'] ?? '',
        'remember'      => ! empty( $_POST['rememberme'] ),
    );

    $user = wp_signon( $creds, is_ssl() );

    if ( is_wp_error( $user ) ) {
        $login_page = home_url( '/account/' );
        wp_safe_redirect( add_query_arg( 'login', 'failed', $login_page ) );
        exit;
    }

    $redirect_url = ! empty( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/account/' );
    wp_safe_redirect( $redirect_url );
    exit;
}

add_filter( 'login_redirect', 'bsc_full_screen_login_redirect', 99, 3 );
function bsc_full_screen_login_redirect( $redirect_to, $request, $user ) {
    if ( $user instanceof WP_User && user_can( $user, BSC_ADMIN_CAP ) ) {
        if ( ! empty( $request ) && false !== strpos( $request, 'wp-admin' ) ) {
            return $request;
        }
        return admin_url( 'admin.php?page=bsc-dashboard' );
    }
    return ! empty( $request ) ? $request : home_url( '/account/' );
}

add_action( 'admin_post_nopriv_bsc_custom_register', 'bsc_handle_custom_register' );
add_action( 'admin_post_bsc_custom_register', 'bsc_handle_custom_register' );
function bsc_handle_custom_register() {
    $name         = sanitize_text_field( wp_unslash( $_POST['reg_name'] ?? '' ) );
    $email        = sanitize_email( wp_unslash( $_POST['reg_email'] ?? '' ) );
    $phone        = sanitize_text_field( wp_unslash( $_POST['reg_phone'] ?? '' ) );
    $password     = $_POST['reg_pass'] ?? '';
    $redirect_to  = ! empty( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : home_url( '/account/' );

    if ( empty( $email ) || empty( $password ) || empty( $name ) ) {
        wp_safe_redirect( home_url( '/account/?register=missing_fields' ) );
        exit;
    }

    if ( email_exists( $email ) || username_exists( $email ) ) {
        $login_page = home_url( '/account/' );
        wp_safe_redirect( add_query_arg( 'register', 'exists', $login_page ) );
        exit;
    }

    $user_id = wp_create_user( $email, $password, $email );
    if ( is_wp_error( $user_id ) ) {
        wp_safe_redirect( home_url( '/account/?register=error' ) );
        exit;
    }

    wp_update_user( array(
        'ID'           => $user_id,
        'display_name' => $name,
        'first_name'   => $name,
    ) );

    if ( ! empty( $phone ) ) {
        update_user_meta( $user_id, 'phone_number', $phone );
    }

    $creds = array(
        'user_login'    => $email,
        'user_password' => $password,
        'remember'      => true,
    );
    $logged_user = wp_signon( $creds, is_ssl() );

    if ( is_wp_error( $logged_user ) ) {
        wp_safe_redirect( home_url( '/account/' ) );
        exit;
    }

    wp_safe_redirect( $redirect_to );
    exit;
}

add_action( 'init', 'bsc_handle_google_oauth_callback' );
function bsc_handle_google_oauth_callback() {
    if ( ! isset( $_GET['bsc_auth'] ) || 'google_callback' !== $_GET['bsc_auth'] ) {
        return;
    }

    $client_id     = get_option( 'bsc_google_client_id', '' );
    $client_secret = get_option( 'bsc_google_client_secret', '' );
    $redirect_uri  = home_url( '/account/?bsc_auth=google_callback' );

    $state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
    if ( ! wp_verify_nonce( $state, 'bsc_google_oauth_state' ) ) {
        wp_safe_redirect( add_query_arg( 'auth_error', 'invalid_state', home_url( '/login/' ) ) );
        exit;
    }

    if ( empty( $client_id ) || empty( $client_secret ) || ! isset( $_GET['code'] ) ) {
        wp_safe_redirect( add_query_arg( 'auth_error', 'missing_credentials', home_url( '/login/' ) ) );
        exit;
    }

    $code = sanitize_text_field( wp_unslash( $_GET['code'] ) );

    $token_response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
        'timeout' => 15,
        'body'    => array(
            'code'          => $code,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'grant_type'    => 'authorization_code',
        ),
    ) );

    if ( is_wp_error( $token_response ) ) {
        wp_safe_redirect( home_url( '/login/?login=network_error' ) );
        exit;
    }

    $token_body = json_decode( wp_remote_retrieve_body( $token_response ), true );
    if ( empty( $token_body['access_token'] ) ) {
        wp_safe_redirect( home_url( '/login/?login=token_error' ) );
        exit;
    }

    $profile_response = wp_remote_get( 'https://www.googleapis.com/oauth2/v3/userinfo', array(
        'timeout' => 15,
        'headers' => array( 'Authorization' => 'Bearer ' . sanitize_text_field( $token_body['access_token'] ) ),
    ) );

    if ( is_wp_error( $profile_response ) ) {
        wp_safe_redirect( home_url( '/login/?login=profile_error' ) );
        exit;
    }

    $profile = json_decode( wp_remote_retrieve_body( $profile_response ), true );
    if ( empty( $profile['email'] ) ) {
        wp_safe_redirect( home_url( '/login/?login=no_email' ) );
        exit;
    }

    $email = sanitize_email( $profile['email'] );
    $name  = sanitize_text_field( $profile['name'] ?? 'Google User' );
    $user  = get_user_by( 'email', $email );

    if ( ! $user ) {
        $username = sanitize_user( current( explode( '@', $email ) ) . '_' . wp_rand( 100, 999 ), true );
        $password = wp_generate_password( 24, true, true );
        $user_id  = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_safe_redirect( home_url( '/login/?login=create_failed' ) );
            exit;
        }

        update_user_meta( $user_id, 'first_name', $name );
        $user = get_user_by( 'id', $user_id );
    }

    wp_set_current_user( $user->ID, $user->user_login );
    wp_set_auth_cookie( $user->ID, true );
    do_action( 'wp_login', $user->user_login, $user );

    wp_safe_redirect( home_url( '/account/' ) );
    exit;
}

add_filter( 'login_url', 'bsc_redirect_to_custom_login', 10, 3 );
function bsc_redirect_to_custom_login( $login_url, $redirect, $force_reauth ) {
    if ( is_admin() || ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) ) {
        return $login_url;
    }

    $custom_login = home_url( '/custom-login/' );

    if ( ! empty( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'custom-login' ) ) {
        return $login_url;
    }

    if ( ! empty( $redirect ) && false === strpos( $redirect, 'custom-login' ) ) {
        $custom_login = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $custom_login );
    }

    return $custom_login;
}