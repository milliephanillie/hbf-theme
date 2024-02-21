<?php
namespace Harrison\WC_Helpers;

class HBF_GetOrderPaymentUrl {
    public function __construct() {
        add_action('woocommerce_order_partially_refunded', [$this, 'update_order_status_to_partial_refund'], 10, 2);
        add_filter('woocommerce_get_order_payment_url', [$this, 'custom_order_payment_url'], 30, 2);
    }

    public function update_order_status_to_partial_refund($order_id, $refund_id) {
        error_log('Function triggered for order ID: ' . $order_id);
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('Order not found for ID: ' . $order_id);
            return;
        }
        $order_total = $order->get_total();
        $refunded_amount = $order->get_total_refunded();
        if ($refunded_amount > 0 && $refunded_amount < $order_total) {
            $order->update_status('partial-refund');
            error_log('Order status updated to Partial Refund for order ID: ' . $order_id);
        }
    }

    public function custom_order_payment_url($pay_url, $order) {
        error_log('custom_order_payment_url function called. Original URL: ' . $pay_url);
        $user_id = $order->get_user_id();
        // Create a nonce
        $nonce = wp_create_nonce('switch_user_nonce');
        // Add custom parameters for user switching and nonce
        return add_query_arg(['switch_user_to' => $user_id, '_wpnonce' => $nonce], $pay_url);
    }
}