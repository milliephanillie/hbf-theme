<?php
namespace Harrison\REST;

class HBF_Checkout {
    public function __construct() {
        $this->boot();
    }

    public function boot() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        $namespace = 'harrison/v1';
        $route = 'get-user-billing-shipping-info';

        register_rest_route($namespace, $route, [
            'methods' => [
                \WP_REST_Server::READABLE,
                \WP_REST_Server::CREATABLE,
            ],
            'callback' => [$this, 'get_user_billing_shipping_info'],
            'permission_callback' => '__return_true'

        ]);
    }

    public function get_user_billing_shipping_info(\WP_REST_Request $request) {
        $params = $request->get_params();
        $user_id = isset($params['user_id']) ? intval($params['user_id']) : null;

        if(null === $user_id) {
            return rest_ensure_response(new \WP_REST_Response([
                'success' => false,
                'message' => 'No user ID provided.'
            ]));
        }

        $billing_info = [];
        $shipping_info = [];

        $billing_fields = \WC()->checkout->get_checkout_fields('billing');
        foreach ($billing_fields as $key => $field) {
            $billing_info[$key] = get_user_meta($user_id, $key, true);
        }

        $shipping_fields = \WC()->checkout->get_checkout_fields('shipping');
        foreach ($shipping_fields as $key => $field) {
            $shipping_info[$key] = get_user_meta($user_id, $key, true);
        }

        return rest_ensure_response(new \WP_REST_Response([
            'success' => true,
            'billing_info' => $billing_info,
            'shipping_info' => $shipping_info
        ]));
    }
}