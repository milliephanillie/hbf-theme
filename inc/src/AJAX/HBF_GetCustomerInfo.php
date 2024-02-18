<?php
namespace Harrison\AJAX;

class HBF_GetCustomerInfo {
    public function __construct() {
        add_action('wp_ajax_get_customer_info', [$this, 'get_customer_info']);
    }

    public function get_customer_info() {
        // Ensure user ID is provided
        if (!isset($_POST['user_id'])) {
            echo json_encode(array('success' => false, 'message' => 'No user ID provided.'));
            wp_die();
        }

        $user_id = intval($_POST['user_id']);
        $user_info = get_userdata($user_id);

        // Fetch address components
        $address_1 = get_user_meta($user_id, 'billing_address_1', true);
        $address_2 = get_user_meta($user_id, 'billing_address_2', true);
        $city = get_user_meta($user_id, 'billing_city', true);
        $state = get_user_meta($user_id, 'billing_state', true);
        $postcode = get_user_meta($user_id, 'billing_postcode', true);
        $country = get_user_meta($user_id, 'billing_country', true);

        // Create full address
        $full_address = $address_1;
        if (!empty($address_2)) {
            $full_address .= ", " . $address_2;
        }
        $full_address .= ", " . $city;
        if (!empty($state)) {
            $full_address .= ", " . $state;
        }
        $full_address .= " " . $postcode;
        if (!empty($country)) {
            $full_address .= ", " . $country;
        }

        // Fetch the credit balance for the user
        $credit_balance = get_user_meta($user_id, 'credit_balance', true);

        echo json_encode(array(
            'success' => true,
            'first_name' => $user_info->first_name,
            'last_name' => $user_info->last_name,
            'address' => $full_address,
            'phone' => get_user_meta($user_id, 'billing_phone', true),
            'email' => $user_info->user_email,
            'credit_balance' => $credit_balance ? $credit_balance : '0.00' // Show 0.00 if no balance
        ));
        wp_die();
    }
}