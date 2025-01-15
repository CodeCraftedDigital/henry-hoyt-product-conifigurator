<?php

namespace CodeCraftedDigital\ACFWooAddOns;

use WC;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ProductFormRenderer
 *
 * Renders the custom product form for variable products and handles "Add to Cart".
 */
class ProductFormRenderer {

    private $product;
    private $variationsFetcher;

    /**
     * Constructor
     *
     * @param \WC_Product $product The product object (should be a variable product).
     * @param ProductVariationsFetcher $variationsFetcher Instance to fetch grouped variations.
     */
    public function __construct( $product, ProductVariationsFetcher $variationsFetcher ) {
        $this->product           = $product;
        $this->variationsFetcher = $variationsFetcher;
    }

    /**
     * Renders the HTML form that lets users pick color, size, quantity,
     * and any ACF-based add-ons (if enabled).
     */
    public function render_empty_form() {
        // Make sure we're on a product page and the product is variable.
        if ( ! is_product() || ! $this->product->is_type( 'variable' ) ) {
            return;
        }

        // If the user is POSTing size_quantities, handle add-to-cart here,
        // or you can let it go through handle_cart_item_data() in MainPlugin.
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['size_quantities'] ) ) {
            $this->add_to_cart( $_POST['size_quantities'] );
        }

        $product_id = $this->product->get_id();

        // Hide the default WooCommerce variation form:
        echo '<style>
                .variations_form.cart {
                    display: none !important;
                }
              </style>';

        // Print any notices (like "Added to cart" or validation errors).
        wc_print_notices();
        ?>

        <!-- START: Custom Form -->
        <form id="ccd-form" data-product-id="<?php echo esc_attr( $product_id ); ?>" method="POST">
            <!-- STEP 1: Choose Color -->
            <div>
                <label for="color" class="ccd-form__label">
                    <span class="ccd-step-number">1</span> Choose your color
                </label>
                <select name="color" id="color-options" class="ccd-select" required>
                    <option value="" selected disabled>Please Choose A Color</option>
                    <!-- Options will be appended by JS via the REST API data -->
                </select>
            </div>

            <!-- STEP 2: Sizes & Quantities -->
            <div class="ccd-size__container">
                <div class="ccd-size__container--size-guide">
                    <div>
                        <label class="ccd-form__label">
                            <span class="ccd-step-number">2</span> Select sizes and quantities
                        </label>
                    </div>
                    <div>
                        <?php
                        // If you store a size guide via ACF or custom field, we can show a link here:
                        $size_guide_file = get_field( 'product_size_guide', $this->product->get_id() );
                        if ( $size_guide_file && ! empty( $size_guide_file['url'] ) ) : ?>
                            <a href="<?php echo esc_url( $size_guide_file['url'] ); ?>" target="_blank" class="ccd-size-guide-btn">
                                View Size Guide
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="ccd-size__block">
                    <!-- The JS code appends size input fields here dynamically -->
                </div>
            </div>

            <!-- STEP 3: Product Options -->
            <?php
            global $ccd_product_options_config;
            // Check if there's anything to show at all:
            $has_any_acf_option_enabled = false;
            if ( ! empty( $ccd_product_options_config ) ) {
                foreach ( $ccd_product_options_config as $option ) {
                    if ( get_field( $option['acf_key'], $product_id ) ) {
                        $has_any_acf_option_enabled = true;
                        break;
                    }
                }
            }

            if ( $has_any_acf_option_enabled ) : ?>
                <div class="ccd-product-options">
                    <label class="ccd-form__label">
                        <span class="ccd-step-number">3</span> Product Options
                    </label>

                    <div class="ccd-product-options__container">
                        <?php
                        // Loop over each ACF-based option
                        foreach ( $ccd_product_options_config as $option ) :
                            // If ACF toggle is off, skip
                            if ( ! get_field( $option['acf_key'], $product_id ) ) {
                                continue;
                            }
                            ?>
                            <div class="ccd-product-option">
                                <!-- Option Label -->
                                <label class="ccd-addon-label">
                                    <?php echo esc_html( $option['label'] ); ?>
                                    <?php if ( $option['upcharge'] > 0 ) : ?>
                                        <span class="ccd-add-on-upcharge">
                                            (+$<?php echo esc_html( $option['upcharge'] ); ?>)
                                        </span>
                                    <?php endif; ?>
                                </label>

                                <?php
                                // Render input(s) based on $option['type']
                                switch ( $option['type'] ) {
                                    case 'select':
                                        ?>
                                        <select
                                                class="ccd-select"
                                                name="<?php echo esc_attr( $option['post_field'] ); ?>">
                                            <!-- Example options: -->
                                            <option value="Blank">Blank</option>
                                            <option value="HFH Logo">HFH Logo</option>
                                        </select>
                                        <!-- Optionally an image container toggled by JS -->
                                        <div
                                                id="ccd-addon-img-container-<?php echo esc_attr( $option['post_field'] ); ?>"
                                                class="ccd-hidden"
                                        >
                                            <img
                                                    class="ccd-addon-img"
                                                    src="<?php echo esc_url( ACFWOOADDONS_PLUGIN_URL . 'assets/images/right-chest-logo.jpg' ); ?>"
                                                    alt="Addon Image"
                                            >
                                        </div>
                                        <?php
                                        break;

                                    case 'text':
                                        ?>
                                        <input
                                                type="text"
                                                name="<?php echo esc_attr( $option['post_field'] ); ?>"
                                                class="ccd-input"
                                                placeholder="Enter your text..."
                                        >
                                        <?php
                                        break;

                                    case 'select-and-text':
                                        ?>
                                        <select
                                                class="ccd-select"
                                                name="<?php echo esc_attr( $option['post_field'] ); ?>"
                                                id="ccd-<?php echo esc_attr( $option['post_field'] ); ?>"
                                        >
                                            <option value="none">No <?php echo esc_html( $option['label'] ); ?></option>
                                            <option value="yes">Add <?php echo esc_html( $option['label'] ); ?></option>
                                        </select>
                                        <div
                                                id="ccd-<?php echo esc_attr( $option['post_field'] ); ?>-container"
                                                class="ccd-hidden"
                                        >
                                            <input
                                                    type="text"
                                                    name="<?php echo esc_attr( $option['post_field'] ); ?>_value"
                                                    placeholder="Enter detail..."
                                                    class="ccd-input"
                                            >
                                            <img
                                                    class="ccd-addon-img"
                                                    src="<?php echo esc_url( ACFWOOADDONS_PLUGIN_URL . 'assets/images/Department-name-back.jpg' ); ?>"
                                                    alt="Department Name Back Image"
                                            >
                                        </div>
                                        <?php
                                        break;
                                }
                                ?>
                            </div> <!-- end .ccd-product-option -->
                        <?php endforeach; ?>
                    </div> <!-- end .ccd-product-options__container -->
                </div> <!-- end .ccd-product-options -->
            <?php endif; ?>

            <button id="ccd-submit-btn" type="submit">Add To Cart</button>
        </form>
        <!-- END: Custom Form -->
        <?php
    }

    /**
     * Processes the submitted sizes/quantities and adds variations to cart.
     * The rest of the add-ons are handled by MainPlugin->handle_cart_item_data().
     *
     * @param array $quantities variation_id => quantity
     */
    private function add_to_cart( $quantities ) {
        if ( empty( $quantities ) ) {
            wc_add_notice( __( 'Please select at least one variation.', 'ccd-product-options' ), 'error' );
            return;
        }

        $product_id = $this->product->get_id();

        foreach ( $quantities as $variation_id => $quantity ) {
            $qty = intval( $quantity );
            if ( $qty > 0 ) {
                $variation = wc_get_product( $variation_id );
                if ( ! $variation || ! $variation->exists() || ! $variation->is_in_stock() ) {
                    wc_add_notice( __( 'One or more variations are invalid or out of stock.', 'ccd-product-options' ), 'error' );
                    return;
                }

                // You could attach partial cart_item_data here if needed,
                // but MainPlugin->handle_cart_item_data() does it globally.
                WC()->cart->add_to_cart( $variation_id, $qty, 0, [], [] );
            }
        }

        if ( WC()->cart->is_empty() ) {
            wc_add_notice( __( 'No items were added to your cart. Please try again.', 'ccd-product-options' ), 'error' );
        } else {
            wc_add_notice( __( 'Products added to your cart.', 'ccd-product-options' ), 'success' );
        }

        wp_redirect( get_permalink( $product_id ) );
        exit;
    }
}
