<?php
/**
 * Reservation Data Meta Boxes.
 *
 * @author   Benito Lopez <hello@lopezb.com>
 * @category Admin
 * @package  Hotelier/Admin/Meta Boxes
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'HTL_Meta_Box_Reservation_Data' ) ) :

/**
 * HTL_Meta_Box_Reservation_Data Class
 */
class HTL_Meta_Box_Reservation_Data {

	/**
	 * Guest details
	 *
	 * @var array
	 */
	protected static $guest_details = array();

	/**
	 * Guest info
	 *
	 * @var array
	 */
	protected static $guest_info = array();

	/**
	 * Init guest details fields
	 */
	public static function init_guest_details() {

		self::$guest_details = apply_filters( 'hotelier_admin_guest_details_fields', array(
			'first_name' => array(
				'label'    => esc_html__( 'First name', 'wp-hotelier' ),
				'required' => true,
			),
			'last_name' => array(
				'label'         => esc_html__( 'Last name', 'wp-hotelier' ),
				'wrapper_class' => 'form-field-last',
				'required'      => true,
			),
			'email' => array(
				'label'    => esc_html__( 'Email address', 'wp-hotelier' ),
				'type'     => 'email',
			),
			'telephone' => array(
				'label'         => esc_html__( 'Telephone', 'wp-hotelier' ),
				'wrapper_class' => 'form-field-last'
			),
			'address1' => array(
				'label'         => esc_html__( 'Address', 'wp-hotelier' ),
				'wrapper_class' => 'form-field-wide'
			),
			'city' => array(
				'label' => esc_html__( 'Town / City', 'wp-hotelier' ),
			),
			'country' => array(
				'label'         => esc_html__( 'Country', 'wp-hotelier' ),
				'wrapper_class' => 'form-field-last',
				'required'      => true,
				'type'			=> 'select',
				'options'		=> htl_country_array()
			),
			'internal_notes' => array(
				'label'         => esc_html__( 'Internal notes', 'wp-hotelier' ),
				'type'			=> 'textarea',
				'wrapper_class' => 'form-field-last'
			)
		) );
	}

	/**
	 * Init guest info fields
	 */
	public static function init_guest_info() {

		self::$guest_info = apply_filters( 'hotelier_admin_guest_info_fields', array(
			'guest_arrival_time' => array(
				'id'      => 'guest_arrival_time',
				'label'   => esc_html__( 'Estimated arrival time', 'wp-hotelier' ),
				'type'    => 'select',
				'options' => htl_arrival_times_array()
			)
		) );
	}

	/**
	 * Get guest details fields
	 */
	public static function get_guest_details_fields() {
		self::init_guest_details();

		return self::$guest_details;
	}

	/**
	 * Get guest info fields
	 */
	public static function get_guest_info_fields() {
		self::init_guest_info();

		return self::$guest_info;
	}

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		global $thereservation;

		if ( ! is_object( $thereservation ) ) {
			$thereservation = htl_get_reservation( $post->ID );
		}

		$reservation = $thereservation;
		if ( HTL()->payment_gateways() ) {
			$payment_gateways = HTL()->payment_gateways->get_available_payment_gateways();
		} else {
			$payment_gateways = array();
		}

		$payment_method = $reservation->get_payment_method() ? $reservation->get_payment_method() : '';

		$booking_method = $reservation->booking_method;

		self::init_guest_details();
		self::init_guest_info();

		wp_nonce_field( 'hotelier_save_data', 'hotelier_meta_nonce' );
		?>

		<style type="text/css">
			#post-body-content, #titlediv, .misc-pub-section.misc-pub-post-status, #visibility, #minor-publishing-actions { display:none }
		</style>
		<link rel="stylesheet" type="text/css" href="https://code.jquery.com/ui/1.12.0/themes/smoothness/jquery-ui.css">

		<script type="text/javascript" src="https://code.jquery.com/ui/1.12.0/jquery-ui.js"></script>

		<div class="panel-wrap hotelier">
			<input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? esc_attr__( 'Reservation', 'wp-hotelier' ) : esc_attr( $post->post_title ); ?>" />
			<input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />

			<div id="reservation-data" class="panel">
				<h2><?php echo sprintf( esc_html__( 'Reservation #%d details', 'wp-hotelier' ), $reservation->id ); ?></h2>

				<p class="booking-details">
					<span><?php echo esc_html__( 'Booking mode', 'wp-hotelier' ) . ': ' . esc_html( ucfirst( str_replace( '-', ' ', $booking_method ) ) ); ?></span>

