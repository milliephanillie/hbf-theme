<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;
use Harrison\WCHelpers\{
    HBF_AddToCartRedirect,
    HBF_ContinueShoppingRedirect,
    HBF_BeforeCart,
    HBF_BeforeCalculateTotals,
    HBF_CartCalculateFees,
    HBF_PackageRates,
    HBF_BeforeAddToCartButton,
    HBF_BeforeCheckoutForm,
    HBF_GetPriceHTML,
    HBF_GetStockHTML,
    HBF_CheckoutUpdateOrderMeta,
    HBF_CheckoutOrderProcessed,
    HBF_CheckoutAfterCustomerDetails,
    HBF_OrderIsEditable,
    HBF_PaymentComplete,
    HBF_GetOrderPaymentUrl,
    HBF_IsPurchasable,
    HBF_AdminOrderAfterBillingAddress,
    HBF_ReviewOrderBeforeShipping,
    HBF_ReviewOrderBeforePayment,
    HBF_ThankYou,
    HBF_OrderStatusChanged,
    HBF_CheckoutScripts
};

class HBF_CheckoutFlowHelper {
    public static function boot() {
        add_action('woocommerce_loaded', ['HBF_AddToCartRedirect', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_ContinueShoppingRedirect', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_BeforeCart', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_BeforeCalculateTotals', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_CartCalculateFees', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_PackageRates', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_BeforeAddToCartButton', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_BeforeCheckoutForm', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_GetPriceHTML', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_GetStockHTML', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_CheckoutUpdateOrderMeta', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_CheckoutOrderProcessed', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_CheckoutAfterCustomerDetails', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_OrderIsEditable', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_PaymentComplete', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_GetOrderPaymentUrl', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_IsPurchasable', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_AdminOrderAfterBillingAddress', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_ReviewOrderBeforeShipping', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_ReviewOrderBeforePayment', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_ThankYou', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_OrderStatusChanged', 'init_hooks']);
        add_action('woocommerce_loaded', ['HBF_CheckoutScripts', 'init_hooks']); // Note: This is for wp_head and wp_footer, not directly WooCommerce hooks
    }

    public static function boot_ui() {
        add_filter( 'body_class', function ( $classes ) {
            if ( ! is_admin() && is_product() ) {
                global $post;
                $product = wc_get_product( $post->ID );
                $cssclass = 'hide_pricing_'.$product->get_type();
                return array_merge( $classes, array( $cssclass ) );
            }
            else{
                return $classes;
            }
        });

        add_filter( 'show_admin_bar', [HBF_CheckoutFlowHelper::class, 'hf_hide_admin_bar']);
    }

    public static function hf_hide_admin_bar(){
        $user = wp_get_current_user();

        if(function_exists('get_old_user')){
            $old_user = get_old_user();
            if($old_user && (in_array('administrator', $old_user->roles) || user_can($old_user->ID, 'view_extra_fields'))){
                return true;

            }
        }

        if(in_array('administrator', $user->roles) || user_can($user->ID,'view_extra_fields') ){
            return true;
        }
        return false;
    }
}