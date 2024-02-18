<?php
namespace Harrison\Shortcodes;

use Harrison\Shortcodes\HBF_Shortcodes;

class HBF_GuestUserCreationFormShortcode extends HBF_Shortcodes {
    protected function set_sc_settings() {
        $this->sc_settings = [
            'handle' => 'guest_user_form_shortcode',
            'name' => 'guest_user_creation',
            'capability' => 'view_extra_fields',
            'permission_callback' => 'validate_user_has_been_admin'
        ];
    }

    public function render_shortcode($atts, $content = null) {
        $user_has_required_caps = $this->validate_user(wp_get_current_user());

        if(!$user_has_required_caps) {
            return $this->sc_settings['validation_failure_message']  ?? '';
        }

        $sc_script_handle = $this->sc_settings['handle'] ?? null;
        if ($sc_script_handle) {
            wp_enqueue_script($sc_script_handle);

            $script_data = [
                'countries' => \WC()->countries->get_countries(),
                'states'    => \WC()->countries->get_states(),
            ];

            wp_localize_script($sc_script_handle, 'guest_user_creation_form_shortcode_object', $script_data);
        }


        $output = '
        <div class="user-info-container">
            <form id="createGuestUserForm" class="custom-form">
                <div class="form-container">
                    <div>
                        <h3>Billing Information</h3>
                        <label for="guest_billing_first_name">First Name:</label>
                        <input type="text" id="guest_billing_first_name" name="billing_first_name" required>

                        <label for="guest_billing_last_name">Last Name:</label>
                        <input type="text" id="guest_billing_last_name" name="billing_last_name" required>

                        <label for="guest_billing_company">Company:</label>
                        <input type="text" id="guest_billing_company" name="billing_company">

                        <label for="guest_billing_phone">Phone:</label>
                        <input type="text" id="guest_billing_phone" name="billing_phone" required>

                        <label for="guest_billing_address_1">Address Line 1:</label>
                        <input type="text" id="guest_billing_address_1" name="billing_address_1" required>

                        <label for="guest_billing_address_2">Address Line 2:</label>
                        <input type="text" id="guest_billing_address_2" name="billing_address_2">

                        <label for="guest_billing_city">City:</label>
                        <input type="text" id="guest_billing_city" name="billing_city" required>

                        <label for="guest_billing_state">State:</label>
                        <select id="guest_billing_state" name="billing_state">
                            <!-- States will be populated here -->
                        </select>

                        <label for="guest_billing_postcode">Postcode:</label>
                        <input type="text" id="guest_billing_postcode" name="billing_postcode" required>

                        <label for="guest_billing_country">Country:</label>
                        <select id="guest_billing_country" name="billing_country">
                            <!-- Populate this dropdown with countries -->
                        </select>
                    </div>
                    <div>
                        <h3>Shipping Information</h3>
                        <label for="guest_shipping_first_name">First Name:</label>
                        <input type="text" id="guest_shipping_first_name" name="shipping_first_name">

                        <label for="guest_shipping_last_name">Last Name:</label>
                        <input type="text" id="guest_shipping_last_name" name="shipping_last_name">

                        <label for="guest_shipping_company">Company:</label>
                        <input type="text" id="guest_shipping_company" name="shipping_company">

                        <label for="guest_shipping_address_1">Address Line 1:</label>
                        <input type="text" id="guest_shipping_address_1" name="shipping_address_1">

                        <label for="guest_shipping_address_2">Address Line 2:</label>
                        <input type="text" id="guest_shipping_address_2" name="shipping_address_2">

                        <label for="guest_shipping_city">City:</label>
                        <input type="text" id="guest_shipping_city" name="shipping_city">

                        <label for="guest_shipping_state">State:</label>
                        <select id="guest_shipping_state" name="shipping_state">
                            <!-- States will be populated here -->
                        </select>

                        <label for="guest_shipping_postcode">Postcode:</label>
                        <input type="text" id="guest_shipping_postcode" name="shipping_postcode">

                        <label for="guest_shipping_country">Country:</label>
                        <select id="guest_shipping_country" name="shipping_country">
                            <!-- Populate this dropdown with countries -->
                        </select>
                    </div>
                </div>

                <button type="button" id="copyBillingToShipping" class="savecustinfo-button">Copy Billing to Shipping</button>
                
                <button type="submit" class="savecustinfo-button">Create Guest User</button>
            </form>
        </div>
        ';

        return $output;
    }
}
