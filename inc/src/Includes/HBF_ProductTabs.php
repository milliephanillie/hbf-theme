<?php
namespace Harrison\Includes;

class HBF_ProductTabs {
    private $tabs = null;

    private $template = null;

    private $template_path = null;

    private $products_with_tab_content = [
        [
            'product_id' => 274,
            'templates' => [
                'directions_ingredients' => 'ql_high_potency_coarse_content',
                'product_videos' => 'ql_adult_lifetime_super_fine_content',
            ]
        ],
        [
            'product_id' => 314,
            'templates' => [
                'directions_ingredients' => 'ql_high_potency_fine_content',
                'product_videos' => 'ql_high_potency_fine_vids_content',
            ]
        ],
        [
            'product_id' => 322,
            'templates' => [
                'directions_ingredients' => 'ql_high_potency_sf_content',
            ]
        ],
        [
            'product_id' => 330,
            'templates' => [
                'directions_ingredients' => 'ql_high_potency_mash_content',
            ]
        ],
        [
            'product_id' => 367,
            'templates' => [
                'directions_ingredients' => 'ql_adult_lifetime_coarse_content',
            ]
        ],
        [
            'product_id' => 380,
            'templates' => [
                'directions_ingredients' => 'ql_adult_lifetime_super_fine_content',
                'product_videos' => 'ql_adult_lifetime_super_fine_vids_content',
            ]
        ],
        [
            'product_id' => 363,
            'templates' => [
                'directions_ingredients' => 'ql_power_treats_content',
                'product_videos' => 'ql_power_treats_vids_content',
            ]
        ],
        [
            'product_id' => 400,
            'templates' => [
                'directions_ingredients' => 'ql_juvenile_hand_feeding_content',
            ]
        ],
        [
            'product_id' => 393,
            'templates' => [
                'directions_ingredients' => 'ql_pepper_lifetime_coarse_content',
            ]
        ],
        [
            'product_id' => 385,
            'templates' => [
                'directions_ingredients' => 'ql_high_potency_pepper_content',
                'product_videos' => 'ql_high_potency_pepper_vids_content',
            ]
        ],
        [
            'product_id' => 361,
            'templates' => [
                'directions_ingredients' => 'ql_new_jumpstart_omega_content',
            ]
        ],
        [
            'product_id' => 357,
            'templates' => [
                'directions_ingredients' => 'ql_jumpstart_grey_millet_content',
            ]
        ],
        [
            'product_id' => 383,
            'templates' => [
                'directions_ingredients' => 'ql_adult_lifetime_mash_content',
            ]
        ],
        [
            'product_id' => 374,
            'templates' => [
                'directions_ingredients' => 'ql_adult_lifetime_fine_content',
                'product_videos' => 'ql_adult_lifetime_fine_vids_content',
            ]
        ],
        [
            'product_id' => 413,
            'templates' => [
                'directions_ingredients' => 'ql_recovery_formula_content',
                'product_videos' => 'ql_recovery_formula_vids_content',
            ]
        ],
        [
            'product_id' => 365,
            'templates' => [
                'directions_ingredients' => 'ql_bread_mix_content',
                'product_videos' => 'ql_bread_mix_vids_content',
            ]
        ],
        [
            'product_id' => 902,
            'templates' => [
                'directions_ingredients' => 'ql_bread_mix_omega_content',
                'product_videos' => 'ql_breaded_mix_omega_vids_content',
            ]
        ],
        [
            'product_id' => 1759,
            'templates' => [
                'directions_ingredients' => 'ql_hopper_topper_content',
                'product_videos' => 'ql_hopper_topper_vids_content',
            ]
        ],
        [
            'product_id' => 447,
            'templates' => [
                'directions_ingredients' => 'ql_feeder_frenzy_content',
                'product_videos' => 'ql_feeder_frenzy_vids_content',
            ]
        ],
        [
            'product_id' => 443,
            'templates' => [
                'directions_ingredients' => 'ql_black_and_white_content',
                'product_videos' => 'ql_black_and_white_vids_content',
            ]
        ],
        [
            'product_id' => 440,
            'templates' => [
                'directions_ingredients' => 'ql_safflower_seed_content',
                'product_videos' => 'ql_safflower_seed_vids_content',
            ]
        ],
        [
            'product_id' => 437,
            'templates' => [
                'directions_ingredients' => 'ql_black_oil_content',
                'product_videos' => 'ql_black_oil_vids_content',
            ]
        ],
        [
            'product_id' => 435,
            'templates' => [
                'directions_ingredients' => 'ql_no_filler_content',
                'product_videos' => 'ql_no_filler_vids_content',
            ]
        ],
        [
            'product_id' => 433,
            'templates' => [
                'directions_ingredients' => 'ql_gray_millet_content',
                'product_videos' => 'ql_gray_millet_vids_content',
            ]
        ],
    ];

    public function __construct() {
        add_filter( 'woocommerce_product_tabs', [$this, 'generate_tab_content']);
        add_filter( 'woocommerce_product_tabs', [$this, 'tab_priorities']);
    }

    public function tab_priorities($tabs) {
        $tabs['description']['priority'] = 5;


        if(isset($tabs['directions_ingredients'])) {
            $tabs['directions_ingredients']['priority'] = 10;
        }

        if(isset($tabs['product_videos'])) {
            $tabs['product_videos']['priority'] = 20;
        }

        $tabs['reviews']['priority'] = 30;

        return $tabs;
    }

    public function generate_tab_content($tabs) {
        global $product;
        $product_id = $product->get_id();

        $products_with_tab_content = $this->products_with_tab_content;
        foreach ($products_with_tab_content as $tab) {
            if($tab['product_id'] == $product_id) {
                $tabs['directions_ingredients'] = [
                    'title' => __( 'Directions / Ingredients', 'woocommerce' ),
                    'priority' => 50,
                    'callback' => [$this, 'render_tab_content'],
                    'template' => $tab['templates']['directions_ingredients']
                ];

                $tabs['product_videos'] = [
                    'title' => __( 'Product Videos', 'woocommerce' ), 
                    'priority' => 50,
                    'callback' => [$this, 'render_tab_content'],
                    'template' => $tab['templates']['product_videos']
                ];

            }
        }

        return $tabs;
    }

    public function render_tab_content($key, $tab) {
        $this->template_path = $this->get_template_path() . $tab['template'] . '.php';

        echo $this->get_template_content();
    }

    //todo we can put this in it's own class
    public function get_template_content($template_path = null, $vars = []) {
        $template_path = $template_path ?? $this->template_path;

        if(null !== $template_path && file_exists($template_path)) {
            extract($vars);

            ob_start();

            include $template_path;

            return ob_get_clean();
        }

        return false;
    }

    public function get_template_path() {
        return trailingslashit(trailingslashit(HBF_PLUGIN_PATH) . 'templates/product_tabs');
    }
}