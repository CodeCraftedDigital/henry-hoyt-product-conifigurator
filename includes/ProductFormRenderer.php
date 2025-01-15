<?php

namespace CodeCraftedDigital\ACFWooAddOns;

use WC;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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

        // If the user POSTed "size_quantities", handle them:
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['size_quantities'] ) ) {
            $this->add_to_cart( $_POST['size_quantities'] );
        }

        $product_id = $this->product->get_id();

        // Hide the default WC variation form
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
                    <!-- Populated by JS via REST -->
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
                        // If there's a size guide ACF field, show it
                        $size_guide_file = get_field( 'product_size_guide', $product_id );
                        if ( $size_guide_file && ! empty( $size_guide_file['url'] ) ) : ?>
                            <a href="<?php echo esc_url( $size_guide_file['url'] ); ?>"
                               target="_blank"
                               class="ccd-size-guide-btn">
                                View Size Guide
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="ccd-size__block">
                    <!-- The JS code will append size input fields here -->
                </div>
            </div>

            <!-- STEP 3: Product Options (fully dynamic) -->
            <?php
            // If there's a master ACF toggle for the entire product-options section, check it.
            if ( get_field( 'enable_po_section', $product_id ) ) :
                global $ccd_product_options_config;

                // Check if *any* config option is turned on
                $any_enabled = false;
                foreach ( $ccd_product_options_config as $opt ) {
                    if ( get_field( $opt['acf_key'], $product_id ) ) {
                        $any_enabled = true;
                        break;
                    }
                }

                if ( $any_enabled ) : ?>
                    <div class="ccd-product-options">
                        <label class="ccd-form__label">
                            <span class="ccd-step-number">3</span> Product Options
                        </label>
                        <div class="ccd-product-options__container">
                            <?php
                            foreach ( $ccd_product_options_config as $option ) :
                                // Skip if the ACF field is off
                                if ( ! get_field( $option['acf_key'], $product_id ) ) {
                                    continue;
                                }

                                // Create IDs / data attributes for the JS
                                $field_id   = 'ccd-' . $option['post_field'];
                                $img_div_id = 'ccd-addon-img-container-' . $option['post_field'];

                                ?>
                                <div class="ccd-addon-container">
                                    <div class="ccd-addon-item">
                                        <label
                                                class="ccd-addon-label"
                                                for="<?php echo esc_attr( $field_id ); ?>"
                                        >
                                            <?php echo esc_html( $option['label'] ); ?>
                                            <?php if ( ! empty($option['upcharge']) ) : ?>
                                                <span class="ccd-add-on-upcharge">
                                                    (+$<?php echo esc_html( $option['upcharge'] ); ?>)
                                                </span>
                                            <?php endif; ?>
                                        </label>

                                        <?php
                                        // Render input based on "type" (select, text, select-and-text)
                                        switch ( $option['type'] ) {
                                            case 'select':
                                                ?>
                                                <select
                                                        id="<?php echo esc_attr( $field_id ); ?>"
                                                        name="<?php echo esc_attr( $option['post_field'] ); ?>"
                                                        class="ccd-select ccd-dynamic-field"
                                                        data-field-type="select"
                                                >
                                                    <?php
                                                    if ( ! empty($option['choices']) && is_array($option['choices']) ) {
                                                        foreach ( $option['choices'] as $choice ) {
                                                            echo '<option value="' . esc_attr($choice) . '">' . esc_html($choice) . '</option>';
                                                        }
                                                    } else {
                                                        // Default fallback
                                                        echo '<option value="Blank">Blank</option>';
                                                        echo '<option value="HFH Logo">HFH Logo</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <?php
                                                // If there's an image
                                                if ( ! empty($option['img_url']) ) {
                                                    ?>
                                                    <div
                                                            id="<?php echo esc_attr($img_div_id); ?>"
                                                            class="ccd-hidden"
                                                    >
                                                        <img
                                                                class="ccd-addon-img"
                                                                src="<?php echo esc_url( $option['img_url'] ); ?>"
                                                                alt="<?php echo esc_attr( $option['label'] ); ?>"
                                                        >
                                                    </div>
                                                    <?php
                                                }
                                                break;

                                            case 'text':
                                                $placeholder = isset($option['placeholder'])
                                                    ? $option['placeholder']
                                                    : 'Enter...';
                                                ?>
                                                <input
                                                        type="text"
                                                        id="<?php echo esc_attr( $field_id ); ?>"
                                                        name="<?php echo esc_attr( $option['post_field'] ); ?>"
                                                        class="ccd-input ccd-dynamic-field"
                                                        data-field-type="text"
                                                        placeholder="<?php echo esc_attr($placeholder); ?>"
                                                >
                                                <?php
                                                break;

                                            case 'select-and-text':
                                                ?>
                                                <select
                                                        id="<?php echo esc_attr( $field_id ); ?>"
                                                        name="<?php echo esc_attr( $option['post_field'] ); ?>"
                                                        class="ccd-select ccd-dynamic-field"
                                                        data-field-type="select-and-text"
                                                >
                                                    <?php
                                                    if ( ! empty($option['choices']) && is_array($option['choices']) ) {
                                                        foreach ( $option['choices'] as $val => $label_text ) {
                                                            echo '<option value="' . esc_attr($val) . '">' . esc_html($label_text) . '</option>';
                                                        }
                                                    } else {
                                                        // Fallback
                                                        echo '<option value="none">No Option</option>';
                                                        echo '<option value="yes">Add Option</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <div
                                                        id="<?php echo esc_attr($field_id . '-container'); ?>"
                                                        class="ccd-hidden"
                                                >
                                                    <input
                                                            type="text"
                                                            name="<?php echo esc_attr( $option['post_field'] . '_value' ); ?>"
                                                            class="ccd-input"
                                                            placeholder="Enter detail..."
                                                    >
                                                    <?php
                                                    if ( ! empty($option['img_url']) ) {
                                                        ?>
                                                        <img
                                                                class="ccd-addon-img"
                                                                src="<?php echo esc_url($option['img_url']); ?>"
                                                                alt="<?php echo esc_attr($option['label']); ?>"
                                                        >
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <?php
                                                break;
                                        } // end switch
                                        ?>
                                    </div> <!-- end .ccd-addon-item -->
                                </div> <!-- end .ccd-addon-container -->

                            <?php
                            endforeach; // end foreach $ccd_product_options_config
                            ?>
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
     * If user posted sizes, add them to cart.
     * The main plugin logic handles the add-ons.
     */
    private function add_to_cart( $quantities ) {
        if ( empty($quantities) ) {
            wc_add_notice( 'Please select at least one variation.', 'error' );
            return;
        }

        $product_id = $this->product->get_id();

        foreach ( $quantities as $variation_id => $qty ) {
            $q = intval($qty);
            if ( $q > 0 ) {
                $variation = wc_get_product($variation_id);
                if ( ! $variation || ! $variation->exists() || ! $variation->is_in_stock() ) {
                    wc_add_notice( 'One or more variations are invalid or out of stock.', 'error' );
                    return;
                }
                WC()->cart->add_to_cart( $variation_id, $q, 0, [], [] );
            }
        }

        if ( WC()->cart->is_empty() ) {
            wc_add_notice( 'No items were added to your cart. Please try again.', 'error' );
        } else {
            wc_add_notice( 'Products added to your cart.', 'success' );
        }

        wp_redirect( get_permalink($product_id) );
        exit;
    }
}
