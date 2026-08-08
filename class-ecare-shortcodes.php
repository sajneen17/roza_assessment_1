<?php
/**
 * Frontend HTML shortcode markup.
 * File: includes/class-ecare-shortcodes.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECare_Shortcodes {

	public static function init() {
		add_shortcode( 'ecare_caregiver_booking', array( __CLASS__, 'caregiver_booking' ) );
		add_shortcode( 'ecare_caregiver_registration', array( __CLASS__, 'caregiver_registration' ) );
		add_shortcode( 'ecare_lab_tests', array( __CLASS__, 'lab_tests' ) );
		add_shortcode( 'ecare_ambulance_request', array( __CLASS__, 'ambulance_request' ) );
		add_shortcode( 'ecare_ambulance_registration', array( __CLASS__, 'ambulance_registration' ) );
	}

	/** [ecare_caregiver_booking] */
	public static function caregiver_booking() {
		$provider_types = array( 'Nurse', 'Senior Care', 'Nanny', 'Physiotherapist' );

		$caregivers = get_posts( array(
			'post_type'      => 'ecare_caregiver',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array( 'key' => '_approval_status', 'value' => 'approved' ),
			),
		) );

		ob_start();
		?>
		<div class="ecare-caregiver-wrapper">
			<div class="ecare-filter-tabs">
				<?php foreach ( $provider_types as $type ) : ?>
					<button type="button" class="ecare-tab" data-type="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type ); ?></button>
				<?php endforeach; ?>
			</div>

			<div class="ecare-caregiver-grid">
				<?php foreach ( $caregivers as $cg ) :
					$provider_type = get_post_meta( $cg->ID, '_provider_type', true );
					$phone         = get_post_meta( $cg->ID, '_phone', true );
					$rates_json    = get_post_meta( $cg->ID, '_package_rates', true );
					$rates         = json_decode( $rates_json, true );
					?>
					<div class="ecare-caregiver-card" data-type="<?php echo esc_attr( $provider_type ); ?>" data-id="<?php echo esc_attr( $cg->ID ); ?>">
						<?php echo get_the_post_thumbnail( $cg->ID, 'thumbnail' ); ?>
						<h3><?php echo esc_html( $cg->post_title ); ?></h3>
						<p class="ecare-provider-type"><?php echo esc_html( $provider_type ); ?></p>
						<?php if ( ! empty( $rates ) ) : ?>
							<select class="ecare-package-select">
								<?php foreach ( $rates as $rate ) : ?>
									<option value="<?php echo esc_attr( $rate['price'] ); ?>" data-name="<?php echo esc_attr( $rate['duration'] ); ?>">
										<?php echo esc_html( $rate['duration'] . ' - ৳' . $rate['price'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>
						<button type="button" class="ecare-book-btn" data-provider="<?php echo esc_attr( $cg->ID ); ?>">Book Now</button>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Booking Modal -->
			<div class="ecare-modal" id="ecare-caregiver-modal" style="display:none;">
				<div class="ecare-modal-content">
					<span class="ecare-modal-close">&times;</span>
					<h3>Book Caregiver</h3>
					<form id="ecare-caregiver-booking-form">
						<input type="hidden" name="provider_id" id="ecare-modal-provider-id">
						<input type="hidden" name="package_name" id="ecare-modal-package-name">
						<input type="hidden" name="package_price" id="ecare-modal-package-price">
						<p><label>Name</label><input type="text" name="name" required></p>
						<p><label>Phone</label><input type="text" name="phone" required></p>
						<p><label>Address</label><textarea name="address" required></textarea></p>
						<button type="submit" class="ecare-submit-btn">Submit Booking</button>
					</form>
					<div class="ecare-form-message"></div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** [ecare_caregiver_registration] */
	public static function caregiver_registration() {
		ob_start();
		?>
		<form id="ecare-caregiver-registration-form" enctype="multipart/form-data" class="ecare-registration-form">
			<h3>Register as a Caregiver</h3>
			<p><label>Full Name</label><input type="text" name="full_name" required></p>
			<p><label>Provider Type</label>
				<select name="provider_type" required>
					<option value="Nurse">Nurse</option>
					<option value="Senior Care">Senior Care</option>
					<option value="Nanny">Nanny</option>
					<option value="Physiotherapist">Physiotherapist</option>
				</select>
			</p>
			<p><label>Phone</label><input type="text" name="phone" required></p>
			<p><label>Biography / Education / Special Skills</label><textarea name="bio" rows="4"></textarea></p>
			<p><label>Package Rates (e.g. 1 Day - 1500, 1 Week - 8000)</label><textarea name="package_rates" rows="3"></textarea></p>
			<p><label>Bank Account Info</label><input type="text" name="bank_info"></p>
			<p><label>Upload Certificate / NID</label><input type="file" name="certificate"></p>
			<button type="submit" class="ecare-submit-btn">Submit Registration</button>
			<div class="ecare-form-message"></div>
		</form>
		<?php
		return ob_get_clean();
	}

	/** [ecare_lab_tests] */
	public static function lab_tests() {
		$tests = get_posts( array(
			'post_type'      => 'ecare_lab_test',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		) );

		ob_start();
		?>
		<div class="ecare-labtest-wrapper">
			<div class="ecare-location-bar">
				<select id="ecare-division" class="ecare-loc-select" data-level="division"><option value="">Division</option></select>
				<select id="ecare-district" class="ecare-loc-select" data-level="district" disabled><option value="">District</option></select>
				<select id="ecare-area" class="ecare-loc-select" data-level="area" disabled><option value="">Area</option></select>
				<select id="ecare-provider" class="ecare-loc-select" data-level="provider" disabled><option value="">Lab Provider</option></select>
				<input type="text" id="ecare-test-search" placeholder="Search tests...">
			</div>

			<div class="ecare-test-grid">
				<?php foreach ( $tests as $test ) :
					$price = get_post_meta( $test->ID, '_test_price', true );
					?>
					<div class="ecare-test-card" data-name="<?php echo esc_attr( strtolower( $test->post_title ) ); ?>">
						<h4><?php echo esc_html( $test->post_title ); ?></h4>
						<p class="ecare-test-price">৳<?php echo esc_html( $price ); ?></p>
						<button type="button" class="ecare-add-cart-btn" data-test-id="<?php echo esc_attr( $test->ID ); ?>">+</button>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** [ecare_ambulance_request] */
	public static function ambulance_request() {
		ob_start();
		?>
		<form id="ecare-ambulance-form" class="ecare-ambulance-form">
			<h3>Request an Ambulance</h3>
			<div class="ecare-ambulance-types">
				<label class="ecare-type-card"><input type="radio" name="ambulance_type" value="Standard Non-AC" required> Standard Non-AC</label>
				<label class="ecare-type-card"><input type="radio" name="ambulance_type" value="ICU AC"> ICU AC</label>
				<label class="ecare-type-card"><input type="radio" name="ambulance_type" value="Freezer Van"> Freezer Van</label>
			</div>
			<p><label>Name</label><input type="text" name="name" required></p>
			<p><label>Phone</label><input type="text" name="phone" required></p>
			<p><label>Pickup Location</label><input type="text" name="pickup_location" required></p>
			<p><label>Drop Location</label><input type="text" name="drop_location" required></p>
			<input type="hidden" name="estimated_fare" id="ecare-estimated-fare" value="0">
			<div class="ecare-fare-summary">Estimated Fare: ৳<span id="ecare-fare-display">0</span></div>
			<p><label><input type="checkbox" required> I agree to the terms and conditions</label></p>
			<button type="submit" class="ecare-submit-btn">Request Ambulance</button>
			<div class="ecare-form-message"></div>
		</form>
		<?php
		return ob_get_clean();
	}

	/** [ecare_ambulance_registration] */
	public static function ambulance_registration() {
		ob_start();
		?>
		<form id="ecare-ambulance-registration-form" class="ecare-registration-form">
			<h3>Register Your Ambulance</h3>
			<p><label>Owner Name</label><input type="text" name="owner_name" required></p>
			<p><label>Vehicle Type</label>
				<select name="vehicle_type" required>
					<option value="Standard Non-AC">Standard Non-AC</option>
					<option value="ICU AC">ICU AC</option>
					<option value="Freezer Van">Freezer Van</option>
				</select>
			</p>
			<p><label>Driver Name</label><input type="text" name="driver_name" required></p>
			<p><label>Driver Phone</label><input type="text" name="driver_phone" required></p>
			<p><label>Driver License / NID</label><input type="file" name="driver_license"></p>
			<p><label>Base Fare (BDT)</label><input type="number" step="0.01" name="base_fare" required></p>
			<button type="submit" class="ecare-submit-btn">Submit Registration</button>
			<div class="ecare-form-message"></div>
		</form>
		<?php
		return ob_get_clean();
	}
}
