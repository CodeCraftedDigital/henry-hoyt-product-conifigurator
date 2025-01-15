<?php

namespace CodeCraftedDigital\ACFWooAddOns;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ProductOptionsEnqueuer
 *
 * Enqueues the CSS and JS for the custom product options.
 */
class ProductOptionsEnqueuer implements Enqueuable {

    /**
     * Enqueue front-end styles and scripts.
     */
    public function enqueue_scripts() {
        // Enqueue your plugin’s stylesheet
        wp_enqueue_style(
            'ccd-product-options-style',
            ACFWOOADDONS_PLUGIN_URL . 'assets/css/style.css',
            [],
            '1.0.0'
        );

        // Enqueue your plugin’s JS
        wp_enqueue_script(
            'ccd-product-options',
            ACFWOOADDONS_PLUGIN_URL . 'assets/js/product-options.js',
            [],
            '1.0.0',
            true
        );
    }
}
