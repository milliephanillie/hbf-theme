<?php
namespace Harrison\WCHelpers;

class HBF_BeforeAddToCartButton {
    public static function init_hooks() {
        add_action( 'woocommerce_before_add_to_cart_button', ['self', 'hide_subscription_pricing_for_guests'], 10 );
    }

    public static function hide_subscription_pricing_for_guests() {
        if ( ! is_user_logged_in() ) {
            echo '<style>.wcsatt-options-product, .wcsatt-options-wrapper { display: none; }</style>';
            echo '<p style="padding: 4rem 0; font-size: 22px;"><a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" style="font-weight: bold; color: red;">Register or Login</a> to Subscribe and Save.</p>';

        }
    }
}