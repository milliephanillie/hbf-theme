<?php
namespace Harrison\WCHelpers;

class HBF_BeforeCalculateTotals {
    public static function init_hooks() {
        add_action('woocommerce_before_calculate_totals', ['self', 'set_custom_total']);
    }

    public static function set_custom_total($cart_object) {
        if (\WC()->session->get('partial_payment_order_id') && \WC()->session->get('partial_payment_amount')) {
            $payment_amount = \WC()->session->get('partial_payment_amount');
            foreach ($cart_object->get_cart() as $hash => $value) {
                $value['data']->set_price($payment_amount);
            }
        }
    }
}