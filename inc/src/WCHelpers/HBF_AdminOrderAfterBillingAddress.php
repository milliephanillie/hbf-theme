<?php
namespace Harrison\WCHelpers;

class HBF_AdminOrderAfterBillingAddress {
    public static function init_hooks() {
        add_action('woocommerce_admin_order_data_after_billing_address', ['self', 'display_credit_in_admin_order'], 10, 1);
        add_action('woocommerce_admin_order_data_after_billing_address', ['self', 'display_custom_fields_in_admin_order'], 10, 1);
    }

    public static function display_credit_in_admin_order($order) {
        $creditApplied = get_post_meta($order->get_id(), '_applied_credit', true);
        if (!empty($creditApplied)) {
            echo '<p><strong>' . __('Credit Applied:') . '</strong> ' . wc_price($creditApplied) . '</p>';
        }
    }

    public static function display_custom_fields_in_admin_order( $order ) {
        echo '<p><strong>' . __('Order Type') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_type', true ) . '</p>';
        echo '<div style="clear:both;"></div>';  // Clear the float to ensure the next fields line up 2x2
        echo '<p><strong>' . __('Shipping') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_shipping_custom', true ) . '</p>';
        echo '<p><strong>' . __('Admin Fee') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_admin_fee', true ) . '</p>';
        echo '<p><strong>' . __('Pallet Fee') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_pallet_fee', true ) . '</p>';
        echo '<p><strong>' . __('Miscellaneous Fee') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_misc_fee', true ) . '</p>';
    }
}