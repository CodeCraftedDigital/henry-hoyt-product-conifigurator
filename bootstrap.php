<?php
/**
 * Bootstrap / Loader file for ACF Woo Add-Ons plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Define plugin constants
 */
define( 'ACFWOOADDONS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACFWOOADDONS_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

/**
 * 2. Load Composer's autoload (generated by composer install).
 *    This handles automatic loading of any namespaced classes under includes/.
 */
if ( file_exists( ACFWOOADDONS_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
    require_once ACFWOOADDONS_PLUGIN_PATH . 'vendor/autoload.php';
}

/**
 * 3. Load any global config files or other includes that don't define classes.
 *    For example, our "config-product-options.php" which returns a config array.
 */
if ( file_exists( ACFWOOADDONS_PLUGIN_PATH . 'includes/config-product-options.php' ) ) {
    require_once ACFWOOADDONS_PLUGIN_PATH . 'includes/config-product-options.php';
}

/**
 * 4. Hook plugin initialization to 'plugins_loaded' (or 'init').
 *    We'll instantiate our main plugin class here.
 */
add_action( 'plugins_loaded', function() {
    // The MainPlugin class will reside in includes/MainPlugin.php
    // with namespace CodeCraftedDigital\ACFWooAddOns (per our PSR-4 setup).
    if ( class_exists( '\\CodeCraftedDigital\\ACFWooAddOns\\MainPlugin' ) ) {
        new \CodeCraftedDigital\ACFWooAddOns\MainPlugin();
    }
} );
