<?php
/**
 * Admin View: Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>

<div class="wrap hotelier">

    <h1><?php esc_html_e( 'Stats', 'wp-hotelier' ); ?></h1>
    <table class="stats-form-table">
        <tr>
            <th scope="row"><?php esc_html_e( 'Start date:', 'wp-hotelier' ); ?></th>
            <td>
                <input class="date-from" type="text" placeholder="YYYY-MM-DD" name="from" value="<?php echo esc_attr( $today ); ?>">
            </td>
            <th scope="row"><?php esc_html_e( 'End date:', 'wp-hotelier' ); ?></th>
            <td>
                <input class="date-to" type="text" placeholder="YYYY-MM-DD" name="to" value="<?php echo esc_attr( $tomorrow ); ?>">
            </td>
            <td>
                <input class="submit" type="submit" name="submit" value="Show me the statzzz">
            </td>
        </tr>
    </table>

</div>