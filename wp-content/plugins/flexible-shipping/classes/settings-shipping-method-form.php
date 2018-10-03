<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Settings for flexible shipment, shipment method
 */

$shipping_classes = array();
$shipping_classes['0'] = __( 'Select shipment class', 'flexible-shipping' );
foreach ( WC()->shipping->get_shipping_classes() as $shipping_class ) {
	$shipping_classes[$shipping_class->term_id] = $shipping_class->name;
}

$integrations = get_locale() === 'pl_PL' ? sprintf( __( '%sAdd integrations%s with DHL, Paczkomaty InPost, eNadawca Poczta Polska, Paczka w Ruchu.', 'flexible-shipping' ), '<a href="https://www.wpdesk.pl/kategoria-produktu/wysylka/?utm_source=flexible-shipping-method&utm_medium=text-link&utm_campaign=flexible-shipping-integrations" target="_blank">', ' &rarr;</a>' ) : '';
$integrations_tip = get_locale() === 'pl_PL' ? false : true;

if ( !isset( $shipping_method['method_free_shipping_label'] ) ) {
	$shipping_method['method_free_shipping_label'] = __( 'Free', 'flexible-shipping' );
}

$this->settings['method_free_shipping'] = $shipping_method['method_free_shipping'];

if ( empty( $shipping_method['method_integration'] ) ) {
	$shipping_method['method_integration'] = '';
}

$method_free_shipping = '';
if ( isset( $shipping_method['method_free_shipping'] ) && $shipping_method['method_free_shipping'] != '' ) {
	$method_free_shipping = floatval( $shipping_method['method_free_shipping'] );
}

$settings = array(
	'method_enabled' => array(
		'title'         => __( 'Enable/Disable', 'flexible-shipping' ),
		'type' 	        => 'checkbox',
		'default'       => $shipping_method['method_enabled'],
		'label'         => __( 'Enable this shipment method', 'flexible-shipping' )
	),
	'method_title' => array(
		'title' 		    => __( 'Method Title', 'flexible-shipping' ),
		'type' 			    => 'text',
		'description' 	    => __( 'This controls the title which the user sees during checkout.', 'flexible-shipping' ),
		'default'		    => __( 'Flexible Shipping', 'flexible-shipping' ),
		'desc_tip'		    => true,
		'default'           => $shipping_method['method_title'],
		'custom_attributes' => array('required' => true)
	),
	'method_description' => array(
		'title' 		    => __( 'Method Description', 'flexible-shipping' ),
		'type' 			    => 'text',
		'description' 	    => __( 'This controls method description which the user sees during checkout.', 'flexible-shipping' ),
		'default'		    => '',
		'desc_tip'		    => true,
		'default'           => $shipping_method['method_description'],
	),
    array(
        'title'         => '',
        'type'          => 'title',
        'description'   => '',
    ),
	'method_free_shipping' 	=> array(
		'title' 		=> __( 'Free Shipping', 'flexible-shipping' ),
		'type' 			=> 'price',
		'default' 		=> $method_free_shipping,
		'description' 	=> __( 'Enter a minimum order amount for free shipment. This will override the costs configured below.', 'flexible-shipping' ),
		'desc_tip'		=> true
	),
	'method_free_shipping_label' 	=> array(
		'title' 		=> __( 'Free Shipping Label', 'flexible-shipping' ),
		'type' 			=> 'text',
		'default' 		=> $shipping_method['method_free_shipping_label'],
		'description' 	=> __( 'Enter additional label for shipment when free shipment available.', 'flexible-shipping' ),
		'desc_tip'		=> true
	),
    array(
        'title'         => '',
        'type'          => 'title',
        'description'   => '',
    ),
	'method_calculation_method' => array(
        'title' 		=> __( 'Calculation Method', 'flexible-shipping' ),
        'type' 			=> 'select',
        'description' 	=> __( 'Select how rules will be calculated. If you choose "sum" the rules order is important.', 'flexible-shipping' ),
        'default' 		=> $shipping_method['method_calculation_method'],
        'desc_tip'		=> true,
        'options' 		=> array( 'sum' => __( 'Sum', 'flexible-shipping' ) )
	),
	'method_visibility' => array(
		'title' 		=> __( 'Visibility', 'flexible-shipping' ),
		'type' 			=> 'checkbox',
		'default' 		=> $shipping_method['method_visibility'],
		'label' 		=> __( 'Show only for logged in users', 'flexible-shipping' )
	),
	'method_default' => array(
		'title' 		=> __( 'Default', 'flexible-shipping' ),
		'type' 			=> 'checkbox',
		'default' 		=> $shipping_method['method_default'],
		'label' 		=> __( 'Check the box to set this option as the default selected choice on the cart page.', 'flexible-shipping' )
	),
	'method_integration' => array(
        'title' 		=> __( 'Integration', 'flexible-shipping' ),
        'type' 			=> 'select',
        'description' 	=> __( 'Select integration. ' , 'flexible-shipping' ) . $integrations,
        'desc_tip'      => $integrations_tip,
        'default' 		=> $shipping_method['method_integration'],
        'options' 		=> apply_filters( 'flexible_shipping_integration_options' , array( '' => __( 'None', 'flexible-shipping' ) ) )
	),
);

$settings = apply_filters( 'flexible_shipping_method_settings', $settings, $shipping_method );

if ( isset( $settings['method_max_cost'] ) ) {
	$this->settings['method_max_cost'] = $settings['method_max_cost']['default'];
}

$settings['method_rules'] = array(
			'title' 		=> __( 'Rules', 'flexible-shipping' ),
			'type' 			=> 'shipping_rules',
			'default' 		=> isset( $shipping_method['method_rules'] ) ? $shipping_method['method_rules'] : array(),
			'method_title'  => $shipping_method['method_title'],
);

if ( version_compare( WC()->version, '2.6' ) < 0 ) {
	unset( $settings['method_free_shipping_label'] );
}

if ( class_exists('WC_Shipping_Zones1') ) {
	$shipping_zones = WC_Shipping_Zones::get_zones();
	$shipping_zones_options = array();
	foreach ( $shipping_zones as $shipping_zone ) {
		$shipping_zones_options[$shipping_zone['zone_id']] = $shipping_zone['zone_name'];
	}
	$settings_with_zones = array();
	foreach ( $settings as $key => $setting ) {
		$settings_with_zones[$key] = $setting;
		if ( $key == 'method_title' ) {
			$settings_with_zones['method_shipping_zone'] = array(
					'title' 		=> __( 'Shipping Zone', 'flexible-shipping' ),
					'type' 			=> 'select',
					'default' 		=> $shipping_method['method_shipping_zone'],
					'options' 		=> $shipping_zones_options,
			);
		}
	}
	return $settings_with_zones;
}

$docs_link = get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/docs/flexible-shipping-pro-woocommerce-docs/' : 'https://www.wpdesk.net/docs/flexible-shipping-pro-woocommerce-docs/';

echo '<p>' . sprintf( __( 'See how to %sconfigure shipment methods%s.', 'flexible-shipping' ), '<a href="' . $docs_link . '" target="_blank">', '</a>' ) . '</p>';

return $settings;
