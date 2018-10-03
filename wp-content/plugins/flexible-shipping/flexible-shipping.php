<?php
/*
	Plugin Name: Flexible Shipping
	Plugin URI: https://wordpress.org/plugins/flexible-shipping/
	Description:  Create additional shipment methods in WooCommerce and enable pricing based on cart weight or total.
	Version: 1.9.5
	Author: WP Desk
	Author URI: https://www.wpdesk.net/
	Text Domain: flexible-shipping
	Domain Path: /languages/
	Tested up to: 4.8

	Copyright 2017 WP Desk Ltd.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/


if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('inspire_Plugin4')) {
    require_once('classes/inspire/plugin4.php');
}

require_once('classes/tracker.php');

if ( !function_exists( 'wpdesk_is_plugin_active' ) ) {
	function wpdesk_is_plugin_active( $plugin_file ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( $plugin_file, $active_plugins ) || array_key_exists( $plugin_file, $active_plugins );
	}
}

if ( wpdesk_is_plugin_active( 'woocommerce/woocommerce.php' ) && !class_exists( 'WPDesk_Flexible_Shipping_Free_Plugin' ) ) {

	class WPDesk_Flexible_Shipping_Free_Plugin extends inspire_Plugin4 {

	    private $scripts_version = '31';

		protected $_pluginNamespace = "flexible-shipping";

		public function __construct() {
			parent::__construct();
			add_action( 'plugins_loaded', array($this, 'init_flexible_shipping'), 1 );
		}

		public function init_flexible_shipping() {

		    require_once('classes/shipment/cpt-shipment.php');
		    new WPDesk_Flexible_Shipping_Shipment_CPT( $this );

            require_once('classes/manifest/cpt-shipping-manifest.php');
            new WPDesk_Flexible_Shipping_Shipping_Manifest_CPT( $this );

            require_once('classes/shipment/ajax.php');
            new WPDesk_Flexible_Shipping_Shipment_Ajax( $this );

            require_once('classes/shipment/class-shipment.php');
            require_once('classes/shipment/interface-shipment.php');
            require_once('classes/shipment/functions.php');

            require_once('classes/manifest/class-manifest.php');
            require_once('classes/manifest/interface-manifest.php');
            require_once('classes/manifest/functions.php');
			require_once('classes/manifest/class-manifest-fs.php');

			require_once('classes/bulk-actions.php');
			new WPDesk_Flexible_Shipping_Bulk_Actions();

			require_once('classes/order-add-shipping.php');
			new WPDesk_Flexible_Shipping_Add_Shipping();

			require_once('classes/shipping_method.php');

			add_action( 'admin_init', array( $this, 'session_init') );

			add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'), 75 );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			add_action( 'woocommerce_after_shipping_rate', array( $this, 'woocommerce_after_shipping_rate' ), 10, 2 );

			add_action( 'flexible_shipping_method_rate_id', array( $this, 'flexible_shipping_method_rate_id' ), 9999999, 2 );

			add_action( 'wp_ajax_flexible_shipping_export', array( $this, 'wp_ajax_flexible_shipping_export' ) );

            add_filter( 'woocommerce_shipping_chosen_method', array( $this, 'woocommerce_shipping_chosen_method' ), 10, 2);

			add_action( 'woocommerce_checkout_update_order_meta', array(
				$this,
				'woocommerce_checkout_update_order_meta'
			) );

			add_action( 'woocommerce_checkout_create_order', array(
				$this,
				'woocommerce_checkout_create_order'
			) );


			add_action( 'init', array( $this, 'init_polylang') );
			add_action( 'admin_init', array( $this, 'init_wpml') );

			global $woocommerce_wpml;

			if ( isset( $woocommerce_wpml ) ) {
				add_filter( 'flexible_shipping_value_in_currency', array( $this, 'flexible_shipping_value_in_currency_wpml' ), 1 );
			}

			if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
				add_filter( 'flexible_shipping_value_in_currency', array( $this, 'flexible_shipping_value_in_currency_aelia' ), 1 );
			}

			if ( function_exists( 'wmcs_convert_price' ) ) {
				add_filter( 'flexible_shipping_value_in_currency', array( $this, 'flexible_shipping_value_in_currency_wmcs' ), 1 );
			}

			if ( isset( $GLOBALS['WOOCS'] ) ) {
				add_filter( 'flexible_shipping_value_in_currency', array( $this, 'flexible_shipping_value_in_currency_woocs' ), 1 );
            }
			add_filter( 'option_woocommerce_cod_settings', array( $this, 'option_woocommerce_cod_settings' ) );

		}

		public function option_woocommerce_cod_settings( $value ) {
			if ( is_checkout() ) {
				if (
				    !empty( $value )
                    && is_array( $value )
                    && $value['enabled'] == 'yes'
                    && !empty( $value['enable_for_methods'] )
                    && is_array( $value['enable_for_methods'] )
                ) {
					foreach ( $value['enable_for_methods'] as $method ) {
						if ( $method == 'flexible_shipping' ) {
							$all_fs_methods = flexible_shipping_get_all_shipping_methods();
							$all_shipping_methods = flexible_shipping_get_all_shipping_methods();
							$flexible_shipping = $all_shipping_methods['flexible_shipping'];
							$flexible_shipping_rates = $flexible_shipping->get_all_rates();
							foreach ( $flexible_shipping_rates as $flexible_shipping_rate ) {
								$value['enable_for_methods'][] = $flexible_shipping_rate['id_for_shipping'];
							}
							break;
						}
					}
				}
			}
			return $value;
		}

		public function session_init() {
			if ( ! session_id() ) {
				session_start();
			}
		}

		public function woocommerce_checkout_create_order( WC_Order $order ) {
			$order_shipping_methods = $order->get_shipping_methods();
            foreach ( $order_shipping_methods as $shipping_id => $shipping_method ) {
				if ( isset( $shipping_method['item_meta'] )
				     && isset( $shipping_method['item_meta']['_fs_method'] )
				) {
					$fs_method = $shipping_method['item_meta']['_fs_method'];
					if ( !empty( $fs_method['method_integration'] ) ) {
					    $order->add_meta_data( '_flexible_shipping_integration', $fs_method['method_integration'] );
					}
				}
			}
		}


		public function woocommerce_checkout_update_order_meta( $order_id ) {
			$order = wc_get_order( $order_id );
			$order_shipping_methods = $order->get_shipping_methods();
			foreach ( $order_shipping_methods as $shipping_id => $shipping_method ) {
                if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                    if (isset($shipping_method['item_meta'])
                        && isset($shipping_method['item_meta']['_fs_method'])
                        && isset($shipping_method['item_meta']['_fs_method'][0])
                    ) {
                        $fs_method = unserialize($shipping_method['item_meta']['_fs_method'][0]);
                        if ( !empty( $fs_method['method_integration'] ) ) {
                            add_post_meta($order->id, '_flexible_shipping_integration', $fs_method['method_integration']);
                        }
                    }
                }
			}
		}

		function woocommerce_shipping_chosen_method( $method, $available_methods ) {
            $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
            if ( isset( $chosen_shipping_methods[0] ) ) {
                foreach ($available_methods as $available_method ) {
                    if ( $available_method->id ==
                        $chosen_shipping_methods[0] ) {
                        $method = $available_method->id;
                        break;
                    }
                }
            }
            return $method;
        }

		function wp_ajax_flexible_shipping_export() {
			check_ajax_referer( 'flexible_shipping', 'flexible_shipping_nonce' );
            $ret = array( 'status' => 'ok' );
            $flexible_shipping_action = '';
            if ( isset( $_REQUEST['flexible_shipping_action'] ) ) {
                $flexible_shipping_action = $_REQUEST['flexible_shipping_action'];
            }
            if ( $flexible_shipping_action == 'export' ) {
	            $instance_id = '';
	            if ( isset( $_REQUEST['instance_id'] ) ) {
		            $instance_id = $_REQUEST['instance_id'];
	            }
	            $ret['instance_id'] = $instance_id;
	            $methods            = '';
	            if ( isset( $_REQUEST['methods'] ) ) {
		            $methods = $_REQUEST['methods'];
	            }
	            $methods_array = explode( ',', $methods );
                $shipping_method = WC_Shipping_Zones::get_shipping_method( $instance_id );
                $wc_shipping_classes = WC()->shipping->get_shipping_classes();
	            $ret['shipping_method'] = $shipping_method;

	            $all_shipping_methods = flexible_shipping_get_all_shipping_methods();

                $flexible_shipping = $all_shipping_methods['flexible_shipping'];
                $flexible_shipping_rates = $flexible_shipping->get_all_rates();

                $filename = 'fs_' . str_replace( 'http://', '', str_replace( 'https://', '', site_url() ) ) . '-' . $instance_id;

	            $ret['all_rates'] = $flexible_shipping_rates;
	            $ret['methods'] = $methods;
	            $csv_array = array();
	            $csv_header = array(
                    'Method Title',
                    'Method Description',
                    'Free Shipping',
                    'Maximum Cost',
                    'Calculation Method',
                    'Visibility',
                    'Default',
                    'Based on',
                    'Min',
                    'Max',
                    'Cost per order',
                    'Additional cost',
                    'Value',
                    'Shipping Class',
                    'Stop',
                    'Cancel',
                );
	            $csv_array[] = $csv_header;
	            foreach ( $flexible_shipping_rates as $flexible_shipping_rate ) {
	                if ( !in_array( $flexible_shipping_rate['id'], $methods_array ) ) {
	                    continue;
                    }
	                $filename .= '_' . $flexible_shipping_rate['id'];
                    if ( !isset( $flexible_shipping_rate['method_description'] ) ) {
                        $flexible_shipping_rate['method_description'] = '';
                    }
                    if ( !isset( $flexible_shipping_rate['method_free_shipping'] ) ) {
                        $flexible_shipping_rate['method_free_shipping'] = '';
                    }
                    if ( !isset( $flexible_shipping_rate['method_max_cost'] ) ) {
                        $flexible_shipping_rate['method_max_cost'] = '';
                    }
                    if ( !isset( $flexible_shipping_rate['method_calculation_method'] ) ) {
                        $flexible_shipping_rate['method_calculation_method'] = '';
                    }
                    if ( !isset( $flexible_shipping_rate['method_visibility'] ) ) {
                        $flexible_shipping_rate['method_visibility'] = '';
                    }
                    if ( $flexible_shipping_rate['method_visibility'] != 'yes' ) {
                        $flexible_shipping_rate['method_visibility'] = '';
                    }
                    if ( !isset( $flexible_shipping_rate['method_default'] ) ) {
                        $flexible_shipping_rate['method_default'] = '';
                    }
                    if ( $flexible_shipping_rate['method_default'] != 'yes' ) {
                        $flexible_shipping_rate['method_default'] = '';
                    }
                    $csv_array[] = array(
                        $flexible_shipping_rate['method_title'],
                        $flexible_shipping_rate['method_description'],
                        $flexible_shipping_rate['method_free_shipping'],
                        $flexible_shipping_rate['method_max_cost'],
                        $flexible_shipping_rate['method_calculation_method'],
                        $flexible_shipping_rate['method_visibility'],
                        $flexible_shipping_rate['method_default'],
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                    );
                    foreach ( $flexible_shipping_rate['method_rules'] as $method_rule ) {
                        if ( !isset( $method_rule['based_on'] ) ) {
                            $method_rule['based_on'] = '';
                        }
                        if ( !isset( $method_rule['min'] ) ) {
                            $method_rule['min'] = '';
                        }
                        if ( !isset( $method_rule['max'] ) ) {
                            $method_rule['max'] = '';
                        }
                        if ( !isset( $method_rule['cost_per_order'] ) ) {
                            $method_rule['cost_per_order'] = '';
                        }
                        if ( !isset( $method_rule['cost_additional'] ) ) {
                            $method_rule['cost_additional'] = '';
                        }
                        if ( !isset( $method_rule['per_value'] ) ) {
                            $method_rule['per_value'] = '';
                        }
                        if ( !isset( $method_rule['shipping_class'] ) ) {
                            $method_rule['shipping_class'] = '';
                        }
                        else {
                            foreach ( $wc_shipping_classes as $shipping_class ) {
                                if ( $shipping_class->term_id == $method_rule['shipping_class'] ) {
                                    $method_rule['shipping_class'] = $shipping_class->name;
                                }
                            }
                        }
                        if ( !isset( $method_rule['stop'] ) ) {
                            $method_rule['stop'] = '';
                        }
                        if ( $method_rule['stop'] == '1' ) {
                            $method_rule['stop'] = 'yes';
                        }
                        else {
                            $method_rule['stop'] = '';
                        }
                        if ( !isset( $method_rule['cancel'] ) ) {
                            $method_rule['cancel'] = '';
                        }
                        if ( $method_rule['cancel'] == '1' ) {
                            $method_rule['cancel'] = 'yes';
                        }
                        else {
                            $method_rule['cancel'] = '';
                        }
                        $csv_array[] = array(
                            $flexible_shipping_rate['method_title'],
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $method_rule['based_on'],
                            $method_rule['min'],
                            $method_rule['max'],
                            $method_rule['cost_per_order'],
                            $method_rule['cost_additional'],
                            $method_rule['per_value'],
                            $method_rule['shipping_class'],
                            $method_rule['stop'],
                            $method_rule['cancel'],
                        );
                    }
                }
                $ret['csv_array'] = $csv_array;
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $filename . '.csv');
                $out = fopen('php://output', 'w');
                foreach ( $csv_array as $fields ) {
                    fputcsv( $out, $fields, ';' );
                }
                fclose($out);
	            wp_die();
            }
            echo json_encode($ret,JSON_PRETTY_PRINT);
            wp_die();
        }

		function init_polylang() {
			if ( function_exists( 'pll_register_string' ) ) {
				$all_shipping_methods = flexible_shipping_get_all_shipping_methods();
				$flexible_shipping = $all_shipping_methods['flexible_shipping'];
				$flexible_shipping_rates = $flexible_shipping->get_all_rates();
				foreach ( $flexible_shipping_rates as $flexible_shipping_rate ) {
				    if ( isset( $flexible_shipping_rate['method_title'] ) ) {
					    pll_register_string( $flexible_shipping_rate['method_title'], $flexible_shipping_rate['method_title'], __( 'Flexible Shipping', 'flexible-shipping' ) );
				    }
					if ( isset( $flexible_shipping_rate['method_description'] ) ) {
						pll_register_string( $flexible_shipping_rate['method_description'], $flexible_shipping_rate['method_description'], __( 'Flexible Shipping', 'flexible-shipping' ) );
					}
					if ( isset( $flexible_shipping_rate['method_free_shipping_label'] ) ) {
						pll_register_string( $flexible_shipping_rate['method_free_shipping_label'], $flexible_shipping_rate['method_free_shipping_label'], __( 'Flexible Shipping', 'flexible-shipping' ) );
					}
                }
			}
		}

		function init_wpml() {
			if ( function_exists( 'icl_register_string' ) ) {
				$icl_language_code = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : get_bloginfo('language');
				$all_shipping_methods = flexible_shipping_get_all_shipping_methods();
				$flexible_shipping = $all_shipping_methods['flexible_shipping'];
				$flexible_shipping_rates = $flexible_shipping->get_all_rates();
				foreach ( $flexible_shipping_rates as $flexible_shipping_rate ) {
					if ( isset( $flexible_shipping_rate['method_title'] ) ) {
						icl_register_string( 'flexible-shipping', $flexible_shipping_rate['method_title'], $flexible_shipping_rate['method_title'], false, $icl_language_code );
					}
					if ( isset( $flexible_shipping_rate['method_description'] ) ) {
						icl_register_string( 'flexible-shipping', $flexible_shipping_rate['method_description'], $flexible_shipping_rate['method_description'], false, $icl_language_code );
					}
					if ( isset( $flexible_shipping_rate['method_free_shipping_label'] ) ) {
						icl_register_string( 'flexible-shipping', $flexible_shipping_rate['method_free_shipping_label'], $flexible_shipping_rate['method_free_shipping_label'], false, $icl_language_code );
					}
				}
			}
		}


		public function admin_notices() {
			if ( is_plugin_active( 'woocommerce-active-payments/activepayments.php' ) ) {
				$plugin_activepayments = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce-active-payments/activepayments.php' );

				$version_compare = version_compare( $plugin_activepayments['Version'], '2.7' );
				if ( $version_compare < 0 ) {
					$class = 'notice notice-error';
					$message = __( 'Flexible Shipping requires at least version 2.7 of Active Payments plugin.', 'flexible-shipping' );

					printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
				}
			}
			if ( is_plugin_active( 'woocommerce-enadawca/woocommerce-enadawca.php' ) ) {
				$plugin_enadawca = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce-enadawca/woocommerce-enadawca.php' );

				$version_compare = version_compare( $plugin_enadawca['Version'], '1.2' );
				if ( $version_compare < 0 ) {
					$class = 'notice notice-error';
					$message = __( 'Flexible Shipping requires at least version 1.2 of eNadawca plugin.', 'flexible-shipping' );

					printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
				}
			}
			if ( is_plugin_active( 'woocommerce-paczka-w-ruchu/woocommerce-paczka-w-ruchu.php' ) ) {
				$plugin_pwr = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce-paczka-w-ruchu/woocommerce-paczka-w-ruchu.php' );

				$version_compare = version_compare( $plugin_pwr['Version'], '1.1' );
				if ( $version_compare < 0 ) {
					$class = 'notice notice-error';
					$message = __( 'Flexible Shipping requires at least version 1.1 of Paczka w Ruchu plugin.', 'flexible-shipping' );

					printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
				}
			}
			if ( is_plugin_active( 'woo-flexible-shipping/flexible-shipping.php' ) ) {

				$class = 'notice notice-error';
				$message = sprintf( __( 'You are using WooCommerce Flexible Shipping below 1.4. Please deactivate it on %splugins page%s. Read about big changes in Flexible Shipping on %sour blog →%s', 'flexible-shipping' ), '<a href="' . admin_url('plugins.php') . '">', '</a>', '<a href="https://www.wpdesk.pl/blog/nowy-flexible-shipping/">', '</a>' );

				printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
			}
		}

		public function loadPluginTextDomain() {
			parent::loadPluginTextDomain();
			$ret = load_plugin_textdomain( 'flexible-shipping', FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
		}

		public static function getTextDomain() {
			return 'flexible-shipping';
		}

		function enqueue_admin_scripts() {
            $current_screen = get_current_screen();
            if ( $current_screen->id === 'woocommerce_page_wc-settings' || $current_screen->id === 'edit-shop_order' || $current_screen->id === 'shop_order' ) {
	            $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                wp_register_script( 'fs_admin', $this->getPluginUrl() . 'assets/js/admin' . $suffix . '.js', array( 'jquery' ), $this->scripts_version );
                wp_localize_script( 'fs_admin', 'fs_admin', array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                ));
                wp_enqueue_script( 'fs_admin' );
                wp_enqueue_style( 'fs_admin', $this->getPluginUrl() . 'assets/css/admin' . $suffix . '.css', array(), $this->scripts_version );
            }
		}

		function enqueue_scripts() {
		}

		function admin_footer() {
		}

		/**
		 * action_links function.
		 *
		 * @access public
		 * @param mixed $links
		 * @return void
		 */
		public function linksFilter( $links ) {
		    $docs_link = get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/docs/flexible-shipping-pro-woocommerce-docs/' : 'https://www.wpdesk.net/docs/flexible-shipping-pro-woocommerce-docs/';
            $support_link = get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/support/' : 'https://www.wpdesk.net/support';

            $plugin_links = array(
                '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping') . '">' . __( 'Settings', 'flexible-shipping' ) . '</a>',
                '<a href="' . $docs_link . '">' . __( 'Docs', 'flexible-shipping' ) . '</a>',
                '<a href="' . $support_link . '">' . __( 'Support', 'flexible-shipping' ) . '</a>',
            );
            $pro_link = get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/sklep/flexible-shipping-pro-woocommerce/' : 'https://www.wpdesk.net/products/flexible-shipping-pro-woocommerce/';
            $utm = '?utm_source=wp-admin-plugins&utm_medium=link&utm_campaign=flexible-shipping-plugins-upgrade-link';

            if ( ! wpdesk_is_plugin_active( 'flexible-shipping-pro/flexible-shipping-pro.php' ) )
                $plugin_links[] = '<a href="' . $pro_link . $utm . '" target="_blank" style="color:#d64e07;font-weight:bold;">' . __( 'Upgrade', 'flexible-shipping' ) . '</a>';
			return array_merge( $plugin_links, $links );
        }


        public function woocommerce_after_shipping_rate( $method, $index ) {
        	if ( $method->method_id == 'flexible_shipping' ) {
        		$description = WC()->session->get('flexible_shipping_description_' . $method->id, false );
        		if ( $description && $description != '' ) {
        			wc_get_template(
        					'cart/flexible-shipping/after-shipping-rate.php',
        					array(
        							'method_description' 	=> $description,
        					)
        					, ''
        					, trailingslashit( plugin_dir_path( __FILE__ ) ) . 'templates/'
        			);
               	}
        	}
        }

        public function flexible_shipping_method_rate_id( $method_id, array $shipping_method ) {
			if ( isset( $shipping_method['id_for_shipping'] ) && $shipping_method['id_for_shipping'] != '' ) {
				$method_id = $shipping_method['id_for_shipping'];
			}
			return $method_id;
		}

        public function flexible_shipping_value_in_currency_aelia( $value ) {
        	$aelia = WC_Aelia_CurrencySwitcher::instance();
        	$aelia_settings = WC_Aelia_CurrencySwitcher::settings();
        	$from_currency = $aelia_settings->base_currency();
        	$to_currency = $aelia->get_selected_currency();
        	$value = $aelia->convert( $value, $from_currency, $to_currency );
        	return $value;
        }

	    public function flexible_shipping_value_in_currency_wmcs( $value ) {
	    	$value = wmcs_convert_price( $value );
       		return $value;
        }

	    public function flexible_shipping_value_in_currency_wpml( $value ) {
       		return apply_filters( 'wcml_raw_price_amount', $value );
        }

	    public function flexible_shipping_value_in_currency_woocs( $value ) {
		     return $GLOBALS['WOOCS']->woocs_exchange_value( $value );
        }

	}

	function flexible_shipping_get_all_shipping_methods() {
		/*
		$all_shipping_methods = WC()->shipping()->get_shipping_methods();
		if ( empty( $all_shipping_methods ) ) {
			$all_shipping_methods = WC()->shipping()->load_shipping_methods();
		}
		*/
		$all_shipping_methods = WC()->shipping()->load_shipping_methods();
		return $all_shipping_methods;
	}


	function flexible_shipping_method_selected_in_cart( $shipping_method_integration ) {
    	global $woocommerce;
        $shippings = $woocommerce->session->get('chosen_shipping_methods');
	    $all_shipping_methods = flexible_shipping_get_all_shipping_methods();
		$flexible_shipping = $all_shipping_methods['flexible_shipping'];
		$flexible_shipping_rates = $flexible_shipping->get_all_rates();
		foreach ( $shippings as $id => $shipping ) {
			if ( isset( $flexible_shipping_rates[$shipping] ) ) {
				$shipping_method = $flexible_shipping_rates[$shipping];
				if ( $shipping_method['method_integration'] == $shipping_method_integration ) {
					return $shipping_method;
				}
			}
		}
		return false;
    }

	function flexible_shipping_method_selected( $order, $shipping_method_integration ) {
        if ( is_numeric( $order ) ) {
	        $order = wc_get_order( $order );
        }
		$shippings = $order->get_shipping_methods();
		$all_shipping_methods = flexible_shipping_get_all_shipping_methods();
		if ( isset( $all_shipping_methods['flexible_shipping'] ) ) {
			$flexible_shipping_rates = $all_shipping_methods['flexible_shipping']->get_all_rates();
			foreach ( $shippings as $id => $shipping ) {
				if ( isset( $flexible_shipping_rates[ $shipping['method_id'] ] ) ) {
					$shipping_method = $flexible_shipping_rates[ $shipping['method_id'] ];
					if ( $shipping_method['method_integration'] == $shipping_method_integration ) {
						return $shipping_method;
					}
				}
			}
		}
		return false;
	}

	function flexible_shipping_get_integration_for_method( $method_id ) {
		$all_shipping_methods = flexible_shipping_get_all_shipping_methods();
		if ( isset( $all_shipping_methods['flexible_shipping'] ) ) {
			$flexible_shipping_rates = $all_shipping_methods['flexible_shipping']->get_all_rates();
			if ( isset( $flexible_shipping_rates[$method_id] ) ) {
				return $flexible_shipping_rates[$method_id]['method_integration'];
            }
		}
		return false;
    }

	if ( !function_exists('wpdesk_redirect') ) {
		function wpdesk_redirect( $redirect ) {
			if ( 1==1 && headers_sent() ) {
				?>
				<span><?php printf( __( 'Redirecting. If page not redirects click %s here %s.', 'flexible-shipping'), '<a href="' . $redirect . '" >', '</a>' ); ?></span>

				<script>
					parent.location.replace('<?php echo $redirect; ?>');
				</script>
				<?php
			}
			else {
				wp_safe_redirect($redirect);
			}
			exit;
		}
	}

	if ( !function_exists( 'wpdesk__' ) ) {
		function wpdesk__( $text, $domain ) {
			if ( function_exists( 'icl_sw_filters_gettext' ) ) {
				return icl_sw_filters_gettext( $text, $text, $domain, $text );
			}
			if ( function_exists( 'pll__' ) ) {
				return pll__( $text );
			}
			return __( $text, $domain );
		}
	}

	if ( !function_exists( 'wpdesk__e' ) ) {
		function wpdesk__e( $text, $domain ) {
			echo wpdesk__( $text, $domain );
		}
	}

	$_GLOBALS['woocommerce_flexible_shipping'] = new WPDesk_Flexible_Shipping_Free_Plugin();

	add_action( 'plugins_loaded', 'flexible_shipping_plugins_loaded', 9 );
	function flexible_shipping_plugins_loaded() {
		if ( !class_exists( 'WPDesk_Tracker' ) ) {
			include( 'inc/wpdesk-tracker/class-wpdesk-tracker.php' );
			WPDesk_Tracker::init( basename( dirname( __FILE__ ) ) );
		}
	}

}
