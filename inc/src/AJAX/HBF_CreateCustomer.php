<?php
namespace Harrison\AJAX;

class HBF_CreateCustomer {
    public function __construct() {
        add_action('wp_ajax_create_woocommerce_customer', [$this, 'create_woocommerce_customer']);
    }

    public function create_woocommerce_customer() {
        if (empty($_POST['billing_email']) || !is_email($_POST['billing_email'])) {
            echo json_encode(array('success' => false, 'message' => 'Invalid email.'));
            wp_die();
        }

        if (empty($_POST['account_password'])) {
            $password = wp_generate_password(12, true);
        } else {
            $password = sanitize_text_field($_POST['account_password']);
            if (strlen($password) < 6) {
                echo json_encode(array('success' => false, 'message' => 'Password must be at least 6 characters.'));
                wp_die();
            }
        }

        $email = sanitize_email($_POST['billing_email']);
        $password = sanitize_text_field($_POST['account_password']);
        $first_name = sanitize_text_field($_POST['billing_first_name']);
        $last_name = sanitize_text_field($_POST['billing_last_name']);

        $user_id = username_exists($email);

        if (!$user_id && email_exists($email) == false) {
            $user_id = wc_create_new_customer($email, $email, $password);

            if (is_wp_error($user_id)) {
                echo json_encode(array('success' => false, 'message' => $user_id->get_error_message()));
                wp_die();
            }

            wp_update_user(array('ID' => $user_id, 'display_name' => "$first_name $last_name"));

            update_user_meta($user_id, 'billing_first_name', $first_name);
            update_user_meta($user_id, 'billing_last_name', $last_name);
            update_user_meta($user_id, 'billing_company', sanitize_text_field($_POST['billing_company']));
            update_user_meta($user_id, 'billing_address_1', sanitize_text_field($_POST['billing_address_1']));
            update_user_meta($user_id, 'billing_address_2', sanitize_text_field($_POST['billing_address_2']));
            update_user_meta($user_id, 'billing_city', sanitize_text_field($_POST['billing_city']));
            update_user_meta($user_id, 'billing_postcode', sanitize_text_field($_POST['billing_postcode']));
            update_user_meta($user_id, 'billing_country', sanitize_text_field($_POST['billing_country']));
            update_user_meta($user_id, 'billing_state', sanitize_text_field($_POST['billing_state']));
            update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
            update_user_meta($user_id, 'billing_email', $email);

            if (!empty($_POST['isDistributor']) && $_POST['isDistributor'] == 'true') {
                $user = new WP_User($user_id);
                $user->add_role('distributor');
            }

            if (!empty($_POST['isExport']) && $_POST['isExport'] == 'true') {
                $user = new WP_User($user_id);
                $user->add_role('export');
            }

            if (!empty($_POST['isInternational']) && $_POST['isInternational'] == 'true') {
                $user = new WP_User($user_id);
                $user->add_role('international');
            }

            echo json_encode(array('success' => true, 'message' => 'Customer has been created', 'redirect' => site_url('/manual-orders')));
        } else {
            echo json_encode(array('success' => false, 'message' => 'User already exists.'));
        }

        wp_die();
    }
}