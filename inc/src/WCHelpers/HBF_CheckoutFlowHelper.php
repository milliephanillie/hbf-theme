<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_CheckoutFlowHelper {
    public function __construct() {
        add_filter('wp_head', [$this, 'hfood_skip_cart_redirect_checkout']);
        add_filter('woocommerce_get_stock_html', [$this, 'custom_remove_in_stock_text'], 10, 2);
        add_action( 'woocommerce_admin_order_data_after_billing_address', [$this, 'display_custom_fields_in_admin_order'], 10, 1 );
        add_action( 'woocommerce_review_order_before_shipping', [$this, 'checkout_shipping_form_packing_addition'], 20 );
        add_filter( 'woocommerce_package_rates', [$this, 'define_default_shipping_method'], 100, 2 );
        add_action( 'woocommerce_before_add_to_cart_button', [$this, 'hide_subscription_pricing_for_guests'], 10 );
        add_filter('woocommerce_get_price_html', [$this, 'hide_subscription_price_shop_page'], 10, 2);
        //todo what is this actin doing? it's passing a price arg, but wp_footer doesn't pass that?
        add_action('wp_footer', [$this, 'hide_subscription_price_for_guests_js']);
        // Thank You Page
        add_action( 'woocommerce_thankyou', function( $order_id ) {
            if ( is_old_admin() ) {
                $old_user = HBF_User::get_old_user();
                $printUrl = $this->generate_invoice_print_url($order_id);

                $order = wc_get_order($order_id);

                include $template;
            }
        });

        add_filter( 'body_class', function ( $classes ) {
            if ( ! is_admin() && is_product() ) {
                global $post;
                $product = wc_get_product( $post->ID );
                $cssclass = 'hide_pricing_'.$product->get_type();
                return array_merge( $classes, array( $cssclass ) );
            }
            else{
                return $classes;
            }
        });

        //Adding the cart fragment after billing address
        add_action('woocommerce_checkout_after_customer_details', function(){
            if(!is_checkout()){
                return;
            }
            ?>
            <br>
            <style>
                .cart_item .product-name{
                    width: 50%;
                }
                .cart_item .product-name a.remove{
                    float: right;
                }
                .product-name .quantity{
                    display: inline-block !important;
                }
            </style>
            <script>

            </script>
            <?php
            //echo do_shortcode('[woocommerce_cart]');
        });

        add_filter( 'show_admin_bar', [$this, 'hf_hide_admin_bar']);
    }

    public function generate_invoice_print_url($order_id) {
        $order_id = intval($order_id);

        $print_nonce = wp_create_nonce('print-invoice');

        return home_url("/checkout/order-received/{$order_id}/?wc_pip_action=print&wc_pip_document=invoice&order_id={$order_id}&_wpnonce={$print_nonce}");
    }

    public function hfood_skip_cart_redirect_checkout( $url ) {
        global $post;

        if(is_user_logged_in()){
            $user = wp_get_current_user();

            if($post->post_name == 'cart' && (in_array('administrator', $user->roles) || is_old_admin())){
                if(\WC()->cart->get_cart_contents_count() == 0){
                    ?>
                    <script>
                        window.location = '/manual-orders';
                    </script>
                    <?php
                }
                ?>
                <script>
                    window.location = '<?= wc_get_checkout_url(); ?>';
                </script>
                <?php
            }
        }
    }

    public function custom_remove_in_stock_text( $html, $product ) {
        if ( $product->is_in_stock() ) {
            return '';
        }
        return $html;
    }

    public function display_custom_fields_in_admin_order( $order ) {
        echo '<p><strong>' . __('Order Type') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_type', true ) . '</p>';
        echo '<div style="clear:both;"></div>';  // Clear the float to ensure the next fields line up 2x2
        echo '<p><strong>' . __('Shipping') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_shipping_custom', true ) . '</p>';
        echo '<p><strong>' . __('Admin Fee') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_admin_fee', true ) . '</p>';
        echo '<p><strong>' . __('Pallet Fee') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_pallet_fee', true ) . '</p>';
        echo '<p><strong>' . __('Miscellaneous Fee') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_misc_fee', true ) . '</p>';
    }

    public function checkout_shipping_form_packing_addition( ) {
        $domain = 'woocommerce';

        $chosen   = \WC()->session->get('chosen_order_type');
        $shipping_amount   = \WC()->session->get('shipping-amount') ?? '';
        $admin_fee   = \WC()->session->get('admin-fee') ?? '';
        $pallet_fee   = \WC()->session->get('pallet-fee') ?? '';
        $misc_fee   = \WC()->session->get('misc-fee') ?? '';

        if(!is_user_logged_in()){
            return;
        }

        $user = wp_get_current_user();

        if(!in_array('administrator',$user->roles) && !is_old_admin() && !user_can($user->ID, 'view_extra_fields')){
            return;
        }

        echo '<tr class="order-type manual-order-row"><th>' . __('Order Type', $domain) . '</th><td>';

        // Add a custom checkbox field
        woocommerce_form_field( 'chosen_order_type', array(
            'type'      => 'select',
            'class'     => array( 'form-row-wide type' ),
            'options'   => array(
                'national' =>  'National',
                'international' => 'International',
            ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'chosen_order_type')
        ), $chosen );

        echo '</td></tr>';

        echo '<tr class="shipping-amount manual-order-row"><th>' . __('Shipping Cost ($)', $domain) . '</th><td>';

        woocommerce_form_field( 'shipping-amount', array(
            'type'      => 'number',
            'class'     => array( 'form-row-wide shipping-amount fee-field' ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'shipping-amount', 'step' => '0.1', 'min' => '0')
        ), $shipping_amount );

        echo '</td></tr>';

        echo '<tr class="admin-fee manual-order-row"><th>' . __('Admin Fee ($)', $domain) . '</th><td>';

        woocommerce_form_field( 'admin-fee', array(
            'type'      => 'number',
            'class'     => array( 'form-row-wide admin-fee fee-field' ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'admin-fee', 'step' => '0.1', 'min' => '0')
        ), $admin_fee );

        echo '</td></tr>';

        echo '<tr class="admin-fee manual-order-row"><th>' . __('Pallet Fee ($)', $domain) . '</th><td>';

        woocommerce_form_field( 'pallet-fee', array(
            'type'      => 'number',
            'class'     => array( 'form-row-wide pallet-fee fee-field' ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'pallet-fee', 'step' => '0.1', 'min' => '0')
        ), $pallet_fee );

        echo '</td></tr>';

        echo '<tr class="admin-fee manual-order-row"><th>' . __('Misc Fee ($)', $domain) . '</th><td>';

        woocommerce_form_field( 'misc-fee', array(
            'type'      => 'number',
            'class'     => array( 'form-row-wide misc-fee fee-field' ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'misc-fee', 'step' => '0.1', 'min' => '0')
        ), $misc_fee );

        echo '</td></tr>';
    }

    public function define_default_shipping_method( $rates, $package ) {
        $order_type = \WC()->session->get( 'chosen_order_type' );
        $shipping = floatval(\WC()->session->get( 'shipping-amount' ));

        if($order_type === 'international'){
            foreach( $rates as $rate_id => $rate_val ) {
                if($rate_id == 'free_shipping:10'){
                    continue;
                }
                unset( $rates[ $rate_id ] );
            }
        }

        return $rates;
    }

    public function hide_subscription_pricing_for_guests() {
        if ( ! is_user_logged_in() ) {
            echo '<style>.wcsatt-options-product, .wcsatt-options-wrapper { display: none; }</style>';
            echo '<p style="padding: 4rem 0; font-size: 22px;"><a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" style="font-weight: bold; color: red;">Register or Login</a> to Subscribe and Save.</p>';

        }
    }

    public function hide_subscription_price_for_guests_js() {
        if (!is_user_logged_in()) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('.price').each(function() {
                        var text = $(this).text();
                        var newText = text.split('—')[0];
                        $(this).text(newText);
                    });
                });
            </script>
            <?php
        }
    }

    public function hf_hide_admin_bar(){
        $user = wp_get_current_user();

        if(function_exists('get_old_user')){
            $old_user = get_old_user();
            if($old_user && (in_array('administrator', $old_user->roles) || user_can($old_user->ID, 'view_extra_fields'))){
                return true;

            }
        }

        if(in_array('administrator', $user->roles) || user_can($user->ID,'view_extra_fields') ){
            return true;
        }
        return false;
    }

    public function hide_subscription_price_shop_page($price, $product) {
        if (!is_user_logged_in()) {
            if (is_product()) {
                return $price; // Show regular price on product page for guests
            } else {
                return preg_replace('/— or (.*) \/ month/', '', $price); // Hide subscription price on other pages for guests
            }
        }
        return $price; // Show price for logged-in users and everywhere else
    }
}