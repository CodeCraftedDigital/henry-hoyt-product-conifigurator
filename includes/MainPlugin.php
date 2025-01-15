<?php

namespace CodeCraftedDigital\ACFWooAddOns;

use WC;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MainPlugin {

    private $productVariationsFetcher;

    public function __construct() {
        // Variation fetcher (for color/size REST endpoint)
        $this->productVariationsFetcher = new ProductVariationsFetcherImplementation();

        // Hook scripts, REST route, custom form
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'rest_api_init',      [ $this, 'register_rest_route' ] );
        add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'render_product_form' ], 10 );

        // Cart & order meta logic
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'handle_cart_item_data' ], 10, 2 );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'handle_upcharges' ] );
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_in_cart' ], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_meta_to_order' ], 10, 4 );
    }

    /**
     * Enqueue scripts (CSS/JS) only if we're on a single product page AND it's variable.
     */
    public function enqueue_scripts() {
        // If not on a single product page, bail immediately
        if ( ! is_product() ) {
            return;
        }

        // Safely get a product object from the current post ID
        $product_obj = wc_get_product( get_the_ID() );
        if ( ! $product_obj ) {
            return; // No product found
        }

        // If the product isn't variable, skip
        if ( ! $product_obj->is_type( 'variable' ) ) {
            return;
        }

        // Only here if it's a variable product on a single product page => enqueue scripts
        wp_enqueue_style(
            'ccd-product-options-style',
            ACFWOOADDONS_PLUGIN_URL . 'assets/css/style.css',
            [],
            '2.0'
        );

        wp_enqueue_script(
            'ccd-product-options-js',
            ACFWOOADDONS_PLUGIN_URL . 'assets/js/product-options.js',
            [],
            '2.0',
            true
        );

        // Localize script for REST route (and anything else you need)
        wp_localize_script(
            'ccd-product-options-js',
            'ccdData',
            [
                // e.g. "https://yoursite.com/wp-json/code/v1/get-variations/"
                'restBase' => esc_url_raw( rest_url( 'code/v1/get-variations/' ) ),
            ]
        );
    }

    /**
     * Register the custom REST route for color/size variations (public).
     */
    public function register_rest_route() {
        register_rest_route(
            'code/v1',
            '/get-variations/(?P<product_id>\d+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this->productVariationsFetcher, 'get_product_variations' ],
                'permission_callback' => '__return_true', // or your custom security
            ]
        );
    }

    /**
     * Render the custom product form on variable products.
     */
    public function render_product_form() {
        global $product;
        if ( ! $product || ! $product->is_type('variable') ) {
            return;
        }

        $renderer = new ProductFormRenderer( $product, $this->productVariationsFetcher );
        $renderer->render_empty_form();
    }

    /**
     * Handle cart item data for custom product options.
     */
    public function handle_cart_item_data( $cart_item_data, $product_id ) {
        global $ccd_product_options_config;

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return $cart_item_data;
        }

        foreach ( $ccd_product_options_config as $option ) {
            if ( ! get_field($option['acf_key'], $product_id) ) {
                continue;
            }

            $posted_value      = isset($_POST[ $option['post_field'] ])
                ? sanitize_text_field($_POST[ $option['post_field'] ])
                : 'None';

            $posted_text_value = isset($_POST[ $option['post_field'] . '_value' ])
                ? sanitize_text_field($_POST[ $option['post_field'] . '_value' ])
                : 'None';

            // Convert "blank" or empty to "None"
            if ( strtolower($posted_value) === 'blank' || $posted_value === '' ) {
                $posted_value = 'None';
            }

            // If "select-and-text"
            if ( $option['type'] === 'select-and-text' ) {
                if (
                    $posted_value !== 'none' &&
                    $posted_value !== 'None' &&
                    $posted_text_value !== 'None' &&
                    $posted_text_value !== ''
                ) {
                    $cart_item_data[ $option['post_field'] . '_value' ] = $posted_text_value;
                } else {
                    $cart_item_data[ $option['post_field'] . '_value' ] = 'None';
                }
            }

            $cart_item_data[ $option['post_field'] ] = $posted_value;

            // Upcharge logic
            if ( isset($option['upcharge']) && $option['upcharge'] > 0 ) {
                if (
                    ($option['type'] === 'text' && $posted_value !== 'None') ||
                    ($option['type'] === 'select-and-text' && $posted_value !== 'none' && $posted_text_value !== 'None' && $posted_text_value !== '') ||
                    ($option['type'] === 'select' && $posted_value !== 'None')
                ) {
                    $cart_item_data[ $option['post_field'] . '_upcharge' ] = floatval($option['upcharge']);
                }
            }
        }

        return $cart_item_data;
    }

    /**
     * Handle upcharges before totals.
     */
    public function handle_upcharges( $cart ) {
        if ( is_admin() && ! defined('DOING_AJAX') ) {
            return;
        }

        global $ccd_product_options_config;
        foreach ( $cart->get_cart() as $cart_item ) {
            foreach ( $ccd_product_options_config as $option ) {
                $key = $option['post_field'] . '_upcharge';
                if ( isset( $cart_item[$key] ) && $cart_item[$key] > 0 ) {
                    $cart_item['data']->set_price(
                        $cart_item['data']->get_price() + floatval($cart_item[$key])
                    );
                }
            }
        }
    }

    /**
     * Display custom item data in the cart/checkout.
     */
    public function display_in_cart( $item_data, $cart_item ) {
        global $ccd_product_options_config;

        foreach ( $ccd_product_options_config as $option ) {
            $field  = $option['post_field'];
            $detail = $field . '_value';

            if ( ! isset($cart_item[$field]) ) {
                continue;
            }

            $main_val   = $cart_item[$field];
            $detail_val = isset($cart_item[$detail]) ? $cart_item[$detail] : 'None';

            if ( $option['type'] === 'select-and-text' ) {
                if ( $main_val !== 'None' && $detail_val !== 'None' ) {
                    $to_show = $detail_val;
                } else {
                    $to_show = 'None';
                }
            } else {
                $to_show = $main_val;
            }

            $item_data[] = [
                'key'   => $option['label'],
                'value' => wc_clean( $to_show ),
            ];
        }

        return $item_data;
    }

    /**
     * Add custom meta to order line items so it appears in the admin/emails.
     */
    public function add_meta_to_order( $item, $cart_item_key, $values, $order ) {
        global $ccd_product_options_config;

        foreach ( $ccd_product_options_config as $option ) {
            $field  = $option['post_field'];
            $detail = $field . '_value';

            if ( ! isset($values[$field]) ) {
                continue;
            }

            $main_val   = $values[$field];
            $detail_val = isset($values[$detail]) ? $values[$detail] : 'None';

            if ( $option['type'] === 'select-and-text' ) {
                if ( $main_val !== 'None' && $detail_val !== 'None' ) {
                    $to_show = $detail_val;
                } else {
                    $to_show = 'None';
                }
            } else {
                $to_show = $main_val;
            }

            $item->add_meta_data( $option['label'], $to_show, true );

            // If there's an upcharge
            $upcharge_key = $field . '_upcharge';
            if ( isset($values[$upcharge_key]) ) {
                $item->add_meta_data(
                    'Upcharge - ' . $option['label'],
                    wc_price($values[$upcharge_key]),
                    true
                );
            }
        }
    }
}
