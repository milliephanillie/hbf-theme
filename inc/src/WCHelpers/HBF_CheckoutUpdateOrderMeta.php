<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_CheckoutUpdateOrderMeta {
    public static function init_hooks() {
        add_action('woocommerce_checkout_update_order_meta', ['self', 'copy_billing_to_shipping_if_empty']);
        add_action('woocommerce_checkout_update_order_meta', ['self', 'save_custom_order_notes']);
        add_action('woocommerce_checkout_update_order_meta', ['self', 'save_shipping_notes']);
        add_action('woocommerce_checkout_update_order_meta', ['self', 'save_credit_info_to_order_meta']);
    }

    public static function save_credit_info_to_order_meta($order_id) {
        $creditAmount = \WC()->session->get('applied_credit');
        if ($creditAmount) {
            update_post_meta($order_id, '_applied_credit', $creditAmount);
            \WC()->session->__unset('applied_credit');
        }
    }

    public static function save_shipping_notes( $order_id ) {
        $shipping_notes = \WC()->session->get('shipping_notes');
        if (!empty($shipping_notes)) {
            update_post_meta($order_id, '_shipping_notes', $shipping_notes);
            \WC()->session->__unset('shipping_notes');
        }
    }

    public static function save_custom_order_notes( $order_id ) {
        if ( isset($_POST['custom_order_notes']) && !empty($_POST['custom_order_notes']) ) {
            $order = wc_get_order( $order_id );
            $order->add_order_note( sanitize_textarea_field($_POST['custom_order_notes']) );
        }
    }

    public static function copy_billing_to_shipping_if_empty( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( empty( $order->get_shipping_address_1() ) ) {
            $billing_address_1 = $order->get_billing_address_1();
            $billing_address_2 = $order->get_billing_address_2();
            $billing_city = $order->get_billing_city();
            $billing_state = $order->get_billing_state();
            $billing_postcode = $order->get_billing_postcode();
            $billing_country = $order->get_billing_country();

            $order->set_shipping_address_1( $billing_address_1 );
            $order->set_shipping_address_2( $billing_address_2 );
            $order->set_shipping_city( $billing_city );
            $order->set_shipping_state( $billing_state );
            $order->set_shipping_postcode( $billing_postcode );
            $order->set_shipping_country( $billing_country );

            $order->save();
        }
    }
}


