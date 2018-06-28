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
        include_once( 'views/html-admin-stats.php' );
    }
}

endif;