<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_ContinueShoppingRedirect {
    public static function init_hooks() {
        add_filter('woocommerce_continue_shopping_redirect', ['self', 'custom_continue_shopping_redirect_url']);
    }

    public static function custom_continue_shopping_redirect_url() {
        return wc_get_page_permalink('shop');
    }
}