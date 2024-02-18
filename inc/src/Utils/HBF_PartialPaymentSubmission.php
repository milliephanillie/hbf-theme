<?php
namespace Harrison\Utils;

class HBF_PartialPaymentSubmission {
    public function __construct() {
        add_action('admin_post_apply_partial_payment', [$this, 'handle_partial_payment_submission']);
    }

    public function handle_partial_payment_submission() {
        // Perform nonce check for security
        check_admin_referer('apply_partial_payment_nonce');

        if (!function_exists('WC')) {
            wp_die('WooCommerce is not active');
        }

        // Ensure WC session is started
        if (null === \WC()->session) {
            wc_load_cart(); // This function will start the session with WooCommerce if it hasn't started already.
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0;

        // Set the session variables
        \WC()->session->set('partial_payment_order_id', $order_id);
        \WC()->session->set('partial_payment_amount', $payment_amount);

        // Redirect to the checkout page with the payment amount
        \WC()->cart->empty_cart(); // Empty the cart first
        $payment_product_id = get_option('woocommerce_apply_payment_product_id');
        if (!$payment_product_id) {
            wp_die('Payment product ID is not set');
        }
        \WC()->cart->add_to_cart($payment_product_id, 1, '', [], ['subtotal' => $payment_amount, 'total' => $payment_amount]);
        wp_redirect(wc_get_checkout_url());
        exit;
    }
}


