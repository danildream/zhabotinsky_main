<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPDesk_Flexible_Shipping_Shipment_CPT {

    private $plugin = null;

    public function __construct( WPDesk_Flexible_Shipping_Free_Plugin $plugin ) {
        $this->plugin = $plugin;
        $this->hooks();
    }

    public function hooks() {

        add_action( 'init', array( $this, 'register_post_types' ), 20 );
        add_action( 'init', array( $this, 'flexible_shipping_get_label' ), 9999999 );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );

        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'woocommerce_checkout_update_order_meta' ) );

        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'woocommerce_order_details_after_order_table' ) );

        add_action( 'woocommerce_email_after_order_table', array( $this, 'woocommerce_email_after_order_table' ), 10, 2 );
    }

    /**
     * Register post types.
     */
    public function register_post_types() {

        if ( post_type_exists( 'shipment' ) ) {
            return;
        }

        register_post_type( 'shipment',
            array(
                'labels'              => array(
                    'name'               => __( 'Shipments', 'flexible-shipping' ),
                    'singular_name'      => __( 'Shipment', 'flexible-shipping' ),
                    'menu_name'          => __( 'Shipments', 'flexible-shipping' ),
                    'parent_item_colon'  => '',
                    'all_items'          => __( 'Shipments', 'flexible-shipping' ),
                    'view_item'          => __( 'View Shipments', 'flexible-shipping' ),
                    'add_new_item'       => __( 'Add new Shipment', 'flexible-shipping' ),
                    'add_new'            => __( 'Add new Shipment', 'flexible-shipping' ),
                    'edit_item'          => __( 'Edit Shipment', 'flexible-shipping' ),
                    'update_item'        => __( 'Save Shipment', 'flexible-shipping' ),
                    'search_items'       => __( 'Search Shipments', 'flexible-shipping' ),
                    'not_found'          => __( 'Shipment not found', 'flexible-shipping' ),
                    'not_found_in_trash' => __( 'Shipment not found in trash', 'flexible-shipping' )
                ),
                'description'         => __( 'Shipments.', 'flexible-shipping' ),
                'public'              => false,
                'show_ui'             => false,
                'capability_type'     => 'post',
                'capabilities'        => array(),
                'map_meta_cap'        => true,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'hierarchical'        => false,
                'query_var'           => true,
                'supports'            => array( 'title' ),
                'has_archive'         => false,
                'show_in_nav_menus'   => true,
                'menu_icon'           => 'dashicons-upload',
            )
        );

        $shipment_statuses = apply_filters( 'flexible_shipping_register_shipment_statuses',
            array(
                'fs-new'    => array(
                    'label'                     => _x( 'New', 'Shipment status', 'flexible-shipping' ),
                    'public'                    => false,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop( 'New <span class="count">(%s)</span>', 'New <span class="count">(%s)</span>', 'flexible-shipping' ),
                ),
                'fs-created'    => array(
                    'label'                     => _x( 'Created', 'Shipment status', 'flexible-shipping' ),
                    'public'                    => false,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop( 'Created <span class="count">(%s)</span>', 'Created <span class="count">(%s)</span>', 'flexible-shipping' ),
                ),
                'fs-confirmed'    => array(
                    'label'                     => _x( 'Confirmed', 'Shipment status', 'flexible-shipping' ),
                    'public'                    => false,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>', 'flexible-shipping' ),
                ),
                'fs-manifest'    => array(
                    'label'                     => _x( 'Manifest created', 'Shipment status', 'flexible-shipping' ),
                    'public'                    => false,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>', 'flexible-shipping' ),
                ),
                'fs-failed'    => array(
                    'label'                     => _x( 'Failed', 'Shipment status', 'flexible-shipping' ),
                    'public'                    => false,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'flexible-shipping' ),
                ),
            )
        );

        foreach ( $shipment_statuses as $shipment_status => $values ) {
            register_post_status( $shipment_status, $values );
        }

    }

    public function add_meta_boxes( $post_type, $post ) {
        if ( $post_type == 'shipment' ) {
            add_meta_box(
                'shipment_meta_box',
                __('Shipment data', 'flexible-shipping'),
                array( $this, 'metabox' ),
                'shipment',
                'normal',
                'high'
            );
        }
        if ( $post_type == 'shop_order' ) {
            $shipments = fs_get_order_shipments( $post->ID );
            foreach ( $shipments as $shipment ) {
                $args = array( 'shipment' => $shipment );
                add_meta_box(
                    'shipment_meta_box_' . $shipment->get_id(),
                    $shipment->get_order_metabox_title(),
                    array( $this, 'order_metabox' ),
                    'shop_order',
                    $shipment->get_order_metabox_context(),
                    'default',
                    $args
                );

            }
        }
    }

    public function order_metabox( $post, $args ) {
        $shipment = $args['args']['shipment'];
        include( 'views/order-metabox.php' );
    }

    public function metabox() {
        global $post;
        echo '<pre>';
        print_r( $post );
        echo '</pre>';
        $meta_data = get_post_meta( $post->ID );
        foreach ( $meta_data as $key => $val ) {
            echo '<pre>';
            echo $key;
            echo ' = ';
            print_r( maybe_unserialize( $val[0] ) );
            echo '</pre>';
        }
    }

    public function flexible_shipping_get_label() {
        if ( !empty( $_GET['flexible_shipping_get_label'] ) && !empty( $_GET['nonce'] ) ) {
            $nonce = $_GET['nonce'];
            if ( !wp_verify_nonce( $nonce, 'flexible_shipping_get_label' ) ) {
                echo __( 'Invalid nonce!', 'flexible-shipping' );
            }
            try {
                $shipment_id = $_GET['flexible_shipping_get_label'];
                $shipment = fs_get_shipment( $shipment_id );
                $label_data = $shipment->get_label();
                header( "Content-type: application/octet-stream" );
                header( "Content-Disposition: attachment; filename=" . $label_data['file_name'] );
                echo $label_data['content'];
            }
            catch ( Exception $e ) {
                echo $e->getMessage();
            }
            exit();
        }
    }

    public function woocommerce_checkout_update_order_meta( $order_id ) {
        $order = wc_get_order( $order_id );
        $order_shipping_methods = $order->get_shipping_methods();
        $packages = WC()->shipping->get_packages();
        $package_id = -1;
        global $fs_package_id;
        foreach ( $order_shipping_methods as $shipping_id => $shipping_method ) {
            $package_id++;
            $fs_package_id = $package_id;
            $fs_method = array();
	        if ( version_compare( WC_VERSION, '2.6', '<' ) ) {
		        if (isset($shipping_method['item_meta'])
		            && isset($shipping_method['item_meta']['method_id'])
		            && isset($shipping_method['item_meta']['method_id'][0])
		        ) {
		        	$all_shipping_methods = flexible_shipping_get_all_shipping_methods();
			        $flexible_shipping = $all_shipping_methods['flexible_shipping'];
			        $flexible_shipping_rates = $flexible_shipping->get_all_rates();
			        $fs_method = $flexible_shipping_rates[$shipping_method['item_meta']['method_id'][0]];
		        }
	        }
            if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                if (isset($shipping_method['item_meta'])
                    && isset($shipping_method['item_meta']['_fs_method'])
                    && isset($shipping_method['item_meta']['_fs_method'][0])
                ) {
                    $fs_method = unserialize($shipping_method['item_meta']['_fs_method'][0]);
                }
            }
            else {
                if ( isset( $shipping_method['item_meta'] )
                    && isset( $shipping_method['item_meta']['_fs_method'] )
                ) {
                    $fs_method = $shipping_method['item_meta']['_fs_method'];
                }
            }
            if ( !empty( $fs_method['method_integration'] ) ) {
                if ( fs_shipment_integration_exists( $fs_method['method_integration'] ) ) {
                    $shipment = fs_create_shipment( $order, $fs_method );
                    $shipment->set_meta( '_fs_method', $fs_method );
                    $shipment->set_meta( '_shipping_id', $shipping_id );
                    $shipment->set_meta( '_shipping_method', $shipping_method );
                    $shipment->set_meta( '_package', $packages[$package_id] );
                    $shipment->set_meta( '_packages', $packages );
                    $shipment->checkout( $fs_method, $packages[$package_id] );
                    $shipment->save();
                }
            }
        }
    }

    public function woocommerce_order_details_after_order_table( WC_Order $order ) {
        if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
            $order_id = $order->id;
        }
        else {
            $order_id = $order->get_id();
        }
        $shipments = fs_get_order_shipments( $order_id );
        foreach ( $shipments as $shipment ) {
            echo $shipment->get_after_order_table();
        }
    }

    public function woocommerce_email_after_order_table( $order, $sent_to_admin ) {
        if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
            $order_id = $order->id;
        }
        else {
            $order_id = $order->get_id();
        }
        $shipments = fs_get_order_shipments( $order_id );
        foreach ( $shipments as $shipment ) {
            echo $shipment->get_email_after_order_table();
        }
    }


}