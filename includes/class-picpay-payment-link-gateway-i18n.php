<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://daniseveriano.tech
 * @since      1.0.0
 *
 * @package    Picpay_Payment_Link_Gateway
 * @subpackage Picpay_Payment_Link_Gateway/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Picpay_Payment_Link_Gateway
 * @subpackage Picpay_Payment_Link_Gateway/includes
 * @author     Daniele Severiano <contato@daniseveriano.tech>
 */
class Picpay_Payment_Link_Gateway_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'picpay-payment-link-gateway',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
