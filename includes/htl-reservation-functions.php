<?php
/**
 * Hotelier Reservation Functions.
 *
 * @author   Benito Lopez <hello@lopezb.com>
 * @category Core
 * @package  Hotelier/Functions
 * @version  1.7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Main function for returning reservations.
 *
 * @param  mixed $the_reservation Post object or post ID of the reservation.
 * @return HTL_Reservation
 */
function htl_get_reservation( $the_reservation = false ) {
	return new HTL_Reservation( $the_reservation );
}

/**
 * Create a new reservation programmatically
 *
 * Returns a new reservation object on success which can then be used to add additional data.
 *
 * @return HTL_Reservation on success, WP_Error on failure
 */
function htl_create_reservation( $args = array() ) {
	$default_args = array(
		'status'           => '',
		'guest_name'       => '',
		'email'            => '',
		'special_requests' => null,
		'reservation_id'   => 0,
		'created_via'      => '',
		'parent'           => 0
	);

	$args             = wp_parse_args( $args, $default_args );
	$reservation_data = array();

	if ( $args[ 'reservation_id' ] > 0 ) {
		$updating                 = true;
		$reservation_data[ 'ID' ] = $args[ 'reservation_id' ];
	} else {
		$updating                            = false;
		$reservation_data[ 'post_type' ]     = 'room_reservation';
		$reservation_data[ 'post_author' ]   = 1;
		$reservation_data[ 'post_status' ]   = 'htl-completed';
		$reservation_data[ 'ping_status' ]   = 'closed';
		$reservation_data[ 'post_title' ]    = wp_strip_all_tags( $args[ 'guest_name' ] );
		$reservation_data[ 'post_parent' ]   = absint( $args[ 'parent' ] );
	}

	if ( $args[ 'status' ] ) {
		if ( ! in_array( 'htl-' . $args[ 'status' ], array_keys( htl_get_reservation_statuses() ) ) ) {
			return new WP_Error( 'hotelier_invalid_reservation_status', esc_html__( 'Invalid reservation status', 'wp-hotelier' ) );
		}

		$reservation_data[ 'post_status' ]  = 'htl-' . $args[ 'status' ];
	}

	if ( ! is_null( $args[ 'special_requests' ] ) ) {
		$reservation_data[ 'post_excerpt' ] = $args[ 'special_requests' ];
	}

	if ( $updating ) {
		$reservation_id = wp_update_post( $reservation_data );
	} else {
		$reservation_id = wp_insert_post( apply_filters( 'hotelier_new_reservation_data', $reservation_data ), true );

		// insert the reservation ID before the title ( #123 - John Doe )
		$reservation_title = '#' . $reservation_id . ' - ' . get_the_title( $reservation_id );
		$reservation_id = wp_update_post( array( 'ID' => $reservation_id, 'post_title' => apply_filters( 'hotelier_new_reservation_title', sanitize_text_field( $reservation_title ) ) ) );
	}

	if ( is_wp_error( $reservation_id ) ) {
		return $reservation_id;
	}

	if ( ! $updating ) {
		update_post_meta( $reservation_id, '_reservation_key', 'htl_' . apply_filters( 'hotelier_generate_reservation_key', uniqid( 'reservation_' ) ) );
		update_post_meta( $reservation_id, '_reservation_guest_ip_address', htl_get_ip() );
		update_post_meta( $reservation_id, '_reservation_currency', htl_get_currency() );
		update_post_meta( $reservation_id, '_created_via', sanitize_text_field( $args[ 'created_via' ] ) );
	}

	return htl_get_reservation( $reservation_id );
}

/**
 * Update a reservation. Uses htl_create_reservation.
 *
 * @param  array $args
 * @return string | HTL_Reservation
 */
function htl_update_reservation( $args ) {
	if ( ! $args[ 'reservation_id' ] ) {
		return new WP_Error( esc_html__( 'Invalid reservation ID', 'wp-hotelier' ) );
	}

	return htl_create_reservation( $args );
}

/**
 * Get all reservation statuses
 *
 * @return array
 */
