<?php
namespace Harrison\AJAX;

class HBF_ApplyCreditToCustomer {
    public function __construct() {
        add_action('wp_ajax_apply_credit_to_customer', [$this, 'apply_credit_to_customer']);
        add_action('wp_ajax_nopriv_apply_credit_to_customer', [$this, 'apply_credit_to_customer']);
    }

    public function apply_credit_to_customer() {
        if (!isset($_POST['creditAmount'], $_POST['userId'])) {
            wp_send_json_error(['message' => 'Invalid data provided']);
            wp_die();
        }

        $creditAmount = floatval($_POST['creditAmount']);
        $userId = intval($_POST['userId']);
        $currentBalance = floatval(get_user_meta($userId, 'credit_balance', true));
        $newBalance = $currentBalance - $creditAmount;

        $currentBalance = floatval(get_user_meta($userId, 'credit_balance', true));
        $newBalance = $currentBalance - $creditAmount;

        if ($newBalance < 0) {
            wp_send_json_error(['message' => 'Credit amount exceeds available balance']);
            wp_die();
        }

        update_user_meta($userId, 'credit_balance', $newBalance);
        \WC()->session->set('applied_credit', $creditAmount);

        wp_send_json_success(['message' => 'Credit applied successfully', 'newBalance' => $newBalance]);
        wp_die();
    }
}