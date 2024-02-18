<?php
namespace Harrison\WCHelpers;

class HBF_CartCaculateFees {
    public function __construct() {
        add_action( 'woocommerce_cart_calculate_fees', [$this, 'custom_checkout_fee'] );
        add_action( 'woocommerce_cart_calculate_fees', [$this, 'add_packaging_fee'], 20, 1 );
    }

    public function custom_checkout_fee() {
        if ( current_user_can( 'view_extra_fields' ) ) {
            $shipping = \WC()->session->get('shipping-amount') ?? 0;
            $admin_fee = isset( $_POST['order_admin_fee'] ) ? floatval($_POST['order_admin_fee']) : 0;
            $pallet_fee = isset( $_POST['order_pallet_fee'] ) ? floatval($_POST['order_pallet_fee']) : 0;
            $misc_fee = isset( $_POST['order_misc_fee'] ) ? floatval($_POST['order_misc_fee']) : 0;

            $total_fees = $admin_fee + $pallet_fee + $misc_fee;

            if ($total_fees != 0 || $shipping != 0 || $admin_fee != 0 || $pallet_fee != 0 || $misc_fee != 0) {
                \WC()->cart->add_fee('Additional Fees', $total_fees);
            }


            $order_type = isset( $_POST['order_type'] ) ? $_POST['order_type'] : 'national';
            if ( $order_type == 'international' ) {
                $total_cost = \WC()->cart->cart_contents_total;
                $fee = $total_cost * 0.03;  // 3% fee
                \WC()->cart->add_fee( '3% Credit Card Convenience Fee', $fee );

                // Set Free Shipping
                $shipping_packages = \WC()->cart->get_shipping_packages();
                if ( is_array( $shipping_packages ) ) {
                    foreach ( $shipping_packages as $package_id => $package ) {
                        if ( isset( $package['rates'] ) && is_array( $package['rates'] ) ) {
                            foreach ( $package['rates'] as $rate ) {
                                if ( 'free_shipping:10' === $rate->method_id ) {
                                    \WC()->session->set( 'chosen_shipping_methods', array( $package_id => $rate->id ) );
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function add_packaging_fee( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) )
            return;

        global $woocommerce;

        $order_type = \WC()->session->get( 'chosen_order_type' );
        $shipping = floatval(\WC()->session->get( 'shipping-amount' ));
        $admin = floatval(\WC()->session->get( 'admin-fee' ));
        $pallet = floatval(\WC()->session->get( 'pallet-fee' ));
        $misc = floatval(\WC()->session->get( 'misc-fee' ));

        if($order_type === 'national'){
            // var_dump($cart->fees_api);
            return;

        }

        if(!empty($shipping)){
            $cart->add_fee( 'Shipping', $shipping );
        }

        if(!empty($admin)){
            $cart->add_fee( 'Admin Fee', $admin );
        }

        if(!empty($pallet)){
            $cart->add_fee( 'Pallet Fee', $pallet );
        }

        if(!empty($misc)){
            $cart->add_fee( 'Misc Fee', $misc );
        }

        if($order_type == 'international'){
            $fees = $shipping + $misc + $admin + $pallet;
            $cart->add_fee( 'Credit card convenience fee (3%)', ($cart->cart_contents_total + $fees) * 0.03);
        }

        return;
    }
}


