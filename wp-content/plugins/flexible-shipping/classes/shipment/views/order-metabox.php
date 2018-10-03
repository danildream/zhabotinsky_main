<div class="flexible_shipping_shipment" id="flexible_shipping_shipment_<?php echo $shipment->get_id(); ?>" data-id="<?php echo $shipment->get_id(); ?>">
    <?php wp_nonce_field( 'flexible_shipping_shipment_nonce', 'flexible_shipping_shipment_nonce_' . $shipment->get_id(), false ); ?>
    <div class="flexible_shipping_shipment_content">
        <?php $shipment->order_metabox(); ?>
    </div>
    <div class="flexible_shipping_shipment_message flexible_shipping_shipment_message_error">
        <?php echo $shipment->get_error_message(); ?>
    </div>
</div>
