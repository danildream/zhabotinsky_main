<div class="flexible-shipping-pro-box">
    <div class="metabox-holder">
        <div class="stuffbox">
            <h3 class="hndle"><?php _e( 'Get Flexible Shipping PRO!', 'flexible-shipping' ); ?></h3>

            <?php
                $fs_link = get_locale() === 'pl_PL' ? 'https://www.wpdesk.pl/sklep/flexible-shipping-pro-woocommerce/' : 'https://www.wpdesk.net/products/flexible-shipping-pro-woocommerce/';
            ?>

            <div class="inside">
                <div class="main">
                    <ul>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Shipping Classes support', 'flexible-shipping' ); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Product count based costs', 'flexible-shipping' ); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Stopping a rule', 'flexible-shipping' ); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Cancelling a rule', 'flexible-shipping' ); ?></li>
                        <li><span class="dashicons dashicons-yes"></span> <?php _e( 'Additional calculation methods (sum, lowest cost, highest cost)', 'flexible-shipping' ); ?></li>
                    </ul>

                    <a class="button button-primary" href="<?php echo $fs_link; ?>?utm_source=flexible-shipping-settings&utm_medium=button&utm_campaign=flexible-shipping-pro-plugin" target="_blank"><?php _e( 'Upgrade Now &rarr;', 'flexible-shipping' ); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
