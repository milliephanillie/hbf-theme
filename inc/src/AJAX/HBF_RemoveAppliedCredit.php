<?php
namespace Harrison\AJAX;

class HBF_RemoveAppliedCredit {
    public function __construct() {
        add_action('wp_ajax_remove_applied_credit', [$this, 'remove_applied_credit']);
        add_action('wp_ajax_nopriv_remove_applied_credit', [$this, 'remove_applied_credit']);
    }

    public function remove_applied_credit() {
        $user_id = get_current_user_id();

        if ($user_id && isset(\WC()->session)) {
            $creditAmount = \WC()->session->get('applied_credit');
            if ($creditAmount) {
                // Restore credit to user
                $currentBalance = floatval(get_user_meta($user_id, 'credit_balance', true));
                $newBalance = $currentBalance + $creditAmount;
                update_user_meta($user_id, 'credit_balance', $newBalance);

                // Remove credit from session
                \WC()->session->__unset('applied_credit');

                wp_send_json_success(['message' => 'Credit removed successfully']);
            } else {
                wp_send_json_error(['message' => 'No credit to remove']);
            }
        } else {
            wp_send_json_error(['message' => 'User not logged in']);
        }

        wp_die();
    }
}