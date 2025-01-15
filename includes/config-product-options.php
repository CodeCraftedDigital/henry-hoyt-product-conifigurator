<?php
/**
 * config-product-options.php
 *
 * A single source of truth for custom product options.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * We store our config array in a global variable so it's accessible
 * throughout the plugin (in MainPlugin.php, etc.).
 */
global $ccd_product_options_config;

$ccd_product_options_config = [
    [
        'acf_key'    => 'right_chest_logo_sp',
        'post_field' => 'right_chest_screen_print',
        'label'      => 'Right Chest – Screen Print',
        'type'       => 'select',
        'default'    => 'Blank',
        'upcharge'   => 0,
    ],
    [
        'acf_key'    => 'right_chest_logo_em',
        'post_field' => 'right_chest_embroidery',
        'label'      => 'Right Chest – Embroidery',
        'type'       => 'select',
        'default'    => 'Blank',
        'upcharge'   => 0,
    ],
    [
        'acf_key'    => 'left_chest_pn',
        'post_field' => 'personalized_name_left_chest',
        'label'      => 'Personalized Name (Left Chest)',
        'type'       => 'text',
        'default'    => 'none',
        'upcharge'   => 8,
    ],
    [
        'acf_key'    => 'dp_name_left_chest',
        'post_field' => 'department_name_left_chest',
        'label'      => 'Department Name (Left Chest)',
        'type'       => 'select-and-text',
        'default'    => 'none',
        'upcharge'   => 4,
    ],
    [
        'acf_key'    => 'dp_name_back',
        'post_field' => 'department_name_back',
        'label'      => 'Department Name (Back)',
        'type'       => 'select-and-text',
        'default'    => 'none',
        'upcharge'   => 0,
    ],
];


