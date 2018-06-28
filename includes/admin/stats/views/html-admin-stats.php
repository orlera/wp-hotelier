<?php
/**
 * Admin View: Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


$all_reservations = htl_get_all_reservations( $from->format('Y-m-d'), $to->format('Y-m-d') );

//var_dump($all_reservations);


?>

<div class="wrap hotelier">

    <h1><?php esc_html_e( 'Stats', 'wp-hotelier' ); ?></h1>
    <form action="<?php echo admin_url( 'admin.php' ); ?>" method="get" class="form form--bc">
        <input type="hidden" name="page" value="hotelier-stats">
        <table class="stats-form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Start date:', 'wp-hotelier' ); ?></th>
                <td>
                    <input class="datepicker date-from" type="text" placeholder="YYYY-MM-DD" name="from" value="<?php echo esc_attr( $from->format('Y-m-d') ); ?>">
                </td>
                <th scope="row"><?php esc_html_e( 'End date:', 'wp-hotelier' ); ?></th>
                <td>
                    <input class="datepicker date-to" type="text" placeholder="YYYY-MM-DD" name="to" value="<?php echo esc_attr( $to->format('Y-m-d') ); ?>">
                </td>
                <td>
                    <input class="button" type="submit" value="<?php esc_attr_e( 'Generate stats', 'wp-hotelier' ); ?>">
                </td>
            </tr>
        </table>
    </form>

    <?php include_once( 'html-admin-stats-table.php' ); ?>
</div>