<?php
/**
 * Custom Post Type definitions & metaboxes.
 * File: includes/class-ecare-cpt.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECare_CPT {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_metaboxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_metaboxes' ) );
	}

	public static function register_post_types() {

		// Caregiver / Nurse.
		register_post_type( 'ecare_caregiver', array(
			'labels' => array(
				'name'          => 'Caregivers',
				'singular_name' => 'Caregiver',
				'add_new_item'  => 'Add New Caregiver',
				'edit_item'     => 'Edit Caregiver',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'ecare-dashboard',
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'    => 'dashicons-groups',
		) );

		// Lab Test.
		register_post_type( 'ecare_lab_test', array(
			'labels' => array(
				'name'          => 'Lab Tests',
				'singular_name' => 'Lab Test',
				'add_new_item'  => 'Add New Lab Test',
				'edit_item'     => 'Edit Lab Test',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'ecare-dashboard',
			'supports'     => array( 'title', 'editor' ),
			'menu_icon'    => 'dashicons-clipboard',
		) );

		// Ambulance.
		register_post_type( 'ecare_ambulance', array(
			'labels' => array(
				'name'          => 'Ambulances',
				'singular_name' => 'Ambulance',
				'add_new_item'  => 'Add New Ambulance',
				'edit_item'     => 'Edit Ambulance',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'ecare-dashboard',
			'supports'     => array( 'title', 'editor' ),
			'menu_icon'    => 'dashicons-car',
		) );
	}

	public static function add_metaboxes() {
		add_meta_box( 'ecare_caregiver_details', 'Caregiver Details', array( __CLASS__, 'render_caregiver_metabox' ), 'ecare_caregiver', 'normal', 'high' );
		add_meta_box( 'ecare_lab_test_details', 'Lab Test Details', array( __CLASS__, 'render_lab_test_metabox' ), 'ecare_lab_test', 'normal', 'high' );
		add_meta_box( 'ecare_ambulance_details', 'Ambulance Details', array( __CLASS__, 'render_ambulance_metabox' ), 'ecare_ambulance', 'normal', 'high' );
	}

	public static function render_caregiver_metabox( $post ) {
		wp_nonce_field( 'ecare_save_meta', 'ecare_meta_nonce' );
		$provider_type = get_post_meta( $post->ID, '_provider_type', true );
		$phone         = get_post_meta( $post->ID, '_phone', true );
		$package_rates = get_post_meta( $post->ID, '_package_rates', true ); // JSON string
		$status        = get_post_meta( $post->ID, '_approval_status', true ) ?: 'pending';
		?>
		<p>
			<label>Provider Type</label><br>
			<select name="ecare_provider_type" style="width:100%;">
				<option value="Nurse" <?php selected( $provider_type, 'Nurse' ); ?>>Nurse</option>
				<option value="Senior Care" <?php selected( $provider_type, 'Senior Care' ); ?>>Senior Care</option>
				<option value="Nanny" <?php selected( $provider_type, 'Nanny' ); ?>>Nanny</option>
				<option value="Physiotherapist" <?php selected( $provider_type, 'Physiotherapist' ); ?>>Physiotherapist</option>
			</select>
		</p>
		<p>
			<label>Phone</label><br>
			<input type="text" name="ecare_phone" value="<?php echo esc_attr( $phone ); ?>" style="width:100%;">
		</p>
		<p>
			<label>Package Rates (JSON: [{"duration":"1 Day","price":1500}])</label><br>
			<textarea name="ecare_package_rates" style="width:100%;" rows="4"><?php echo esc_textarea( $package_rates ); ?></textarea>
		</p>
		<p>
			<label>Approval Status</label><br>
			<select name="ecare_approval_status">
				<option value="pending" <?php selected( $status, 'pending' ); ?>>Pending</option>
				<option value="approved" <?php selected( $status, 'approved' ); ?>>Approved</option>
				<option value="rejected" <?php selected( $status, 'rejected' ); ?>>Rejected</option>
			</select>
		</p>
		<?php
	}

	public static function render_lab_test_metabox( $post ) {
		wp_nonce_field( 'ecare_save_meta', 'ecare_meta_nonce' );
		$price       = get_post_meta( $post->ID, '_test_price', true );
		$location_id = get_post_meta( $post->ID, '_location_id', true );
		?>
		<p>
			<label>Price (BDT)</label><br>
			<input type="number" step="0.01" name="ecare_test_price" value="<?php echo esc_attr( $price ); ?>" style="width:100%;">
		</p>
		<p>
			<label>Location ID (from ecare_locations table, provider level)</label><br>
			<input type="number" name="ecare_location_id" value="<?php echo esc_attr( $location_id ); ?>" style="width:100%;">
		</p>
		<?php
	}

	public static function render_ambulance_metabox( $post ) {
		wp_nonce_field( 'ecare_save_meta', 'ecare_meta_nonce' );
		$vehicle_type = get_post_meta( $post->ID, '_vehicle_type', true );
		$driver_name  = get_post_meta( $post->ID, '_driver_name', true );
		$driver_phone = get_post_meta( $post->ID, '_driver_phone', true );
		$base_fare    = get_post_meta( $post->ID, '_base_fare', true );
		?>
		<p>
			<label>Vehicle Type</label><br>
			<select name="ecare_vehicle_type">
				<option value="Standard Non-AC" <?php selected( $vehicle_type, 'Standard Non-AC' ); ?>>Standard Non-AC</option>
				<option value="ICU AC" <?php selected( $vehicle_type, 'ICU AC' ); ?>>ICU AC</option>
				<option value="Freezer Van" <?php selected( $vehicle_type, 'Freezer Van' ); ?>>Freezer Van</option>
			</select>
		</p>
		<p>
			<label>Driver Name</label><br>
			<input type="text" name="ecare_driver_name" value="<?php echo esc_attr( $driver_name ); ?>" style="width:100%;">
		</p>
		<p>
			<label>Driver Phone</label><br>
			<input type="text" name="ecare_driver_phone" value="<?php echo esc_attr( $driver_phone ); ?>" style="width:100%;">
		</p>
		<p>
			<label>Base Fare (BDT)</label><br>
			<input type="number" step="0.01" name="ecare_base_fare" value="<?php echo esc_attr( $base_fare ); ?>" style="width:100%;">
		</p>
		<?php
	}

	public static function save_metaboxes( $post_id ) {
		if ( ! isset( $_POST['ecare_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ecare_meta_nonce'], 'ecare_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( 'ecare_caregiver' === $post_type ) {
			if ( isset( $_POST['ecare_provider_type'] ) ) {
				update_post_meta( $post_id, '_provider_type', sanitize_text_field( $_POST['ecare_provider_type'] ) );
			}
			if ( isset( $_POST['ecare_phone'] ) ) {
				update_post_meta( $post_id, '_phone', sanitize_text_field( $_POST['ecare_phone'] ) );
			}
			if ( isset( $_POST['ecare_package_rates'] ) ) {
				update_post_meta( $post_id, '_package_rates', wp_kses_post( $_POST['ecare_package_rates'] ) );
			}
			if ( isset( $_POST['ecare_approval_status'] ) ) {
				update_post_meta( $post_id, '_approval_status', sanitize_text_field( $_POST['ecare_approval_status'] ) );
			}
		}

		if ( 'ecare_lab_test' === $post_type ) {
			if ( isset( $_POST['ecare_test_price'] ) ) {
				update_post_meta( $post_id, '_test_price', floatval( $_POST['ecare_test_price'] ) );
			}
			if ( isset( $_POST['ecare_location_id'] ) ) {
				update_post_meta( $post_id, '_location_id', intval( $_POST['ecare_location_id'] ) );
			}
		}

		if ( 'ecare_ambulance' === $post_type ) {
			if ( isset( $_POST['ecare_vehicle_type'] ) ) {
				update_post_meta( $post_id, '_vehicle_type', sanitize_text_field( $_POST['ecare_vehicle_type'] ) );
			}
			if ( isset( $_POST['ecare_driver_name'] ) ) {
				update_post_meta( $post_id, '_driver_name', sanitize_text_field( $_POST['ecare_driver_name'] ) );
			}
			if ( isset( $_POST['ecare_driver_phone'] ) ) {
				update_post_meta( $post_id, '_driver_phone', sanitize_text_field( $_POST['ecare_driver_phone'] ) );
			}
			if ( isset( $_POST['ecare_base_fare'] ) ) {
				update_post_meta( $post_id, '_base_fare', floatval( $_POST['ecare_base_fare'] ) );
			}
		}
	}
}
