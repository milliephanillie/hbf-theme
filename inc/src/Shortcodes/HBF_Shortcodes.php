<?php
namespace Harrison\Shortcodes;

abstract class HBF_Shortcodes {
    const JS_RESOURCE_BASE = 'js/shortcodes';

    protected $sc_settings = [
        'name' => null,
        'handle' => null,
        'capability' => null,
        'permission_callback' => null,
    ];

    public function __construct() {
        $this->set_sc_settings();
        $this->boot();
    }

    abstract protected function set_sc_settings();

    abstract public function render_shortcode($atts, $content = null);

    public function boot()
    {
        $sc_name = $this->sc_settings['name'] ?? null;
        $sc_script_handle = $this->sc_settings['handle'] ?? null;

        if ($sc_name) {
            add_shortcode($sc_name, [$this, 'render_shortcode']);

            if (null !== $sc_script_handle) {
                $this->register_script($sc_script_handle);
            }
        }
    }

    public function register_script(string $handle = null, bool $footer = true) {
        if(null !== $handle) {
            try {
                $resource = $this->get_resource($handle);
            } catch (\Exception $exception) {
                throw $exception;
            }

            wp_register_script(
                $handle,
                $resource,
                ['jquery'],
                '1.0.46',
                $footer
            );
        }
    }

    public function validate_user(\WP_User $user, $default = true) {
        $callback = $this->sc_settings['permission_callback'] ?? null;
        $capability = $this->sc_settings['capability'] ?? null;

        if ($callback && is_callable([$this, $callback])) {
            return call_user_func([$this, $callback], $user);
        } elseif($capability) {
            return current_user_can($capability);
        }

        return $default;
    }

    public function get_resource(string $handle = null) {
        if(null === $handle) {
            throw new \Exception('Missing resource handle.');
        }

        $resource_file_path = $this->get_resource_path() . $handle . '.js';
        $resource_file = HBF_THEME_ASSETS_PATH . $resource_file_path;

        if(!file_exists($resource_file)) {
            throw new \Exception('Resource file ' . $resource_file . ' does not exist.');
        }

        $resource = trailingslashit(HBF_THEME_ASSETS_URL) . $resource_file_path;

        return $resource;
    }

    public function get_resource_path(string $path = null) {
        if(null !== $path && !is_string($path)) {
            throw \Exception('Path must be a valid string');
        }

        return $path ?? trailingslashit(self::JS_RESOURCE_BASE);
    }
}