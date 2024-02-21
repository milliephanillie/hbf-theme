<?php
namespace Harrison\WCHelpers;

class HBF_BeforeCart {
    public static function init_hooks() {
        add_action('woocommerce_before_cart', ['self', 'recalculate_shipping_when_cart_updated']);
    }

    public static function recalculate_shipping_when_cart_updated() {
        \WC()->cart->calculate_totals();
    }
}