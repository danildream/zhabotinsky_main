<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WPDesk_Flexible_Shipping_Add_Shipping' ) ) {

	class WPDesk_Flexible_Shipping_Add_Shipping {

		private $shipping_added = false;

		public function __construct() {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}

		public function admin_init() {
			if ( isset( $_GET['fs_add_shipping'] ) && isset( $_GET['post'] ) ) {
				if ( isset( $_GET['_wpnonce'] ) ) {
					if ( wp_verify_nonce( $_GET['_wpnonce'], 'fs_add_shipping' ) ) {
						if ( class_exists( 'WPDesk_Flexible_Shipping_Shipment_' . $_GET['fs_add_shipping'] ) ) {
							$order = wc_get_order( $_GET['post'] );
							if ( $order ) {
								if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
									$order_id = $order->id;
								}
								else {
									$order_id = $order->get_id();
								}
								$integration = $_GET['fs_add_shipping'];
								$post_title = sprintf( __( 'Shipment for order %s, %s', 'flexible-shipping' ), $order_id, $integration );
								//$post_title = apply_filters( 'flexible_shipping_shipment_post_title_'. $integration, $post_title, $fs_method );
								$shipment_post = array(
									'post_title'    => $post_title,
									'post_type'     => 'shipment',
									'post_status'   => 'fs-new',
									'post_parent'   => $order_id
								);
								$shipment_id = wp_insert_post( $shipment_post );
								update_post_meta( $shipment_id, '_integration', $integration );
								$shipment = fs_get_shipment( $shipment_id, $order );
								if ( method_exists( $shipment, 'admin_add_shipment' ) ) {
									$shipment->admin_add_shipment();
									$shipment->save();
								}
								$this->shipping_added = true;
							}
						}
					}
				}
			}
		}

		public function add_meta_boxes( $post_type, $post ) {
			if ( $post_type == 'shop_order' ) {
				$add_metabox = false;
				$order = wc_get_order( $post->ID );
				if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
					$created_via = get_post_meta( $order->id, '_created_via', true );
				}
				else {
					$created_via = $order->get_created_via();
				}
				if ( $created_via != 'checkout' ) {
					$add_metabox = true;
				}
				if ( !$add_metabox ) {
					$order_shipping_methods  = $order->get_shipping_methods();
					$all_shipping_methods    = flexible_shipping_get_all_shipping_methods();
					$flexible_shipping       = $all_shipping_methods['flexible_shipping'];
					$flexible_shipping_rates = $flexible_shipping->get_all_rates();
					foreach ( $order_shipping_methods as $order_shipping_method ) {
						if ( isset( $flexible_shipping_rates[ $order_shipping_method['method_id'] ] ) ) {
							$add_metabox = true;
						}
					}
				}
				$select_options = array();
				$select_options = apply_filters( 'flexible_shipping_add_shipping_options', $select_options );
				if ( $add_metabox && count( $select_options ) ) {
					$select_options = array_merge(
						array( '' => __( 'Select integration', 'flexible-shipping' ) ),
						$select_options
					);
					$args = array(
						'select_options'    => $select_options,
						'order_id'          => $post->ID
					);
					add_meta_box(
						'add_shipping_meta_box',
						__( 'Add shipping', 'flexible-shipping' ),
						array( $this, 'order_metabox' ),
						'shop_order',
						'side',
						'default',
						$args
					);

				}
			}
		}

		public function order_metabox( $post, $args ) {
			$select_options = $args['args']['select_options'];
			$order_id = $args['args']['order_id'];
			$add_shipping_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
			$add_shipping_url = wp_nonce_url( $add_shipping_url, 'fs_add_shipping' );
			$add_shipping_url = str_replace( '&amp;', '&', $add_shipping_url );
			include( 'views/html-order-add_shipping-metabox.php' );
		}

	}

}
