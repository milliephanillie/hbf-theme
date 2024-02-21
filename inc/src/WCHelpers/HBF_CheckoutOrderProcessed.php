<?php
namespace Harrison\Includes;

class HBF_CheckoutOrderProcessed {
    public static function init_hooks() {
        add_action('woocommerce_checkout_order_processed', ['self', 'custom_payment_checkout_handler'], 10, 3);
    }

    public static function custom_payment_checkout_handler($order_id, $posted_data, $order) {
        $original_order_id = isset($_GET['original_order_id']) ? $_GET['original_order_id'] : null;
        $payment_product_id = 4781;

        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $payment_product_id) {
                if ($original_order_id) {
                    $original_order = wc_get_order($original_order_id);
                    if ($original_order) {
                        $order->update_status('cancelled', __('Payment applied to original order.', 'woocommerce'));
                    }
                    break;
                }
            }
        }
    }
}