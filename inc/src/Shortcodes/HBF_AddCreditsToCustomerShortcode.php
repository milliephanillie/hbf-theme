<?php
namespace Harrison\Shortcodes;

use Harrison\Shortcodes\HBF_Shortcodes;

class HBF_AddCreditsToCustomerShortcode extends HBF_Shortcodes
{
    protected function set_sc_settings()
    {
        $this->sc_settings = [
            'handle' => 'add_credits_to_customer',
            'name' => 'add_credits_to_customer',
            'permission_callback' => 'validate_user_is_logged_in'
        ];
    }

    public function render_shortcode($atts, $content = null)
    {
        $user_has_required_caps = $this->validate_user(wp_get_current_user());

        if (!$user_has_required_caps) {
            return $this->sc_settings['validation_failure_message'] ?? '';
        }

        $sc_script_handle = $this->sc_settings['handle'] ?? null;
        if ($sc_script_handle) {
            wp_enqueue_script($sc_script_handle);
        }

        $output = '
        <div class="user-info-container">
            <div class="user-search-container">
                <label for="shortcode-user-search-2">Load Customer Information:</label>
                <input type="text" id="shortcode-user-search-2" placeholder="Search for customer...">
                <div id="shortcode-user-search-2-results" class="user-search-results"></div>
            </div>
            <div id="selected-customer-info" style="margin-bottom: 1rem;"></div>
            <button id="loadPreviousOrder2" class="load-previous-order-button" disabled>Load a Previous Order</button>
            <div id="previousOrdersCredits" class="previous-orders-container-2" style="display:none;">
                <table id="ordersTableCredits" class="orders-table">
                    <thead style="display:none;">
                        <tr>
                            <th>Order ID</th>
                            <th>Order Date</th>
                            <th>Order Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Order rows will be populated here -->
                    </tbody>
                </table>
            </div>
            <script type="text/template" id="orderDetailsRowTemplate2">
                <tr class="order-details-row">
                    <td colspan="3">
                        <div class="order-details-dropdown"></div>
                    </td>
                </tr>
            </script>
            <div class="form-container">
                <div>
                    <div id="orderItemsContainer" class="order-items-container" style="display:none;">
                        <!-- Order items will be populated here -->
                    </div>
                    <div id="creditCalculationContainer" class="credit-calculation-container" style="display:none;">
                        <form id="creditApplicationForm">
                            <!-- Display selected items and total credit amount here -->
                            <button type="submit" id="applyCreditButton">Apply Credit</button>
                        </form>
                    </div>
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