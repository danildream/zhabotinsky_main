<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WPDesk_Flexible_Shipping_Tracker' ) ) {
	class WPDesk_Flexible_Shipping_Tracker {

		public static $script_version = '11';

		public function __construct() {
			$this->hooks();
		}

		public function hooks() {
			add_filter( 'wpdesk_tracker_data', array( $this, 'wpdesk_tracker_data_flexible_shipping' ), 11 );
			add_filter( 'wpdesk_tracker_notice_screens', array( $this, 'wpdesk_tracker_notice_screens' ) );
			add_filter( 'wpdesk_track_plugin_deactivation', array( $this, 'wpdesk_track_plugin_deactivation' ) );

			add_filter( 'plugin_action_links_flexible-shipping/flexible-shipping.php', array( $this, 'plugin_action_links' ) );
			add_action( 'activated_plugin', array( $this, 'activated_plugin' ), 10, 2 );
		}

		public function wpdesk_track_plugin_deactivation( $plugins ) {
			$plugins['flexible-shipping/flexible-shipping.php'] = 'flexible-shipping/flexible-shipping.php';
			return $plugins;
		}

		public function wpdesk_tracker_data_flexible_shipping( $data ) {
			$all_shipping_methods = flexible_shipping_get_all_shipping_methods();

			$flexible_shipping = $all_shipping_methods['flexible_shipping'];

			$flexible_shipping_rates = $flexible_shipping->get_all_rates();
			$data['flexible_shipping'] = array();
			$data['flexible_shipping']['total_shipping_methods'] = 0;
			$data['flexible_shipping']['avg_rules'] = 0;
			$data['flexible_shipping']['max_rules'] = 0;
			$data['flexible_shipping']['integrations'] = array();
			$data['flexible_shipping']['free_shipping_requires'] = array();
			$data['flexible_shipping']['calculation_methods'] = array();
			$data['flexible_shipping']['based_on'] = array();
			$data['flexible_shipping']['shipping_class_option'] = array();
			$data['flexible_shipping']['method_description_count'] = 0;
			$data['flexible_shipping']['free_shipping_label_count'] = 0;
			$data['flexible_shipping']['max_cost_count'] = 0;
			$data['flexible_shipping']['visibility_count'] = 0;
			$data['flexible_shipping']['default_count'] = 0;

			$data['flexible_shipping']['additional_cost_count'] = 0;

			$data['flexible_shipping']['min_count'] = 0;
			$data['flexible_shipping']['max_count'] = 0;

			$data['flexible_shipping']['cost_per_order_count'] = 0;
			$data['flexible_shipping']['stop_count'] = 0;
			$data['flexible_shipping']['cancel_count'] = 0;
			foreach ( $flexible_shipping_rates as $flexible_shipping_rate ) {

				$data['flexible_shipping']['total_shipping_methods']++;

				$data['flexible_shipping']['avg_rules'] += count( $flexible_shipping_rate['method_rules'] );

				if ( count( $flexible_shipping_rate['method_rules'] ) > $data['flexible_shipping']['max_rules'] ) {
					$data['flexible_shipping']['max_rules'] = count( $flexible_shipping_rate['method_rules'] );
				}

				if ( empty( $flexible_shipping_rate['method_integration'] ) ) {
					$flexible_shipping_rate['method_integration'] = 'none';
				}
				if ( empty( $data['flexible_shipping']['integrations'][$flexible_shipping_rate['method_integration']] ) ) {
					$data['flexible_shipping']['integrations'][$flexible_shipping_rate['method_integration']] = 0;
				}
				$data['flexible_shipping']['integrations'][$flexible_shipping_rate['method_integration']]++;

				if ( !empty( $flexible_shipping_rate['method_free_shipping_requires'] ) ) {
					if ( empty( $data['flexible_shipping']['free_shipping_requires'][ $flexible_shipping_rate['method_free_shipping_requires'] ] ) ) {
						$data['flexible_shipping']['free_shipping_requires'][ $flexible_shipping_rate['method_free_shipping_requires'] ] = 0;
					}
					$data['flexible_shipping']['free_shipping_requires'][ $flexible_shipping_rate['method_free_shipping_requires'] ] ++;
				}

				if ( empty( $data['flexible_shipping']['calculation_methods'][$flexible_shipping_rate['method_calculation_method']] ) ) {
					$data['flexible_shipping']['calculation_methods'][$flexible_shipping_rate['method_calculation_method']] = 0;
				}
				$data['flexible_shipping']['calculation_methods'][$flexible_shipping_rate['method_calculation_method']]++;

				if ( !empty( $flexible_shipping_rate['method_description'] ) ) {
					$data['flexible_shipping']['method_description_count']++;
				}

				if ( !empty( $flexible_shipping_rate['method_free_shipping_label'] ) ) {
					$data['flexible_shipping']['free_shipping_label_count']++;
				}

				if ( !empty( $flexible_shipping_rate['method_max_cost'] ) ) {
					$data['flexible_shipping']['max_cost_count']++;
				}

				if ( !empty( $flexible_shipping_rate['method_visibility'] ) && $flexible_shipping_rate['method_visibility'] != 'no' ) {
					$data['flexible_shipping']['visibility_count']++;
				}

				if ( !empty( $flexible_shipping_rate['method_default'] ) && $flexible_shipping_rate['method_default'] != 'no' ) {
					$data['flexible_shipping']['default_count']++;
				}

				foreach ( $flexible_shipping_rate['method_rules'] as $method_rule ) {
					if ( empty( $data['flexible_shipping']['based_on'][$method_rule['based_on']] ) ) {
						$data['flexible_shipping']['based_on'][$method_rule['based_on']] = 0;
					}
					$data['flexible_shipping']['based_on'][$method_rule['based_on']]++;

					if ( !empty( $method_rule['shipping_class'] ) ) {
						$shipping_class = $method_rule['shipping_class'];
						if ( !in_array( $shipping_class, array( 'all', 'any', 'none' ) ) ) {
							$shipping_class = 'shipping_class';
						}
						if ( empty( $data['flexible_shipping']['shipping_class_option'][$shipping_class] ) ) {
							$data['flexible_shipping']['shipping_class_option'][$shipping_class] = 0;
						}
						$data['flexible_shipping']['shipping_class_option'][$shipping_class]++;
					}

					if ( !empty( $method_rule['cost_additional'] ) ) {
						$data['flexible_shipping']['additional_cost_count']++;
					}

					if ( !empty( $method_rule['min'] ) ) {
						$data['flexible_shipping']['min_count']++;
					}

					if ( !empty( $method_rule['max'] ) ) {
						$data['flexible_shipping']['max_count']++;
					}

					if ( !empty( $method_rule['cost_per_order'] ) ) {
						$data['flexible_shipping']['cost_per_order_count']++;
					}

					if ( !empty( $method_rule['stop'] ) ) {
						$data['flexible_shipping']['stop_count']++;
					}

					if ( !empty( $method_rule['cancel'] ) ) {
						$data['flexible_shipping']['cancel_count']++;
					}

				}

			}
			if ( $data['flexible_shipping']['total_shipping_methods'] != 0 ) {
				$data['flexible_shipping']['avg_rules'] = $data['flexible_shipping']['avg_rules'] / $data['flexible_shipping']['total_shipping_methods'];
			}
			return $data;
		}

		public function wpdesk_tracker_notice_screens( $screens ) {
			$current_screen = get_current_screen();
			if ( $current_screen->id == 'woocommerce_page_wc-settings' ) {
				if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'shipping' ) {
					$screens[] = $current_screen->id;
				}
			}
			return $screens;
		}

		public function plugin_action_links( $links ) {
			if ( !wpdesk_tracker_enabled() ) {
				return $links;
			}
			$options = get_option('wpdesk_helper_options', array() );
			if ( empty( $options['wpdesk_tracker_agree'] ) ) {
				$options['wpdesk_tracker_agree'] = '0';
			}
			$plugin_links = array();
			if ( $options['wpdesk_tracker_agree'] == '0' ) {
				$opt_in_link = admin_url( 'admin.php?page=wpdesk_tracker&plugin=flexible-shipping/flexible-shipping.php' );
				$plugin_links[] = '<a href="' . $opt_in_link . '">' . __( 'Opt-in', 'flexible-shipping' ) . '</a>';
			}
			else {
				$opt_in_link = admin_url( 'plugins.php?wpdesk_tracker_opt_out=1&plugin=flexible-shipping/flexible-shipping.php' );
				$plugin_links[] = '<a href="' . $opt_in_link . '">' . __( 'Opt-out', 'flexible-shipping' ) . '</a>';
			}
			return array_merge( $plugin_links, $links );
		}


		public function activated_plugin( $plugin, $network_wide ) {
			if ( wpdesk_tracker_enabled() && apply_filters( 'wpdesk_tracker_do_not_ask', false ) ) {
				return;
			}
			if ( $plugin == 'flexible-shipping/flexible-shipping.php' ) {
				$options = get_option('wpdesk_helper_options', array() );
				if ( empty( $options ) ) {
					$options = array();
				}
				if ( empty( $options['wpdesk_tracker_agree'] ) ) {
					$options['wpdesk_tracker_agree'] = '0';
				}
				$wpdesk_tracker_skip_plugin = get_option( 'wpdesk_tracker_skip_flexible_shipping', '0' );
				if ( $options['wpdesk_tracker_agree'] == '0' && $wpdesk_tracker_skip_plugin == '0' ) {
					update_option( 'wpdesk_tracker_notice', '1' );
					update_option( 'wpdesk_tracker_skip_flexible_shipping', '1' );
					wp_redirect( admin_url( 'admin.php?page=wpdesk_tracker&plugin=flexible-shipping/flexible-shipping.php' ) );
					exit;
				}
			}
		}


	}

	new WPDesk_Flexible_Shipping_Tracker();

}

if ( !function_exists( 'wpdesk_activated_plugin_activation_date' ) ) {
	function wpdesk_activated_plugin_activation_date( $plugin, $network_wide ) {
		$option_name = 'plugin_activation_' . $plugin;
		$activation_date = get_option( $option_name, '' );
		if ( $activation_date == '' ) {
			$activation_date = current_time( 'mysql' );
			update_option( $option_name, $activation_date );
		}
	}
	add_action( 'activated_plugin', 'wpdesk_activated_plugin_activation_date', 10, 2 );
}

if ( !function_exists( 'wpdesk_tracker_enabled' ) ) {
	function wpdesk_tracker_enabled() {
		$tracker_enabled = true;
		if ( !empty( $_SERVER['SERVER_ADDR'] ) && $_SERVER['SERVER_ADDR'] == '127.0.0.1' ) {
			$tracker_enabled = false;
		}
		return apply_filters( 'wpdesk_tracker_enabled', $tracker_enabled );
		// add_filter( 'wpdesk_tracker_enabled', '__return_true' );
		// add_filter( 'wpdesk_tracker_do_not_ask', '__return_true' );
	}
}

