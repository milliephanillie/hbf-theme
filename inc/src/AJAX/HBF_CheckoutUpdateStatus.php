<?php
namespace Harrison\AJAX;

class HBF_CheckoutUpdateStatus {
    public function __construct() {
        add_action( 'wp_ajax_woo_get_ajax_data', [$this, 'woo_get_ajax_data'] );
        add_action( 'wp_ajax_nopriv_woo_get_ajax_data', [$this, 'woo_get_ajax_data'] );
    }
    public function woo_get_ajax_data() {
        // Check the nonce named 'update_session_nonce' that we created and passed in the localized script
        check_ajax_referer('update_session_nonce', 'security');

        if ( isset($_POST['type']) && isset($_POST['val']) ) {
            $type = sanitize_key( $_POST['type'] );
            $val = sanitize_text_field( $_POST['val'] ); // Sanitize the value

            // Validate the value if needed, for example, ensure it's a number if it's supposed to be
            if ( 'shipping-amount' === $type || 'admin-fee' === $type || 'pallet-fee' === $type || 'misc-fee' === $type ) {
                $val = floatval( $val );
            }

            \WC()->session->set($type, $val);

            // Determine if we need to update the checkout
            $need_update = true; // You can add conditions here to decide when to update the checkout

            wp_send_json_success( array('value' => $val, 'need_update' => $need_update) );
        } else {
            wp_send_json_error('Invalid data received.');
        }

        wp_die();
    }
}