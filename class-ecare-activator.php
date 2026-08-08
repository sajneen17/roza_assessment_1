<?php
/**
 * Fired during plugin activation.
 * Creates custom DB tables and seeds default location data.
 *
 * File: includes/class-ecare-activator.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECare_Activator {

	public static function activate() {
		self::create_tables();
		self::seed_locations();
		update_option( 'ecare_activation_date', current_time( 'mysql' ) );
		flush_rewrite_rules();
	}

	private static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$bookings_table = $wpdb->prefix . 'ecare_bookings';
		$locations_table = $wpdb->prefix . 'ecare_locations';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Main bookings / dispatch table (caregiver, lab test, ambulance).
		$sql_bookings = "CREATE TABLE {$bookings_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_type VARCHAR(30) NOT NULL,          -- caregiver | lab_test | ambulance
			provider_id BIGINT(20) UNSIGNED DEFAULT NULL,
			customer_name VARCHAR(191) NOT NULL,
			customer_phone VARCHAR(30) NOT NULL,
			customer_address TEXT NULL,
			package_name VARCHAR(191) NULL,
			package_price DECIMAL(10,2) DEFAULT 0,
			ambulance_type VARCHAR(60) NULL,
			pickup_location VARCHAR(191) NULL,
			drop_location VARCHAR(191) NULL,
			order_id BIGINT(20) UNSIGNED DEFAULT NULL,   -- linked WooCommerce order
			status VARCHAR(30) NOT NULL DEFAULT 'pending', -- pending|confirmed|in_progress|completed|cancelled
			meta LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY booking_type (booking_type),
			KEY status (status),
			KEY provider_id (provider_id)
		) {$charset_collate};";

		// Location hierarchy table: Division -> District -> Area -> Lab Provider.
		$sql_locations = "CREATE TABLE {$locations_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			parent_id BIGINT(20) UNSIGNED DEFAULT 0,
			level VARCHAR(20) NOT NULL,   -- division|district|area|provider
			name VARCHAR(191) NOT NULL,
			PRIMARY KEY  (id),
			KEY parent_id (parent_id),
			KEY level (level)
		) {$charset_collate};";

		dbDelta( $sql_bookings );
		dbDelta( $sql_locations );
	}

	private static function seed_locations() {
		global $wpdb;
		$table = $wpdb->prefix . 'ecare_locations';

		// Avoid re-seeding on re-activation.
		$existing = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $existing > 0 ) {
			return;
		}

		// Dhaka Division.
		$wpdb->insert( $table, array( 'parent_id' => 0, 'level' => 'division', 'name' => 'Dhaka' ) );
		$division_id = $wpdb->insert_id;

		// Dhaka District.
		$wpdb->insert( $table, array( 'parent_id' => $division_id, 'level' => 'district', 'name' => 'Dhaka' ) );
		$district_id = $wpdb->insert_id;

		// Ashkona Area.
		$wpdb->insert( $table, array( 'parent_id' => $district_id, 'level' => 'area', 'name' => 'Ashkona' ) );
		$area_id = $wpdb->insert_id;

		// Praava Health Provider.
		$wpdb->insert( $table, array( 'parent_id' => $area_id, 'level' => 'provider', 'name' => 'Praava Health' ) );
	}
}
