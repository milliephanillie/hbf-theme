<?php
namespace Harrison\WCHelpers;

class HBF_GetPriceHTML {
    public static function init_hooks() {
        add_filter('woocommerce_get_price_html', ['self', 'hide_subscription_price_shop_page'], 10, 2);

    }

    public static function hide_subscription_price_shop_page($price, $product) {
        if (!is_user_logged_in()) {
            if (is_product()) {
                return $price; // Show regular price on product page for guests
            } else {
                return preg_replace('/— or (.*) \/ month/', '', $price); // Hide subscription price on other pages for guests
            }
        }
        return $price; // Show price for logged-in users and everywhere else
    }
}