<?php

class ProductOptionsEnqueuer implements Enqueuable {
    public function enqueue_scripts() {
        wp_enqueue_style('ccd-product-options-style', plugin_dir_url(__FILE__) . '../assets/css/style.css');
        wp_enqueue_script('ccd-product-options', plugin_dir_url(__FILE__) . '../assets/js/product-options.js', [], null, true);
    }
}
