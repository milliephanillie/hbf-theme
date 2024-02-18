<?php
namespace Harrison\AJAX;

class HBF_SearchUsersForManualOrders {
    public function __construct() {
        add_action('wp_ajax_search_users_for_manual_orders', [$this, 'search_users_for_manual_orders']);
        add_action('wp_ajax_nopriv_search_users_for_manual_orders', [$this, 'search_users_for_manual_orders']);
    }

    public function search_users_for_manual_orders() {
        if (!isset($_POST['search_term'])) {
            echo json_encode(array('success' => false, 'message' => 'No search term provided.'));
            wp_die();
        }

        $search_term = sanitize_text_field($_POST['search_term']);

        $users_by_email_and_name = get_users(array(
            'search' => '*' . esc_attr($search_term) . '*',
            'search_columns' => array('user_login', 'user_nicename', 'user_email'),
        ));

        $users_by_billing_phone = get_users(array(
            'meta_query' => array(
                array(
                    'key' => 'billing_phone',
                    'value' => $search_term,
                    'compare' => 'LIKE'
                )
            )
        ));

        $users_by_shipping_phone = get_users(array(
            'meta_key' => 'shipping_phone',
            'meta_value' => '%' . $search_term . '%',  // Add wildcards
            'meta_compare' => 'LIKE',
        ));

        $users = array_unique(array_merge($users_by_email_and_name, $users_by_billing_phone, $users_by_shipping_phone), SORT_REGULAR);

        $response = array();
        foreach ($users as $user) {
            $user_meta = get_user_meta($user->ID);
            $response[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'billing_phone' => !empty($user_meta['billing_phone']) ? $user_meta['billing_phone'][0] : '',
                'shipping_phone' => !empty($user_meta['shipping_phone']) ? $user_meta['shipping_phone'][0] : '',
            );
        }

        echo json_encode(array('success' => true, 'users' => $response));
        wp_die();
    }
}
