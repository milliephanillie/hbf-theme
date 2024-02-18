<?php
namespace Harrison\Ajax;

class HBF_GetCustomerCreditInfo {
    public function __construct()
    {
        add_action('wp_ajax_get_customer_credit_info', [$this, 'get_customer_credit_info']);
        add_action('wp_ajax_nopriv_get_customer_credit_info', [$this, 'get_customer_credit_info']);
    }

    public function get_customer_credit_info() {
        if (!isset($_POST['userId'])) {
            echo json_encode(array('success' => false, 'message' => 'No user ID provided.'));
            wp_die();
        }

        $user_id = intval($_POST['userId']);
        $user_info = get_userdata($user_id);

        if (!$user_info) {
            echo json_encode(array('success' => false, 'message' => 'User not found.'));
            wp_die();
        }

        // Get the customer's name
        $customer_name = $user_info->first_name . ' ' . $user_info->last_name;

        // Get the customer's credit balance
        $credit_balance = get_user_meta($user_id, 'credit_balance', true);
        $credit_balance = !empty($credit_balance) ? $credit_balance : 0; // Default to 0 if not set

        echo json_encode(array(
            'success' => true,
            'customerName' => $customer_name,
            'creditBalance' => $credit_balance
        ));

        wp_die();
    }
}