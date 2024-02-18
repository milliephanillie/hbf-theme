<?php
namespace Harrison\Utils;

use Harrison\Includes\HBF_User;

class HBF_ManualOrderItem {
    public function __construct() {
        add_action('admin_bar_menu', [$this, 'manual_order_item_menu'], 500);
        add_action('admin_bar_menu', [$this, 'add_switching_info_to_admin_bar'], 100);
    }

    public function manual_order_item_menu($admin_bar) {
        global $current_user;

        if (HBF_User::is_admin_or_can_view_extra_fields()) {
            $admin_bar->add_menu(array(
                'id' => 'manual-order-custom',
                'title' => 'Manual Orders',
                'href'  => site_url('/manual-orders'),
                'parent' => 'top-secondary',
            ));

            global $wp;
            $current_url = home_url(add_query_arg(array(), $wp->request));
            $manual_orders_page_url = site_url('/manual-orders');
            if ($current_url === $manual_orders_page_url) {
                $admin_bar->add_menu(array(
                    'id' => 'create-customer',
                    'title' => '<button id="createCustomerBtn" style="background-color: #0073aa; color: white; border: none; padding: 10px 20px; margin-right: 10px; border-radius: 10px; cursor: pointer;">CREATE CUSTOMER</button>',
                    'parent' => 'manual-order-custom',
                ));
            }
        }
    }

    public function add_switching_info_to_admin_bar($admin_bar) {
        if (current_user_can('manual_ordering')) {
            $original_user = false;

            $current_user_id = get_current_user_id();
            $original_user_id = get_transient('original_user_' . $current_user_id);
            if ($original_user_id) {
                $original_user = get_userdata($original_user_id);
            }

            if (!$original_user && class_exists('user_switching') && method_exists('user_switching', 'get_original_user')) {
                $original_user = user_switching::get_original_user();
            }

            $current_user_data = get_userdata(get_current_user_id());
            $current_username = $current_user_data->user_login;

            $menu_class = $original_user ? 'switched-user' : '';

            $admin_bar->add_menu(array(
                'id' => 'currently-acting-as',
                'title' => 'Currently Acting As: ' . $current_username,
                'parent' => 'top-secondary',
                'meta' => array('class' => $menu_class)
            ));
        }
    }
}