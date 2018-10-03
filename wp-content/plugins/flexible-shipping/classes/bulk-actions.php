<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WPDesk_Flexible_Shipping_Bulk_Actions' ) ) {
	class WPDesk_Flexible_Shipping_Bulk_Actions {

		public function __construct() {
			$this->hooks();
		}

		public function hooks() {

			add_filter( 'manage_edit-shop_order_columns', array( $this, 'manage_edit_shop_order_columns' ), 11 );
			add_action( 'manage_shop_order_posts_custom_column', array(
				$this,
				'manage_shop_order_posts_custom_column'
			), 11 );

			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'bulk_actions_edit_shop_order' ) );
			add_filter( 'handle_bulk_actions-edit-shop_order', array(
				$this,
				'handle_bulk_actions_edit_shop_order'
			), 10, 3 );

			add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ), 9999 );

			//add_filter( 'parse_query', array( $this, 'parse_query' ), 999 );

            add_filter( 'posts_where', array( $this, 'posts_where' ), 999 );
            //add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 999, 2 );

			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			add_action( 'admin_init', array( $this, 'admin_init' ), 1 );

			add_filter( 'flexible_shipping_status', array( $this, 'flexible_shipping_status' ) );

		}

		public function posts_clauses( $clauses, $query ) {
		    return $clauses;
        }

		public function flexible_shipping_status( $statuses ) {
			$statuses['new'] = __( 'New', 'flexible-shipping' );
			$statuses['created'] =  __( 'Created', 'flexible-shipping' );
			$statuses['confirmed'] =  __( 'Confirmed', 'flexible-shipping' );
            $statuses['manifest'] =  __( 'Manifest', 'flexible-shipping' );
			$statuses['failed'] =  __( 'Failed', 'flexible-shipping' );
			return $statuses;
		}

        public function posts_where( $where = '' ) {
            global $pagenow;
            global $wp_query;
            global $wpdb;
            $query = $wp_query;
            $type = 'shop_order';
            if ( isset( $_GET['post_type'] ) ) {
                $type = $_GET['post_type'];
            }
            if ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] == 'shop_order' ) {
                if ( 'shop_order' == $type && is_admin() && 'edit.php' == $pagenow ) {
                    $integration = '';
                    if (isset($_GET['flexible_shipping_integration_filter'])) {
                        $integration = $_GET['flexible_shipping_integration_filter'];
                    }
                    $status = '';
                    if (isset($_GET['flexible_shipping_status_filter'])) {
                        $status = $_GET['flexible_shipping_status_filter'];
                    }
                    if ( $integration != '' || $status != '' ) {
                        $add_where_meta_integration = '';
                        $add_where_meta_status = '';
                        $add_where_shipment_integration = '';
                        $add_where_shipment_status = '';
                        $add_where = '';
                        if ( $integration != '' ) {
                            $add_where_meta_integration = " EXISTS ( SELECT 1 FROM {$wpdb->postmeta} fs_postmeta WHERE wp_posts.ID = fs_postmeta.post_id AND fs_postmeta.meta_key = '_flexible_shipping_integration' AND  fs_postmeta.meta_value = '$integration' ) ";
                            $add_where_shipment_integration = " EXISTS ( SELECT 1 FROM {$wpdb->posts} fs_posts, {$wpdb->postmeta} fs_postmeta WHERE wp_posts.ID = fs_posts.post_parent AND fs_posts.ID = fs_postmeta.post_id AND fs_postmeta.meta_key = '_integration' AND  fs_postmeta.meta_value = '$integration' ) ";
                        }
                        if ( $status != '' ) {
                            $add_where_meta_status = " EXISTS ( SELECT 1 FROM {$wpdb->postmeta} fs_postmeta WHERE wp_posts.ID = fs_postmeta.post_id AND fs_postmeta.meta_key = '_flexible_shipping_status' AND  fs_postmeta.meta_value = '$status' ) ";
                            $add_where_shipment_status = " EXISTS ( SELECT 1 FROM {$wpdb->posts} fs_posts WHERE wp_posts.ID = fs_posts.post_parent AND fs_posts.post_status = 'fs-{$status}' ) ";
                        }
                        $add_where_meta = '';
                        if ( $add_where_meta_integration != '' ) {
                            $add_where_meta .= $add_where_meta_integration;
                        }
                        if ( $add_where_meta_status != '' ) {
                            if ( $add_where_meta != '' ) {
                                $add_where_meta .= ' AND ';
                            }
                            $add_where_meta .= $add_where_meta_status;
                        }
                        $add_where_shipment = '';
                        if ( $add_where_shipment_integration != '' ) {
                            $add_where_shipment .= $add_where_shipment_integration;
                        }
                        if ( $add_where_shipment_status != '' ) {
                            if ( $add_where_shipment != '' ) {
                                $add_where_shipment .= ' AND ';
                            }
                            $add_where_shipment .= $add_where_shipment_status;
                        }
                        $add_where_meta = ' ( ' . $add_where_meta . ' ) ';
                        $add_where_shipment = ' ( ' . $add_where_shipment . ' ) ';
                        $add_where = ' AND ( ' . $add_where_meta . ' OR ' . $add_where_shipment . ' ) ';
                        $where .= $add_where;
                    }
                }
            }
            return $where;
        }

		public function parse_query( $query ) {
			global $pagenow;
			$type = 'shop_order';
			if ( isset( $_GET['post_type'] ) ) {
				$type = $_GET['post_type'];
			}
			if ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] == 'shop_order' ) {
				if ( 'shop_order' == $type && is_admin() && 'edit.php' == $pagenow ) {
					$integration = '';
					if ( isset( $_GET['flexible_shipping_integration_filter'] ) ) {
						$integration = $_GET['flexible_shipping_integration_filter'];
					}
					$status = '';
					if ( isset( $_GET['flexible_shipping_status_filter'] ) ) {
						$status = $_GET['flexible_shipping_status_filter'];
					}
                    if ( $integration != '' || $status != '' ) {
                        if ($integration != '') {
                            if (!isset($query->query_vars['meta_query'])) {
                                $query->query_vars['meta_query'] = array();
                            }
                            $meta_query = array();
                            $meta_query['key'] = '_flexible_shipping_integration';
                            $meta_query['value'] = $integration;
                            $query->query_vars['meta_query'][] = $meta_query;
                        }
                        /* */
                        if ($status != '') {
                            if (!isset($query->query_vars['meta_query'])) {
                                $query->query_vars['meta_query'] = array();
                            }
                            $meta_query = array();
                            $meta_query['key'] = '_flexible_shipping_status';
                            $meta_query['value'] = $status;
                            $query->query_vars['meta_query'][] = $meta_query;
                        }
                    }
				}
			}
		}

		public function restrict_manage_posts() {

			if ( apply_filters( 'flexible_shipping_disable_order_filters', false ) ) {
				return;
			}

            $integrations = apply_filters( 'flexible_shipping_integration_options', array() );
            if ( count( $integrations ) == 0 ) {
                return;
            }

			global $typenow;
			if ( 'shop_order' == $typenow ){
				$integrations = apply_filters( 'flexible_shipping_integration_options', array() );
				$statuses = apply_filters( 'flexible_shipping_status', array() );
				$integration = '';
				if ( isset( $_GET['flexible_shipping_integration_filter'] ) ) {
					$integration = $_GET['flexible_shipping_integration_filter'];
				}
				$status = '';
				if ( isset( $_GET['flexible_shipping_status_filter'] ) ) {
					$status = $_GET['flexible_shipping_status_filter'];
				}
				include( 'views/html-orders-filter-form.php' );
			}
		}

		public function manage_shop_order_posts_custom_column( $column ) {
			global $post;
			if ( $column == 'flexible_shipping' ) {
                $classes = array(
                    'error' => 'failed',
                    'new' => 'on-hold',
                    'created' => 'processing created',
                    'confirmed' => 'processing confirmed',
                    'manifest' => 'processing manifest',
                );
                $statuses = array(
                    'error' => __('Error', 'flexible-shipping'),
                    'new' => __('New shipment', 'flexible-shipping'),
                    'created' => __('Created', 'flexible-shipping'),
                    'confirmed' => __('Confirmed', 'flexible-shipping'),
                    'manifest' => __('Manifest created', 'flexible-shipping'),
                );
                $shippings = array();
                $shipments = fs_get_order_shipments($post->ID);
                foreach ($shipments as $shipment) {
                    /* @var $shipment WPDesk_Flexible_Shipping_Shipment|WPDesk_Flexible_Shipping_Shipment_Interface */
                    $shipping = array();
                    $shipping['order_id'] = $post->ID;
                    $shipping['integration'] = $shipment->get_integration();
                    $shipping['url'] = $shipment->get_order_metabox_url();
                    $shipping['error'] = $shipment->get_error_message();
                    $shipping['status'] = $shipment->get_status_for_shipping_column();
                    $shipping['tracking_number'] = $shipment->get_tracking_number();
                    $shipping['label_url'] = $shipment->get_label_url();
                    $shipping['tracking_url'] = $shipment->get_tracking_url();
                    $shipping['shipment'] = $shipment;
                    $shippings[] = $shipping;
                }
                $shippings = apply_filters( 'flexible_shipping_shipping_data', $shippings );
                if (!session_id()) {
                    session_start();
                }
                foreach ($shippings as $shipping) {
                    if ($shipping['status'] == 'error') {
                        $statuses['error'] = $shipping['error'];
                    } else {
                        $statuses['error'] = __('Error', 'flexible-shipping');
                    }
                    include('views/html-column-shipping-shipping.php');
                }
                $messages = array();
                if (isset($_SESSION['flexible_shipping_bulk_send'])) {
                    $messages = $_SESSION['flexible_shipping_bulk_send'];
                }
                if (isset($messages[$post->ID])) {
                    unset($messages[$post->ID]);
                }
                $_SESSION['flexible_shipping_bulk_send'] = $messages;
            }
		}

		public function manage_edit_shop_order_columns( $columns ) {
            $integrations = apply_filters( 'flexible_shipping_integration_options', array() );
            if ( count( $integrations ) == 0 ) {
                return $columns;
            }
			if ( isset( $columns['flexible_shipping'] ) ) {
				return $columns;
			}
			$ret = array();
			foreach ( $columns as $key => $column ) {
				if ( $key == 'order_actions' ) {
					$ret['flexible_shipping'] = __( 'Shipping', 'flexible-shipping' );
				}
				$ret[$key] = $column;
			}
			return $ret;
		}

		function bulk_actions_edit_shop_order( $bulk_actions ) {
            $integrations = apply_filters( 'flexible_shipping_integration_options', array() );
            if ( count( $integrations ) ) {
                $bulk_actions['flexible_shipping_send'] = __('Send shipment', 'flexible-shipping');
                $bulk_actions['flexible_shipping_labels'] = __('Get labels', 'flexible-shipping');
	            if ( apply_filters( 'flexible_shipping_has_manifests', false ) ) {
		            $bulk_actions['flexible_shipping_manifest'] = __( 'Create shipping manifest', 'flexible-shipping' );
	            }
            }
			return $bulk_actions;
		}

		public function handle_bulk_actions_edit_shop_order( $redirect_to, $do_action, $post_ids ) {
		    $redirect_to = remove_query_arg( 'bulk_flexible_shipping_send', $redirect_to );
            $redirect_to = remove_query_arg( 'bulk_flexible_shipping_labels', $redirect_to );
            $redirect_to = remove_query_arg( 'bulk_flexible_shipping_manifests', $redirect_to );
			if ( $do_action == 'flexible_shipping_send' ) {
				$messages = array();
				foreach ( $post_ids as $post_id ) {
                    $shipments = fs_get_order_shipments( $post_id );
                    $messages[$post_id] = array();
                    foreach ($shipments as $shipment) {
                        /* @var $shipment WPDesk_Flexible_Shipping_Shipment|WPDesk_Flexible_Shipping_Shipment_Interface */
                        try {
                            $shipment->api_create();
                            $messages[$post_id][$shipment->get_id()] = array(
                                'status'  => 'created',
                                'message' => __( 'Shipment created.', 'flexible-shipping' )
                            );
                        }
                        catch ( Exception $e ) {
                            $messages[$post_id][$shipment->get_id()] = array(
                                'status' => 'error',
                                'message' => $e->getMessage()
                            );
                        }
                    }
					$messages[$post_id][] = apply_filters(
						'flexible_shipping_bulk_send',
						array( 'status'  => 'none', 'message' => __( 'No action performed.', 'flexible-shipping' )
					), $post_id );
				}
				if ( ! session_id() ) {
					session_start();
				}
				$_SESSION['flexible_shipping_bulk_send'] = $messages;
				$redirect_to = add_query_arg( 'bulk_flexible_shipping_send', count( $post_ids ), $redirect_to );
                return $redirect_to;
			}
			if ( $do_action == 'flexible_shipping_labels' ) {
				$labels = array();
				foreach ( $post_ids as $post_id ) {
                    $shipments = fs_get_order_shipments( $post_id );
                    foreach ($shipments as $shipment) {
                        /* @var $shipment WPDesk_Flexible_Shipping_Shipment|WPDesk_Flexible_Shipping_Shipment_Interface */
                        try {
                            $label = $shipment->get_label();
                            $labels[] = array(
                                'status' => 'created',
                                'message' => __('Label downloaded.', 'flexible-shipping'),
                                'content' => $label['content'],
                                'file_name' => $label['file_name']
                            );
                        }
                        catch ( Exception $e ) {
                        }
                    }
					$labels = apply_filters( 'flexible_shipping_bulk_label', $labels, $post_id );
				}
				$tmp_zip = tempnam ( 'tmp', 'labels_' ) . '.zip';
				$zip = new ZipArchive();
				if ( !$zip->open( $tmp_zip, ZIPARCHIVE::CREATE) ) {
					$labels['error'] = __( 'Unable to create temporary zip archive for labels. Check temporary folder configuration on server.', 'flexible-shipping' );
				}
				else {
					foreach ( $labels as $label ) {
						if ( isset( $label['content'] ) ) {
							$zip->addFromString( $label['file_name'], $label['content'] );
						}
					}
					$labels['tmp_zip'] = $tmp_zip;
					$zip->close();
					if ( ! session_id() ) {
						session_start();
					}
					$_SESSION['flexible_shipping_bulk_labels'] = $labels;
					$redirect_to = add_query_arg( 'bulk_flexible_shipping_labels', count( $post_ids ), $redirect_to );
                    return $redirect_to;
				}
			}
            if ( $do_action == 'flexible_shipping_manifest' ) {
                $manifests = array();
                foreach ( $post_ids as $post_id ) {
                    $shipments = fs_get_order_shipments( $post_id );
                    foreach ( $shipments as $shipment ) {
                        /* @var $shipment WPDesk_Flexible_Shipping_Shipment|WPDesk_Flexible_Shipping_Shipment_Interface */
                        if ( $shipment->get_status() != 'fs-confirmed' || $shipment->get_meta( '_manifest', '' ) != '' ) {
                            continue;
                        }
                        try {
                            $integration = $shipment->get_integration();
                            $manifest_name = $integration;
							if ( method_exists( $shipment, 'get_manifest_name' ) ) {
								$manifest_name = $shipment->get_manifest_name();
							}
                            $manifest = null;
                            if ( empty( $manifests[$manifest_name] ) ) {
                                if ( fs_manifest_integration_exists( $integration ) ) {
                                    $manifest = fs_create_manifest( $integration );
                                }
                            }
                            else {
                                $manifest = $manifests[$manifest_name];
                            }
                            if ( $manifest != null ) {
                                $manifest->add_shipments( $shipment );
                                $manifest->save();
                                $shipment->update_status('fs-manifest' );
                                $shipment->save();
                                $manifests[$manifest_name] = $manifest;
                            }
                        }
                        catch ( Exception $e ) {
                        }
                    }
                }
                $messages = array();
                $integrations = apply_filters( 'flexible_shipping_integration_options', array() );
                foreach ( $manifests as $manifest ) {
                    try {
                        $manifest->generate();
                        $manifest->save();
                        $download_manifest_url = admin_url('edit.php?post_type=shipping_manifest&flexible_shipping_download_manifest=' . $manifest->get_id() . '&nonce=' . wp_create_nonce('flexible_shipping_download_manifest'));
                        $messages[] = array(
                            'type'      => 'updated',
                            'message'   => sprintf(
                                __( 'Created manifest: %s (%s). If download not start automatically click %shere%s.', 'flexible-shipping' ),
                                $manifest->get_number(),
                                $integrations[$manifest->get_integration()],
                                '<a class="shipping_manifest_download" target="_blank" href="' . $download_manifest_url . '">',
                                '</a>'
                            )
                        );
                    }
                    catch( Exception $e ) {
                        $messages[] = array(
                            'type'      => 'error',
                            'message'   => sprintf(
                                __( 'Manifest creation error: %s (%s).', 'flexible-shipping' ),
                                $e->getMessage(),
                                $integrations[$manifest->get_integration()]
                            )
                        );
                        fs_delete_manifest( $manifest );
                    }
                }
                if ( count( $messages ) == 0 ) {
                    $messages[] = array(
                        'type'      => 'updated',
                        'message'   => __( 'No manifests created.', 'flexible-shipping' )
                    );
                }
                $_SESSION['flexible_shipping_bulk_manifests'] = $messages;
                $redirect_to = add_query_arg( 'bulk_flexible_shipping_manifests', count( $post_ids ), $redirect_to );
                return $redirect_to;
            }
			return $redirect_to;
		}

		public function admin_notices() {
			if ( ! empty( $_REQUEST['bulk_flexible_shipping_send'] ) ) {
				$bulk_flexible_shipping_send_count = intval( $_REQUEST['bulk_flexible_shipping_send'] );
				printf( '<div id="message" class="updated fade"><p>' .
				        __( 'Bulk send shipment - processed orders: %d', 'flexible-shipping' ).
				        '</p></div>', $bulk_flexible_shipping_send_count
				);
			}
			if ( ! empty( $_REQUEST['bulk_flexible_shipping_labels'] ) ) {
				$bulk_flexible_shipping_labels_count = intval( $_REQUEST['bulk_flexible_shipping_labels'] );
				if ( ! session_id() ) {
					session_start();
				}
				$labels = null;
				if ( isset( $_SESSION['flexible_shipping_bulk_labels'] ) ) {
					$labels = $_SESSION['flexible_shipping_bulk_labels'];
					unset( $_SESSION['flexible_shipping_bulk_labels'] );
				}
				if ( is_array( $labels ) ) {
                    $nonce = wp_create_nonce('flexible_shipping_labels');
                    printf('<div id="message" class="updated fade"><p>' .
                        __('Bulk labels - processed orders: %d. If download not start automatically click %shere%s.', 'flexible-shipping') .
                        '</p></div>', $bulk_flexible_shipping_labels_count, '<a id="flexible_shipping_labels_url" target="_blank" href=' . admin_url('?flexible_shipping_labels=' . basename($labels['tmp_zip']) . '&nonce=' . $nonce) . '>', '</a>'
                    );
                }
			}
            if ( ! empty( $_REQUEST['bulk_flexible_shipping_manifests'] ) ) {
                $bulk_flexible_shipping_manifest_count = intval( $_REQUEST['bulk_flexible_shipping_manifests'] );
                printf( '<div id="message" class="updated fade"><p>' .
                    __( 'Bulk shipping manifest - processed orders: %d', 'flexible-shipping' ).
                    '</p></div>', $bulk_flexible_shipping_manifest_count
                );
                if ( ! session_id() ) {
                    session_start();
                }
                $messages = null;
                if ( isset( $_SESSION['flexible_shipping_bulk_manifests'] ) ) {
                    $messages = $_SESSION['flexible_shipping_bulk_manifests'];
                    unset( $_SESSION['flexible_shipping_bulk_manifests'] );
                    foreach ( $messages as $message ) {
                        printf( '<div id="message" class="%s fade"><p>%s</p></div>', $message['type'], $message['message'] );
                    }
                }
            }
		}

		public function admin_init() {
			if ( isset( $_GET['flexible_shipping_labels'] ) && isset( $_GET['nonce'] ) ) {
				if ( wp_verify_nonce( $_GET['nonce'], 'flexible_shipping_labels' ) ) {
					$file = trailingslashit( sys_get_temp_dir() ) . $_GET['flexible_shipping_labels'];
					header( 'Content-Description: File Transfer' );
					header( 'Content-Type: application/octet-stream' );
					header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
					header( 'Expires: 0' );
					header( 'Cache-Control: must-revalidate' );
					header( 'Pragma: public' );
					header( 'Content-Length: ' . filesize( $file ) );
					readfile( $file );
					unlink( $file );
					die();
				}
			}
		}

	}
}
