<?php
namespace Harrison\AJAX;

class HBF_UpdateShippingMethod {
    public function __construct() {
        add_action('wp_ajax_update_shipping_method', [$this, 'update_shipping_method']);
        add_action('wp_ajax_nopriv_update_shipping_method', [$this, 'update_shipping_method']);
    }

    public function update_shipping_method() {
        if (
            isset($_POST['shipping_method']) && !empty($_POST['shipping_method'])) {
            $chosen_methods = \WC()->session->get('chosen_shipping_methods');
            $chosen_methods[0] = wc_clean($_POST['shipping_method']);
            \WC()->session->set('chosen_shipping_methods', $chosen_methods);

            // Recalculate the cart totals
            \WC()->cart->calculate_totals();

            // Return the updated totals
            echo json_encode(array(
                'success' => true,
                'message' => 'Shipping method updated successfully.',
                'subtotal' => \WC()->cart->get_cart_subtotal(),
                'total' => \WC()->cart->get_total()
            ));
        } else {
            echo json_encode(array(
                'success' => false,
                'message' => 'Invalid or missing shipping method.'
            ));
        }
        wp_die();
    }
}