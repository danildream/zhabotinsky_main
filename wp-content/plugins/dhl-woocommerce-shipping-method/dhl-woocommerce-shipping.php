<?php
/*
	Plugin Name: DHL (BASIC) WooCommerce Extension
	Plugin URI: https://www.xadapter.com/product/woocommerce-dhl-shipping-plugin-with-print-label/
	Description: Obtain real time shipping rates via DHL Shipping API.
	Version: 1.1.16
	Author: XAdapter
	Author URI: www.xadapter.com/
*/

//Dev Version: 1.4.1	
if( !defined('WF_DHL_ID') ){
	define("WF_DHL_ID", "wf_dhl_shipping");
}

/**
 * Plugin activation check
 */
function wf_dhl_activation_check(){
	if ( is_plugin_active('dhl-woocommerce-shipping/dhl-woocommerce-shipping.php') ){
        deactivate_plugins( basename( __FILE__ ) );
		wp_die("Is everything fine? You already have the Premium version installed in your website. For any issues, kindly raise a ticket via <a target='_blank' href='//support.xadapter.com/'>support.xadapter.com</a>", "", array('back_link' => 1 ));
	}

	if ( ! class_exists( 'SoapClient' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        wp_die( 'Sorry, but you cannot run this plugin, it requires the <a href="http://php.net/manual/en/class.soapclient.php">SOAP</a> support on your server/hosting to function.' );
	}
}

//register_activation_hook( __FILE__, 'wf_dhl_activation_check' );

/**
 * Check if WooCommerce is active
 */
if (in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )) {	
	include_once ( 'dhl-deprecated-functions.php' );

	if (!function_exists('wf_dhl_is_eu_country')){
		function wf_dhl_is_eu_country ($countrycode, $destinationcode) {
			$eu_countrycodes = array(
                'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 
                'ES', 'FI', 'FR', 'GB', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV',
                'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
                'HR', 'GR'
			);
			return(in_array($countrycode, $eu_countrycodes) && in_array($destinationcode, $eu_countrycodes));
		}
	}

	if( !class_exists('wf_dhl_wooCommerce_shipping_setup') ){
		class wf_dhl_wooCommerce_shipping_setup {
			public function __construct() {
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
				add_action( 'woocommerce_shipping_init', array( $this, 'wf_dhl_wooCommerce_shipping_init' ) );
				add_filter( 'woocommerce_shipping_methods', array( $this, 'wf_dhl_wooCommerce_shipping_methods' ) );		
				add_filter( 'admin_enqueue_scripts', array( $this, 'wf_dhl_scripts' ) );		
			}

			public function wf_dhl_scripts() {
				wp_enqueue_script( 'jquery-ui-sortable' );
			}

			public function plugin_action_links( $links ) {
				$plugin_links = array(
					'<a href="' . admin_url( 'admin.php?page=' . wf_get_settings_url() . '&tab=shipping&section=wf_dhl_woocommerce_shipping_method' ) . '">' . __( 'Settings', 'wf_dhl_wooCommerce_shipping' ) . '</a>',

					'<a href="https://www.xadapter.com/product/woocommerce-dhl-shipping-plugin-with-print-label/" target="_blank">' . __( 'Premium Upgrade', 'wf-shipping-canada-post' ) . '</a>',

					'<a href="https://wordpress.org/support/plugin/dhl-woocommerce-shipping-method" target="_blank">' . __( 'Support', 'wf_dhl_wooCommerce_shipping' ) . '</a>',

				);
				return array_merge( $plugin_links, $links );
			}			

			public function wf_dhl_wooCommerce_shipping_init() {
				include_once( 'includes/class-wf-dhl-woocommerce-shipping.php' );
			}

			public function wf_dhl_wooCommerce_shipping_methods( $methods ) {
				$methods[] = 'wf_dhl_woocommerce_shipping_method';
				return $methods;
			}
		}
		new wf_dhl_wooCommerce_shipping_setup();	
	}
}

