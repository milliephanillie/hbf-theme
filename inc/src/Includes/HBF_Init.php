<?php
namespace Harrison\Includes;

class HBF_Init {
    public function __construct() {
        add_action('init', [$this, 'add_custom_order_status'], 30);
        add_action('init', [$this, 'register_partial_refund_order_status']);
        add_action('init', [$this, 'save_user_billing_shipping_info']);
    }

    public function add_custom_order_status() {
        register_post_status('wc-net15-pending', array(
            'label' => 'NET15 Pending',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('NET15 Pending <span class="count">(%s)</span>', 'NET15 Pending <span class="count">(%s)</span>'),
            'post_type' => 'shop_order',
        ));
    }

    public function register_partial_refund_order_status() {
        register_post_status('wc-partial-refund', array(
            'label'                     => 'Partial Refund',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Partial Refund <span class="count">(%s)</span>', 'Partial Refund <span class="count">(%s)</span>'),
        ));
    }

    

    public function save_user_billing_shipping_info() {
        if (isset($_POST['save_billing_info'])) {
            $user_id = intval($_POST['selected_user_id']);
            $billing_fields = \WC()->checkout->get_checkout_fields('billing');
            foreach ($billing_fields as $key => $field) {
                if (isset($_POST[$key])) {
                    update_user_meta($user_id, $key, sanitize_text_field($_POST[$key]));
                }
            }
        }
        if (isset($_POST['save_shipping_info'])) {
            $user_id = intval($_POST['selected_user_id']);
            $shipping_fields = \WC()->checkout->get_checkout_fields('shipping');
            foreach ($shipping_fields as $key => $field) {
                if (isset($_POST[$key])) {
                    update_user_meta($user_id, $key, sanitize_text_field($_POST[$key]));
                }
            }
        }
    }
}