					<?php if ( ( get_post_meta( $post->ID, '_created_via', true ) == 'admin' ) ) : ?>
						<span> <?php esc_html_e( '(created by admin)', 'wp-hotelier' ); ?></span>
					<?php endif; ?>

					<?php if ( $payment_method ) : ?>
						<span> &ndash; <?php echo sprintf( esc_html__( 'Payment via %s', 'wp-hotelier' ), $reservation->get_payment_method_title() ); ?></span>

						<?php if ( $transaction_id = $reservation->get_transaction_id() ) {
							if ( isset( $payment_gateways[ $payment_method ] ) && ( $url = $payment_gateways[ $payment_method ]->get_transaction_url( $reservation ) ) ) {
								?>
								<div><small><?php _e( 'Transaction ID:', 'wp-hotelier' ); ?> <a class="transaction-id" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php echo esc_html( $transaction_id ); ?></a></small></div>
								<?php
							}
						} ?>
					<?php endif; ?>

					<?php do_action( 'hotelier_reservation_after_booking_details' ); ?>
				</p>

				<div class="reservation-data-column-wrap">
					<div class="reservation-data-column">
						<h4><?php esc_html_e( 'General details', 'wp-hotelier' ); ?></h4>

						<p class="form-field form-field-wide"><label for="reservation-status"><?php _e( 'Reservation status:', 'wp-hotelier' ) ?></label>

						<select id="reservation-status" name="reservation_status">
							<?php
								$statuses = htl_get_reservation_statuses();

