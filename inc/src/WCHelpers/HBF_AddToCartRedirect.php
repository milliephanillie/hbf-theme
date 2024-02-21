<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_AddToCartRedirect {
    public static function init_hooks() {
        add_filter('woocommerce_add_to_cart_redirect', ['self', 'custom_add_to_cart_redirect']);
    }

    public static function custom_add_to_cart_redirect() {
        if (HBF_User::is_admin_or_can_view_extra_fields()) {
            return wc_get_checkout_url();
        }

        return wc_get_page_permalink('shop');
    }
}