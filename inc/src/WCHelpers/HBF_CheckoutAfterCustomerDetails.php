<?php
namespace Harrison\Includes;

class HBF_CheckoutAfterCustomerDetails {
    public static function init_hooks() {
        add_action('woocommerce_checkout_after_customer_details', ['self', 'do_checkout_after_cd']);
    }

    public static function do_checkout_after_cd() {
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
    }
}