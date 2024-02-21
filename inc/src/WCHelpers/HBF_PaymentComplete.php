<?php
namespace Harrison\WCHelpers;

class HBF_PaymentComplete {
    public static function init_hooks() {
        add_action('woocommerce_payment_complete', ['self', 'handle_partial_payment_complete']);
    }

    public static function handle_partial_payment_complete($order_id) {
        $partial_order_id = \WC()->session->get('partial_payment_order_id');
        if ($partial_order_id && $partial_order_id == $order_id) {
            $order = wc_get_order($order_id);
            $payment_amount = \WC()->session->get('partial_payment_amount');

            \WC()->session->set('partial_payment_order_id', null);
            \WC()->session->set('partial_payment_amount', null);
        }
    }
}