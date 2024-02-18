<?php
namespace Harrison\AJAX;

class HBF_UserBillingInfo {
    public function __construct() {
        add_action('wp_ajax_fetch_billing_info', [$this, 'fetch_billing_info']);
        add_action('wp_ajax_get_user_billing_shipping_info', [$this, 'get_user_billing_shipping_info']);
    }

    public function get_user_billing_shipping_info() {
        if (!isset($_POST['user_id'])) {
            echo json_encode(array('success' => false, 'message' => 'No user ID provided.'));
            wp_die();
        }

        $user_id = intval($_POST['user_id']);
        $billing_info = array();
        $shipping_info = array();

        // Get billing information
        $billing_fields = \WC()->checkout->get_checkout_fields('billing');
        foreach ($billing_fields as $key => $field) {
            $billing_info[$key] = get_user_meta($user_id, $key, true);
        }

        // Get shipping information
        $shipping_fields = \WC()->checkout->get_checkout_fields('shipping');
        foreach ($shipping_fields as $key => $field) {
            $shipping_info[$key] = get_user_meta($user_id, $key, true);
        }

        echo json_encode(array('success' => true, 'billing_info' => $billing_info, 'shipping_info' => $shipping_info));
        wp_die();
    }

    function fetch_billing_info() {
        // Verify nonce
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'fetch_billing_info_nonce')) {
            wp_send_json_error('Nonce verification failed.');
            return;
        }

        // Check for necessary parameters
        if (!isset($_POST['user_id'])) {
            wp_send_json_error('User ID not provided.');
            return;
        }

        $user_id = intval($_POST['user_id']);
        $billing_info = get_user_meta($user_id, 'billing', true);
        wp_send_json_success($billing_info);
    }
}