<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_ReviewOrderBeforeShipping {
    public static function init_hooks() {
        add_action( 'woocommerce_review_order_before_shipping', ['self', 'checkout_shipping_form_packing_addition'], 20 );
    }

    public static function checkout_shipping_form_packing_addition( ) {
        $domain = 'woocommerce';

        $chosen   = \WC()->session->get('chosen_order_type');
        $shipping_amount   = \WC()->session->get('shipping-amount') ?? '';
        $admin_fee   = \WC()->session->get('admin-fee') ?? '';
        $pallet_fee   = \WC()->session->get('pallet-fee') ?? '';
        $misc_fee   = \WC()->session->get('misc-fee') ?? '';

        if(!is_user_logged_in()){
            return;
        }

        $user = wp_get_current_user();

        if(!HBF_User::is_admin_or_can_view_extra_fields()){
            return;
        }

        echo '<tr class="order-type manual-order-row"><th>' . __('Order Type', $domain) . '</th><td>';

        // Add a custom checkbox field
        woocommerce_form_field( 'chosen_order_type', array(
            'type'      => 'select',
            'class'     => array( 'form-row-wide type' ),
            'options'   => array(
                'national' =>  'National',
                'international' => 'International',
            ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'chosen_order_type')
        ), $chosen );

        echo '</td></tr>';

        echo '<tr class="shipping-amount manual-order-row"><th>' . __('Shipping Cost ($)', $domain) . '</th><td>';

        woocommerce_form_field( 'shipping-amount', array(
            'type'      => 'number',
            'class'     => array( 'form-row-wide shipping-amount fee-field' ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'shipping-amount', 'step' => '0.1', 'min' => '0')
        ), $shipping_amount );

        echo '</td></tr>';

        echo '<tr class="admin-fee manual-order-row"><th>' . __('Admin Fee ($)', $domain) . '</th><td>';

        woocommerce_form_field( 'admin-fee', array(
            'type'      => 'number',
            'class'     => array( 'form-row-wide admin-fee fee-field' ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'admin-fee', 'step' => '0.1', 'min' => '0')
        ), $admin_fee );

        echo '</td></tr>';

        echo '<tr class="admin-fee manual-order-row"><th>' . __('Pallet Fee ($)', $domain) . '</th><td>';

        woocommerce_form_field( 'pallet-fee', array(
            'type'      => 'number',
            'class'     => array( 'form-row-wide pallet-fee fee-field' ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'pallet-fee', 'step' => '0.1', 'min' => '0')
        ), $pallet_fee );

        echo '</td></tr>';

        echo '<tr class="admin-fee manual-order-row"><th>' . __('Misc Fee ($)', $domain) . '</th><td>';

        woocommerce_form_field( 'misc-fee', array(
            'type'      => 'number',
            'class'     => array( 'form-row-wide misc-fee fee-field' ),
            'required'  => false,
            'custom_attributes' => array('data-type' => 'misc-fee', 'step' => '0.1', 'min' => '0')
        ), $misc_fee );

        echo '</td></tr>';
    }
}
