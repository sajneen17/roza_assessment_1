<?php
/**
 * Checkout order completion updates - links WooCommerce orders back to
 * ecare_bookings rows for lab tests.
 * File: includes/class-ecare-woocommerce.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECare_WooCommerce {

	public static function init() {
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'mark_lab_test_paid' ) );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'mark_lab_test_paid' ) );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'record_lab_test_order' ), 10, 3 );
	}

	/**
	 * When a checkout completes, create/update an ecare_bookings row of
	 * type "lab_test" for each linked lab-test product line item.
	 */
	public static function record_lab_test_order( $order_id, $posted_data, $order ) {
		global $wpdb;

		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order ) {
			return;
		}

		$table = $wpdb->prefix . 'ecare_bookings';

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();

			// Find the ecare_lab_test post linked to this product.
			$lab_tests = get_posts( array(
				'post_type'  => 'ecare_lab_test',
				'meta_key'   => '_linked_product_id',
				'meta_value' => $product_id,
				'fields'     => 'ids',
				'posts_per_page' => 1,
			) );

			if ( empty( $lab_tests ) ) {
				continue;
			}

			$test_id = $lab_tests[0];

			$wpdb->insert( $table, array(
				'booking_type'     => 'lab_test',
				'provider_id'      => $test_id,
				'customer_name'    => $order->get_formatted_billing_full_name(),
				'customer_phone'   => $order->get_billing_phone(),
				'customer_address' => $order->get_formatted_billing_address(),
				'package_name'     => get_the_title( $test_id ),
				'package_price'    => (float) $item->get_total(),
				'order_id'         => $order_id,
				'status'           => 'pending',
				'created_at'       => current_time( 'mysql' ),
				'updated_at'       => current_time( 'mysql' ),
			) );
		}
	}

	/**
	 * Update booking status to "confirmed" once payment is completed/processing.
	 */
	public static function mark_lab_test_paid( $order_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ecare_bookings';

		$wpdb->update(
			$table,
			array( 'status' => 'confirmed', 'updated_at' => current_time( 'mysql' ) ),
			array( 'order_id' => $order_id, 'booking_type' => 'lab_test' )
		);
	}
}
