<?php
namespace Harrison\Shortcodes;

use Harrison\Shortcodes\HBF_Shortcodes;

class HBF_RefundFormShortcode extends HBF_Shortcodes
{
    protected function set_sc_settings() {
        $this->sc_settings = [
            'handle' => 'refund_form_shortcode',
            'name' => 'refund_form',
            'permission_callback' => '__return_true'
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

            $refund_form_data_array = array(
                'refundNonce' => wp_create_nonce('refund_nonce_action'),
                // Add any other data needs to pass to the refund-form.js script
            );
            wp_localize_script($sc_script_handle, 'refundFormVars', $refund_form_data_array);
        }

        $output = '
        <div id="refund-form">
            <h2>Refund Form</h2>
            <form id="refund-formout">
                <label for="order-number">Order Number:</label>
                <input type="text" id="order-number" name="order-number" required>
                <button type="submit">Submit</button>
            </form>
            <div id="order-details" style="display: none;">
                <h3>Order Details</h3>
                <div id="refund-status"></div> 
                <div id="order-items"></div>
                <div id="customer-details" style="display: none;"></div>
                <button id="refund-button" style="display: none;">Refund Selected Items $0.00</button>
                <button id="refund-entire-order-button" style="display: none;">Refund Entire Order $0.00</button>
                <label for="refund-reason" class="refund-reason-label">Reason for refund (optional):</label>
                <textarea id="refund-reason" class="refund-reason-textarea" name="refund-reason" rows="4" cols="50"></textarea>
                <button id="cancel-refund-button" class="cancel-refund-button">Cancel Refund</button>
            </div>
        </div>
        ';

        return $output;
    }
}