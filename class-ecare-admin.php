<?php
/**
 * Meditaj/Shukhee-style Admin Menu Pages.
 * File: admin/class-ecare-admin.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECare_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	public static function register_menu() {
		add_menu_page(
			'E-Care Dashboard',
			'E-Care',
			'manage_options',
			'ecare-dashboard',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-heart',
			26
		);

		add_submenu_page( 'ecare-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'ecare-dashboard', array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( 'ecare-dashboard', 'Caregiver Bookings', 'Caregiver Bookings', 'manage_options', 'ecare-caregiver-bookings', array( __CLASS__, 'render_caregiver_bookings' ) );
		add_submenu_page( 'ecare-dashboard', 'Lab Test Orders', 'Lab Test Orders', 'manage_options', 'ecare-labtest-orders', array( __CLASS__, 'render_labtest_orders' ) );
		add_submenu_page( 'ecare-dashboard', 'Ambulance Requests', 'Ambulance Requests', 'manage_options', 'ecare-ambulance-requests', array( __CLASS__, 'render_ambulance_requests' ) );
	}

	private static function get_kpis() {
		global $wpdb;
		$table = $wpdb->prefix . 'ecare_bookings';

		return array(
			'total_bookings'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
			'active_providers'   => (int) wp_count_posts( 'ecare_caregiver' )->publish + (int) wp_count_posts( 'ecare_ambulance' )->publish,
			'pending_approvals'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" ),
			'completed_missions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'completed'" ),
		);
	}

	private static function render_kpi_row() {
		$kpis = self::get_kpis();
		?>
		<div class="ecare-kpi-row">
			<div class="ecare-kpi-card"><span class="ecare-kpi-value"><?php echo esc_html( $kpis['total_bookings'] ); ?></span><span class="ecare-kpi-label">Total Bookings</span></div>
			<div class="ecare-kpi-card"><span class="ecare-kpi-value"><?php echo esc_html( $kpis['active_providers'] ); ?></span><span class="ecare-kpi-label">Active Providers</span></div>
			<div class="ecare-kpi-card"><span class="ecare-kpi-value"><?php echo esc_html( $kpis['pending_approvals'] ); ?></span><span class="ecare-kpi-label">Pending Approvals</span></div>
			<div class="ecare-kpi-card"><span class="ecare-kpi-value"><?php echo esc_html( $kpis['completed_missions'] ); ?></span><span class="ecare-kpi-label">Completed Missions</span></div>
		</div>
		<?php
	}

	private static function status_badge( $status ) {
		$colors = array(
			'pending'     => '#F59E0B',
			'confirmed'   => '#3B82F6',
			'in_progress' => '#8B5CF6',
			'completed'   => '#0E9F6E',
			'cancelled'   => '#EF4444',
		);
		$color = isset( $colors[ $status ] ) ? $colors[ $status ] : '#6B7280';
		printf( '<span class="ecare-status-pill" style="background:%1$s20;color:%1$s;">%2$s</span>', esc_attr( $color ), esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ) );
	}

	private static function bookings_table( $type ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ecare_bookings';
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE booking_type = %s ORDER BY created_at DESC", $type ) );
		?>
		<table class="ecare-data-table widefat">
			<thead>
				<tr>
					<th>ID</th><th>Customer</th><th>Phone</th><th>Package/Type</th><th>Price</th><th>Order</th><th>Status</th><th>Date</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="8">No records found.</td></tr>
				<?php else : foreach ( $rows as $row ) : ?>
					<tr>
						<td>#<?php echo esc_html( $row->id ); ?></td>
						<td><?php echo esc_html( $row->customer_name ); ?></td>
						<td><?php echo esc_html( $row->customer_phone ); ?></td>
						<td><?php echo esc_html( $row->package_name ?: $row->ambulance_type ); ?></td>
						<td>৳<?php echo esc_html( number_format( (float) $row->package_price, 2 ) ); ?></td>
						<td><?php echo $row->order_id ? '<a href="' . esc_url( admin_url( 'post.php?post=' . $row->order_id . '&action=edit' ) ) . '">#' . esc_html( $row->order_id ) . '</a>' : '—'; ?></td>
						<td>
							<select class="ecare-status-dropdown" data-booking-id="<?php echo esc_attr( $row->id ); ?>">
								<?php foreach ( array( 'pending', 'confirmed', 'in_progress', 'completed', 'cancelled' ) as $status ) : ?>
									<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $row->status, $status ); ?>><?php echo esc_html( ucfirst( $status ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<td><?php echo esc_html( mysql2date( 'd M Y, h:i A', $row->created_at ) ); ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
		<?php
	}

	public static function render_dashboard() {
		?>
		<div class="wrap ecare-admin-wrap">
			<h1>E-Care Health Services Dashboard</h1>
			<?php self::render_kpi_row(); ?>
			<div class="ecare-actions-header">
				<input type="text" id="ecare-admin-search" placeholder="Search bookings...">
				<button type="button" class="ecare-export-btn" id="ecare-export-csv">Export to CSV</button>
			</div>
			<h2>Recent Activity (All Bookings)</h2>
			<?php self::bookings_table_all(); ?>
		</div>
		<?php
	}

	private static function bookings_table_all() {
		global $wpdb;
		$table = $wpdb->prefix . 'ecare_bookings';
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 50" );
		?>
		<table class="ecare-data-table widefat">
			<thead>
				<tr><th>ID</th><th>Type</th><th>Customer</th><th>Phone</th><th>Price</th><th>Status</th><th>Date</th></tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="7">No bookings yet.</td></tr>
				<?php else : foreach ( $rows as $row ) : ?>
					<tr>
						<td>#<?php echo esc_html( $row->id ); ?></td>
						<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $row->booking_type ) ) ); ?></td>
						<td><?php echo esc_html( $row->customer_name ); ?></td>
						<td><?php echo esc_html( $row->customer_phone ); ?></td>
						<td>৳<?php echo esc_html( number_format( (float) $row->package_price, 2 ) ); ?></td>
						<td><?php self::status_badge( $row->status ); ?></td>
						<td><?php echo esc_html( mysql2date( 'd M Y, h:i A', $row->created_at ) ); ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
		<?php
	}

	public static function render_caregiver_bookings() {
		?>
		<div class="wrap ecare-admin-wrap">
			<h1>Caregiver Bookings</h1>
			<?php self::bookings_table( 'caregiver' ); ?>
		</div>
		<?php
	}

	public static function render_labtest_orders() {
		?>
		<div class="wrap ecare-admin-wrap">
			<h1>Lab Test Orders</h1>
			<?php self::bookings_table( 'lab_test' ); ?>
		</div>
		<?php
	}

	public static function render_ambulance_requests() {
		?>
		<div class="wrap ecare-admin-wrap">
			<h1>Ambulance Requests</h1>
			<?php self::bookings_table( 'ambulance' ); ?>
		</div>
		<?php
	}
}
