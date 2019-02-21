<?php
/**
 * Hotelier View Calendar Page.
 *
 * @author   Andrea Orler
 * @category Admin
 * @package  Hotelier/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'HTL_Admin_Stats' ) ) :

/**
 * HTL_Admin_Stats Class
 */
class HTL_Admin_Stats {

    /**
     * Show the view calendar page
     */
    public static function output() {
    	// from date
		$from_parameter = $_GET[ 'from' ];

		$from = DateTime::createFromFormat('Y-m-d', $from_parameter);

		// if from param is not valid - default to this month's first day
		if ( $from === FALSE) {
			$from = new DateTime('first day of this month');
		}

    	// to date
		$to_parameter = $_GET[ 'to' ];

		$to = DateTime::createFromFormat('Y-m-d', $to_parameter);

		// if to param is not valid or gt from date - default to next month's first day
		if ($to === FALSE || $to <= $from) {
			$to = clone ($from);
			$to->modify('+1 month')->modify('first day of this month');
		}

        include_once( 'views/html-admin-stats.php' );
    }
}

endif;