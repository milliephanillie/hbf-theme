<?php
namespace Harrison\WCHelpers;

class HBF_CreditInfoHelper {
    public function __construct() {
        add_action('woocommerce_cart_calculate_fees', [$this, 'apply_credit_as_fee']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_credit_info_to_order_meta']);
    }

    public function apply_credit_as_fee(\WC_Cart $cart) {
        $creditAmount = \WC()->session->get('applied_credit');
        if ($creditAmount) {
            $cart->add_fee(__('Credit Applied', 'woocommerce'), -$creditAmount, false); // Negative fee
        }
    }

    public function save_credit_info_to_order_meta($order_id) {
        $creditAmount = \WC()->session->get('applied_credit');
        if ($creditAmount) {
            update_post_meta($order_id, '_applied_credit', $creditAmount);
            \WC()->session->__unset('applied_credit');
        }
    }
}