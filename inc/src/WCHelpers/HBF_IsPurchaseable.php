<?php
namespace Harrison\WC_Helpers;

class HBF_IsPurchasable {
    public static function init_hooks() {
        add_filter('woocommerce_is_purchasable', ['self', 'limit_product_purchase_once_per_customer'], 10, 2);
        add_filter('woocommerce_variation_is_purchasable', ['self', 'limit_product_purchase_once_per_customer'], 10, 2);
    }

    public static function limit_product_purchase_once_per_customer($purchasable, $product) {
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