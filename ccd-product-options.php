<?php
/*
Plugin Name: CCD Product Options
Description: Custom product options for WooCommerce (color, size, quantity).
Version: 1.0
Author: Your Name
Text Domain: ccd-product-options
*/

// Include the Interface files first
require_once plugin_dir_path(__FILE__) . 'includes/interface-enqueuable.php'; // Enqueuable interface
require_once plugin_dir_path(__FILE__) . 'includes/interface-product-variations-fetcher.php'; // ProductVariationsFetcher interface

// Include the necessary class files
require_once plugin_dir_path(__FILE__) . 'includes/class-product-options-enqueuer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-product-variations-fetcher.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-product-form-renderer.php';

// Main plugin class
class CCD_Product_Options {

    private $productOptionsEnqueuer;
    private $productVariationsFetcher;
    private $productFormRenderer;

    public function __construct() {
        // Instantiate dependencies
        $this->productOptionsEnqueuer = new ProductOptionsEnqueuer();
        $this->productVariationsFetcher = new ProductVariationsFetcherImplementation();

        // Hook actions and filters
        add_action('wp_enqueue_scripts', [$this->productOptionsEnqueuer, 'enqueue_scripts']);
        add_action('rest_api_init', [$this, 'register_rest_route']);
        add_action('woocommerce_before_add_to_cart_form', [$this, 'render_product_form'], 10);
    }

    // Register REST API Route
    public function register_rest_route() {
        register_rest_route('code/v1', '/get-variations/(?P<product_id>\d+)', array(
            'methods' => 'GET',
            'callback' => [$this->productVariationsFetcher, 'get_product_variations'],
        ));
    }

    // Render the product form
    public function render_product_form() {
        global $product;
        $this->productFormRenderer = new ProductFormRenderer($product, $this->productVariationsFetcher);
        $this->productFormRenderer->render_empty_form();
    }
}

// Initialize the plugin class
new CCD_Product_Options();
?>
