<?php
namespace Harrison\AJAX;

class HBF_ResetCreditBalance {
    public function __construct() {
        add_action('wp_ajax_reset_credit_balance', [$this, 'reset_credit_balance']);
    }

    public function reset_credit_balance() {
        if (!isset($_POST['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'No user ID provided.']);
            wp_die();
        }

        $user_id = intval($_POST['user_id']);

        // Reset the credit balance
        update_user_meta($user_id, 'credit_balance', 0);

        echo json_encode(['success' => true, 'message' => 'Credit balance reset successfully.']);
        wp_die();
    }
}