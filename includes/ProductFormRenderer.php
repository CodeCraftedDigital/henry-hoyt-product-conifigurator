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
 * Dynamically renders the custom product form,
 * including Step 3 which loops over $ccd_product_options_config.
 */
class ProductFormRenderer {

    private $product;
    private $variationsFetcher;

    public function __construct( $product, ProductVariationsFetcher $variationsFetcher ) {
        $this->product           = $product;
        $this->variationsFetcher = $variationsFetcher;
    }

    public function render_empty_form() {
        if ( ! is_product() || ! $this->product->is_type( 'variable' ) ) {
            return;
        }

        // If user posted size_quantities, handle adding to cart
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['size_quantities']) ) {
            $this->add_to_cart( $_POST['size_quantities'] );
        }

        $product_id = $this->product->get_id();

        // Hide default WC variation form
        echo '<style>
            .variations_form.cart { display: none !important; }
        </style>';

        wc_print_notices();
        ?>

        <!-- START: Custom Form -->
        <form id="ccd-form" data-product-id="<?php echo esc_attr( $product_id ); ?>" method="POST">
            <!-- STEP 1: Choose Color -->
            <div>
                <label for="color-options" class="ccd-form__label">
                    <span class="ccd-step-number">1</span> Choose your color
                </label>
                <select name="color" id="color-options" class="ccd-select" required>
                    <option value="" selected disabled>Please Choose A Color</option>
                    <!-- JS will populate more options from the REST API -->
                </select>
            </div>

            <!-- STEP 2: Sizes & Quantities -->
            <div class="ccd-size__container" id="ccd-size__container">
                <div class="ccd-size__container--size-guide">
                    <div>
                        <label class="ccd-form__label">
                            <span class="ccd-step-number">2</span> Select sizes and quantities
                        </label>
                    </div>
                    <div>
                        <?php
                        // Optionally show a size guide link
                        $size_guide_file = get_field( 'product_size_guide', $product_id );
                        if ( $size_guide_file && ! empty($size_guide_file['url']) ) : ?>
                            <a href="<?php echo esc_url($size_guide_file['url']); ?>"
                               target="_blank"
                               class="ccd-size-guide-btn">
                                View Size Guide
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="ccd-size__block">
                    <!-- JS appends the dynamic size fields here -->
                </div>
            </div>

            <!-- STEP 3: Product Options -->
            <?php
            // If there's a master ACF toggle "enable_po_section" for Step 3, check it:
            if ( get_field( 'enable_po_section', $product_id ) ) :
                global $ccd_product_options_config;

                // Check if at least one ACF option is on:
                $has_any_option = false;
                foreach ( $ccd_product_options_config as $opt ) {
                    if ( get_field( $opt['acf_key'], $product_id ) ) {
                        $has_any_option = true;
                        break;
                    }
                }

                if ( $has_any_option ) : ?>
                    <div class="ccd-product-options">
                        <label class="ccd-form__label">
                            <span class="ccd-step-number">3</span> Product Options
                        </label>
                        <div class="ccd-product-options__container">
                            <?php
                            // Loop over each config item
                            foreach ( $ccd_product_options_config as $option ) :
                                // If ACF says it's off, skip
                                if ( ! get_field( $option['acf_key'], $product_id ) ) {
                                    continue;
                                }
                                // IDs and classes
                                $field_id   = 'ccd-' . $option['post_field'];
                                $img_div_id = 'ccd-addon-img-container-' . $option['post_field'];
                                ?>

                                <div class="ccd-addon-container">
                                    <div class="ccd-addon-item">
                                        <label class="ccd-addon-label" for="<?php echo esc_attr($field_id); ?>">
                                            <?php echo esc_html( $option['label'] ); ?>
                                            <?php if ( ! empty($option['upcharge']) ) : ?>
                                                <span class="ccd-add-on-upcharge">(+$<?php echo esc_html($option['upcharge']); ?>)</span>
                                            <?php endif; ?>
                                        </label>

                                        <?php
                                        switch ( $option['type'] ) {
                                            case 'select':
                                                // Simple select
                                                echo '<select
                                                    class="ccd-select ccd-dynamic-field"
                                                    name="' . esc_attr($option['post_field']) . '"
                                                    id="' . esc_attr($field_id) . '"
                                                    data-field-type="select"
                                                  >';

                                                foreach ( $option['choices'] as $choice ) {
                                                    echo '<option value="' . esc_attr($choice) . '">' . esc_html($choice) . '</option>';
                                                }
                                                echo '</select>';

                                                // If there's an image
                                                if ( ! empty( $option['img_url'] ) ) {
                                                    echo '<div id="' . esc_attr($img_div_id) . '" class="ccd-hidden">';
                                                    echo '  <img
                                                            class="ccd-addon-img"
                                                            src="' . esc_url($option['img_url']) . '"
                                                            alt="' . esc_attr($option['label']) . ' Image"
                                                        >';
                                                    echo '</div>';
                                                }
                                                break;

                                            case 'text':
                                                // A single text input
                                                $placeholder = isset($option['placeholder']) ? $option['placeholder'] : 'Enter value...';
                                                echo '<input
                                                    type="text"
                                                    name="' . esc_attr($option['post_field']) . '"
                                                    id="' . esc_attr($field_id) . '"
                                                    class="ccd-input ccd-dynamic-field"
                                                    data-field-type="text"
                                                    placeholder="' . esc_attr($placeholder) . '"
                                                  >';
                                                break;

                                            case 'select-and-text':
                                                // A select
                                                echo '<select
                                                    class="ccd-select ccd-dynamic-field"
                                                    name="' . esc_attr($option['post_field']) . '"
                                                    id="' . esc_attr($field_id) . '"
                                                    data-field-type="select-and-text"
                                                  >';

                                                foreach ( $option['choices'] as $val => $label_choice ) {
                                                    echo '<option value="' . esc_attr($val) . '">' . esc_html($label_choice) . '</option>';
                                                }
                                                echo '</select>';

                                                // A hidden container w/ text input + optional image
                                                echo '<div
                                                    id="' . esc_attr($field_id) . '-container"
                                                    class="ccd-hidden"
                                                  >';
                                                echo '  <input
                                                        type="text"
                                                        name="' . esc_attr($option['post_field'] . '_value') . '"
                                                        class="ccd-input"
                                                        placeholder="Enter detail..."
                                                     >';

                                                if ( ! empty($option['img_url']) ) {
                                                    echo '  <img
                                                            class="ccd-addon-img"
                                                            src="' . esc_url($option['img_url']) . '"
                                                            alt="' . esc_attr($option['label']) . ' Image"
                                                        >';
                                                }

                                                echo '</div>';
                                                break;
                                        } // end switch
                                        ?>
                                    </div> <!-- end .ccd-addon-item -->
                                </div> <!-- end .ccd-addon-container -->

                            <?php endforeach; ?>
                        </div> <!-- end .ccd-product-options__container -->
                    </div> <!-- end .ccd-product-options -->
                <?php endif; ?>
            <?php endif; ?>

            <button id="ccd-submit-btn" type="submit">Add To Cart</button>
        </form>
        <!-- END: Custom Form -->
        <?php
    }

    /**
     * Handle size_quantities => add variations to cart.
     */
    private function add_to_cart( $quantities ) {
        if ( empty($quantities) ) {
            wc_add_notice('Please select at least one variation.', 'error');
            return;
        }

        $product_id = $this->product->get_id();

        foreach ( $quantities as $variation_id => $qty ) {
            $q = intval($qty);
            if ( $q > 0 ) {
                $variation = wc_get_product($variation_id);
                if ( ! $variation || ! $variation->exists() || ! $variation->is_in_stock() ) {
                    wc_add_notice('One or more variations are invalid or out of stock.', 'error');
                    return;
                }
                // Add to cart
                WC()->cart->add_to_cart($variation_id, $q, 0, [], []);
            }
        }

        if ( WC()->cart->is_empty() ) {
            wc_add_notice('No items were added to your cart. Please try again.', 'error');
        } else {
            wc_add_notice('Products added to your cart.', 'success');
        }

        wp_redirect( get_permalink($product_id) );
        exit;
    }
}
