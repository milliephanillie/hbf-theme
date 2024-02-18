<?php
namespace Harrison\AJAX;

class HBF_CreateGuestCustomer {
    public function __construct() {
        add_action('wp_ajax_create_guest_woocommerce_customer', [$this, 'create_guest_woocommerce_customer']);
        add_action('wp_ajax_nopriv_create_guest_woocommerce_customer', [$this, 'create_guest_woocommerce_customer']);
    }

    public function create_guest_woocommerce_customer() {
        // Validate the phone number
        if (empty($_POST['billing_phone'])) {
            wp_send_json_error('Phone number is required.');
            wp_die();
        }

        // Sanitize user input
        $phone = sanitize_text_field($_POST['billing_phone']);
        $first_name = sanitize_text_field($_POST['billing_first_name']);
        $last_name = sanitize_text_field($_POST['billing_last_name']);
        $company = isset($_POST['billing_company']) ? sanitize_text_field($_POST['billing_company']) : '';
        $address_1 = sanitize_text_field($_POST['billing_address_1']);
        $address_2 = isset($_POST['billing_address_2']) ? sanitize_text_field($_POST['billing_address_2']) : '';
        $city = sanitize_text_field($_POST['billing_city']);
        $state = sanitize_text_field($_POST['billing_state']);
        $postcode = sanitize_text_field($_POST['billing_postcode']);
        $country = sanitize_text_field($_POST['billing_country']);

        $shipping_first_name = isset($_POST['shipping_first_name']) ? sanitize_text_field($_POST['shipping_first_name']) : $first_name;
        $shipping_last_name = isset($_POST['shipping_last_name']) ? sanitize_text_field($_POST['shipping_last_name']) : $last_name;
        $shipping_company = isset($_POST['shipping_company']) ? sanitize_text_field($_POST['shipping_company']) : $company;
        $shipping_address_1 = isset($_POST['shipping_address_1']) ? sanitize_text_field($_POST['shipping_address_1']) : $address_1;
        $shipping_address_2 = isset($_POST['shipping_address_2']) ? sanitize_text_field($_POST['shipping_address_2']) : $address_2;
        $shipping_city = isset($_POST['shipping_city']) ? sanitize_text_field($_POST['shipping_city']) : $city;
        $shipping_state = isset($_POST['shipping_state']) ? sanitize_text_field($_POST['shipping_state']) : $state;
        $shipping_postcode = isset($_POST['shipping_postcode']) ? sanitize_text_field($_POST['shipping_postcode']) : $postcode;
        $shipping_country = isset($_POST['shipping_country']) ? sanitize_text_field($_POST['shipping_country']) : $country;


        // Generate a unique username and dummy email
        $random_code = wp_rand(10000, 99999);
        $username = 'guest_' . $phone;
        $email = $username . '_' . $random_code . '@example.com'; // Using a dummy email domain

        // Check if user already exists
        if (username_exists($username)) {
            wp_send_json_error('A guest with this phone number already exists.');
            wp_die();
        }

        // Create the user
        $user_id = wc_create_new_customer($email, $username, wp_generate_password());


        // Check for errors
        if (is_wp_error($user_id)) {
            echo json_encode(array('success' => false, 'message' => $user_id->get_error_message()));
            wp_die();
        }

        // Set the user's display name to their first and last name
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $first_name . ' ' . $last_name
        ));

        // Update user meta with billing information
        update_user_meta($user_id, 'billing_first_name', $first_name);
        update_user_meta($user_id, 'billing_last_name', $last_name);
        update_user_meta($user_id, 'billing_company', $company);
        update_user_meta($user_id, 'billing_phone', $phone);
        update_user_meta($user_id, 'billing_address_1', $address_1);
        update_user_meta($user_id, 'billing_address_2', $address_2);
        update_user_meta($user_id, 'billing_city', $city);
        update_user_meta($user_id, 'billing_postcode', $postcode);
        update_user_meta($user_id, 'billing_country', $country);
        update_user_meta($user_id, 'billing_state', $state);

        // Update user meta with shipping information
        update_user_meta($user_id, 'shipping_first_name', $shipping_first_name);
        update_user_meta($user_id, 'shipping_last_name', $shipping_last_name);
        update_user_meta($user_id, 'shipping_company', $shipping_company);
        update_user_meta($user_id, 'shipping_address_1', $shipping_address_1);
        update_user_meta($user_id, 'shipping_address_2', $shipping_address_2);
        update_user_meta($user_id, 'shipping_city', $shipping_city);
        update_user_meta($user_id, 'shipping_postcode', $shipping_postcode);
        update_user_meta($user_id, 'shipping_country', $shipping_country);
        update_user_meta($user_id, 'shipping_state', $shipping_state);

        // Assign Customer role
        $user = new \WP_User($user_id);
        $user->set_role('customer');

        // Success response
        echo json_encode(array('success' => true, 'message' => 'Guest user created successfully.'));
        wp_die();
    }
}