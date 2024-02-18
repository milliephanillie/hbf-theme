<?php
namespace Harrison\Includes;

class HBF_FooterModal {
    const TEMPLATE_PATH = 'templates/components/footer-modal.php';
    const SCRIPT_PATH = 'js/footer-modal.js';

    private $script_handle = null;
    private $modal_template = null;

    public function __construct() {
        $this->script_handle = 'footer-modal';
        $this->modal_template = $this->get_footer_modal();
        $this->footer_script = $this->get_footer_script();

        wp_register_script(
            $this->script_handle,
            $this->footer_script,
            ['jquery'],
            '1.0.3',
            true
        );

        add_action('wp_footer', [$this, 'add_modal_and_script']);
    }

    public function add_modal_and_script() {
        if (current_user_can('view_extra_fields')) {
            include_once $this->modal_template;

            wp_enqueue_script($this->script_handle);

            $script_data = [
                'countries' => \WC()->countries->get_countries(),
                'states' => \WC()->countries->get_states(),
            ];
            wp_localize_script($this->script_handle, 'wc_locations', $script_data);
        }
    }

    public function get_footer_modal() {
        $modal_template = HBF_THEME_TEMPLATES_PATH . self::TEMPLATE_PATH;

        if(!file_exists($modal_template)) {
            throw new \Exception("Missing modal template: $modal_template");
        }

        return $modal_template;
    }

    public function get_footer_script() {
        $footer_file = HBF_THEME_ASSETS_PATH . self::SCRIPT_PATH;
        $footer_script = HBF_THEME_ASSETS_PATH . self::SCRIPT_PATH;

        if(!file_exists($footer_file)) {
            throw new \Exception("Missing footer scripts $footer_script");
        }

        return $footer_script;
    }
}