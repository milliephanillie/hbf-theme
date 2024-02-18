<?php

namespace Harrison\Shortcodes;

use Harrison\Shortcodes\HBF_Shortcodes;

class HBF_QuoteButtonShortcode extends HBF_Shortcodes
{
    protected function set_sc_settings() {
        $this->sc_settings = [
            'name' => 'add_to_quote_button',
            'permission_callback' => 'validate_admin_can_view_extra_fields'
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

        $nonce = wp_create_nonce('add-to-quote-nonce');

        $output = '
        <button id="add-to-quote-btn" class="button alt" data-nonce="' . esc_attr($nonce) . '">
            Add to Quote
        </button>';


        return $output;
    }

    public function validate_admin_can_view_extra_fields(\WP_User $user) {
        return (in_array('administrator', $user->roles) || current_user_can('view_extra_fields'));
    }
}