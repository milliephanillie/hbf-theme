<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_RedirectHelper {
    public function __construct() {
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'custom_add_to_cart_redirect']);
        add_filter('woocommerce_continue_shopping_redirect', [$this, 'custom_continue_shopping_redirect_url']);
    }

    public function custom_add_to_cart_redirect() {
        if (HBF_User::is_admin_or_can_view_extra_fields()) {
            return wc_get_checkout_url();
        }

        return wc_get_page_permalink('shop');
    }

    public function custom_continue_shopping_redirect_url() {
        return wc_get_page_permalink('shop');
    }
}