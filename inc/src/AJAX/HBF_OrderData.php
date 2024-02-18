<?php
namespace Harrison\AJAX;

class HBF_OrderData {
    public function __construct() {
        add_action('wp_ajax_fetch_previous_orders', [$this, 'fetch_previous_orders']);
        add_action('wp_ajax_nopriv_fetch_previous_orders', [$this, 'fetch_previous_orders']);

        add_action('wp_ajax_fetch_previous_orders_for_credits', [$this, 'fetch_previous_orders_for_credits']);
        add_action('wp_ajax_nopriv_fetch_previous_orders_for_credits', [$this, 'fetch_previous_orders_for_credits']);

        add_action('wp_ajax_fetch_order_details_for_credit', [$this, 'fetch_order_details_for_credit']);
        add_action('wp_ajax_nopriv_fetch_order_details_for_credit', [$this, 'fetch_order_details_for_credit']);

        add_action('wp_ajax_fetch_order_details', [$this, 'fetch_order_details']);
        add_action('wp_ajax_nopriv_fetch_order_details', [$this, 'fetch_order_details']);
    }

    public function fetch_previous_orders() {
        if (!isset($_POST['user_id'])) {
            echo json_encode(array('success' => false, 'message' => 'No user ID provided.'));
            wp_die();
        }

        // Check if the user has 'manual_ordering' or 'view_extra_fields' capability
        if (!current_user_can('manual_ordering') && !current_user_can('view_extra_fields')) {
            echo json_encode(array('success' => false, 'message' => 'Permission denied.'));
            wp_die();
        }

        if (!function_exists('wc_get_orders')) {
            echo json_encode(array('success' => false, 'message' => 'WooCommerce not active.'));
            wp_die();
        }

        $user_id = intval($_POST['user_id']);

        // Fetch the previous orders for the user
        $orders = wc_get_orders(array('customer_id' => $user_id, 'limit' => -1));

        $response = array();
        foreach ($orders as $order) {
            $response[] = array(
                'order_id' => $order->get_id(),
                'order_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'order_total' => $order->get_total(),
            );
        }

        if (empty($response)) {
            echo json_encode(array('success' => false, 'message' => 'No orders found for this user.'));
            wp_die();
        }

        echo json_encode(array('success' => true, 'orders' => $response));
        wp_die();
    }

    public function fetch_previous_orders_for_credits() {
        if (!isset($_POST['user_id'])) {
            echo json_encode(array('success' => false, 'message' => 'No user ID provided.'));
            wp_die();
        }

        // Check if the user has 'manual_ordering' or 'view_extra_fields' capability
        if (!current_user_can('manual_ordering') && !current_user_can('view_extra_fields')) {
            echo json_encode(array('success' => false, 'message' => 'Permission denied.'));
            wp_die();
        }

        if (!function_exists('wc_get_orders')) {
            echo json_encode(array('success' => false, 'message' => 'WooCommerce not active.'));
            wp_die();
        }

        $user_id = intval($_POST['user_id']);
        $orders = wc_get_orders(array('customer_id' => $user_id, 'limit' => -1));

        $response = array();
        foreach ($orders as $order) {
            $is_fully_refunded = $order->has_status('refunded');
            $total_refunded = $order->get_total_refunded();
            $order_total = $order->get_total();
            $is_partially_refunded = $total_refunded > 0 && $total_refunded < $order_total;

            $response[] = array(
                'order_id' => $order->get_id(),
                'order_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'order_total' => $order_total,
                'is_refunded' => $is_fully_refunded,
                'is_partially_refunded' => $is_partially_refunded
            );
        }

        if (empty($response)) {
            echo json_encode(array('success' => false, 'message' => 'No orders found for this user.'));
            wp_die();
        }

        echo json_encode(array('success' => true, 'orders' => $response));
        wp_die();
    }

    public function fetch_order_details() {
        if (!isset($_POST['order_id'])) {
            echo json_encode(array('success' => false, 'message' => 'No order ID provided.'));
            wp_die();
        }

        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);

        if (!$order) {
            echo json_encode(array('success' => false, 'message' => 'Invalid order ID.'));
            wp_die();
        }

        $order_items = array();
        $item_count = 0; // Initialize item count
        $product_total = 0; // Initialize product total

        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);
            $product_name = $item->get_name();
            $item_price = wc_format_decimal($order->get_item_total($item, false, false), 2); // Get item total excluding taxes and discounts
            $item_quantity = $item->get_quantity();

            $order_items[] = array(
                'name' => $product_name,
                'price' => $item_price,
                'quantity' => $item_quantity
            );

            $item_count += $item_quantity; // Update item count
            $product_total += $item_price * $item_quantity; // Update product total
        }

        $user_id = $order->get_user_id(); // Get the user ID associated with the order

        echo json_encode(array(
            'success' => true,
            'order_items' => $order_items,
            'item_count' => $item_count, // Return item count
            'product_total' => $product_total, // Return product total
            'user_id' => $user_id // Return user ID
        ));
        wp_die();
    }

    public function fetch_order_details_for_credit() {
        if (!isset($_POST['order_id'])) {
            echo json_encode(array('success' => false, 'message' => 'No order ID provided.'));
            wp_die();
        }

        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);

        if (!$order || $order->has_status('refunded')) {
            echo json_encode(array('success' => false, 'message' => 'Order is fully refunded or invalid.'));
            wp_die();
        }

        // Fetch credited items meta
        $credited_items = get_post_meta($order_id, 'credited_items', true) ?: [];

        $order_items = array();
        foreach ($order->get_items() as $item_id => $item) {
            $refunded_quantity = $order->get_qty_refunded_for_item($item_id);
            $remaining_quantity = $item->get_quantity() - abs($refunded_quantity);

            if ($remaining_quantity <= 0) {
                continue; // Skip fully refunded items
            }

            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $product = wc_get_product($variation_id ? $variation_id : $product_id);

            $product_name = $product->get_name();
            if ($variation_id) {
                $product_name .= ' - ' . implode(', ', $product->get_variation_attributes());
            }

            $line_total = $item->get_total(); // Line total excluding tax
            $line_tax = $item->get_total_tax(); // Total tax for the line

            // Construct a unique key for the product or variation
            $item_key = $variation_id ? $product_id . '_' . $variation_id : $product_id;

            // Fetch credited quantity using the unique key
            $credited_qty = isset($credited_items[$item_key]) ? absint($credited_items[$item_key]) : 0;

            $order_items[] = array(
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'name' => $product_name,
                'price' => wc_format_decimal($line_total / $item->get_quantity(), 2),
                'tax' => wc_format_decimal($line_tax, 2),
                'quantity' => $remaining_quantity,
                'line_total' => wc_format_decimal($line_total + $line_tax, 2),
                'credited_qty' => $credited_qty
            );
        }

        error_log('Credit Module: Order Items - ' . print_r($order_items, true));

        echo json_encode(array(
            'success' => true,
            'order_items' => $order_items
        ));
        wp_die();
    }
}