								foreach ( $statuses as $status => $status_name ) {
									echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, 'htl-' . $reservation->get_status(), false ) . '>' . esc_html( $status_name ) . '</option>';
								}
							?>
						</select></p>

						<div class="reservation-details">
							<p>
								<strong><?php esc_html_e( 'Check-in:', 'wp-hotelier' ) ?></strong>	
								<span class="date-default" id="checkin-date-default">
									<?php echo esc_html( $reservation->get_formatted_checkin() ); ?>
									<a href="#" class="edit-date"><i class="dashicons dashicons-edit"></i></a>
								</span>
								<span class="date-edit" id="checkin-date-edit">
									<input name="checkin-date" class="reservation-date" id="date-from" type="text" placeholder="YYYY-MM-DD" name="from" value="<?php echo esc_attr( $reservation->get_checkin() ); ?>">
									<a href="#" class="save-date"><i class="dashicons dashicons-yes"></i></a>
								</span>
							</p>

							<p>
								<strong><?php esc_html_e( 'Check-out:', 'wp-hotelier' ) ?></strong>
								<span class="date-default" id="checkout-date-default">
									<?php echo esc_html( $reservation->get_formatted_checkout() ); ?>
									<a href="#" class="edit-date"><i class="dashicons dashicons-edit"></i></a>
								</span>
								<span class="date-edit" id="checkout-date-edit">
									<input name="checkout-date" class="reservation-date" id="date-to" type="text" placeholder="YYYY-MM-DD" name="to" value="<?php echo esc_attr( $reservation->get_checkout() ); ?>">
									<a href="#" class="save-date"><i class="dashicons dashicons-yes"></i></a>
								</span>
							</p>

							<p class="night-stay"><strong><?php printf( esc_html__( '%d-night stay', 'wp-hotelier' ), $reservation->get_nights() ); ?></strong></p>
						</div>
					</div>

					<div class="reservation-data-column">
						<h4>
							<?php esc_html_e( 'Guest details', 'wp-hotelier' ); ?>
							<a href="#" class="edit-address"><i class="dashicons dashicons-edit"></i></a>
						</h4>

						<div class="guest-details guest-data">
							<?php do_action( 'hotelier_reservation_guest_data' ); ?>

							<?php do_action( 'hotelier_reservation_before_guest_details' ); ?>

							<?php if ( $reservation->get_formatted_guest_address() ) : ?>
								<p>
									<strong><?php esc_html_e( 'Address', 'wp-hotelier' ); ?>:</strong>
									<?php echo wp_kses( $reservation->get_formatted_guest_address(), array( 'br' => array() ) ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $reservation->guest_email ) : ?>
								<p>
									<strong><?php esc_html_e( 'Email', 'wp-hotelier' ); ?>:</strong>
									<a href="mailto:<?php echo esc_attr( esc_html( $reservation->guest_email ) ); ?>"><?php echo esc_html( $reservation->guest_email ); ?></a>
								</p>
							<?php endif; ?>

							<?php if ( $reservation->guest_telephone ) : ?>
								<p>
									<strong><?php esc_html_e( 'Telephone', 'wp-hotelier' ); ?>:</strong>
									<?php echo esc_html( $reservation->guest_telephone ); ?>
								</p>
							<?php endif; ?>

							<?php do_action( 'hotelier_reservation_after_guest_details' ); ?>
						</div>

						<div class="edit-guest-details edit-fields">
							<?php
							foreach ( self::$guest_details as $key => $field ) {
								if ( ! isset( $field[ 'id' ] ) ){
									$field[ 'id' ] = '_guest_' . $key;
								}
								if ( $key == "internal_notes" ) {
									continue;
								}

								if ( $field['type'] == 'select') {
									HTL_Meta_Boxes_Helper::select_input( $field );
								} else {
									HTL_Meta_Boxes_Helper::text_input( $field );
								}

								
							}
							?>
						</div>
					</div>

					<div class="reservation-data-column">
						<h4>
							<?php esc_html_e( 'Guest notes', 'wp-hotelier' ); ?>
							<a href="#" class="edit-address"><i class="dashicons dashicons-edit"></i></a>
						</h4>

						<div class="guest-data">

							<?php do_action( 'hotelier_reservation_before_guest_arrival_time' ); ?>

							<?php if ( $reservation->get_arrival_time() ) : ?>
								<p>
									<strong><?php esc_html_e( 'Estimated arrival time', 'wp-hotelier' ); ?>:</strong>
									<?php echo esc_html( $reservation->get_formatted_arrival_time() ); ?>
								</p>
							<?php endif; ?>

							<?php do_action( 'hotelier_reservation_after_guest_arrival_time' ); ?>

							<?php do_action( 'hotelier_reservation_before_guest_special_requets' ); ?>

							<p class="guest-special-requests">
								<strong><?php esc_html_e( 'Special requests', 'wp-hotelier' ); ?>:</strong>
								<?php echo esc_html( $reservation->get_guest_special_requests() ? $reservation->get_guest_special_requests() : esc_html__( 'None', 'wp-hotelier' ) ); ?>
							</p>

							<p class="guest-special-requests">
								<strong><?php esc_html_e( 'Internal notes', 'wp-hotelier' ); ?>:</strong>
								<?php echo esc_html( $reservation->get_guest_internal_notes() ? $reservation->get_guest_internal_notes() : esc_html__( 'None', 'wp-hotelier' ) ); ?>
							</p>

							<?php do_action( 'hotelier_reservation_after_guest_special_requets' ); ?>

						</div>

						<div class="edit-guest-info edit-fields">
							<p class="form-field form-field-wide"><label for="reservation-status"><?php esc_html_e( 'Estimated arrival time', 'wp-hotelier' ) ?></label>
							<select id="guest-arrival-time" name="guest_arrival_time">
								<?php
									$hours = htl_arrival_times_array();

									foreach ( $hours as $hour => $display ) {
										echo '<option value="' . esc_attr( $hour ) . '" ' . selected( $hour, $reservation->get_arrival_time(), false ) . '>' . esc_html( $display ) . '</option>';
									}
								?>
							</select></p>

							<p class="form-field"><label><?php esc_html_e( 'Special requests', 'wp-hotelier' ); ?></label><textarea name="guest_special_requests" class="input-text" rows="7" cols="5"><?php echo esc_html( $reservation->get_guest_special_requests() ); ?></textarea></p>

							<p class="form-field"><label><?php esc_html_e( 'Internal notes', 'wp-hotelier' ); ?></label><textarea name="guest_internal_notes" class="input-text" rows="7" cols="5"><?php echo esc_html( $reservation->get_guest_internal_notes() ); ?></textarea></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save reservation data
	 */
	public static function save( $post_id, $post ) {
		$reservation = htl_get_reservation( $post_id );

		// Reservation status
		$reservation->update_status( sanitize_text_field( $_POST[ 'reservation_status' ] ), '', true );

		// Guest special requests
		$reservation->update_guest_special_requests( sanitize_text_field( $_POST[ 'guest_special_requests' ] ), '', true );

		// Guest internal notes
		$reservation->update_guest_internal_notes( sanitize_text_field( $_POST[ 'guest_internal_notes' ] ) );

		// Guest estimated arrival time
		$reservation->set_arrival_time( sanitize_text_field( $_POST[ 'guest_arrival_time' ] ) );

		// Reservation total price
		$reservation->set_total( sanitize_text_field( str_replace('.', '', $_POST[ 'new-total' ]) * 100 ) );

		// Check-in date
		$reservation->set_checkin( $_POST[ 'checkin-date' ] );

		// Check-out date
		$reservation->set_checkout( $_POST[ 'checkout-date' ] );
	}
}

endif;
