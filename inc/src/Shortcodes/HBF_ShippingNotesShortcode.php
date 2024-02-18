<?php
namespace Harrison\Shortcodes;

use Harrison\Shortcodes\HBF_Shortcodes;
use Harrison\Includes\HBF_User;

class HBF_ShippingNotesShortcode extends HBF_Shortcodes {
    protected function set_sc_settings() {
        $this->sc_settings = [
            'name' => 'shipping_notes_field',
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
        }

        $output = '
        <div class="custom-shipping-notes">
            <label for="shipping_notes">Shipping Notes:</label>
            <textarea id="shipping_notes" name="shipping_notes" class="custom-shipping-notes-textarea"></textarea>
        </div>
        ';

        return $output;
    }

    public function validate_user_has_been_admin(\WP_User $user) {
        return in_array('administrator', $user->roles) || HBF_User::is_old_admin() || user_can($user->ID, 'view_extra_fields');
    }
}