<?php

/**
 * @link              https://astbit.com
 * @since             1.0.0
 * @package           Kwfy_Wc
 *
 * @wordpress-plugin
 * Plugin Name:       KiwifyWoo: Kiwify Gateway for WooCommerce
 * Plugin URI:        https://astbit.com/connect/kiwify-woocommerce
 * Description:       Kiwify WooCommerce Integration
 * Version:           1.0.0
 * Author:            AstBit
 * Author URI:        https://astbit.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       kwfy-wc
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'KWFY_WC_VERSION', '1.0.0' );

require plugin_dir_path( __FILE__ ) . 'includes/class-kwfy-wc.php';

function run_kwfy_wc() {

	$plugin = new KwfyWC();
	$plugin->run();

}
run_kwfy_wc();
