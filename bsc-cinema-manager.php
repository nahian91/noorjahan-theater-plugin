<?php
/**
 * Plugin Name:       Big Screen Cinemas - Master Controller Suite
 * Plugin URI:        https://bigscreencinemas.com
 * Description:       Cinema Multiplex Management Suite with 4K Media Uploads, CRUD Movie Management, Dynamic Scheduling, and Turnstile Booking Engine.
 * Version:           12.1.0
 * Author:            Abdullah Nahian
 * Text Domain:       bcs-screen-manager
 * Domain Path:       /languages
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

// =============================================================================
// DATABASE LAYER & MODULE INCLUDES
// =============================================================================
require_once BSC_PLUGIN_PATH . 'inc/database.php';

require_once BSC_PLUGIN_PATH . 'admin/page-dashboard.php';
require_once BSC_PLUGIN_PATH . 'admin/page-booking.php';
require_once BSC_PLUGIN_PATH . 'admin/page-movies.php';
require_once BSC_PLUGIN_PATH . 'admin/page-gallery.php';
require_once BSC_PLUGIN_PATH . 'admin/page-customers.php';
require_once BSC_PLUGIN_PATH . 'admin/page-accounts.php';
require_once BSC_PLUGIN_PATH . 'admin/page-users.php';
require_once BSC_PLUGIN_PATH . 'admin/page-settings.php';

// =============================================================================
// ACTIVATION & LIFECYCLE HOOKS
// =============================================================================
register_activation_hook( __FILE__, 'bsc_activate_plugin' );
function bsc_activate_plugin() {
    if ( function_exists( 'bsc_create_database_tables' ) ) {
        bsc_create_database_tables();
    }
}

// =============================================================================
// CORE UTILITIES & HELPERS
// =============================================================================
function bsc_generate_secure_token( $prefix = 'BSC' ) {
    return sprintf( '#%s-%s-TX', sanitize_key( $prefix ), strtoupper( wp_generate_password( 8, false, false ) ) );
}

function bsc_is_cinema_screen() {
    if ( ! is_admin() ) {
        return false;
    }
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    return ( 0 === strpos( $page, 'bsc-' ) );
}

function bsc_get_custom_logo_url() {
    $extensions = array( 'svg', 'png', 'webp', 'jpg' );
    foreach ( $extensions as $ext ) {
        $file_path = BSC_PLUGIN_PATH . 'assets/logo.' . $ext;
        if ( file_exists( $file_path ) ) {
            return BSC_PLUGIN_URL . 'assets/logo.' . $ext;
        }
    }
    return '';
}

// =============================================================================
// LOGIN REDIRECTION & ROUTING
// =============================================================================
add_filter( 'login_redirect', 'bsc_custom_login_redirect', 999, 3 );
function bsc_custom_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
    if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
        return $redirect_to;
    }

    if ( user_can( $user, BSC_ADMIN_CAP ) ) {
        return admin_url( 'admin.php?page=bsc-dashboard' );
    }

    return home_url();
}

add_action( 'init', 'bsc_handle_custom_admin_slug' );
function bsc_handle_custom_admin_slug() {
    if ( empty( $_SERVER['REQUEST_URI'] ) ) {
        return;
    }

    $raw_uri    = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
    $path       = wp_parse_url( $raw_uri, PHP_URL_PATH );
    $clean_path = untrailingslashit( (string) $path );

    if ( in_array( $clean_path, array( '/admin', '/a/admin' ), true ) ) {
        $target_url = admin_url( 'admin.php?page=bsc-dashboard' );
        if ( is_user_logged_in() ) {
            wp_safe_redirect( $target_url );
        } else {
            wp_safe_redirect( wp_login_url( $target_url ) );
        }
        exit;
    }
}

// Intercept admin logout page early before rendering headers
add_action( 'admin_init', 'bsc_handle_logout_action' );
function bsc_handle_logout_action() {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( 'bsc-logout' === $page ) {
        wp_safe_redirect( wp_logout_url( home_url() ) );
        exit;
    }
}

// =============================================================================
// LOGIN SCREEN STYLING & SECURE MATH CAPTCHA (OWASP / ANTI-REPLAY)
// =============================================================================
add_action( 'login_enqueue_scripts', 'bsc_enqueue_login_styles' );
function bsc_enqueue_login_styles() {
    wp_enqueue_style(
        'bsc-login-fonts',
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap',
        array(),
        null
    );

    wp_register_style( 'bsc-custom-login', false, array( 'bsc-login-fonts' ), BSC_VERSION );
    wp_enqueue_style( 'bsc-custom-login' );

    $logo_url = bsc_get_custom_logo_url();
    $logo_css = ! empty( $logo_url )
        ? "background-image: url('" . esc_url_raw( $logo_url ) . "') !important; background-size: contain !important;"
        : 'background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2314b8a6\'%3E%3Cpath d=\'M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4zm-4.5 10.5v-5l4 2.5-4 2.5z\'/%3E%3C/svg%3E") !important; background-size: 80px 80px !important;';

    $custom_css = "
        body.login {
            background: radial-gradient(circle at top right, #0f373d 0%, #081a20 40%, #03080a 100%) !important;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        body.login::before {
            content: '';
            position: absolute;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(13, 148, 136, 0.18) 0%, rgba(0, 0, 0, 0) 70%);
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            pointer-events: none;
            z-index: 0;
        }
        #login {
            position: relative;
            z-index: 1;
            padding: 30px 20px !important;
            width: 400px !important;
        }
        #login h1 a {
            {$logo_css}
            background-position: center !important;
            background-repeat: no-repeat !important;
            width: 140px !important;
            height: 80px !important;
            margin: 0 auto 12px auto !important;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        #login h1 a:hover { transform: scale(1.05); }
        #login h1::after {
            content: 'BIG SCREEN CINEMAS';
            display: block;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: 0.1em;
            margin-top: 6px;
            text-align: center;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        .login form {
            background: rgba(15, 23, 42, 0.75) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            border-radius: 18px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(20, 184, 166, 0.15) !important;
            padding: 32px 28px !important;
            color: #e2e8f0 !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
        }
        .login form label {
            color: #94a3b8 !important;
            font-weight: 700 !important;
            font-size: 0.82rem !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .login input[type='text'], .login input[type='password'], .login input[type='number'] {
            background: rgba(2, 6, 23, 0.7) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 10px !important;
            color: #ffffff !important;
            font-size: 0.95rem !important;
            padding: 12px 14px !important;
            margin-top: 6px !important;
            box-shadow: none !important;
            transition: all 0.25s ease !important;
        }
        .login input[type='text']:focus, .login input[type='password']:focus, .login input[type='number']:focus {
            border-color: #14b8a6 !important;
            background: rgba(2, 6, 23, 0.95) !important;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.3) !important;
            outline: none !important;
        }
        .bsc-captcha-wrapper {
            margin: 20px 0 14px 0;
            padding: 14px 16px;
            background: rgba(13, 148, 136, 0.08);
            border: 1px dashed rgba(20, 184, 166, 0.45);
            border-radius: 12px;
        }
        .bsc-captcha-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #cbd5e1;
        }
        .bsc-captcha-equation {
            font-family: 'Space Grotesk', monospace;
            background: #0d9488;
            color: #ffffff;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.4);
        }
        .wp-core-ui .button-primary {
            width: 100% !important;
            margin-top: 14px !important;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            border-radius: 10px !important;
            padding: 12px !important;
            height: auto !important;
            font-size: 0.95rem !important;
            letter-spacing: 0.03em;
            transition: all 0.25s ease !important;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.4) !important;
            cursor: pointer !important;
        }
        .wp-core-ui .button-primary:hover {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.5) !important;
        }
        .login #login_error, .login .message {
            background: rgba(15, 23, 42, 0.9) !important;
            border-left: 4px solid #ef4444 !important;
            color: #fca5a5 !important;
            border-radius: 10px !important;
            backdrop-filter: blur(10px) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
        }
        .login .message {
            border-left-color: #14b8a6 !important;
            color: #e2e8f0 !important;
        }
        .login #nav, .login #backtoblog { text-align: center; }
        .login #nav a, .login #backtoblog a {
            color: #64748b !important;
            font-weight: 600;
            font-size: 0.85rem;
            transition: color 0.2s;
        }
        .login #nav a:hover, .login #backtoblog a:hover { color: #14b8a6 !important; }
        .login .privacy-policy-page-link { display: none !important; }
    ";
    wp_add_inline_style( 'bsc-custom-login', $custom_css );
}

add_filter( 'login_headerurl', 'bsc_custom_login_logo_url' );
function bsc_custom_login_logo_url() {
    return home_url();
}

add_filter( 'login_headertext', 'bsc_custom_login_logo_title' );
function bsc_custom_login_logo_title() {
    return get_bloginfo( 'name' );
}

add_action( 'login_form', 'bsc_render_number_captcha_field' );
function bsc_render_number_captcha_field() {
    $num1      = wp_rand( 2, 9 );
    $num2      = wp_rand( 1, 9 );
    $sum       = $num1 + $num2;
    $timestamp = time();

    // Replay-proof signature bound to current timestamp & secure salt
    $hash          = hash_hmac( 'sha256', "{$sum}|{$timestamp}", wp_salt( 'nonce' ) );
    $token_payload = base64_encode( "{$hash}:{$timestamp}" );
    ?>
    <div class="bsc-captcha-wrapper">
        <label class="bsc-captcha-label" for="bsc_captcha_answer">
            <span><?php esc_html_e( 'Human Check', 'bcs-screen-manager' ); ?></span>
            <span class="bsc-captcha-equation"><?php echo esc_html( "{$num1} + {$num2} = ?" ); ?></span>
        </label>
        <input type="number" name="bsc_captcha_answer" id="bsc_captcha_answer" class="input" placeholder="<?php esc_attr_e( 'Answer', 'bcs-screen-manager' ); ?>" required autocomplete="off" />
        <input type="hidden" name="bsc_captcha_token" value="<?php echo esc_attr( $token_payload ); ?>" />
    </div>
    <?php
}

add_filter( 'authenticate', 'bsc_verify_number_captcha', 25, 3 );
function bsc_verify_number_captcha( $user, $username, $password ) {
    if ( ! isset( $_POST['log'] ) ) {
        return $user;
    }

    $answer  = isset( $_POST['bsc_captcha_answer'] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_captcha_answer'] ) ) : '';
    $payload = isset( $_POST['bsc_captcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_captcha_token'] ) ) : '';

    if ( '' === $answer || '' === $payload ) {
        return new WP_Error( 'bsc_captcha_missing', '<strong>' . esc_html__( 'Security Check:', 'bcs-screen-manager' ) . '</strong> ' . esc_html__( 'Please solve the math verification problem.', 'bcs-screen-manager' ) );
    }

    $decoded = base64_decode( $payload, true );
    if ( false === $decoded || false === strpos( $decoded, ':' ) ) {
        return new WP_Error( 'bsc_captcha_invalid', '<strong>' . esc_html__( 'Security Check:', 'bcs-screen-manager' ) . '</strong> ' . esc_html__( 'Malformed verification token.', 'bcs-screen-manager' ) );
    }

    list( $hash, $timestamp ) = explode( ':', $decoded, 2 );
    $timestamp = (int) $timestamp;

    // Tokens expire after 300 seconds (5 minutes) or if timestamp is in the future
    if ( ( time() - $timestamp ) > 300 || $timestamp > ( time() + 30 ) ) {
        return new WP_Error( 'bsc_captcha_expired', '<strong>' . esc_html__( 'Security Check:', 'bcs-screen-manager' ) . '</strong> ' . esc_html__( 'Captcha expired. Please refresh and try again.', 'bcs-screen-manager' ) );
    }

    $expected_hash = hash_hmac( 'sha256', (int) $answer . "|{$timestamp}", wp_salt( 'nonce' ) );

    if ( ! hash_equals( $expected_hash, $hash ) ) {
        return new WP_Error( 'bsc_captcha_incorrect', '<strong>' . esc_html__( 'Security Check:', 'bcs-screen-manager' ) . '</strong> ' . esc_html__( 'Incorrect math answer. Please try again.', 'bcs-screen-manager' ) );
    }

    return $user;
}

// =============================================================================
// ADMIN ASSETS & VIEWPORT STYLING
// =============================================================================
add_action( 'admin_enqueue_scripts', 'bsc_enqueue_admin_assets' );
function bsc_enqueue_admin_assets( $hook ) {
    if ( ! bsc_is_cinema_screen() ) {
        return;
    }

    wp_enqueue_media();

    // 1. Google Fonts
    wp_enqueue_style(
        'bsc-admin-fonts',
        'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap',
        array(),
        null
    );

    // 2. Custom CSS Stylesheet (assets/css/style.css) if present
    if ( file_exists( BSC_PLUGIN_PATH . 'assets/css/style.css' ) ) {
        wp_enqueue_style(
            'bsc-admin-style',
            BSC_PLUGIN_URL . 'assets/css/style.css',
            array( 'bsc-admin-fonts' ),
            BSC_VERSION
        );
    }

    // 3. Cinema Mode Viewport Styles
    wp_register_style( 'bsc-admin-cinema-theme', false, array( 'bsc-admin-fonts' ), BSC_VERSION );
    wp_enqueue_style( 'bsc-admin-cinema-theme' );

    $admin_css = "
        html.wp-toolbar { padding: 0; }
        
        /* Hide WordPress admin menu and toolbar on ALL bsc- pages */
        body[class*=\"_page_bsc-\"] #adminmenumain, 
        body[class*=\"_page_bsc-\"] #adminmenuback, 
        body[class*=\"_page_bsc-\"] #adminmenuwrap, 
        body[class*=\"_page_bsc-\"] #wpadminbar, 
        body[class*=\"_page_bsc-\"] #wpfooter {
            display: none !important;
        }
        body[class*=\"_page_bsc-\"] #wpcontent, 
        body[class*=\"_page_bsc-\"] #wpbody, 
        body[class*=\"_page_bsc-\"] #wpbody-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }

        :root {
            --cinema-bg-canvas: #f8fafc;
            --cinema-sidebar-bg: #0f172a;
            --cinema-card-bg: #ffffff;
            --cinema-primary-dark: #0f172a;
            --cinema-accent-teal: #0d9488;
            --cinema-accent-green: #10b981;
            --cinema-accent-amber: #f59e0b;
            --cinema-accent-red: #ef4444;
            --cinema-accent-blue: #3b82f6;
            --cinema-border: #e2e8f0;
            --cinema-border-dark: rgba(255, 255, 255, 0.08);
            --cinema-text-main: #0f172a;
            --cinema-text-muted: #64748b;
            --cinema-text-nav: #94a3b8;
            --cinema-radius-sm: 8px;
            --cinema-radius-md: 14px;
            --cinema-radius-lg: 20px;
            --cinema-font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --cinema-font-mono: 'Space Grotesk', monospace;
        }
        .bsc-app-wrap {
            display: flex;
            width: 100%;
            background-color: var(--cinema-bg-canvas);
            font-family: var(--cinema-font-main);
            color: var(--cinema-text-main);
            box-sizing: border-box;
        }
        .bsc-sidebar {
            width: 280px;
            background: var(--cinema-sidebar-bg);
            border-right: 1px solid var(--cinema-border-dark);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-shrink: 0;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
        }
        .bsc-sidebar-header {
            padding: 1.5rem 1.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--cinema-border-dark);
        }
        .bsc-brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .bsc-brand-logo {
            max-height: 40px;
            max-width: 120px;
            object-fit: contain;
        }
        .bsc-brand-fallback-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0d9488 0%, #064e3b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }
        .bsc-brand-fallback-icon svg {
            width: 20px;
            height: 20px;
            fill: #ffffff;
        }
        .bsc-brand-titles h4 {
            margin: 0;
            font-family: var(--cinema-font-mono);
            font-size: 0.95rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.05em;
        }
        .bsc-brand-titles span {
            font-size: 0.7rem;
            color: #14b8a6;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .bsc-admin-pill-card {
            margin: 1rem 1.25rem 0.5rem 1.25rem;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--cinema-border-dark);
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .bsc-avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #1e293b;
            border: 2px solid #0d9488;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.8rem;
        }
        .bsc-admin-name {
            font-weight: 700;
            font-size: 0.85rem;
            color: #f1f5f9;
            line-height: 1.2;
        }
        .bsc-admin-role {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 600;
        }
        .bsc-nav-list {
            list-style: none;
            padding: 0.8rem 1rem;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }
        .bsc-nav-item a {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.75rem 1rem;
            color: var(--cinema-text-nav);
            font-weight: 600;
            font-size: 0.88rem;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .bsc-nav-item a:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #ffffff;
            transform: translateX(3px);
        }
        .bsc-nav-item.active a {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.4);
        }
        .bsc-nav-item svg {
            width: 19px;
            height: 19px;
            fill: currentColor;
            flex-shrink: 0;
        }
        .bsc-sidebar-bottom {
            padding: 1rem 1rem 1.2rem 1rem;
            border-top: 1px solid var(--cinema-border-dark);
            margin-top: auto;
        }
        .bsc-canvas {
            flex: 1;
            padding: 25px;
            box-sizing: border-box;
            background: var(--cinema-bg-canvas);
        }
    ";
    wp_add_inline_style( 'bsc-admin-cinema-theme', $admin_css );
}

// =============================================================================
// SHELL LAYOUT: OPEN WRAPPER & SIDEBAR
// =============================================================================
function bsc_render_page_shell_open( $current_page ) {
    $logout_url = wp_logout_url( home_url() );
    $logo_url   = bsc_get_custom_logo_url();

    // Contextual Current User Info
    $current_user = wp_get_current_user();
    $display_name = $current_user->exists() ? $current_user->display_name : __( 'Cinema Staff', 'bcs-screen-manager' );
    
    // Initials generation (safe two-letter extraction with fallback polyfill)
    $words    = preg_split( "/\s+/", trim( $display_name ) );
    $initials = '';
    if ( ! empty( $words[0] ) ) {
        $initials .= function_exists( 'mb_substr' ) ? mb_substr( $words[0], 0, 1 ) : substr( $words[0], 0, 1 );
    }
    if ( count( $words ) > 1 && ! empty( $words[1] ) ) {
        $initials .= function_exists( 'mb_substr' ) ? mb_substr( $words[1], 0, 1 ) : substr( $words[1], 0, 1 );
    }
    $initials = strtoupper( $initials ?: 'OP' );

    $role = ! empty( $current_user->roles ) ? ucwords( str_replace( '_', ' ', $current_user->roles[0] ) ) : __( 'Operator', 'bcs-screen-manager' );
    ?>
    <div class="bsc-app-wrap">
        <aside class="bsc-sidebar">
            <div>
                <div class="bsc-sidebar-header">
                    <div class="bsc-brand-box">
                        <?php if ( ! empty( $logo_url ) ) : ?>
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Logo', 'bcs-screen-manager' ); ?>" class="bsc-brand-logo" />
                        <?php else : ?>
                            <div class="bsc-brand-fallback-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                            </div>
                        <?php endif; ?>
                        <div class="bsc-brand-titles">
                            <h4><?php esc_html_e( 'Big Sreen Cinema', 'bcs-screen-manager' ); ?></h4>
                            <span><?php esc_html_e( '● Live Operations', 'bcs-screen-manager' ); ?></span>
                        </div>
                    </div>
                </div>

                <div class="bsc-admin-pill-card">
                    <div class="bsc-avatar-sm"><?php echo esc_html( $initials ); ?></div>
                    <div>
                        <div class="bsc-admin-name"><?php echo esc_html( $display_name ); ?></div>
                        <div class="bsc-admin-role"><?php echo esc_html( $role ); ?></div>
                    </div>
                </div>

                <ul class="bsc-nav-list">
                    <li class="bsc-nav-item <?php echo ( 'bsc-dashboard' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-dashboard' ) ); ?>">
                            <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                            <span><?php esc_html_e( 'Dashboard', 'bcs-screen-manager' ); ?></span>
                        </a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-booking' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-booking' ) ); ?>">
                            <svg viewBox="0 0 24 24"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
                            <span><?php esc_html_e( 'Booking', 'bcs-screen-manager' ); ?></span>
                        </a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-movies' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-movies' ) ); ?>">
                            <svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
                            <span><?php esc_html_e( 'Movies & Shows', 'bcs-screen-manager' ); ?></span>
                        </a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-gallery' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-gallery' ) ); ?>">
                            <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                            <span><?php esc_html_e( 'Gallery', 'bcs-screen-manager' ); ?></span>
                        </a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-customers' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-customers' ) ); ?>">
                            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                            <span><?php esc_html_e( 'Customers', 'bcs-screen-manager' ); ?></span>
                        </a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-accounts' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-accounts' ) ); ?>">
                            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm0 16H5V5h14v14z"/></svg>
                            <span><?php esc_html_e( 'Financials', 'bcs-screen-manager' ); ?></span>
                        </a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-users' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-users' ) ); ?>">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            <span><?php esc_html_e( 'Staff & Users', 'bcs-screen-manager' ); ?></span>
                        </a>
                    </li>
                    <li class="bsc-nav-item <?php echo ( 'bsc-settings' === $current_page ) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-settings' ) ); ?>">
                            <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                            <span><?php esc_html_e( 'Settings', 'bcs-screen-manager' ); ?></span>
                        </a>
                    </li>
                    <li class="bsc-nav-item">
                        <a href="<?php echo esc_url( $logout_url ); ?>" style="color:#f87171;">
                            <svg viewBox="0 0 24 24"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
                            <span><?php esc_html_e( 'Sign Out', 'bcs-screen-manager' ); ?></span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="bsc-sidebar-bottom" style="display:none;"></div>
        </aside>

        <main class="bsc-canvas">
    <?php
}

