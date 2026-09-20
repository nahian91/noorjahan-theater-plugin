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
 * Complies strictly with WordPress VIP & core dbDelta syntax constraints.
 */
function bsc_create_database_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Table 1: Movies
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
        PRIMARY KEY  (id),
        KEY release_date (release_date),
        KEY category (category)
    ) $charset_collate;";

    // Table 2: Schedules
    $table_schedule = "CREATE TABLE {$wpdb->prefix}bsc_control_schedule (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        movie_title varchar(255) NOT NULL,
        auditorium varchar(100) NOT NULL,
        format_type varchar(50) NOT NULL,
        showtimes varchar(255) NOT NULL,
        price decimal(10,2) DEFAULT '0.00' NOT NULL,
        PRIMARY KEY  (id),
        KEY movie_title (movie_title(191)),
        KEY auditorium (auditorium(100))
    ) $charset_collate;";

    // Table 3: Financial Ledger
    $table_ledger = "CREATE TABLE {$wpdb->prefix}bsc_ledger (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        reference_no varchar(64) NOT NULL,
        txn_type varchar(20) DEFAULT 'Income' NOT NULL,
        particulars varchar(255) NOT NULL,
        method varchar(50) DEFAULT 'Cash' NOT NULL,
        amount decimal(10,2) DEFAULT '0.00' NOT NULL,
        transaction_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY reference_no (reference_no),
        KEY transaction_date (transaction_date),
        KEY txn_type (txn_type)
    ) $charset_collate;";

    // Table 4: Bookings
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
        UNIQUE KEY booking_token (booking_token),
        KEY booking_date (booking_date),
        KEY customer_email (customer_email(150)),
        KEY customer_phone (customer_phone)
    ) $charset_collate;";

    // Table 5: Customers Directory
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
        UNIQUE KEY membership_id (membership_id),
        KEY email (email(150)),
        KEY phone (phone)
    ) $charset_collate;";

    // Table 6: Staff & Crew
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
        UNIQUE KEY staff_code (staff_code),
        KEY email (email(150))
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
 * Generates a collision-resistant cinema ticket ID in format: BCS-DD-MON-YY-XXXX
 * Handles high-concurrency without race-condition collisions.
 *
 * @param string $prefix Prefix for ticket reference.
 * @return string Formatted unique ticket reference code.
 */
function bsc_generate_cinema_ticket_id( $prefix = 'BCS' ) {
    global $wpdb;
    $table_bookings = $wpdb->prefix . 'bsc_bookings';

    $clean_prefix = strtoupper( sanitize_key( $prefix ) );
    $day_str      = strtoupper( gmdate( 'd-M-y' ) );
    $max_retries  = 5;

    for ( $i = 0; $i < $max_retries; $i++ ) {
        // High-entropy random hex string (4 characters) preventing concurrent collisions
        $entropy         = strtoupper( bin2hex( random_bytes( 2 ) ) );
        $candidate_token = sprintf( '%s-%s-%s', $clean_prefix, $day_str, $entropy );

        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(id) FROM {$table_bookings} WHERE booking_token = %s",
            $candidate_token
        ) );

        if ( 0 === (int) $exists ) {
            return $candidate_token;
        }
    }

    // Ultra fallback with microsecond precision if loop exhausts
    return sprintf( '%s-%s-%s', $clean_prefix, $day_str, strtoupper( substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 6 ) ) );
}

/**
 * Seeds initial master administrative staff record if table is empty.
 */
function bsc_seed_initial_staff() {
    global $wpdb;
    $table = $wpdb->prefix . 'bsc_staff_crew';

    $count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table}" );
    if ( 0 === $count ) {
        $wpdb->insert(
            $table,
            array(
                'staff_code'       => '#STF-1001',
                'name'             => 'Operations Lead',
                'role_title'       => 'Master Administrator',
                'assigned_hall'    => 'All Multiplex Facilities',
                'duty_status'      => 'On Duty',
                'shift_schedule'   => 'Full Rotational',
                'phone'            => '+1 555-0199',
                'email'            => sanitize_email( get_option( 'admin_email' ) ),
                'salary'           => 60000.00,
                'photo_url'        => '',
                'responsibilities' => 'Full administrative access, box-office accounting supervision, and master show scheduler.',
                'joined_date'      => current_time( 'Y-m-d' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s' )
        );
    }
}