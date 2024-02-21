<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_BeforeCheckoutForm {
    const ASSET_VERSION = '1.0.0';
    
    public static function init_hooks() {
        add_action('woocommerce_before_checkout_form', ['self', 'check_for_pobox_in_shipping'], 10, 0);
        add_action('woocommerce_before_checkout_form', ['self', 'custom_back_button_for_manual_orders'], 10);
    }

    public static function check_for_pobox_in_shipping()
    {
        if (!HBF_User::is_hbf_admin()) {
            wp_enqueue_script('check-for-pobox-in-shipping', HBF_THEME_ASSETS_URL . 'js/check_for_pobox_in_shipping.js', null, self::ASSET_VERSION, true);
        }
    }

    public static function custom_back_button_for_manual_orders() {
        $user = wp_get_current_user();
        if ( HBF_User::is_admin_or_can_view_extra_fields() ) {
            echo '<a href="/manual-orders" class="custom-back-button"><i class="fa fa-arrow-left"></i> BACK</a>';
        }
    }
}