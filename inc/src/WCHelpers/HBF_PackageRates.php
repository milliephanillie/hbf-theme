<?php

class HBF_PackageRates {
    public static function init_hooks() {
        add_filter('woocommerce_package_rates', ['self', 'custom_shipping_cost'], 10, 2);
        add_filter('woocommerce_package_rates', ['self', 'filter_shipping_methods_based_on_user_role'], 10, 2);
        add_filter('woocommerce_package_rates', ['self', 'filter_shipping_methods'], 10, 2);
        add_filter('woocommerce_package_rates', ['self', 'disable_shipping_methods_for_pobox'], 10, 2);
        add_filter('woocommerce_package_rates', ['self', 'define_default_shipping_method'], 100, 2 );
    }

    public function define_default_shipping_method( $rates, $package ) {
        $order_type = \WC()->session->get( 'chosen_order_type' );
        $shipping = floatval(\WC()->session->get( 'shipping-amount' ));

        if($order_type === 'international'){
            foreach( $rates as $rate_id => $rate_val ) {
                if($rate_id == 'free_shipping:10'){
                    continue;
                }
                unset( $rates[ $rate_id ] );
            }
        }

        return $rates;
    }

    public static function custom_shipping_cost($rates, $package) {
        $weight_limit = 6;
        $addendum_percentage = 30;

        $package_weight = 0;
        foreach ($package['contents'] as $item_id => $values) {
            $product = $values['data'];
            $package_weight += $product->get_weight() * $values['quantity'];
        }

        if ($package_weight >= $weight_limit) {
            foreach ($rates as $rate_key => $rate) {
                if (strpos($rate->label, 'UPS') !== false) {
                    $old_cost = $rate->cost;

                    $new_cost = $old_cost + ($old_cost * ($addendum_percentage / 100));
                    $rates[$rate_key]->cost = $new_cost;
                }
            }
        }

        return $rates;
    }

    public static function filter_shipping_methods_based_on_user_role($rates, $package) {
        $user = wp_get_current_user();

        $allowed_roles = array('administrator', 'shop_manager', 'shipping_manager');

        $is_allowed = array_intersect($allowed_roles, $user->roles) || HBF_User::is_old_admin() || user_can($user->ID, 'view_extra_fields');

        if (!$is_allowed) {
            foreach ($rates as $rate_id => $rate) {
                if (strpos($rate_id, 'usps') !== false) {
                    unset($rates[$rate_id]);
                }
            }
        }

        return $rates;
    }

    public static function filter_shipping_methods($rates, $package) {
        $can_view_walk_in = HBF_User::can_view_walkin();

        if (!$can_view_walk_in) {
            foreach ($rates as $rate_id => $rate) {
                if ('free_shipping' === $rate->method_id) {
                    unset($rates[$rate_id]);
                }
            }
        }

        return $rates;
    }

    private static function is_pobox($address)
    {
        $address = strtolower(str_replace(array('.', ',', ' '), '', $address));
        return (strpos($address, 'pobox') !== false || preg_match('/box.*po/i', $address) || preg_match('/po.*box/i', $address));
    }

    public static function disable_shipping_methods_for_pobox($rates, $package) {
        if (HBF_User::is_hbf_admin()) {
            return $rates;
        }

        $shipping_address = $package['destination']['address'];
        if (self::is_pobox($shipping_address)) {
            return array();
        }
        return $rates;
    }
}