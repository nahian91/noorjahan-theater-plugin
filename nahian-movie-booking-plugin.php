<?php
/**
 * Plugin Name:       Nahian Movie Booking Plugin
 * Plugin URI:        https://bigscreencinemas.com
 * Description:       Cinema Multiplex Management Suite with 4K Media Uploads, CRUD Movie Management, Dynamic Scheduling, and Turnstile Booking Engine.
 * Version:           1.0
 * Author:            Abdullah Nahian
 * Text Domain:       nahian-movie-booking-plugin
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NMBP_VERSION', '1.0' );
define( 'NMBP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'NMBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NMBP_ADMIN_CAP', 'manage_options' );

// =============================================================================
// DATABASE LAYER & MODULE INCLUDES
// =============================================================================
require_once NMBP_PLUGIN_PATH . 'inc/database.php';

require_once NMBP_PLUGIN_PATH . 'admin/page-dashboard.php';
require_once NMBP_PLUGIN_PATH . 'admin/page-booking.php';
require_once NMBP_PLUGIN_PATH . 'admin/page-movies.php';
require_once NMBP_PLUGIN_PATH . 'admin/page-gallery.php';
require_once NMBP_PLUGIN_PATH . 'admin/page-customers.php';
require_once NMBP_PLUGIN_PATH . 'admin/page-accounts.php';
require_once NMBP_PLUGIN_PATH . 'admin/page-users.php';
require_once NMBP_PLUGIN_PATH . 'admin/page-settings.php';

// =============================================================================
// ACTIVATION & LIFECYCLE HOOKS
// =============================================================================
register_activation_hook( __FILE__, 'nmbp_activate_plugin' );
function nmbp_activate_plugin() {
	if ( function_exists( 'nmbp_create_database_tables' ) ) {
		nmbp_create_database_tables();
	}
}

// =============================================================================
// CORE UTILITIES & HELPERS
// =============================================================================
function nmbp_generate_secure_token( $prefix = 'NMBP' ) {
	return sprintf( '#%s-%s-TX', sanitize_key( $prefix ), strtoupper( wp_generate_password( 8, false, false ) ) );
}

function nmbp_is_cinema_screen( $hook = '' ) {
	if ( ! is_admin() ) {
		return false;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 0 === strpos( $page, 'nmbp-' ) ) {
		return true;
	}

	if ( ! empty( $hook ) && ( false !== strpos( $hook, '_page_nmbp-' ) || false !== strpos( $hook, 'toplevel_page_nmbp-' ) ) ) {
		return true;
	}

	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( $screen && ! empty( $screen->id ) && ( false !== strpos( $screen->id, '_page_nmbp-' ) || false !== strpos( $screen->id, 'toplevel_page_nmbp-' ) ) ) {
			return true;
		}
	}

	return false;
}

function nmbp_get_custom_logo_url() {
	$extensions = array( 'svg', 'png', 'webp', 'jpg' );
	foreach ( $extensions as $ext ) {
		$file_path = NMBP_PLUGIN_PATH . 'assets/logo.' . $ext;
		if ( file_exists( $file_path ) ) {
			return NMBP_PLUGIN_URL . 'assets/logo.' . $ext;
		}
	}
	return '';
}

// =============================================================================
// LOGIN REDIRECTION & ROUTING
// =============================================================================
add_filter( 'login_redirect', 'nmbp_custom_login_redirect', 999, 3 );
function nmbp_custom_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
		return $redirect_to;
	}

	if ( user_can( $user, NMBP_ADMIN_CAP ) ) {
		return admin_url( 'admin.php?page=nmbp-dashboard' );
	}

	return home_url();
}

add_action( 'init', 'nmbp_handle_custom_admin_slug' );
function nmbp_handle_custom_admin_slug() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$raw_uri    = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$path       = wp_parse_url( $raw_uri, PHP_URL_PATH );
	$clean_path = untrailingslashit( (string) $path );

	if ( in_array( $clean_path, array( '/admin', '/a/admin' ), true ) ) {
		$target_url = admin_url( 'admin.php?page=nmbp-dashboard' );
		if ( is_user_logged_in() ) {
			wp_safe_redirect( $target_url );
		} else {
			wp_safe_redirect( wp_login_url( $target_url ) );
		}
		exit;
	}
}

// Intercept admin logout page early before rendering headers.
add_action( 'admin_init', 'nmbp_handle_logout_action' );
function nmbp_handle_logout_action() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'nmbp-logout' === $page ) {
		wp_safe_redirect( wp_logout_url( home_url() ) );
		exit;
	}
}

// =============================================================================
// LOGIN SCREEN STYLING & SECURE MATH CAPTCHA (OWASP / ANTI-REPLAY)
// =============================================================================
add_action( 'login_enqueue_scripts', 'nmbp_enqueue_login_styles' );
function nmbp_enqueue_login_styles() {
	wp_enqueue_style(
		'nmbp-login-fonts',
		'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap',
		array(),
		null
	);
}

add_action( 'login_head', 'nmbp_render_login_styles' );
function nmbp_render_login_styles() {
	$logo_url = nmbp_get_custom_logo_url();
	?>
	<style id="nmbp-custom-login-css">
		body.login {
			background: radial-gradient(circle at top right, #0f373d 0%, #081a20 40%, #03080a 100%);
			font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
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
			padding: 30px 20px;
			width: 400px;
		}
		#login h1 a {
			<?php if ( ! empty( $logo_url ) ) : ?>
			background-image: url('<?php echo esc_url( $logo_url ); ?>');
			background-size: contain;
			<?php else : ?>
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2314b8a6'%3E%3Cpath d='M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4zm-4.5 10.5v-5l4 2.5-4 2.5z'/%3E%3C/svg%3E");
			background-size: 80px 80px;
			<?php endif; ?>
			background-position: center;
			background-repeat: no-repeat;
			width: 140px;
			height: 80px;
			margin: 0 auto 12px auto;
			transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
		}
		#login h1 a:hover {
			transform: scale(1.05);
		}
		#login h1::after {
			content: 'NAHIAN MOVIE BOOKING';
			display: block;
			font-family: 'Space Grotesk', sans-serif;
			font-size: 1.15rem;
			font-weight: 700;
			color: #f1f5f9;
			letter-spacing: 0.1em;
			margin-top: 6px;
			text-align: center;
			text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
		}
		.login form {
			background: rgba(15, 23, 42, 0.75);
			border: 1px solid rgba(255, 255, 255, 0.12);
			border-radius: 18px;
			box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(20, 184, 166, 0.15);
			padding: 32px 28px;
			color: #e2e8f0;
			backdrop-filter: blur(20px);
			-webkit-backdrop-filter: blur(20px);
		}
		.login form label {
			color: #94a3b8;
			font-weight: 700;
			font-size: 0.82rem;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.login input[type='text'],
		.login input[type='password'],
		.login input[type='number'] {
			background: rgba(2, 6, 23, 0.7);
			border: 1px solid rgba(255, 255, 255, 0.15);
			border-radius: 10px;
			color: #ffffff;
			font-size: 0.95rem;
			padding: 12px 14px;
			margin-top: 6px;
			box-shadow: none;
			transition: all 0.25s ease;
		}
		.login input[type='text']:focus,
		.login input[type='password']:focus,
		.login input[type='number']:focus {
			border-color: #14b8a6;
			background: rgba(2, 6, 23, 0.95);
			box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.3);
			outline: none;
		}
		.nmbp-captcha-wrapper {
			margin: 20px 0 14px 0;
			padding: 14px 16px;
			background: rgba(13, 148, 136, 0.08);
			border: 1px dashed rgba(20, 184, 166, 0.45);
			border-radius: 12px;
		}
		.nmbp-captcha-label {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 8px;
			font-size: 0.82rem;
			font-weight: 700;
			color: #cbd5e1;
		}
		.nmbp-captcha-equation {
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
			width: 100%;
			margin-top: 14px;
			background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
			border: none;
			color: #ffffff;
			font-weight: 800;
			border-radius: 10px;
			padding: 12px;
			height: auto;
			font-size: 0.95rem;
			letter-spacing: 0.03em;
			transition: all 0.25s ease;
			box-shadow: 0 4px 14px rgba(13, 148, 136, 0.4);
			cursor: pointer;
		}
		.wp-core-ui .button-primary:hover {
			background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
			transform: translateY(-2px);
			box-shadow: 0 6px 20px rgba(20, 184, 166, 0.5);
		}
		.login #login_error,
		.login .message {
			background: rgba(15, 23, 42, 0.9);
			border-left: 4px solid #ef4444;
			color: #fca5a5;
			border-radius: 10px;
			backdrop-filter: blur(10px);
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
		}
		.login .message {
			border-left-color: #14b8a6;
			color: #e2e8f0;
		}
		.login #nav,
		.login #backtoblog {
			text-align: center;
		}
		.login #nav a,
		.login #backtoblog a {
			color: #64748b;
			font-weight: 600;
			font-size: 0.85rem;
			transition: color 0.2s;
		}
		.login #nav a:hover,
		.login #backtoblog a:hover {
			color: #14b8a6;
		}
		.login .privacy-policy-page-link {
			display: none;
		}
	</style>
	<?php
}

add_filter( 'login_headerurl', 'nmbp_custom_login_logo_url' );
function nmbp_custom_login_logo_url() {
	return home_url();
}

add_filter( 'login_headertext', 'nmbp_custom_login_logo_title' );
function nmbp_custom_login_logo_title() {
	return get_bloginfo( 'name' );
}

add_action( 'login_form', 'nmbp_render_number_captcha_field' );
function nmbp_render_number_captcha_field() {
	$num1      = wp_rand( 2, 9 );
	$num2      = wp_rand( 1, 9 );
	$sum       = $num1 + $num2;
	$timestamp = time();

	// Replay-proof signature bound to current timestamp & secure salt.
	$hash          = hash_hmac( 'sha256', "{$sum}|{$timestamp}", wp_salt( 'nonce' ) );
	$token_payload = base64_encode( "{$hash}:{$timestamp}" );
	?>
	<div class="nmbp-captcha-wrapper">
		<label class="nmbp-captcha-label" for="nmbp_captcha_answer">
			<span><?php esc_html_e( 'Human Check', 'nahian-movie-booking-plugin' ); ?></span>
			<span class="nmbp-captcha-equation"><?php echo esc_html( "{$num1} + {$num2} = ?" ); ?></span>
		</label>
		<input type="number" name="nmbp_captcha_answer" id="nmbp_captcha_answer" class="input" placeholder="<?php esc_attr_e( 'Answer', 'nahian-movie-booking-plugin' ); ?>" required autocomplete="off" />
		<input type="hidden" name="nmbp_captcha_token" value="<?php echo esc_attr( $token_payload ); ?>" />
	</div>
	<?php
}

add_filter( 'authenticate', 'nmbp_verify_number_captcha', 25, 3 );
function nmbp_verify_number_captcha( $user, $username, $password ) {
	if ( ! isset( $_POST['log'] ) ) {
		return $user;
	}

	$answer  = isset( $_POST['nmbp_captcha_answer'] ) ? sanitize_text_field( wp_unslash( $_POST['nmbp_captcha_answer'] ) ) : '';
	$payload = isset( $_POST['nmbp_captcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['nmbp_captcha_token'] ) ) : '';

	if ( '' === $answer || '' === $payload ) {
		return new WP_Error( 'nmbp_captcha_missing', '<strong>' . esc_html__( 'Security Check:', 'nahian-movie-booking-plugin' ) . '</strong> ' . esc_html__( 'Please solve the math verification problem.', 'nahian-movie-booking-plugin' ) );
	}

	$decoded = base64_decode( $payload, true );
	if ( false === $decoded || false === strpos( $decoded, ':' ) ) {
		return new WP_Error( 'nmbp_captcha_invalid', '<strong>' . esc_html__( 'Security Check:', 'nahian-movie-booking-plugin' ) . '</strong> ' . esc_html__( 'Malformed verification token.', 'nahian-movie-booking-plugin' ) );
	}

	list( $hash, $timestamp ) = explode( ':', $decoded, 2 );
	$timestamp = (int) $timestamp;

	// Tokens expire after 300 seconds (5 minutes) or if timestamp is in the future.
	if ( ( time() - $timestamp ) > 300 || $timestamp > ( time() + 30 ) ) {
		return new WP_Error( 'nmbp_captcha_expired', '<strong>' . esc_html__( 'Security Check:', 'nahian-movie-booking-plugin' ) . '</strong> ' . esc_html__( 'Captcha expired. Please refresh and try again.', 'nahian-movie-booking-plugin' ) );
	}

	$expected_hash = hash_hmac( 'sha256', (int) $answer . "|{$timestamp}", wp_salt( 'nonce' ) );

	if ( ! hash_equals( $expected_hash, $hash ) ) {
		return new WP_Error( 'nmbp_captcha_incorrect', '<strong>' . esc_html__( 'Security Check:', 'nahian-movie-booking-plugin' ) . '</strong> ' . esc_html__( 'Incorrect math answer. Please try again.', 'nahian-movie-booking-plugin' ) );
	}

	return $user;
}

// =============================================================================
// ADMIN ASSETS & VIEWPORT STYLING
// =============================================================================
add_action( 'admin_enqueue_scripts', 'nmbp_enqueue_admin_assets' );
function nmbp_enqueue_admin_assets( $hook ) {
	if ( ! nmbp_is_cinema_screen( $hook ) ) {
		return;
	}

	wp_enqueue_media();

	// 1. Google Fonts.
	wp_enqueue_style(
		'nmbp-admin-fonts',
		'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap',
		array(),
		null
	);

	// 2. Custom CSS Stylesheet (assets/css/style.css) if present.
	if ( file_exists( NMBP_PLUGIN_PATH . 'assets/css/style.css' ) ) {
		wp_enqueue_style(
			'nmbp-admin-style',
			NMBP_PLUGIN_URL . 'assets/css/style.css',
			array( 'nmbp-admin-fonts' ),
			NMBP_VERSION
		);
	}
}

add_action( 'admin_head', 'nmbp_render_admin_cinema_styles' );
function nmbp_render_admin_cinema_styles() {
	if ( ! nmbp_is_cinema_screen() ) {
		return;
	}
	?>
	<style id="nmbp-admin-cinema-theme-css">
		html.wp-toolbar {
			padding: 0;
		}

		/* Hide WordPress admin menu and toolbar on ALL nmbp- pages */
		body[class*="_page_nmbp-"] #adminmenumain,
		body[class*="_page_nmbp-"] #adminmenuback,
		body[class*="_page_nmbp-"] #adminmenuwrap,
		body[class*="_page_nmbp-"] #wpadminbar,
		body[class*="_page_nmbp-"] #wpfooter {
			display: none;
		}
		body[class*="_page_nmbp-"] #wpcontent,
		body[class*="_page_nmbp-"] #wpbody,
		body[class*="_page_nmbp-"] #wpbody-content {
			margin-left: 0;
			padding: 0;
		}

		:root {
			--nmbp-bg-canvas: #f8fafc;
			--nmbp-sidebar-bg: #0f172a;
			--nmbp-card-bg: #ffffff;
			--nmbp-primary-dark: #0f172a;
			--nmbp-accent-teal: #0d9488;
			--nmbp-accent-green: #10b981;
			--nmbp-accent-amber: #f59e0b;
			--nmbp-accent-red: #ef4444;
			--nmbp-accent-blue: #3b82f6;
			--nmbp-border: #e2e8f0;
			--nmbp-border-dark: rgba(255, 255, 255, 0.08);
			--nmbp-text-main: #0f172a;
			--nmbp-text-muted: #64748b;
			--nmbp-text-nav: #94a3b8;
			--nmbp-radius-sm: 8px;
			--nmbp-radius-md: 14px;
			--nmbp-radius-lg: 20px;
			--nmbp-font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
			--nmbp-font-mono: 'Space Grotesk', monospace;
		}
		.nmbp-app-wrap {
			display: flex;
			width: 100%;
			min-height: 100vh;
			background-color: var(--nmbp-bg-canvas);
			font-family: var(--nmbp-font-main);
			color: var(--nmbp-text-main);
			box-sizing: border-box;
		}
		.nmbp-sidebar {
			width: 280px;
			background: var(--nmbp-sidebar-bg);
			border-right: 1px solid var(--nmbp-border-dark);
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			flex-shrink: 0;
			box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
		}
		.nmbp-sidebar-header {
			padding: 1.5rem 1.4rem;
			display: flex;
			align-items: center;
			justify-content: space-between;
			border-bottom: 1px solid var(--nmbp-border-dark);
		}
		.nmbp-brand-box {
			display: flex;
			align-items: center;
			gap: 12px;
		}
		.nmbp-brand-logo {
			max-height: 40px;
			max-width: 120px;
			object-fit: contain;
		}
		.nmbp-brand-fallback-icon {
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
		.nmbp-brand-fallback-icon svg {
			width: 20px;
			height: 20px;
			fill: #ffffff;
		}
		.nmbp-brand-titles h4 {
			margin: 0;
			font-family: var(--nmbp-font-mono);
			font-size: 0.95rem;
			font-weight: 700;
			color: #ffffff;
			letter-spacing: 0.05em;
		}
		.nmbp-brand-titles span {
			font-size: 0.7rem;
			color: #14b8a6;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}
		.nmbp-admin-pill-card {
			margin: 1rem 1.25rem 0.5rem 1.25rem;
			padding: 0.75rem 1rem;
			background: rgba(255, 255, 255, 0.04);
			border: 1px solid var(--nmbp-border-dark);
			border-radius: 12px;
			display: flex;
			align-items: center;
			gap: 0.75rem;
		}
		.nmbp-avatar-sm {
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
		.nmbp-admin-name {
			font-weight: 700;
			font-size: 0.85rem;
			color: #f1f5f9;
			line-height: 1.2;
		}
		.nmbp-admin-role {
			font-size: 0.7rem;
			color: #64748b;
			font-weight: 600;
		}
		.nmbp-nav-list {
			list-style: none;
			padding: 0.8rem 1rem;
			margin: 0;
			display: flex;
			flex-direction: column;
			gap: 0.3rem;
		}
		.nmbp-nav-item a {
			display: flex;
			align-items: center;
			gap: 0.85rem;
			padding: 0.75rem 1rem;
			color: var(--nmbp-text-nav);
			font-weight: 600;
			font-size: 0.88rem;
			border-radius: 10px;
			text-decoration: none;
			transition: all 0.2s ease;
		}
		.nmbp-nav-item a:hover {
			background: rgba(255, 255, 255, 0.06);
			color: #ffffff;
			transform: translateX(3px);
		}
		.nmbp-nav-item.active a {
			background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
			color: #ffffff;
			box-shadow: 0 4px 14px rgba(13, 148, 136, 0.4);
		}
		.nmbp-nav-item svg {
			width: 19px;
			height: 19px;
			fill: currentColor;
			flex-shrink: 0;
		}
		.nmbp-nav-item--signout a {
			color: #f87171;
		}
		.nmbp-sidebar-bottom {
			padding: 1rem 1rem 1.2rem 1rem;
			border-top: 1px solid var(--nmbp-border-dark);
			margin-top: auto;
			display: none;
		}
		.nmbp-canvas {
			flex: 1;
			padding: 25px;
			box-sizing: border-box;
			background: var(--nmbp-bg-canvas);
		}
		.nmbp-logout-screen {
			padding: 40px;
			font-family: sans-serif;
		}
		.nmbp-submenu-signout {
			color: #ef4444;
		}
	</style>
	<?php
}

// =============================================================================
// SHELL LAYOUT: OPEN WRAPPER & SIDEBAR
// =============================================================================
function nmbp_render_page_shell_open( $current_page ) {
	$logout_url = wp_logout_url( home_url() );
	$logo_url   = nmbp_get_custom_logo_url();

	// Contextual Current User Info.
	$current_user = wp_get_current_user();
	$display_name = $current_user->exists() ? $current_user->display_name : __( 'Cinema Staff', 'nahian-movie-booking-plugin' );

	// Initials generation (safe two-letter extraction with fallback polyfill).
	$words    = preg_split( "/\s+/", trim( $display_name ) );
	$initials = '';
	if ( ! empty( $words[0] ) ) {
		$initials .= function_exists( 'mb_substr' ) ? mb_substr( $words[0], 0, 1 ) : substr( $words[0], 0, 1 );
	}
	if ( count( $words ) > 1 && ! empty( $words[1] ) ) {
		$initials .= function_exists( 'mb_substr' ) ? mb_substr( $words[1], 0, 1 ) : substr( $words[1], 0, 1 );
	}
	$initials = strtoupper( $initials ?: 'OP' );

	$role = ! empty( $current_user->roles ) ? ucwords( str_replace( '_', ' ', $current_user->roles[0] ) ) : __( 'Operator', 'nahian-movie-booking-plugin' );
	?>
	<div class="nmbp-app-wrap">
		<aside class="nmbp-sidebar">
			<div>
				<div class="nmbp-sidebar-header">
					<div class="nmbp-brand-box">
						<?php if ( ! empty( $logo_url ) ) : ?>
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Logo', 'nahian-movie-booking-plugin' ); ?>" class="nmbp-brand-logo" />
						<?php else : ?>
							<div class="nmbp-brand-fallback-icon" aria-hidden="true">
								<svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
							</div>
						<?php endif; ?>
						<div class="nmbp-brand-titles">
							<h4><?php esc_html_e( 'Nahian Booking', 'nahian-movie-booking-plugin' ); ?></h4>
							<span><?php esc_html_e( '● Live Operations', 'nahian-movie-booking-plugin' ); ?></span>
						</div>
					</div>
				</div>

				<div class="nmbp-admin-pill-card">
					<div class="nmbp-avatar-sm"><?php echo esc_html( $initials ); ?></div>
					<div>
						<div class="nmbp-admin-name"><?php echo esc_html( $display_name ); ?></div>
						<div class="nmbp-admin-role"><?php echo esc_html( $role ); ?></div>
					</div>
				</div>

				<ul class="nmbp-nav-list">
					<li class="nmbp-nav-item <?php echo ( 'nmbp-dashboard' === $current_page ) ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-dashboard' ) ); ?>">
							<svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
							<span><?php esc_html_e( 'Dashboard', 'nahian-movie-booking-plugin' ); ?></span>
						</a>
					</li>
					<li class="nmbp-nav-item <?php echo ( 'nmbp-booking' === $current_page ) ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-booking' ) ); ?>">
							<svg viewBox="0 0 24 24"><path d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2s.9-2 2-2zm-9 7.5h-2v-2h2v2zm0-4.5h-2v-2h2v2zm0-4.5h-2v-2h2v2z"/></svg>
							<span><?php esc_html_e( 'Booking', 'nahian-movie-booking-plugin' ); ?></span>
						</a>
					</li>
					<li class="nmbp-nav-item <?php echo ( 'nmbp-movies' === $current_page ) ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-movies' ) ); ?>">
							<svg viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
							<span><?php esc_html_e( 'Movies & Shows', 'nahian-movie-booking-plugin' ); ?></span>
						</a>
					</li>
					<li class="nmbp-nav-item <?php echo ( 'nmbp-gallery' === $current_page ) ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-gallery' ) ); ?>">
							<svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
							<span><?php esc_html_e( 'Gallery', 'nahian-movie-booking-plugin' ); ?></span>
						</a>
					</li>
					<li class="nmbp-nav-item <?php echo ( 'nmbp-customers' === $current_page ) ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-customers' ) ); ?>">
							<svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
							<span><?php esc_html_e( 'Customers', 'nahian-movie-booking-plugin' ); ?></span>
						</a>
					</li>
					<li class="nmbp-nav-item <?php echo ( 'nmbp-accounts' === $current_page ) ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-accounts' ) ); ?>">
							<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm0 16H5V5h14v14z"/></svg>
							<span><?php esc_html_e( 'Financials', 'nahian-movie-booking-plugin' ); ?></span>
						</a>
					</li>
					<li class="nmbp-nav-item <?php echo ( 'nmbp-users' === $current_page ) ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-users' ) ); ?>">
							<svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
							<span><?php esc_html_e( 'Staff & Users', 'nahian-movie-booking-plugin' ); ?></span>
						</a>
					</li>
					<li class="nmbp-nav-item <?php echo ( 'nmbp-settings' === $current_page ) ? 'active' : ''; ?>">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=nmbp-settings' ) ); ?>">
							<svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
							<span><?php esc_html_e( 'Settings', 'nahian-movie-booking-plugin' ); ?></span>
						</a>
					</li>
					<li class="nmbp-nav-item nmbp-nav-item--signout">
						<a href="<?php echo esc_url( $logout_url ); ?>">
							<svg viewBox="0 0 24 24"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
							<span><?php esc_html_e( 'Sign Out', 'nahian-movie-booking-plugin' ); ?></span>
						</a>
					</li>
				</ul>
			</div>
			<div class="nmbp-sidebar-bottom"></div>
		</aside>

		<main class="nmbp-canvas">
	<?php
}

function nmbp_render_page_shell_close() {
	?>
		</main>
	</div>
	<?php
}

// =============================================================================
// SAFE PAGE CALLBACK ROUTERS
// =============================================================================
function nmbp_route_page_callback( $render_func, $shell_slug ) {
	if ( function_exists( $render_func ) ) {
		call_user_func( $render_func );
		return;
	}
	nmbp_render_page_shell_open( $shell_slug );
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'Requested page view is not available.', 'nahian-movie-booking-plugin' ) . '</p></div>';
	nmbp_render_page_shell_close();
}

function nmbp_dashboard_menu_callback() {
	nmbp_route_page_callback( 'nmbp_render_dashboard_page', 'nmbp-dashboard' );
}

function nmbp_booking_menu_callback() {
	nmbp_route_page_callback( 'nmbp_render_booking_page', 'nmbp-booking' );
}

function nmbp_movies_menu_callback() {
	nmbp_route_page_callback( 'nmbp_render_movies_page', 'nmbp-movies' );
}

function nmbp_gallery_menu_callback() {
	nmbp_route_page_callback( 'nmbp_render_gallery_page', 'nmbp-gallery' );
}

function nmbp_customers_menu_callback() {
	nmbp_route_page_callback( 'nmbp_render_customers_page', 'nmbp-customers' );
}

function nmbp_accounts_menu_callback() {
	nmbp_route_page_callback( 'nmbp_render_accounts_page', 'nmbp-accounts' );
}

function nmbp_users_menu_callback() {
	nmbp_route_page_callback( 'nmbp_render_users_page', 'nmbp-users' );
}

function nmbp_settings_menu_callback() {
	nmbp_route_page_callback( 'nmbp_render_settings_page', 'nmbp-settings' );
}

// =============================================================================
// MASTER ADMIN MENU REGISTRATION
// =============================================================================
add_action( 'admin_menu', 'nmbp_register_master_menu', 99 );
function nmbp_register_master_menu() {
	$parent_slug = 'nmbp-dashboard';

	add_menu_page(
		__( 'Movie Theater', 'nahian-movie-booking-plugin' ),
		__( 'Movie Theater', 'nahian-movie-booking-plugin' ),
		NMBP_ADMIN_CAP,
		$parent_slug,
		'nmbp_dashboard_menu_callback',
		'dashicons-video-alt3',
		6
	);

	add_submenu_page( $parent_slug, __( 'Dashboard', 'nahian-movie-booking-plugin' ), __( 'Dashboard', 'nahian-movie-booking-plugin' ), NMBP_ADMIN_CAP, 'nmbp-dashboard', 'nmbp_dashboard_menu_callback' );
	add_submenu_page( $parent_slug, __( 'Booking Manager', 'nahian-movie-booking-plugin' ), __( 'Booking', 'nahian-movie-booking-plugin' ), NMBP_ADMIN_CAP, 'nmbp-booking', 'nmbp_booking_menu_callback' );
	add_submenu_page( $parent_slug, __( 'Movies & Shows', 'nahian-movie-booking-plugin' ), __( 'Movies', 'nahian-movie-booking-plugin' ), NMBP_ADMIN_CAP, 'nmbp-movies', 'nmbp_movies_menu_callback' );
	add_submenu_page( $parent_slug, __( 'Gallery Manager', 'nahian-movie-booking-plugin' ), __( 'Gallery', 'nahian-movie-booking-plugin' ), NMBP_ADMIN_CAP, 'nmbp-gallery', 'nmbp_gallery_menu_callback' );
	add_submenu_page( $parent_slug, __( 'Customers Directory', 'nahian-movie-booking-plugin' ), __( 'Customers', 'nahian-movie-booking-plugin' ), NMBP_ADMIN_CAP, 'nmbp-customers', 'nmbp_customers_menu_callback' );
	add_submenu_page( $parent_slug, __( 'Financial Accounts', 'nahian-movie-booking-plugin' ), __( 'Accounts', 'nahian-movie-booking-plugin' ), NMBP_ADMIN_CAP, 'nmbp-accounts', 'nmbp_accounts_menu_callback' );
	add_submenu_page( $parent_slug, __( 'Staff & Users', 'nahian-movie-booking-plugin' ), __( 'Users', 'nahian-movie-booking-plugin' ), NMBP_ADMIN_CAP, 'nmbp-users', 'nmbp_users_menu_callback' );
	add_submenu_page( $parent_slug, __( 'Theater Settings', 'nahian-movie-booking-plugin' ), __( 'Settings', 'nahian-movie-booking-plugin' ), NMBP_ADMIN_CAP, 'nmbp-settings', 'nmbp_settings_menu_callback' );
	add_submenu_page( $parent_slug, __( 'Sign Out', 'nahian-movie-booking-plugin' ), '<span class="nmbp-submenu-signout">🚪 ' . esc_html__( 'Sign Out', 'nahian-movie-booking-plugin' ) . '</span>', NMBP_ADMIN_CAP, 'nmbp-logout', 'nmbp_handle_logout_screen' );
}

function nmbp_handle_logout_screen() {
	$logout_url = wp_logout_url( home_url() );
	?>
	<div class="wrap nmbp-logout-screen">
		<p><?php esc_html_e( 'Logging out of Nahian Movie Booking Plugin...', 'nahian-movie-booking-plugin' ); ?></p>
		<p><a href="<?php echo esc_url( $logout_url ); ?>" class="button button-primary"><?php esc_html_e( 'Click here if not redirected automatically', 'nahian-movie-booking-plugin' ); ?></a></p>
	</div>
	<script id="nmbp-logout-redirect-js">
		(function() {
			var targetUrl = <?php echo wp_json_encode( esc_url_raw( $logout_url ) ); ?>;
			if (targetUrl) {
				window.location.replace(targetUrl);
			}
		})();
	</script>
	<?php
}