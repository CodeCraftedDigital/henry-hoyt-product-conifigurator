<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Define paths
define( 'ACFWOOADDONS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACFWOOADDONS_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

// 2. Optionally load Composer autoloader if you have one
if ( file_exists( ACFWOOADDONS_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
    require_once ACFWOOADDONS_PLUGIN_PATH . 'vendor/autoload.php';
}

// 3. Load our config array
require_once ACFWOOADDONS_PLUGIN_PATH . 'includes/config-product-options.php';

// 4. Load our classes
require_once ACFWOOADDONS_PLUGIN_PATH . 'includes/ProductVariationsFetcher.php';
require_once ACFWOOADDONS_PLUGIN_PATH . 'includes/ProductVariationsFetcherImplementation.php';
require_once ACFWOOADDONS_PLUGIN_PATH . 'includes/ProductFormRenderer.php';
require_once ACFWOOADDONS_PLUGIN_PATH . 'includes/MainPlugin.php';

// 5. Hook plugin initialization
add_action( 'plugins_loaded', function() {
    new \CodeCraftedDigital\ACFWooAddOns\MainPlugin();
});
