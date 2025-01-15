<?php

namespace CodeCraftedDigital\ACFWooAddOns;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $ccd_product_options_config;

$ccd_product_options_config = [
    [
        'acf_key'      => 'right_chest_logo_sp',
        'post_field'   => 'right_chest_screen_print',
        'label'        => 'Right Chest – Screen Print',
        'type'         => 'select',
        'choices'      => [ 'Blank', 'HFH Logo' ],
        'img_url'      => ACFWOOADDONS_PLUGIN_URL . 'assets/images/right-chest-logo.jpg',
        'upcharge'     => 0
    ],
    [
        'acf_key'      => 'right_chest_logo_em',
        'post_field'   => 'right_chest_embroidery',
        'label'        => 'Right Chest – Embroidery',
        'type'         => 'select',
        'choices'      => [ 'Blank', 'HFH Logo' ],
        'img_url'      => ACFWOOADDONS_PLUGIN_URL . 'assets/images/right-chest-logo.jpg',
        'upcharge'     => 0
    ],
    [
        'acf_key'      => 'left_chest_pn',
        'post_field'   => 'personalized_name_left_chest',
        'label'        => 'Personalized Name (Left Chest)',
        'type'         => 'text',
        'placeholder'  => 'Enter Your Name...',
        'upcharge'     => 8
    ],
    [
        'acf_key'      => 'dp_name_left_chest',
        'post_field'   => 'department_name_left_chest',
        'label'        => 'Department Name (Left Chest)',
        'type'         => 'select-and-text',
        'choices'      => [
            'none'      => 'No Department Name',
            'Left Chest' => 'Left Chest',
        ],
        'img_url'      => ACFWOOADDONS_PLUGIN_URL . 'assets/images/Department-name-left-chest.jpg',
        'upcharge'     => 4
    ],
    [
        'acf_key'      => 'dp_name_back',
        'post_field'   => 'department_name_back',
        'label'        => 'Department Name (Back)',
        'type'         => 'select-and-text',
        'choices'      => [
            'none' => 'No Department Name',
            'yes'  => 'Add Department Name - Back'
        ],
        'img_url'      => ACFWOOADDONS_PLUGIN_URL . 'assets/images/Department-name-back.jpg',
        'upcharge'     => 0
    ],
];
