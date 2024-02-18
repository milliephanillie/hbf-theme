<?php
namespace Harrison\Utils;

class HBF_Templates {
    public static function get_template_content($template_path = null, $vars = []) {
        if(null !== $template_path && file_exists($template_path)) {
            extract($vars);

            ob_start();

            include $template_path;

            return ob_get_clean();
        }

        return false;
    }

    public static function get_template_path($path = null) {
        if(null !== $path) {
            $path = trailingslashit(trailingslashit(HBF_PLUGIN_PATH) . $path);
        }

        return $path;
    }
}