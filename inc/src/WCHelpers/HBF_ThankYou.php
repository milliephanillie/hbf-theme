<?php
namespace Harrison\WCHelpers;

class HBF_ThankYou {
    public static function init_hooks() {
        add_action( 'woocommerce_thankyou', ['self', 'do_thanks'] );
        
    }

    public static function do_thanks( $order_id ) {
        if ( is_old_admin() ) {
            $old_user = HBF_User::get_old_user();
            $printUrl = self::generate_invoice_print_url($order_id);

            $order = wc_get_order($order_id);

            include $template;
        }
    }

    public static function generate_invoice_print_url($order_id) {
        $order_id = intval($order_id);

        $print_nonce = wp_create_nonce('print-invoice');

        return home_url("/checkout/order-received/{$order_id}/?wc_pip_action=print&wc_pip_document=invoice&order_id={$order_id}&_wpnonce={$print_nonce}");
    }
}