<?php
namespace Harrison\WCHelpers;

class HBF_GetStockHTML {
    public static function init_hooks() {
        add_filter('woocommerce_get_stock_html', ['self', 'custom_remove_in_stock_text'], 10, 2);

    }

    public static function custom_remove_in_stock_text( $html, $product ) {
        if ( $product->is_in_stock() ) {
            return '';
        }
        return $html;
    }
}