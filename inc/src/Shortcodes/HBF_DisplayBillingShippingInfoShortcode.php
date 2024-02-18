<?php
namespace Harrison\Shortcodes;

use Harrison\Shortcodes\HBF_Shortcodes;

class HBF_DisplayBillingShippingInfoShortcode extends HBF_Shortcodes
{
    protected function set_sc_settings() {
        $this->sc_settings = [
            'name' => 'user_billing_shipping_info',
            'handle' => 'user_billing_shipping_info_shortcode',
            'permission_callback' => 'validate_user_is_logged_in'
        ];
    }

    public function render_shortcode($atts, $content = null)
    {
        $user_has_required_caps = $this->validate_user(wp_get_current_user());

        if(!$user_has_required_caps) {
            return $this->sc_settings['validation_failure_message']  ?? '';
        }

        $sc_script_handle = $this->sc_settings['handle'] ?? null;
        if ($sc_script_handle) {
            wp_enqueue_script($sc_script_handle);
        }

        $output = '
        <!-- Loading Indicator -->
        <div id="loading-indicator" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999;">
            <div class="spinner"></div>
        </div>
        <div class="user-info-container">
            <div class="user-search-container">
                <label for="shortcode-user-search">Load Customer Information:</label>
                <input type="text" id="shortcode-user-search" placeholder="Search for customer...">
                <div id="shortcode-user-search-results" class="user-search-results"></div>
            </div>
            <button id="loadPreviousOrder" class="load-previous-order-button" disabled>Load a Previous Order</button>
            <div id="previousOrders" class="previous-orders-container" style="display:none; height: 500px; overflow-y: scroll; overflow-x: hidden;">
                <table id="ordersTable" class="orders-table">
                    <thead style="display:none;"><tr><th>Order ID</th><th>Order Date</th><th>Order Total</th></tr></thead>
                    <tbody><!-- Order rows will be populated here --></tbody>
                </table>
            </div>
            <script type="text/template" id="orderDetailsRowTemplate">
                <tr class="order-details-row"><td colspan="3"><div class="order-details-dropdown"></div></td></tr>
            </script>
            <div class="form-container">
                <div>
                    <h3>Billing Information:</h3>
                    <form method="post" class="billing-info-form">
        ';

        $billing_fields = \WC()->checkout->get_checkout_fields('billing');
        $billing_fields['billing_email']['custom_attributes'] = array('autocomplete' => 'off');

        foreach ($billing_fields as $key => $field) {
            $field['id'] = $key . '_billing';
            ob_start();
            woocommerce_form_field($key, $field, '');
            $output .= ob_get_clean();
        }

        $output .= '
                        <input type="hidden" name="selected_user_id" id="selected_user_id" value="">
                        <div class="button-group">
                            <button type="submit" name="save_billing_info" class="savecustinfo-button">SAVE BILLING INFORMATION</button>
                            <button type="reset" id="reset-info-button" class="reset-info-button">RESET FORMS</button>
                        </div>
                        <button type="button" id="switchToUser" class="switch-user-button">SWITCH TO CUSTOMER FOR ORDERING</button>
                    </form>
                </div>
                <div>
                    <form method="post" class="shipping-info-form">
                        <h3 class="ship-to-different-address-heading"><label for="ship-to-different-address-checkbox"><input type="checkbox" id="ship-to-different-address-checkbox" name="ship_to_different_address"> Ship to a different address?</label></h3>
                        <div id="shipping-fields" style="display: none;">
        ';

        $shipping_fields = \WC()->checkout->get_checkout_fields('shipping');
        foreach ($shipping_fields as $key => $field) {
            $field['id'] = $key . '_shipping';
            ob_start();
            woocommerce_form_field($key, $field, '');
            $output .= ob_get_clean();
        }

        $output .= '
                            <input type="hidden" name="selected_user_id" id="selected_user_id" value="">
                            <button type="submit" name="save_shipping_info" class="savecustinfo-button">SAVE SHIPPING INFORMATION</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        ';


        return $output;
    }

    public function validate_user_is_logged_in() {
        if (!is_user_logged_in()) {
            $this->sc_settings['validation_failure_message'] = 'You must be logged in to view this information.';

            return false;
        }

        return true;
    }
}