function htl_get_reservation_statuses() {
	$reservation_statuses = array(
		//'htl-on-hold'  => esc_html_x( 'Confirmed', 'Reservation status', 'wp-hotelier' ),
		'htl-completed'  => esc_html_x( 'Confirmed', 'Reservation status', 'wp-hotelier' ),
		'htl-confirmed'  => esc_html_x( 'Confirmed & Paid', 'Reservation status', 'wp-hotelier' ),
		'htl-pending'    => esc_html_x( 'Pending', 'Reservation status', 'wp-hotelier' ),
		'htl-cancelled'  => esc_html_x( 'Cancelled', 'Reservation status', 'wp-hotelier' ),
	);

	return apply_filters( 'hotelier_reservation_statuses', $reservation_statuses );
}

/**
 * Adds reservation data to the bookings table.
 *
 * @access public
 * @param int $reservation_id
 * @param string $checkin
 * @param string $checkout
 * @param string $status
 * @param bool $force
 * @return mixed
 */
function htl_add_booking( $reservation_id, $checkin, $checkout, $status, $force = false ) {
	global $wpdb;

	$reservation_id = absint( $reservation_id );

	if ( ! $reservation_id || ! $checkin || ! $checkout ) {
		return false;
	}

	// Check dates
	if ( ! HTL_Formatting_Helper::is_valid_checkin_checkout( $checkin, $checkout, $force ) ) {
		return false;
	}

	// Check status
	if ( ! in_array( 'htl-' . $status, array_keys( htl_get_reservation_statuses() ) ) ) {
		$status = 'completed';
	}

	$wpdb->insert(
		$wpdb->prefix . "hotelier_bookings",
		array(
			'reservation_id' => $reservation_id,
			'checkin'        => $checkin,
			'checkout'       => $checkout,
			'status'         => $status,
		),
		array(
			'%s', '%s', '%s', '%s'
		)
	);

	$row_id = absint( $wpdb->insert_id );

	do_action( 'hotelier_new_booking_row', $row_id, $reservation_id, $checkin, $checkout );

	return $row_id;
}

/**
 * Populate rooms_bookings table.
 *
 * @access public
 * @param int $reservation_id
 * @param int $room_id
 * @return mixed
 */
function htl_populate_rooms_bookings( $reservation_id, $room_id ) {
	global $wpdb;

	$reservation_id = absint( $reservation_id );
	$room_id        = absint( $room_id );

	if ( ! $reservation_id || ! $room_id ) {
		return false;
	}

	$wpdb->insert(
		$wpdb->prefix . "hotelier_rooms_bookings",
		array(
			'reservation_id' => $reservation_id,
			'room_id'        => $room_id
		),
		array(
			'%d', '%d'
		)
	);

	$row_id = absint( $wpdb->insert_id );

	do_action( 'hotelier_new_rooms_bookings_row', $row_id, $reservation_id, $room_id );

	return $row_id;
}

/**
 * Add a room to a reservation.
 *
 * @access public
 * @param int $reservation_id
 * @return mixed
 */
function htl_add_reservation_item( $reservation_id, $item ) {
	global $wpdb;

	$reservation_id = absint( $reservation_id );

	if ( ! $reservation_id ) {
		return false;
	}

	$defaults = array(
		'reservation_item_name' => ''
	);

	$item = wp_parse_args( $item, $defaults );

	$wpdb->insert(
		$wpdb->prefix . "hotelier_reservation_items",
		array(
			'reservation_item_name' => $item[ 'reservation_item_name' ],
			'reservation_id'        => $reservation_id
		),
		array(
			'%s', '%d'
		)
	);

	$item_id = absint( $wpdb->insert_id );

	do_action( 'hotelier_new_reservation_item', $item_id, $item, $reservation_id );

	return $item_id;
}

/**
 * Add reservation item meta
 *
 * @access public
 * @param mixed $item_id
 * @param mixed $meta_key
 * @param mixed $meta_value
 * @param bool $unique (default: false)
 * @return bool
 */
function htl_add_reservation_item_meta( $item_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'reservation_item', $item_id, $meta_key, $meta_value, $unique );
}

/**
 * Set table name
 */
function htl_taxonomy_metadata_wpdbfix() {
	global $wpdb;
	$itemmeta_name = 'hotelier_reservation_itemmeta';

	$wpdb->reservation_itemmeta = $wpdb->prefix . $itemmeta_name;

	$wpdb->tables[] = 'hotelier_reservation_itemmeta';
}
add_action( 'init', 'htl_taxonomy_metadata_wpdbfix', 0 );
add_action( 'switch_blog', 'htl_taxonomy_metadata_wpdbfix', 0 );

