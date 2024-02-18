<?php
namespace Harrison\AJAX;

class HBF_ClearCustomerSession {
    public function __construct() {
        add_action('wp_ajax_clear_customer_session', [$this, 'clear_customer_session']);
        add_action('wp_ajax_nopriv_clear_customer_session', [$this, 'clear_customer_session']);
    }

    public function clear_customer_session() {
        check_ajax_referer('country_restrict', 'security');

        \WC()->session->destroy_session();
        wp_send_json('success');
    }
}