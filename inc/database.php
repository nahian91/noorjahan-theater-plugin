<?php
/**
 * Big Screen Cinemas - Database Schema & Migration Handler
 *
 * Path: inc/database.php
 *
 * @package BigScreenCinemas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Creates custom database tables and runs schema migrations via dbDelta.
 */
function bsc_create_database_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table_movies = "CREATE TABLE {$wpdb->prefix}bsc_control_movies (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        category varchar(100) DEFAULT 'Feature Presentation' NOT NULL,
        genre varchar(100) NOT NULL,
        duration varchar(50) NOT NULL,
        rating varchar(10) NOT NULL,
        age_rating varchar(50) DEFAULT 'PG-13' NOT NULL,
        audio_language varchar(150) NOT NULL,
        director varchar(150) NOT NULL,
        writers varchar(255) NOT NULL,
        auditorium varchar(100) DEFAULT 'Auditorium 1 (23 Seats)' NOT NULL,
        showtimes longtext NOT NULL,
        poster_url text NOT NULL,
        trailer_url text NOT NULL,
        badge_tag varchar(50) NOT NULL,
        release_date date NOT NULL,
        short_description text NOT NULL,
        synopsis longtext NOT NULL,
        cast_crew longtext NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $table_schedule = "CREATE TABLE {$wpdb->prefix}bsc_control_schedule (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        movie_title varchar(255) NOT NULL,
        auditorium varchar(100) NOT NULL,
        format_type varchar(50) NOT NULL,
        showtimes varchar(255) NOT NULL,
        price decimal(10,2) DEFAULT '0.00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $table_ledger = "CREATE TABLE {$wpdb->prefix}bsc_ledger (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        reference_no varchar(64) NOT NULL,
        txn_type varchar(20) DEFAULT 'Income' NOT NULL,
        particulars varchar(255) NOT NULL,
        method varchar(50) DEFAULT 'Cash' NOT NULL,
        amount decimal(10,2) DEFAULT '0.00' NOT NULL,
        transaction_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY reference_no (reference_no)
    ) $charset_collate;";

    $table_bookings = "CREATE TABLE {$wpdb->prefix}bsc_bookings (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        booking_token varchar(64) NOT NULL,
        movie_title varchar(255) NOT NULL,
        auditorium varchar(100) NOT NULL,
        showtime varchar(50) NOT NULL,
        ticket_type varchar(50) DEFAULT 'Standard' NOT NULL,
        seats text NOT NULL,
        customer_name varchar(150) DEFAULT 'Walk-In Guest' NOT NULL,
        customer_phone varchar(50) NOT NULL,
        customer_email varchar(150) NOT NULL,
        total_amount decimal(10,2) DEFAULT '0.00' NOT NULL,
        payment_method varchar(50) DEFAULT 'Cash' NOT NULL,
        payment_status varchar(50) DEFAULT 'Confirmed' NOT NULL,
        booking_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY booking_token (booking_token)
    ) $charset_collate;";

    $table_customers = "CREATE TABLE {$wpdb->prefix}bsc_customers (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        membership_id varchar(50) NOT NULL,
        name varchar(150) NOT NULL,
        email varchar(150) NOT NULL,
        phone varchar(50) NOT NULL,
        tier varchar(50) DEFAULT 'VIP CLUB' NOT NULL,
        total_visits int(11) DEFAULT 1 NOT NULL,
        reward_points int(11) DEFAULT 50 NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY membership_id (membership_id)
    ) $charset_collate;";

    $table_staff = "CREATE TABLE {$wpdb->prefix}bsc_staff_crew (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        staff_code varchar(50) NOT NULL,
        name varchar(150) NOT NULL,
        role_title varchar(150) NOT NULL,
        assigned_hall varchar(150) NOT NULL,
        duty_status varchar(50) DEFAULT 'On Duty' NOT NULL,
        shift_schedule varchar(100) NOT NULL,
        phone varchar(50) NOT NULL,
        email varchar(150) NOT NULL,
        salary decimal(10,2) DEFAULT '25000.00' NOT NULL,
        photo_url text NOT NULL,
        responsibilities text NOT NULL,
        joined_date date DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY staff_code (staff_code)
    ) $charset_collate;";

    dbDelta( $table_movies );
    dbDelta( $table_schedule );
    dbDelta( $table_ledger );
    dbDelta( $table_bookings );
    dbDelta( $table_customers );
    dbDelta( $table_staff );

    bsc_seed_initial_staff();
}

/**
 * Generates custom cinema ticket ID in the format: BCS-DD-MON-YY-##
 * Example: BCS-03-SEP-26-01, BCS-03-SEP-26-02
 *
 * @param string $prefix Prefix for ticket sequence.
 * @return string Formatted unique ticket reference code.
 */
function bsc_generate_cinema_ticket_id( $prefix = 'BCS' ) {
    global $wpdb;
    $table_bookings = $wpdb->prefix . 'bsc_bookings';

    $today_ymd = current_time( 'Y-m-d' );
    $day_str   = strtoupper( current_time( 'd-M-y' ) );

    // Count tickets issued today to form the sequential counter
    $count_today = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM {$table_bookings} WHERE DATE(booking_date) = %s",
        $today_ymd
    ) );

    $sequence_number = str_pad( (string) ( $count_today + 1 ), 2, '0', STR_PAD_LEFT );
    $candidate_token = sprintf( '%s-%s-%s', sanitize_key( $prefix ), $day_str, $sequence_number );

    // Collision check: fallback if duplicate index exists
    $token_exists = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(id) FROM {$table_bookings} WHERE booking_token = %s",
        $candidate_token
    ) );

    if ( $token_exists > 0 ) {
        $extra_seq       = str_pad( (string) wp_rand( 10, 99 ), 2, '0', STR_PAD_LEFT );
        $candidate_token = sprintf( '%s-%s-%s', sanitize_key( $prefix ), $day_str, $extra_seq );
    }

    return $candidate_token;
}

/**
 * Seeds initial master administrative staff record if table is empty.
 */
function bsc_seed_initial_staff() {
    global $wpdb;
    $table = $wpdb->prefix . 'bsc_staff_crew';

    $exists = $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
    if ( 0 === (int) $exists ) {
        $wpdb->insert(
            $table,
            array(
                'staff_code'       => '#STF-1001',
                'name'             => 'Abdullah Nahian',
                'role_title'       => 'Master Administrator',
                'assigned_hall'    => 'All Multiplex Facilities',
                'duty_status'      => 'On Duty',
                'shift_schedule'   => 'Full Rotational',
                'phone'            => '+880 1700-000001',
                'email'            => 'admin@bigscreencinemas.com',
                'salary'           => 60000.00,
                'photo_url'        => '',
                'responsibilities' => 'Full administrative access, box-office accounting supervision, and master show scheduler.',
                'joined_date'      => current_time( 'Y-m-d' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
        );
    }
}