function bsc_render_page_shell_close() {
    ?>
        </main>
    </div>
    <?php
}

// =============================================================================
// MASTER ADMIN MENU REGISTRATION
// =============================================================================
add_action( 'admin_menu', 'bsc_register_master_menu', 99 );
function bsc_register_master_menu() {
    $parent_slug = 'bsc-dashboard';

    add_menu_page(
        __( 'Movie Theater', 'bcs-screen-manager' ),
        __( 'Movie Theater', 'bcs-screen-manager' ),
        BSC_ADMIN_CAP,
        $parent_slug,
        'bsc_render_dashboard_page',
        'dashicons-video-alt3',
        6
    );

    add_submenu_page( $parent_slug, __( 'Dashboard', 'bcs-screen-manager' ), __( 'Dashboard', 'bcs-screen-manager' ), BSC_ADMIN_CAP, 'bsc-dashboard', 'bsc_render_dashboard_page' );
    add_submenu_page( $parent_slug, __( 'Booking Manager', 'bcs-screen-manager' ), __( 'Booking', 'bcs-screen-manager' ), BSC_ADMIN_CAP, 'bsc-booking', 'bsc_render_booking_page' );
    add_submenu_page( $parent_slug, __( 'Movies & Shows', 'bcs-screen-manager' ), __( 'Movies', 'bcs-screen-manager' ), BSC_ADMIN_CAP, 'bsc-movies', 'bsc_render_movies_page' );
    add_submenu_page( $parent_slug, __( 'Gallery Manager', 'bcs-screen-manager' ), __( 'Gallery', 'bcs-screen-manager' ), BSC_ADMIN_CAP, 'bsc-gallery', 'bsc_render_gallery_page' );
    add_submenu_page( $parent_slug, __( 'Customers Directory', 'bcs-screen-manager' ), __( 'Customers', 'bcs-screen-manager' ), BSC_ADMIN_CAP, 'bsc-customers', 'bsc_render_customers_page' );
    add_submenu_page( $parent_slug, __( 'Financial Accounts', 'bcs-screen-manager' ), __( 'Accounts', 'bcs-screen-manager' ), BSC_ADMIN_CAP, 'bsc-accounts', 'bsc_render_accounts_page' );
    add_submenu_page( $parent_slug, __( 'Staff & Crew', 'bcs-screen-manager' ), __( 'Users', 'bcs-screen-manager' ), BSC_ADMIN_CAP, 'bsc-users', 'bsc_render_users_page' );
    add_submenu_page( $parent_slug, __( 'Theater Settings', 'bcs-screen-manager' ), __( 'Settings', 'bcs-screen-manager' ), BSC_ADMIN_CAP, 'bsc-settings', 'bsc_render_settings_page' );
    add_submenu_page( $parent_slug, __( 'Sign Out', 'bcs-screen-manager' ), '<span style="color:#ef4444;">🚪 ' . esc_html__( 'Sign Out', 'bcs-screen-manager' ) . '</span>', BSC_ADMIN_CAP, 'bsc-logout', 'bsc_handle_logout_screen' );
}

function bsc_handle_logout_screen() {
    $logout_url = wp_logout_url( home_url() );
    ?>
    <div class="wrap" style="padding: 40px; font-family: sans-serif;">
        <p><?php esc_html_e( 'Logging out of Big Screen Cinemas...', 'bcs-screen-manager' ); ?></p>
        <p><a href="<?php echo esc_url( $logout_url ); ?>" class="button button-primary"><?php esc_html_e( 'Click here if not redirected automatically', 'bcs-screen-manager' ); ?></a></p>
    </div>
    <?php
}