<?php
namespace Harrison\Includes;

use Harrison\Includes\HBF_User;

class HBF_TemplateRedirection {
    public function __construct() {
        add_action('template_redirect', [$this, 'set_redirection_rules']);
        add_action('template_redirect', [$this, 'restrict_international_orders']);
        add_action('template_redirect', [$this, 'custom_redirects']);
    }

    private function enqueue_checkout_country_restrict_js() {
        wp_enqueue_script('country-restrict', HBF_PLUGIN_URL . 'assets/js/country-restrict.js', null, '1.0.0', true);

        $translation_array = [
                'clear_customer_session' => wp_create_nonce('country_restrict')
        ];

        wp_localize_script('country-restrict', 'wp_nonces', $translation_array);
    }

    public function custom_redirects() {
        if ($_SERVER['REQUEST_URI'] == '/juvenilechart') {
            wp_redirect(home_url('/handfeeding-formulas-usage-charts/'), 301);
            exit;
        }
    }

    public function restrict_international_orders() {
        $can_order_internationally = HBF_User::user_can_order_internationally();

        if (is_checkout() && !$can_order_internationally) {
            add_action('wp_enqueue_scripts', 'checkout_country_restrict_js');
        }
    }

    public function set_redirection_rules() {
        if (is_page('manual-orders')) {
            global $current_user;
            if (!user_can($current_user->ID, 'view_extra_fields') && !HBF_User::is_old_admin()) {
                wp_redirect(home_url());
                exit;
            }
        }

        if (is_shop()) {
            wc_clear_notices();
        }

        if (is_page_template('page-pay-later-thank-you.php')) {
            $original_user_id = \WC()->session->get('switched_from_user_id');
            if ($original_user_id) {
                switch_to_user($original_user_id, true);
                \WC()->session->set('switched_from_user_id', null);
            }
        }

        if (is_checkout() && !empty($_GET['switch_user_to']) && !empty($_GET['_wpnonce'])) {
            $user_id_to_switch = intval($_GET['switch_user_to']);
            $nonce = $_GET['_wpnonce'];

            if (!wp_verify_nonce($nonce, 'switch_user_nonce')) {
                wp_die('Security check failed');
            }

            $current_user_id = get_current_user_id();

            if (current_user_can('switch_users')) {
                if (function_exists('switch_to_user')) {
                    if (switch_to_user($user_id_to_switch, true, $current_user_id)) {
                        // User has been switched
                    } else {
                        wp_die('Error: Unable to switch to the specified customer. Please try again.');
                    }
                } else {
                    wp_die('Error: User Switching function is not available. Please ensure the User Switching plugin is active.');
                }
            } else {
                wp_die('Error: You do not have permission to switch customers.');
            }
        }
    }
}