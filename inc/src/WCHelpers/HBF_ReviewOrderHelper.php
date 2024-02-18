<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_ReviewOrderHelper {
    public function __construct() {
        add_action( 'woocommerce_review_order_before_payment', [$this, 'custom_order_notes_field'], 20 );
    }

    public function custom_order_notes_field() {
        if ( HBF_User::is_admin_or_can_view_extra_fields() ) {
            echo '<div class="custom-order-notes">';
            woocommerce_form_field( 'custom_order_notes', array(
                'type'        => 'textarea',
                'class'       => array('form-row-wide'),
                'label'       => __('Order Notes'),
                'placeholder' => __('Enter notes about the order here.'),
            ), '');
            echo '</div>';
        }
    }
}
