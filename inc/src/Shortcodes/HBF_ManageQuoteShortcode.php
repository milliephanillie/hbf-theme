<?php
namespace Harrison\Shortcodes;

use Harrison\Shortcodes\HBF_Shortcodes;

class HBF_ManageQuoteShortcode extends HBF_Shortcodes {
    protected function set_sc_settings() {
        $this->sc_settings = [
            'handle' => 'load_and_manage_quote',
            'name' => 'load_and_manage_quote',
            'capability' => 'view_extra_fields',
            'permission_callback' => 'validate_user_is_logged_in_and_has_cap',
            'validation_failure_message' => null,
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
        <div id="load-quote-form" class="load-quote-container">
            <h2>Load a Quote</h2>
            <form id="load-quote-formout">
                <label for="quote-id">Quote ID:</label>
                <input type="text" id="quoteIdInput" name="quote-id" required class="quote-number">
                <button type="submit" class="submit-button">Load Quote</button>
            </form>
            <div id="quote-details" style="display: none;">
                <h3>Quote Details</h3>
                <div id="quote-status"></div>
                <div id="quote-items"></div>
                <div id="customer-details" style="display: none;"></div>
            </div>
        </div>
        ';

        return $output;
    }

    public function validate_user_is_logged_in_and_has_cap() {
        if (!is_user_logged_in()) {
            $this->sc_settings['validation_failure_message'] = $this->get_logged_out_message();
            return false;
        }

        if (!current_user_can($this->sc_settings['capability'])) {
            $this->sc_settings['validation_failure_message'] = $this->get_required_cap_message();
            return false;
        }

        return true;
    }

    protected function get_logged_out_message() {
        return 'You must be logged in to view this content.';
    }

    protected function get_required_cap_message() {
        return 'You do not have the required permission to view this content.';
    }
}