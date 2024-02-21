<?php
namespace Harrison\WC_Helpers;

class HBF_OrderIsEditable {
    public static function init_hooks() {
        add_filter('woocommerce_order_is_editable', [$this, 'make_net15_orders_editable'], 9999, 2);
    }

    public static function make_net15_orders_editable($purchasable, $product) {
        $limited_product_id = 1580;

        if ($product->get_id() == $limited_product_id) {
            if (is_user_logged_in()) {
                $current_user_id = get_current_user_id();

                if (wc_customer_bought_product('', $current_user_id, $limited_product_id)) {
                    $purchasable = false;
                }
        } else {
            $purchasable = false;
        }
        }

        return $purchasable;
    }
}