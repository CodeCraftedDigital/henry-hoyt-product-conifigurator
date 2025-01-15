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
        // Instantiate variations fetcher (for your color/size REST endpoint)
        $this->productVariationsFetcher = new ProductVariationsFetcherImplementation();

        // Hook up scripts, REST route, custom form
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'rest_api_init',      [ $this, 'register_rest_route' ] );
        add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'render_product_form' ], 10 );

        // Cart item data & order meta
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'handle_cart_item_data' ], 10, 2 );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'handle_upcharges' ] );
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_in_cart' ], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_meta_to_order' ], 10, 4 );
    }

    /**
     * Enqueue scripts (CSS, JS) and localize the REST route + nonce.
     */
    public function enqueue_scripts() {
        // (Optional) Enqueue your plugin’s stylesheet
        wp_enqueue_style(
            'ccd-product-options-style',
            ACFWOOADDONS_PLUGIN_URL . 'assets/css/style.css',
            [],
            '2.0'
        );

        // Enqueue your plugin’s JS
        wp_enqueue_script(
            'ccd-product-options-js',
            ACFWOOADDONS_PLUGIN_URL . 'assets/js/product-options.js',
            [],
            '2.0',
            true
        );

        // Localize script so JS can read the REST route & nonce
        wp_localize_script(
            'ccd-product-options-js',
            'ccdData',
            [
                // This typically becomes "https://yoursite.com/wp-json/code/v1/get-variations/"
                'restBase' => esc_url_raw( rest_url( 'code/v1/get-variations/' ) ),

                // Our nonce for extra security
                'nonce'    => wp_create_nonce( 'ccd_rest_nonce' ),
            ]
        );
    }

    /**
     * Register the custom REST route for color/size variations, using a permission callback.
     */
    public function register_rest_route() {
        register_rest_route(
            'code/v1',
            '/get-variations/(?P<product_id>\d+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this->productVariationsFetcher, 'get_product_variations' ],
                'permission_callback' => [ $this, 'check_nonce_permission' ],
            ]
        );
    }

    /**
     * Permission callback to verify the nonce from the request.
     * If invalid or missing, we return a 403 Forbidden.
     */
    public function check_nonce_permission( $request ) {
        // Our JS sends the nonce in the "X-WP-Nonce" header
        $nonce = $request->get_header( 'x-wp-nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'ccd_rest_nonce' ) ) {
            return new WP_Error(
                'invalid_nonce',
                'Invalid nonce or session expired.',
                [ 'status' => 403 ]
            );
        }
        return true; // nonce is valid
    }

    /**
     * Render the custom product form on variable products.
     */
    public function render_product_form() {
        global $product;
        if ( ! $product || ! $product->is_type('variable') ) {
            return;
        }

        $renderer = new ProductFormRenderer($product, $this->productVariationsFetcher);
        $renderer->render_empty_form();
    }

    /* ================================================================
       =========== CART, ORDER, and META LOGIC (unchanged) ============
       ================================================================ */

    /**
     * handle_cart_item_data
     */
    public function handle_cart_item_data( $cart_item_data, $product_id ) {
        global $ccd_product_options_config;

        $product = wc_get_product($product_id);
        if ( ! $product ) {
            return $cart_item_data;
        }

        foreach ( $ccd_product_options_config as $option ) {
            if ( ! get_field($option['acf_key'], $product_id) ) {
                continue;
            }

            $posted_value      = isset($_POST[ $option['post_field'] ])
                ? sanitize_text_field($_POST[ $option['post_field'] ]) : 'None';

            $posted_text_value = isset($_POST[ $option['post_field'] . '_value' ])
                ? sanitize_text_field($_POST[ $option['post_field'] . '_value' ]) : 'None';

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
     * handle_upcharges
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
     * display_in_cart
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
     * add_meta_to_order
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
