<?php

namespace CodeCraftedDigital\ACFWooAddOns;

use WC;
use WP_Error;
use CodeCraftedDigital\ACFWooAddOns\ProductOptionsEnqueuer;
use CodeCraftedDigital\ACFWooAddOns\ProductVariationsFetcherImplementation;
use CodeCraftedDigital\ACFWooAddOns\ProductFormRenderer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MainPlugin
 *
 * Serves as the primary controller for the ACF Woo Add-Ons plugin.
 */
class MainPlugin {

    private $productOptionsEnqueuer;
    private $productVariationsFetcher;
    private $productFormRenderer;

    /**
     * MainPlugin constructor.
     */
    public function __construct() {
        // Instantiate dependencies
        $this->productOptionsEnqueuer   = new ProductOptionsEnqueuer();
        $this->productVariationsFetcher = new ProductVariationsFetcherImplementation();

        // Hook actions and filters
        add_action( 'wp_enqueue_scripts', [ $this->productOptionsEnqueuer, 'enqueue_scripts' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );
        add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'render_product_form' ], 10 );

        // Handle cart-item data, upcharges, and order meta
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'handle_cart_item_data' ], 10, 2 );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'handle_upcharges_in_cart' ] );
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_custom_data_in_cart' ], 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_meta_to_order_items' ], 10, 4 );
    }

    /**
     * Register custom REST API route for retrieving product variations.
     */
    public function register_rest_route() {
        register_rest_route(
            'code/v1',
            '/get-variations/(?P<product_id>\d+)',
            [
                'methods'  => 'GET',
                'callback' => [ $this->productVariationsFetcher, 'get_product_variations' ],
            ]
        );
    }

    /**
     * Render the custom product form on variable products.
     */
    public function render_product_form() {
        global $product;

        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return;
        }

        $this->productFormRenderer = new ProductFormRenderer( $product, $this->productVariationsFetcher );
        $this->productFormRenderer->render_empty_form();
    }

    /* ------------------------------------------------------------------
       CART ITEM DATA HOOKS
    ------------------------------------------------------------------ */

    /**
     * Store form selections in the cart item. We also normalize any "blank" or empty
     * values to "None," so it's consistent in the cart display.
     */
    public function handle_cart_item_data( $cart_item_data, $product_id ) {
        global $ccd_product_options_config;

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return $cart_item_data;
        }

        // Loop each configured option
        foreach ( $ccd_product_options_config as $option ) {
            // Skip if ACF field is off
            if ( ! get_field( $option['acf_key'], $product_id ) ) {
                continue;
            }

            // Grab the posted value
            $posted_value = isset( $_POST[ $option['post_field'] ] )
                ? sanitize_text_field( $_POST[ $option['post_field'] ] )
                : 'None';

            // Right Chest – Screen Print or Embroidery might use "Blank": convert that to "None"
            if ( strtolower( $posted_value ) === 'blank' ) {
                $posted_value = 'None';
            }
            // If empty or user selected "none"
            if ( empty( $posted_value ) || $posted_value === 'none' ) {
                $posted_value = 'None';
            }

            // Put it in cart data
            $cart_item_data[ $option['post_field'] ] = $posted_value;

            // If type = text and user typed something, handle upcharge
            if (
                $option['type'] === 'text' &&
                $posted_value !== 'None' &&
                $option['upcharge'] > 0
            ) {
                $cart_item_data[ $option['post_field'] . '_upcharge' ] = floatval( $option['upcharge'] );
            }

            // For "select-and-text" (e.g. Department Name)
            if ( $option['type'] === 'select-and-text' ) {
                $posted_text_value = isset( $_POST[ $option['post_field'] . '_value' ] )
                    ? sanitize_text_field( $_POST[ $option['post_field'] . '_value' ] )
                    : 'None';

                // If user picks "yes" AND typed something => store that text; else "None"
                if ( $posted_value === 'yes' && ! empty( $posted_text_value ) ) {
                    $cart_item_data[ $option['post_field'] . '_value' ] = $posted_text_value;

                    if ( $option['upcharge'] > 0 ) {
                        $cart_item_data[ $option['post_field'] . '_upcharge' ] = floatval( $option['upcharge'] );
                    }
                } else {
                    $cart_item_data[ $option['post_field'] . '_value' ] = 'None';
                }
            }
        }

        return $cart_item_data;
    }

    /**
     * Apply upcharges to item price in the cart (before totals).
     */
    public function handle_upcharges_in_cart( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        global $ccd_product_options_config;

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            foreach ( $ccd_product_options_config as $option ) {
                $upcharge_key = $option['post_field'] . '_upcharge';
                if ( isset( $cart_item[ $upcharge_key ] ) && $cart_item[ $upcharge_key ] > 0 ) {
                    $cart_item['data']->set_price(
                        $cart_item['data']->get_price() + floatval( $cart_item[ $upcharge_key ] )
                    );
                }
            }
        }
    }

    /**
     * Display the custom item data in the cart/checkout. Now we ALWAYS show a line
     * for each enabled option. If it's "None," we display "None."
     */
    public function display_custom_data_in_cart( $item_data, $cart_item ) {
        global $ccd_product_options_config;

        foreach ( $ccd_product_options_config as $option ) {
            $field_key   = $option['post_field'];
            $detail_key  = $field_key . '_value';
            $label       = $option['label'];

            // Skip if ACF field wasn't enabled for the product (or not set at all)
            if ( ! isset( $cart_item[ $field_key ] ) ) {
                continue;
            }

            $main_value   = $cart_item[ $field_key ];
            $detail_value = isset( $cart_item[ $detail_key ] ) ? $cart_item[ $detail_key ] : 'None';

            // Determine what final value to display
            $display_value = 'None';

            // If it's select-and-text (like Department Name)
            if ( $option['type'] === 'select-and-text' ) {
                // If the user selected "yes" AND typed something => display that text
                // Otherwise "None"
                if ( $main_value === 'yes' && $detail_value !== 'None' ) {
                    $display_value = $detail_value;
                }
            }
            // If it's just a simple text or select
            else {
                // If not "None," show the main value
                if ( $main_value !== 'None' ) {
                    $display_value = $main_value;
                }
            }

            // Always add a line, showing either "None" or the actual value
            $item_data[] = [
                'key'   => $label,
                'value' => wc_clean( $display_value ),
            ];
        }

        return $item_data;
    }

    /**
     * Add the custom meta data to order line items so it appears in the admin and emails.
     * Same logic as cart display: always show each field as "None" or the typed value.
     */
    public function add_meta_to_order_items( $item, $cart_item_key, $values, $order ) {
        global $ccd_product_options_config;

        foreach ( $ccd_product_options_config as $option ) {
            $field_key   = $option['post_field'];
            $detail_key  = $field_key . '_value';
            $label       = $option['label'];

            if ( ! isset( $values[ $field_key ] ) ) {
                continue;
            }

            $main_value   = $values[ $field_key ];
            $detail_value = isset( $values[ $detail_key ] ) ? $values[ $detail_key ] : 'None';

            $display_value = 'None';

            if ( $option['type'] === 'select-and-text' ) {
                if ( $main_value === 'yes' && $detail_value !== 'None' ) {
                    $display_value = $detail_value;
                }
            } else {
                if ( $main_value !== 'None' ) {
                    $display_value = $main_value;
                }
            }

            // Add meta data so it shows in the order
            $item->add_meta_data( $label, $display_value, true );

            // Upcharge breakdown
            $upcharge_key = $field_key . '_upcharge';
            if ( isset( $values[ $upcharge_key ] ) ) {
                $item->add_meta_data(
                    'Upcharge - ' . $label,
                    wc_price( $values[ $upcharge_key ] ),
                    true
                );
            }
        }
    }
}
