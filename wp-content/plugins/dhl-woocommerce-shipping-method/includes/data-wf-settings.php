<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Array of settings
 */
return array(
	'enabled'          => array(
		'title'           => __( 'Realtime Rates', 'wf-shipping-dhl' ),
		'type'            => 'checkbox',
		'label'           => __( 'Enable', 'wf-shipping-dhl' ),
		'default'         => 'no'
	),
	'title'            => array(
		'title'           => __( 'Method Title', 'wf-shipping-dhl' ),
		'type'            => 'text',
		'description'     => __( 'This controls the title which the user sees during checkout.', 'wf-shipping-dhl' ),
		'default'         => __( 'DHL', 'wf-shipping-dhl' ),
		'desc_tip'        => true
	),
	'account_number'           => array(
		'title'           => __( 'Account Number', 'wf-shipping-dhl' ),
		'type'            => 'text',
		'description'     => __( 'contact DHL.', 'wf-shipping-dhl' ),
		'default'         => '130000279'
    ),
    'site_id'           => array(
		'title'           => __( 'Site ID', 'wf-shipping-dhl' ),
		'type'            => 'text',
		'description'     => __( 'contact DHL.', 'wf-shipping-dhl' ),
		'default'         => 'CIMGBTest',
		'custom_attributes' => array(
			'autocomplete' => 'off'
		)
    ),
    'site_password'           => array(
		'title'           => __( 'Site Password', 'wf-shipping-dhl' ),
		'type'            => 'password',
		'description'     => __( 'contact DHL.', 'wf-shipping-dhl' ),
		'default'         => 'DLUntOcJma',
		'custom_attributes' => array(
			'autocomplete' => 'off'
		)
    ),
	'production'      => array(
		'title'           => __( 'Production Key', 'wf-shipping-dhl' ),
		'label'           => __( 'This is a production key', 'wf-shipping-dhl' ),
		'type'            => 'checkbox',
		'default'         => 'no',
		'desc_tip'    => true,
		'description'     => __( 'If this is a production API key and not a developer key, check this box.', 'wf-shipping-dhl' )
	),
	'region_code'   => array(
		'title'           => __( 'Region Code', 'wf-shipping-dhl' ),
		'type'            => 'select',
		'default'         => 'AM',
		'options'         => array(
			'AP'       => __( 'AP-EM Region: Supports countries in Asia, Africa, Australia and Pacific', 'wf-shipping-dhl' ),
			'EU'       => __( 'EU Region: Supports countries in Europe', 'wf-shipping-dhl' ),
			'AM'       => __( 'AM Region: Supports USA and other countries in North and South Americas', 'wf-shipping-dhl' )
		),
		'description'     => __( 'Choose appropriate Region Code based on the country of origin', 'wf-shipping-dhl' ),
	),
	'timezone_offset' => array(
		'title' 		=> __('Time Zone Offset (Minutes)', 'wf-shipping-dhl'),
		'type' 			=> 'text',
		'default'         => '',
		'description' 	=> __('Please enter a value in this field, if you want to change the shipment time while Label Printing. Enter a negetive value to reduce the time.'),
		'desc_tip' 		=> true
	),
        'dhl_currency_type'      => array(
		'title'       => __( 'DHL Currency', 'wf-shipping-dhl' ),
		'label'       => __( 'DHL Currency', 'wf-shipping-dhl' ),
		'type'        => 'select',
                'options'     => get_woocommerce_currencies(),
		'default'     => get_woocommerce_currency(),
		//'desc_tip'    => true,
		'description' => __( 'This currency will be used to communicate with DHL.', 'wf-shipping-dhl' ),
	),
	'conversion_rate' => array(
		'title' 		=> __('DHL Conversion Rate', 'wf-shipping-dhl'),
		'type' 			=> 'text',
		'default'         => '',
		'description' 	=> __('Enter the conversion rate from DHL Currency to Store currency ('.get_woocommerce_currency_symbol().'). This amount will be multiplied with the shipping rates returned by DHL API. Leave it empty if no conversion required.'),
		//'desc_tip' 		=> true
	),
    
	'dimension_weight_unit' => array(
			'title'           => __( 'Dimension/Weight Unit', 'woocommerce-shipping-dhl' ),
			'label'           => __( 'This unit will be passed to DHL.', 'woocommerce-shipping-dhl' ),
			'type'            => 'select',
			'default'         => 'LBS_IN',
			'description'     => 'Product dimensions and weight will be converted to the selected unit and will be passed to DHL. Please change the box dimensions and weight accordingly as its preloaded with unit Pound and Inches.',
			'options'         => array(
				'LBS_IN'	=> __( 'Pounds & Inches', 'woocommerce-shipping-dhl'),
				'KG_CM' 	=> __( 'Kilograms & Centimetres', 'woocommerce-shipping-dhl')			
			)
		),		
    'insure_contents'      => array(
		'title'       => __( 'Insurance', 'wf-shipping-dhl' ),
		'label'       => __( 'Enable Insurance', 'wf-shipping-dhl' ),
		'type'        => 'checkbox',
		'default'     => 'yes',
		'desc_tip'    => true,
		'description' => __( 'Sends the package value to DHL for insurance.', 'wf-shipping-dhl' ),
	),
	'request_type'     => array(
		'title'           => __( 'Request Type', 'wf-shipping-dhl' ),
		'type'            => 'select',
		'default'         => 'LIST',
		'class'           => '',
		'desc_tip'        => true,
		'options'         => array(
			'LIST'        => __( 'List rates', 'wf-shipping-dhl' ),
			'ACCOUNT'     => __( 'Account rates', 'wf-shipping-dhl' ),
		),
		'description'     => __( 'Choose whether to return List or Account (discounted) rates from the API.', 'wf-shipping-dhl' )
	),	
	'offer_rates'   => array(
		'title'           => __( 'Offer Rates', 'wf-shipping-dhl' ),
		'type'            => 'select',
		'description'     => '',
		'default'         => 'all',
		'options'         => array(
		    'all'         => __( 'Offer the customer all returned rates', 'wf-shipping-dhl' ),
		    'cheapest'    => __( 'Offer the customer the cheapest rate only, anonymously', 'wf-shipping-dhl' ),
		),
    ),
	'services'  => array(
		'type'            => 'services'
	),
	'origin'           => array(
		'title'           => __( 'Origin Postcode', 'wf-shipping-dhl' ),
		'type'            => 'text',
		'description'     => __( 'Enter postcode for the <strong>Shipper</strong>.', 'wf-shipping-dhl' ),
		'default'         => '10027'
    ),
    'freight_shipper_city'           => array(
		'title'           => __( 'Origin City', 'wf-shipping-dhl' ),
		'type'            => 'text',
		'description'     => __( 'Enter city for the <strong>Shipper</strong>.', 'wf-shipping-dhl' ),
		'default'         => '10027'
    ),
	'debug'      => array(
		'title'           => __( 'Debug Mode', 'wf-shipping-dhl' ),
		'label'           => __( 'Enable debug mode', 'wf-shipping-dhl' ),
		'type'            => 'checkbox',
		'default'         => 'no',
		'desc_tip'    => true,
		'description'     => __( 'Enable debug mode to show debugging information on the cart/checkout.', 'wf-shipping-dhl' )
	)
);