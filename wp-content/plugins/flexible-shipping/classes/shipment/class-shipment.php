<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WPDesk_Flexible_Shipping_Shipment' ) ) {
    /**
     * Class WPDesk_Flexible_Shipping_Shipment
     */
    abstract class WPDesk_Flexible_Shipping_Shipment {

        /**
         * @var int
         */
        private $id;

        /**
         * @var WP_Post
         * Post assigned to shipment
         */
        private $post;

        /**
         * @var null|WC_Order
         * WC_Order assigned to shipment
         */
        private $order = null;

        /**
         * @var bool
         * True if assigned post ich changed. Used when saving post
         */
        private $save_post = false;

        /**
         * @var null
         * Holds old status when shipment status is changed
         */
        private $old_status = null;

        /**
         * @var bool
         * True when status changing
         */
        private $status_changed = false;

        /**
         * @var array
         * Shipment metadata (from postmeta table)
         */
        private $meta_data = array();

        /**
         * @var bool
         * True when shipment metadata loaded
         */
        private $meta_data_loaded = false;

        /**
         * @var array
         * Holds changed metadata keys. Used when saving shipment
         */
        private $meta_data_save_keys = array();

        /**
         * @var string
         * Context for order metabox
         */
        private $order_metabox_context = 'side';


        /**
         * WPDesk_Flexible_Shipping_Shipment constructor.
         * @param $shipment
         * @param WC_Order|null $order
         */
        public function __construct( $shipment, WC_Order $order = null ) {
            if ( is_numeric( $shipment ) ) {
                $this->id   = absint( $shipment );
                $this->post = get_post( $this->id );
            } elseif ( $shipment instanceof WPDesk_Flexible_Shipping_Shipment ) {
                $this->id   = absint( $shipment->get_id() );
                $this->post = $shipment->get_post();
            } elseif ( isset( $shipment->ID ) ) {
                $this->id   = absint( $shipment->ID );
                $this->post = $shipment;
            }
            $this->order = $order;
        }

        /**
         * @return mixed
         */
        public function get_id() {
            return $this->id;
        }

        /**
         * @return mixed
         */
        public function get_post() {
            return $this->post;
        }

        /**
         * @return WC_Order
         */
        public function get_order() {
            if ( $this->order == null ) {
                $this->order = wc_get_order( $this->post->post_parent );
            }
            return $this->order;
        }

        /**
         * @return int
         */
        public function get_order_id() {
            return $this->post->post_parent;
        }

        /**
         * @return string
         */
        public function get_order_metabox_context() {
            return $this->order_metabox_context;
        }

        /**
         * @param string $order_metabox_context
         */
        public function set_order_metabox_context( $order_metabox_context ) {
            $this->order_metabox_context = $order_metabox_context;
        }

        /**
         * @param string $meta_key
         * @param null|sting $default
         * @return array|string|null
         */
        public function get_meta( $meta_key = '', $default = null ) {
            $this->load_meta_data();
            if ( $meta_key == '' ) {
                return $this->meta_data;
            }
            if ( isset( $this->meta_data[$meta_key] ) ) {
                return maybe_unserialize( $this->meta_data[$meta_key][0] );
            }
            else {
                return $default;
            }
            return null;
        }

        /**
         * @param string $meta_key
         * @param int|string|array|object|null $value
         */
        public function set_meta( $meta_key, $value ) {
            $this->load_meta_data();
            if ( !isset( $this->meta_data[$meta_key] ) ) {
                $this->meta_data[$meta_key] = array();
            }
            $this->meta_data[$meta_key][0] = $value;
            $this->meta_data_save_keys[$meta_key] = $meta_key;
        }

        /**
         * @param string $meta_key
         */
        public function delete_meta( $meta_key ) {
            unset( $this->meta_data[$meta_key] );
            $this->meta_data_save_keys[$meta_key] = $meta_key;
        }

        /**
         * Saves shipment data to database.
         */
        public function save() {
            if ( $this->save_post ) {
                wp_update_post($this->post);
                $this->save_post = false;
            }
            foreach ( $this->meta_data_save_keys as $key ) {
                if ( isset( $this->meta_data[$key] ) ) {
                    update_post_meta( $this->id, $key, $this->meta_data[$key][0] );
                }
                else {
                    delete_post_meta( $this->id, $key );
                }
                unset( $this->meta_data_save_keys[$key] );
            }
            if ( $this->status_changed ) {
                do_action( 'flexible_shipping_shipment_status_updated', $this->old_status, $this->post->post_status, $this );
                $this->status_changed = false;
                $this->old_status = null;
            }
        }

        /**
         * Loads all meta data from postmeta
         */
        public function load_meta_data() {
            if ( !$this->meta_data_loaded ) {
                $this->meta_data = get_post_meta( $this->id );
                $this->meta_data_loaded = true;
            }
        }

        /**
         * @return array|null
         * Returns integration assigned to shipment
         */
        public function get_integration() {
            return $this->get_meta( '_integration' );
        }

        /**
         * @return string
         * Returns URL for admin metabox for this shipment
         */
        public function get_order_metabox_url() {
            return admin_url( 'post.php?post=' . $this->get_order_id() . '&action=edit#shipment_meta_box_' . $this->get_id() );
        }

        /**
         * @return string
         */
        public function get_status() {
            return $this->post->post_status;
        }

        /**
         * @return string
         */
        public function get_status_for_shipping_column() {
            $statuses = array(
                'fs-new'        => 'new',
                'fs-created'    => 'created',
                'fs-confirmed'  => 'confirmed',
                'fs-failed'     => 'error',
                'fs-manifest'   => 'manifest',
            );
            return $statuses[$this->get_status()];
        }

        /**
         * @return null|string
         * Returns URL for label
         */
        public function get_label_url() {
            if ( in_array( $this->get_status(), array( 'fs-new', 'fs-created', 'fs-failed' ) )  ) {
                return null;
            }
            $label_url = '?flexible_shipping_get_label=' . $this->get_id() . '&nonce=' . wp_create_nonce( 'flexible_shipping_get_label' ) ;
            return site_url( $label_url );
        }

        /**
         * @param string $new_status
         */
        public function update_status( $new_status ) {
            $this->old_status = $this->post->post_status;
            $this->post->post_status = $new_status;
            $this->save_post = true;
            $this->status_changed = true;
        }

        public function add_to_manifest( WPDesk_Flexible_Shipping_Manifest $manifest ) {
            $this->set_meta( '_manifest', $manifest->get_id() );
        }

        public function label_avaliable() {
            if ( in_array( $this->get_status(), array( 'fs-confirmed', 'fs-manifest' ) ) ) {
                return true;
            }
            return false;
        }

        /**
         * Displays shipping column in orders list view.
         * Must be overwritten!
         */
        public function shipping_column() {
            echo _( 'Please override shipping_column method!', 'flexible-shipping' );
            echo '<pre>';
            print_r( $this->post );
            echo '</pre>';
            echo '<pre>';
            print_r( $this->meta_data );
            echo '</pre>';
        }

    }
}
