<?php
namespace Harrison\AJAX;

class HBF_ApplyCreditToCustomerAccount {
    public function __construct() {
        add_action('wp_ajax_apply_credit_to_customer_account', [$this, 'apply_credit_to_customer_account']);
        add_action('wp_ajax_nopriv_apply_credit_to_customer_account', [$this, 'apply_credit_to_customer_account']);
    }

    public function apply_credit_to_customer_account() {
        // Check if the necessary data is present
        if (!isset($_POST['user_id'], $_POST['order_id'], $_POST['credit_data']) || !is_array($_POST['credit_data'])) {
            echo json_encode(array('success' => false, 'message' => 'Invalid data provided.'));
            wp_die();
        }

        $user_id = intval($_POST['user_id']);
        $order_id = intval($_POST['order_id']);
        $credit_data = $_POST['credit_data'];

        // Fetch the order
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('Order not found with ID: ' . $order_id);
            return;
        }

        // Initialize variables
        $total_credit = 0; // Initialize total item credit amount
        $total_tax_credit = 0; // Initialize total tax credit amount
        $credited_items_info = ''; // Initialize credited items information

        // Log the received credit data
        error_log('Received credit data: ' . print_r($credit_data, true));

        // Process each credit item
        foreach ($credit_data as $credit_item) {
            // Sanitize and validate each item
            $quantity = intval($credit_item['quantity']);
            $total_cost_for_all_items = floatval($credit_item['cost']);
            $total_tax_for_all_items = floatval($credit_item['tax']);

            // Log details of each credit item being processed
            error_log('Processing credit item: ' . print_r($credit_item, true));

            // Calculate cost and tax per item
            $cost_per_item = $total_cost_for_all_items / $quantity;
            $tax_per_item = $total_tax_for_all_items / $quantity;

            // Calculate credit for this item (excluding tax)
            $item_credit = $cost_per_item * $quantity;

            // Calculate tax credit for this item
            $item_tax_credit = $tax_per_item * $quantity;

            // Add to total credit and total tax credit
            $total_credit += $item_credit;
            $total_tax_credit += $item_tax_credit;

            // Append credited item info for the order note
            $credited_items_info .= intval($credit_item['quantity']) . ' x ' . $credit_item['product_name'] . ', ';

            // Add a line item for the credited amount
            $item = new \WC_Order_Item_Fee();
            $item->set_name("Credited: " . $credit_item['product_name']);
            $item->set_amount(-$item_credit); // Negative because it's a credit
            $item->set_total(-$item_credit); // Negative because it's a credit
            $order->add_item($item);

            // Log the item and credit details
            error_log('Item credited: ' . $item->get_name() . ', Amount: ' . $item_credit);
        }

        // Calculate the net credit amount (including the tax credit)
        $net_credit_amount = $total_credit + $total_tax_credit;

        // Trim the trailing comma and space
        $credited_items_info = rtrim($credited_items_info, ', ');

        // After applying credit, store the credited quantities
        $credited_items = get_post_meta($order_id, 'credited_items', true) ?: [];
        foreach ($credit_data as $credit_item) {
            $product_id = $credit_item['product_id'];
            $variation_id = $credit_item['variation_id'] ?? null; // Assuming variation_id is part of $credit_item
            $credited_quantity = intval($credit_item['quantity']);
            $item_key = $variation_id ? $product_id . '_' . $variation_id : $product_id;
            $credited_items[$item_key] = ($credited_items[$item_key] ?? 0) + $credited_quantity;
        }
        update_post_meta($order_id, 'credited_items', $credited_items);

        // Update the user's credit balance
        $current_balance = get_user_meta($user_id, 'credit_balance', true);
        $current_balance = $current_balance ? floatval($current_balance) : 0;

        // Include both item credit and tax credit in the new balance calculation
        $new_balance = $current_balance + $total_credit + $total_tax_credit;

        // Log new balance
        error_log("New balance after credit application: $new_balance");

        // Format the new balance to two decimal places
        $formatted_balance = number_format($new_balance, 2, '.', '');

        // Update the user's credit balance with formatted value
        update_user_meta($user_id, 'credit_balance', $formatted_balance);

        // Set the original amount
        $original_order_total = $order->get_total();

        // Set the new order total
        $order->set_total($order->get_total() - $total_credit);

        // Calculate the adjusted order total based on the original total
        $adjusted_order_total = $original_order_total - $net_credit_amount;

        // Log final credited items and adjusted order total
        error_log('Final credited items: ' . $credited_items_info);
        error_log('Adjusted order total: ' . $adjusted_order_total);

        // Add a detailed note to the order including tax in the credit
        $note = "Credit of " . wc_price($total_credit + $total_tax_credit) .
            " (item credit: " . wc_price($total_credit) .
            ", tax credit: " . wc_price($total_tax_credit) .
            ") applied. Items credited: {$credited_items_info}. " .
            "Adjusted order total: " . wc_price($adjusted_order_total);
        $order->add_order_note($note);

        // Save the changes to the order
        $order->calculate_totals();
        $order->save();

        // Log the response being sent back
        $response = array('success' => true, 'message' => 'Credit applied successfully.', 'new_balance' => $formatted_balance);
        error_log('Response: ' . print_r($response, true));

        // Respond back
        echo json_encode($response);
        wp_die();
    }
}