/**
 * Get the nice name for a reservation status
 *
 * @param  string $status
 * @return string
 */
function htl_get_reservation_status_name( $status ) {
	$statuses = htl_get_reservation_statuses();
	$status   = 'htl-' === substr( $status, 0, 4 ) ? substr( $status, 4 ) : $status;
	$status   = isset( $statuses[ 'htl-' . $status ] ) ? $statuses[ 'htl-' . $status ] : $status;

	return $status;
}

/**
 * Get the nice name of a rate name
 *
 * @param  string $rate
 * @return string
 */
function htl_get_formatted_room_rate( $rate ) {
	$term = term_exists( $rate, 'room_rate' );

	if ( $term !== 0 && $term !== null ) {
		$rate = get_term_by( 'id', $term[ 'term_id' ], 'room_rate' );
		$rate = $rate->name;
	} else {
		$rate = ucfirst( str_replace('-', ' ', $rate ) );
	}

	return $rate;
}

/**
 * Finds a Reservation ID based on an reservation key.
 *
 * @access public
 * @param string $reservation_key An reservation key has generated by
 * @return int The ID of an reservation, or 0 if the reservation could not be found
 */
function htl_get_reservation_id_by_reservation_key( $reservation_key ) {
	global $wpdb;

	// Faster than get_posts()
	$reservation_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_reservation_key' AND meta_value = %s", $reservation_key ) );

	return $reservation_id;
}

/**
 * Cancel all pending reservations after hold minutes.
 * Please note: for "pending" we mean "unpaid reservations" (pending and failed) in this case.
 *
 * @access public
 */
