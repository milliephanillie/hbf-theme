<?php
namespace Harrison\AJAX;

class HBF_Cart {
    public function __construct() {
        add_action('wp_ajax_populate_cart_with_order', [$this, 'populate_cart_with_order']);
        add_action('wp_ajax_nopriv_populate_cart_with_order', [$this, 'populate_cart_with_order']);

        add_action('wp_ajax_empty_cart', [$this, 'empty_cart']);
        add_action('wp_ajax_nopriv_empty_cart', [$this, 'empty_cart']);

        add_action('wp_ajax_check_cart_status', [$this, 'check_cart_status']);
        add_action('wp_ajax_nopriv_check_cart_status', [$this, 'check_cart_status']);
    }

    public function populate_cart_with_order() {
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

    public function empty_cart() {
            \WC()->cart->empty_cart();
            echo jsn_encode(array('success' => true));
            wp_die();
    }

    public function check_cart_status() {
        $cart_has_items = !\WC()->cart->is_empty();
        echo json_encode(array('cart_has_items' => $cart_has_items));
        wp_die();
    }
}