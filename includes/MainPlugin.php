<?php

namespace CodeCraftedDigital\ACFWooAddOns;

use WC;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MainPlugin {

    public function __construct() {
        // Variation fetcher: for your color/size REST endpoint
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
     * Enqueue scripts (CSS, JS).
     */
    public function enqueue_scripts() {
        // You can rename or move these as needed:
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
    }

    /**
     * Register REST route for color/size variations.
     */
    public function register_rest_route() {
        register_rest_route('code/v1', '/get-variations/(?P<product_id>\d+)', [
            'methods'  => 'GET',
            'callback' => [ $this->productVariationsFetcher, 'get_product_variations' ],
        ]);
    }

    /**
     * Render the custom form on variable products.
     */
    public function render_product_form() {
        global $product;

        if ( ! $product || ! $product->is_type('variable') ) {
            return;
        }

        // Render the dynamic form
        $renderer = new ProductFormRenderer($product, $this->productVariationsFetcher);
        $renderer->render_empty_form();
    }

    /* ----------------------------------------------------------------
       CART/ORDER LOGIC
    ---------------------------------------------------------------- */

    /**
     * Store data from the form in the cart item.
     */
    public function handle_cart_item_data( $cart_item_data, $product_id ) {
        global $ccd_product_options_config;

        $product = wc_get_product($product_id);
        if ( ! $product ) {
            return $cart_item_data;
        }

        foreach ( $ccd_product_options_config as $option ) {
            // Skip if ACF says it's disabled
            if ( ! get_field($option['acf_key'], $product_id ) ) {
                continue;
            }

            $posted_value      = isset($_POST[ $option['post_field'] ]) ? sanitize_text_field($_POST[ $option['post_field'] ]) : 'None';
            $posted_text_value = isset($_POST[ $option['post_field'] . '_value' ]) ? sanitize_text_field($_POST[ $option['post_field'] . '_value' ]) : 'None';

            // If user left blank or selected "Blank," treat it as "None"
            if ( strtolower($posted_value) === 'blank' || $posted_value === '' ) {
                $posted_value = 'None';
            }

            // If "select-and-text"
            if ( $option['type'] === 'select-and-text' ) {
                // If user picked "yes"/"Left Chest" and typed something => store text
                // else "None"
                if ( $posted_value !== 'none' && $posted_value !== 'None' && $posted_text_value !== 'None' && $posted_text_value !== '' ) {
                    $cart_item_data[ $option['post_field'] . '_value' ] = $posted_text_value;
                } else {
                    $cart_item_data[ $option['post_field'] . '_value' ] = 'None';
                }
            }

            // Save main field
            $cart_item_data[ $option['post_field'] ] = $posted_value;

            // Upcharge if not "None"
            if ( isset($option['upcharge']) && $option['upcharge'] > 0 ) {
                // For a text field: if they typed something, we apply upcharge
                // For select: if user picks something other than "None," etc.
                // Adjust logic as you prefer
                if (
                    ($option['type'] === 'text' && $posted_value !== '' && $posted_value !== 'None') ||
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
     * Apply upcharges to the item price.
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
     * Display the data in the cart/checkout page.
     */
    public function display_in_cart( $item_data, $cart_item ) {
        global $ccd_product_options_config;

        foreach ( $ccd_product_options_config as $option ) {
            $field  = $option['post_field'];
            $detail = $field . '_value';

            // Skip if the product doesn't have this field at all
            if ( ! isset($cart_item[$field]) ) {
                continue;
            }

            $main_val   = $cart_item[$field];
            $detail_val = isset($cart_item[$detail]) ? $cart_item[$detail] : 'None';

            // Always show a line, even if it's "None"
            // For "select-and-text," if user typed something, we display that
            if ( $option['type'] === 'select-and-text' ) {
                // If main is 'none' => "None," else detail
                if ( $main_val !== 'None' && $detail_val !== 'None' ) {
                    $to_show = $detail_val;
                } else {
                    $to_show = 'None';
                }
            } else {
                // For select or text, just show main_val
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
     * Store the data in the order line items (admin, emails, etc.).
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

            // Same logic as in display_in_cart
            if ( $option['type'] === 'select-and-text' ) {
                if ( $main_val !== 'None' && $detail_val !== 'None' ) {
                    $to_show = $detail_val;
                } else {
                    $to_show = 'None';
                }
            } else {
                $to_show = $main_val;
            }

            // Add meta
            $item->add_meta_data(
                $option['label'],
                $to_show,
                true
            );

            // Upcharge breakdown if needed
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
