<?php
namespace Harrison\Integrations;

class HBF_StoreLocator {
    public function __construct() {
        add_filter('wpsl_meta_box_fields', 'custom_meta_box_fields');
    }

    public function custom_meta_box_fields($meta_fields) {
        $meta_fields[__('Additional Information', 'wpsl')] = array(
            'phone' => array(
                'label' => __('Tel', 'wpsl')
            ),
            'fax' => array(
                'label' => __('Fax', 'wpsl')
            ),
            'email' => array(
                'label' => __('Email', 'wpsl')
            ),
            'url' => array(
                'label' => __('Url', 'wpsl')
            )
        );

        return $meta_fields;
    }
}