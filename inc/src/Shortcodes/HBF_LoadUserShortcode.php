<?php
namespace Harrison\Shortcodes;

use Harrison\Includes\HBF_User;
use Harrison\Shortcodes\HBF_Shortcodes;

class HBF_LoadUserShortcode extends HBF_Shortcodes {
    protected function set_sc_settings() {
        $this->sc_settings = [
            'handle' => 'load_user_shortcode',
            'name' => 'load_user',
            'permission_callback' => 'validate_user_has_never_been_admin'
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
        }

        $output = '
        <div class="manual-order-controls">
            <label for="loadUser">Load User:</label>
            <input type="text" id="loadUser" placeholder="Search for a user...">
            <div id="userSearchResults" class="user-search-results" style="background: #DDDDDD"></div>
            <button id="switchToUser" data-current-user="<?php echo is_user_logged_in() ? get_current_user_id() : -1 ?>" disabled>Switch to User</button>
            <button id="loadPreviousOrder" disabled>Load a Previous Order</button>
            <button id="emptyCart" disabled>EMPTY CART</button>
        </div>
    
        <div id="previousOrders" style="display:none;">
            <table id="ordersTable">
                <thead style="display:none;"> <!-- Initially hidden -->
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
    
        <!-- Template for order details row -->
        <script type="text/template" id="orderDetailsRowTemplate">
            <tr class="order-details-row">
                <td colspan="3">
                    <div class="order-details-dropdown"></div>
                </td>
            </tr>
        </script>
        ';

        return $output;
    }

    public function validate_user_has_never_been_admin(\WP_User $user) {
        if (!in_array('administrator', $user->roles) && !user_can($user->ID, 'view_extra_fields') && !is_old_admin()) {
            return false;
        }

        return true;
    }
}