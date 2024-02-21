<?php
namespace Harrison\Includes;

use Harrison\Includes\HBF_User;

class HBF_Theme {
    const ENQUEUE_VERSION = '1.0.01';

    public function __construct() {
        $this->boot();
        $this->set_woocommerce_apply_payment_product_id();
    }

    public function set_woocommerce_apply_payment_product_id() {
        if(! get_option('woocommerce_apply_payment_product_id')) {
            update_option('woocommerce_apply_payment_product_id', '4781');
        }
    }

    public function boot() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_custom_scripts']);

        add_action('wp_head', [$this, 'custom_css_for_logged_out_users']);
        add_action('wp_head', [$this, 'add_custom_notification_bar']);
        add_action('wp_head', [$this, 'custom_et_add_viewport_meta']);

        add_filter('body_class', [$this, 'maybe_add_custom_user_view_class']);

        add_filter('wp_nav_menu_items', [$this, 'add_cart_icon_to_menu'], 10, 2);
        add_filter('wp_nav_menu_items', [$this, 'customize_my_account_menu'], 10, 2);
    }

    public function enqueue_custom_scripts() {
        $jspath = HBF_THEME_ASSETS_URL .  trailingslashit('js');
        $cssspath = HBF_THEME_ASSETS_URL .  trailingslashit('css');

        wp_enqueue_style( 'font-awesome', $cssspath . 'font-awesome/fontawesome-free-5.15.4-web/css/all.css' );

        wp_enqueue_script('index', $jspath . 'index.js', ['jquery'], self::ENQUEUE_VERSION, true);
        $php_vars = [
            'is_user_logged_in' => is_user_logged_in(),
            'myaccount_url' => wc_get_page_permalink('myaccount'),
            'adminAjaxUrl' => admin_url('admin-ajax.php'),
            'fetch_billing_info_nonce' => wp_create_nonce('fetch_billing_info_nonce'),
            'switch_to_selected_user_nonce' => wp_create_nonce('switch_to_selected_user_nonce'),
            'update_session_nonce' => wp_create_nonce('update_session_nonce'),
            'currentUsername' => wp_get_current_user()->display_name,
            'isAdmin' => current_user_can('view_extra_fields'),
            'isOldAdmin' => HBF_USER::is_old_admin(),
            'update_custom_fee_nonce' => wp_create_nonce('update-custom-fee-nonce'),
            'oldUsername' => HBF_User::get_old_user() ? HBF_User::get_old_user()->display_name : '',
            'siteUrl' => site_url(),
        ];
        wp_localize_script('index', 'php_vars', $php_vars);

        wp_enqueue_script('custom-scripts', $jspath . 'custom-scripts.js', ['jquery'], self::ENQUEUE_VERSION, true);


        if (is_checkout() && ! is_wc_endpoint_url()) {
            if ( HBF_User::is_admin_or_can_view_extra_fields() ) {
                wp_enqueue_script( 'checkout-order-type', $jspath . 'checkout_order_type.js', ['jquery'], self::ENQUEUE_VERSION, true );
            }
        }
    }

    public function customize_my_account_menu($items, $args) {
        if ($args->theme_location == 'secondary-menu') {
            $account_page = get_permalink(get_option('woocommerce_myaccount_page_id'));
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                $name = $user->first_name;
                $greeting = 'Hello, ' . $name;
                $account_info = '<a href="' . $account_page . '">(My Account)</a>';
                $items = str_replace('My Account', $greeting . ' ' . $account_info, $items);
            } else {
                $login_register = 'Register / Log In';
                $items = str_replace('My Account', $login_register, $items);
            }
        }
        return $items;
    }

    public function add_cart_icon_to_menu($items, $args) {
        if ($args->theme_location == 'mobile_menu') {
            $cart_count = \WC()->cart->get_cart_contents_count();
            $cart_url = wc_get_cart_url();

            $cart_icon = '
                <a href="' . $cart_url . '" class="menu-cart-icon">' .
                    '<i class="fa fa-shopping-cart"></i>' .
                    '<span class="cart-count">' . $cart_count . '</span>
                </a>
            ';

            $items .= '<li class="menu-item menu-cart">' . $cart_icon . '</li>';
        }

        return $items;
    }

    public function maybe_add_custom_user_view_class($classes) {
        $old_user = HBF_User::get_old_user(); 
        $has_extra_fields_view = $old_user ? user_can($old_user->ID, 'view_extra_fields') : current_user_can('view_extra_fields');
        
        if (is_page(['manual-orders', 'checkout', 'thank-you', 'pay-later-thank-you']) && $has_extra_fields_view ) {
            $classes[] = 'custom-user-view';
        }

        return $classes;
    }

    public function add_custom_notification_bar() {
        echo '<div id="custom-notification-bar" style="display: none;">No Order Has Been Started</div>';
    }

    public function custom_et_add_viewport_meta() {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=1" />';
    }

    public function custom_css_for_logged_out_users() {
        if (!is_user_logged_in()) {
            echo '
            <style type="text/css">
                .subscription-option {
                    display: none;
                }
            </style>
            ';
        }
    }
}