function htl_cancel_pending_reservations() {
	global $wpdb;

	$hold_minutes = htl_get_option( 'booking_hold_minutes', '60' );

	if ( $hold_minutes < 1 ) {
		return;
	}

	$date = date( "Y-m-d H:i:s", strtotime( '-' . absint( $hold_minutes ) . ' MINUTES', current_time( 'timestamp' ) ) );

	$pending_reservations = $wpdb->get_col( $wpdb->prepare( "
		SELECT posts.ID
		FROM {$wpdb->posts} AS posts
		WHERE 	posts.post_type   IN ('room_reservation')
		AND 	posts.post_status = 'htl-pending'
		OR 		posts.post_status = 'htl-failed'
		AND 	posts.post_modified < %s
	", $date ) );

	if ( $pending_reservations ) {
		foreach ( $pending_reservations as $pending_reservation ) {
			$reservation = htl_get_reservation( $pending_reservation );

			if ( apply_filters( 'hotelier_cancel_pending_reservation', 'booking' === get_post_meta( $pending_reservation, '_created_via', true ), $reservation ) ) {
				$reservation->update_status( 'cancelled', esc_html__( 'Time limit reached.', 'wp-hotelier' ) );
			}
		}
	}

	wp_clear_scheduled_hook( 'hotelier_cancel_pending_reservations' );
	wp_schedule_single_event( time() + ( absint( $hold_minutes ) * 60 ), 'hotelier_cancel_pending_reservations' );
}
add_action( 'hotelier_cancel_pending_reservations', 'htl_cancel_pending_reservations' );

/**
 * Process reservations after checkout date.
 *
 * @access public
 */
function htl_process_completed_reservations() {
	global $wpdb;

	$date = date( 'Y-m-d' );

	$completed_reservations = $wpdb->get_col( $wpdb->prepare( "
		SELECT reservations.reservation_id
		FROM {$wpdb->prefix}hotelier_bookings AS reservations
		WHERE 	reservations.checkout < %s
	", $date ) );

	if ( $completed_reservations ) {
		foreach ( $completed_reservations as $completed_reservation ) {
			$reservation = htl_get_reservation( $completed_reservation );

			// Skip already completed or cancelled/refunded reservations
			if ( $reservation->get_status() == 'completed' || $reservation->get_status() == 'cancelled' || $reservation->get_status() == 'refunded' ) {
				return;
			}

			if ( $reservation->get_status() == 'confirmed' ) {
				$reservation->update_status( 'completed', esc_html__( 'Check-out date reached.', 'wp-hotelier' ) );
			} else {
				$reservation->update_status( 'cancelled', esc_html__( 'Check-out date reached.', 'wp-hotelier' ) );
			}
		}
	}

}
add_action( 'hotelier_process_completed_reservations', 'htl_process_completed_reservations' );

/**
 * Function that returns an array containing all reservations made on a specific date range.
 *
 * @access public
 * @param string $checkin
 * @param string $checkout
 * @return array (reservation_id, checkin, checkout and status)
 */
function htl_get_all_reservations( $checkin, $checkout ) {
	$reservations = array();
	$all_rooms = apply_filters( 'hotelier_get_room_ids_for_reservations', htl_get_room_ids() );

	foreach ( $all_rooms as $room_id ) {
		$room_reservations        = htl_get_room_reservations( $room_id, $checkin, $checkout );
		$reservations[ $room_id ] = $room_reservations;
	}

	return $reservations;
}

/**
 * Get reservations by guest email.
 *
 * @since 1.6.0
 * @param array $email
 * @return Array of reservation objects
 */
function htl_get_reservations_by_email( $email ) {
	$reservations_objects = array();

	if ( ! $email ) {
		return $reservations_objects;
	}

	$query_args = array(
		'post_type'           => 'room_reservation',
		'ignore_sticky_posts' => 1,
		'posts_per_page'      => -1,
		'meta_query'          => array(
			array(
				'key'   => '_guest_email',
				'value' => $email,
			),
		),
	);

	$reservations = new WP_Query( $query_args );

	if ( $reservations->have_posts() ) {

		while ( $reservations->have_posts() ) {
			$reservations->the_post();
			global $post;

			$reservations_objects[] = htl_get_reservation( $post->ID );
		}

		wp_reset_postdata();
	}

	return $reservations_objects;
}


/**
*	Returns an array of all countrys
*
*/
function htl_country_array(){

	return array( 'AF'=>'Afghanistan', 'AL'=>'Albania', 'DZ'=>'Algeria', 'AS'=>'American Samoa', 'AD'=>'Andorra', 'AO'=>'Angola', 'AI'=>'Anguilla', 'AQ'=>'Antarctica', 'AG'=>'Antigua And Barbuda', 'AR'=>'Argentina', 'AM'=>'Armenia', 'AW'=>'Aruba', 'AU'=>'Australia', 'AT'=>'Austria', 'AZ'=>'Azerbaijan', 'BS'=>'Bahamas', 'BH'=>'Bahrain', 'BD'=>'Bangladesh', 'BB'=>'Barbados', 'BY'=>'Belarus', 'BE'=>'Belgium', 'BZ'=>'Belize', 'BJ'=>'Benin', 'BM'=>'Bermuda', 'BT'=>'Bhutan', 'BO'=>'Bolivia', 'BA'=>'Bosnia And Herzegovina', 'BW'=>'Botswana', 'BV'=>'Bouvet Island', 'BR'=>'Brazil', 'IO'=>'British Indian Ocean Territory', 'BN'=>'Brunei', 'BG'=>'Bulgaria', 'BF'=>'Burkina Faso', 'BI'=>'Burundi', 'KH'=>'Cambodia', 'CM'=>'Cameroon', 'CA'=>'Canada', 'CV'=>'Cape Verde', 'KY'=>'Cayman Islands', 'CF'=>'Central African Republic', 'TD'=>'Chad', 'CL'=>'Chile', 'CN'=>'China', 'CX'=>'Christmas Island', 'CC'=>'Cocos (Keeling) Islands', 'CO'=>'Colombia', 'KM'=>'Comoros', 'CG'=>'Congo', 'CK'=>'Cook Islands', 'CR'=>'Costa Rica', 'CI'=>'Cote D\'Ivorie (Ivory Coast)', 'HR'=>'Croatia (Hrvatska)', 'CU'=>'Cuba', 'CY'=>'Cyprus', 'CZ'=>'Czech Republic', 'CD'=>'Democratic Republic Of Congo (Zaire)', 'DK'=>'Denmark', 'DJ'=>'Djibouti', 'DM'=>'Dominica', 'DO'=>'Dominican Republic', 'TP'=>'East Timor', 'EC'=>'Ecuador', 'EG'=>'Egypt', 'SV'=>'El Salvador', 'GQ'=>'Equatorial Guinea', 'ER'=>'Eritrea', 'EE'=>'Estonia', 'ET'=>'Ethiopia', 'FK'=>'Falkland Islands (Malvinas)', 'FO'=>'Faroe Islands', 'FJ'=>'Fiji', 'FI'=>'Finland', 'FR'=>'France', 'FX'=>'France, Metropolitan', 'GF'=>'French Guinea', 'PF'=>'French Polynesia', 'TF'=>'French Southern Territories', 'GA'=>'Gabon', 'GM'=>'Gambia', 'GE'=>'Georgia', 'DE'=>'Germany', 'GH'=>'Ghana', 'GI'=>'Gibraltar', 'GR'=>'Greece', 'GL'=>'Greenland', 'GD'=>'Grenada', 'GP'=>'Guadeloupe', 'GU'=>'Guam', 'GT'=>'Guatemala', 'GN'=>'Guinea', 'GW'=>'Guinea-Bissau', 'GY'=>'Guyana', 'HT'=>'Haiti', 'HM'=>'Heard And McDonald Islands', 'HN'=>'Honduras', 'HK'=>'Hong Kong', 'HU'=>'Hungary', 'IS'=>'Iceland', 'IN'=>'India', 'ID'=>'Indonesia', 'IR'=>'Iran', 'IQ'=>'Iraq', 'IE'=>'Ireland', 'IL'=>'Israel', 'IT'=>'Italy', 'JM'=>'Jamaica', 'JP'=>'Japan', 'JO'=>'Jordan', 'KZ'=>'Kazakhstan', 'KE'=>'Kenya', 'KI'=>'Kiribati', 'KW'=>'Kuwait', 'KG'=>'Kyrgyzstan', 'LA'=>'Laos', 'LV'=>'Latvia', 'LB'=>'Lebanon', 'LS'=>'Lesotho', 'LR'=>'Liberia', 'LY'=>'Libya', 'LI'=>'Liechtenstein', 'LT'=>'Lithuania', 'LU'=>'Luxembourg', 'MO'=>'Macau', 'MK'=>'Macedonia', 'MG'=>'Madagascar', 'MW'=>'Malawi', 'MY'=>'Malaysia', 'MV'=>'Maldives', 'ML'=>'Mali', 'MT'=>'Malta', 'MH'=>'Marshall Islands', 'MQ'=>'Martinique', 'MR'=>'Mauritania', 'MU'=>'Mauritius', 'YT'=>'Mayotte', 'MX'=>'Mexico', 'FM'=>'Micronesia', 'MD'=>'Moldova', 'MC'=>'Monaco', 'MN'=>'Mongolia', 'MS'=>'Montserrat', 'MA'=>'Morocco', 'MZ'=>'Mozambique', 'MM'=>'Myanmar (Burma)', 'NA'=>'Namibia', 'NR'=>'Nauru', 'NP'=>'Nepal', 'NL'=>'Netherlands', 'AN'=>'Netherlands Antilles', 'NC'=>'New Caledonia', 'NZ'=>'New Zealand', 'NI'=>'Nicaragua', 'NE'=>'Niger', 'NG'=>'Nigeria', 'NU'=>'Niue', 'NF'=>'Norfolk Island', 'KP'=>'North Korea', 'MP'=>'Northern Mariana Islands', 'NO'=>'Norway', 'OM'=>'Oman', 'PK'=>'Pakistan', 'PW'=>'Palau', 'PA'=>'Panama', 'PG'=>'Papua New Guinea', 'PY'=>'Paraguay', 'PE'=>'Peru', 'PH'=>'Philippines', 'PN'=>'Pitcairn', 'PL'=>'Poland', 'PT'=>'Portugal', 'PR'=>'Puerto Rico', 'QA'=>'Qatar', 'RE'=>'Reunion', 'RO'=>'Romania', 'RU'=>'Russia', 'RW'=>'Rwanda', 'SH'=>'Saint Helena', 'KN'=>'Saint Kitts And Nevis', 'LC'=>'Saint Lucia', 'PM'=>'Saint Pierre And Miquelon', 'VC'=>'Saint Vincent And The Grenadines', 'SM'=>'San Marino', 'ST'=>'Sao Tome And Principe', 'SA'=>'Saudi Arabia', 'SN'=>'Senegal', 'SC'=>'Seychelles', 'SL'=>'Sierra Leone', 'SG'=>'Singapore', 'SK'=>'Slovak Republic', 'SI'=>'Slovenia', 'SB'=>'Solomon Islands', 'SO'=>'Somalia', 'ZA'=>'South Africa', 'GS'=>'South Georgia And South Sandwich Islands', 'KR'=>'South Korea', 'ES'=>'Spain', 'LK'=>'Sri Lanka', 'SD'=>'Sudan', 'SR'=>'Suriname', 'SJ'=>'Svalbard And Jan Mayen', 'SZ'=>'Swaziland', 'SE'=>'Sweden', 'CH'=>'Switzerland', 'SY'=>'Syria', 'TW'=>'Taiwan', 'TJ'=>'Tajikistan', 'TZ'=>'Tanzania', 'TH'=>'Thailand', 'TG'=>'Togo', 'TK'=>'Tokelau', 'TO'=>'Tonga', 'TT'=>'Trinidad And Tobago', 'TN'=>'Tunisia', 'TR'=>'Turkey', 'TM'=>'Turkmenistan', 'TC'=>'Turks And Caicos Islands', 'TV'=>'Tuvalu', 'UG'=>'Uganda', 'UA'=>'Ukraine', 'AE'=>'United Arab Emirates', 'UK'=>'United Kingdom', 'US'=>'United States', 'UM'=>'United States Minor Outlying Islands', 'UY'=>'Uruguay', 'UZ'=>'Uzbekistan', 'VU'=>'Vanuatu', 'VA'=>'Vatican City (Holy See)', 'VE'=>'Venezuela', 'VN'=>'Vietnam', 'VG'=>'Virgin Islands (British)', 'VI'=>'Virgin Islands (US)', 'WF'=>'Wallis And Futuna Islands', 'EH'=>'Western Sahara', 'WS'=>'Western Samoa', 'YE'=>'Yemen', 'YU'=>'Yugoslavia', 'ZM'=>'Zambia', 'ZW'=>'Zimbabwe' );
}

/**
*	Returns options for a country select
*
*	$sel = (optional) selected country
*/
function htl_country_options($sel = ''){

	$countryArray = htl_country_array();
	$country_options = '';
	foreach($countryArray as $short => $country){
		$select = $short == $sel ? ' selected' : '';
		$country_options .= '<option value="'.$short.'"'.$select.'>'.htmlentities($country,ENT_QUOTES).'</option>';
	}

	return $country_options;
}

/**
*	Returns full name of a country
*
*	$country = Index of country
*/

function htl_country_name($country){
	if(!empty($country)){
		$countryArray = htl_country_array();
		if(isset($countryArray[$country])){
			return $countryArray[$country];
		} else {
			return __('Unknown', 'wp-hotelier');
		}
	}
	return $country;
}

/**
*	Returns an array of all arrival times
*
*/
function htl_arrival_times_array(){

	return array(
			'-1' => 'Not specified',
			'0'  => '00:00 - 01:00',
			'1'  => '01:00 - 02:00',
			'2'  => '02:00 - 03:00',
			'3'  => '03:00 - 04:00',
			'4'  => '04:00 - 05:00',
			'5'  => '05:00 - 06:00',
			'6'  => '06:00 - 07:00',
			'7'  => '07:00 - 08:00',
			'8'  => '08:00 - 09:00',
			'9'  => '09:00 - 10:00',
			'10' => '10:00 - 11:00',
			'11' => '11:00 - 12:00',
			'12' => '12:00 - 13:00',
			'13' => '13:00 - 14:00',
			'14' => '14:00 - 15:00',
			'15' => '15:00 - 16:00',
			'16' => '16:00 - 17:00',
			'17' => '17:00 - 18:00',
			'18' => '18:00 - 19:00',
			'19' => '19:00 - 20:00',
			'20' => '20:00 - 21:00',
			'21' => '21:00 - 22:00',
			'22' => '22:00 - 23:00',
			'23' => '23:00 - 00:00'
		);
}

function htl_arrival_time($time){
	if(!empty($time)){
		$timeArray = htl_arrival_times_array();
		if(isset($timeArray[$time])){
			return $timeArray[$time];
		} else {
			return $timeArray[-1];
		}
	}
	return $time;
}