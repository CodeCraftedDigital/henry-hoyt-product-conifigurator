<?php

class ProductFormRenderer {
    private $product;
    private $variationsFetcher;

    public function __construct($product, ProductVariationsFetcher $variationsFetcher) {
        $this->product = $product;
        $this->variationsFetcher = $variationsFetcher;
    }

    // Render the empty form for variable products
    public function render_empty_form() {
        // Ensure we are on a product page and it's a variable product
        if (!is_product() || !$this->product->is_type('variable')) {
            return;
        }

        // Check if the form has been submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['size_quantities'])) {
            // Process the form data and add to cart
            $this->add_to_cart($_POST['size_quantities']);
        }

        // Get the product ID dynamically
        $product_id = $this->product->get_id();

        // Add custom CSS to hide the WooCommerce default variations form
        echo '<style>
            .variations_form.cart {
                display: none !important;
            }
        </style>';

        // Display WooCommerce Notices (Make sure this is in the correct position in your theme)
        wc_print_notices();

        ?>
        <form id="ccd-form" data-product-id="<?php echo esc_attr($product_id); ?>" method="POST">
            <div>
                <label for="color" class="ccd-form__label">
                    <span class="ccd-step-number">1</span> Choose your color
                </label>
                <select id="color-options" class="ccd-select" required>
                    <option value="Please Choose A Color" selected>Please Choose A Color</option>
                    <!-- Color options will be appended here -->
                </select>
            </div>

            <div class="ccd-size__container">
                <div class="ccd-size__container--size-guide">
                    <div>
                        <label class="ccd-form__label">
                            <span class="ccd-step-number">2</span> Select sizes and quantities
                        </label>
                    </div>
                    <div>
                        <?php
                        // Get the size guide file (assuming it's a file upload field)
                        $size_guide_file = get_field('product_size_guide', $this->product->get_id());

                        // Check if the field has a valid file (array and 'url' key exist)
                        if ($size_guide_file && !empty($size_guide_file['url'])): ?>
                            <a href="<?php echo esc_url($size_guide_file['url']); ?>" target="_blank" class="ccd-size-guide-btn">
                                View Size Guide
                            </a>
                        <?php endif; ?>

                    </div>

                </div>

                <div id="ccd-size__block">
                    <!-- Sizes Will Append Here -->
                </div>
            </div>

            <div class="ccd-product-options__container">
                <label class="ccd-form__label">
                    <span class="ccd-step-number">3</span> Product Options
                </label>

            <!--      Product Add Ons          -->
                <?php if (get_field('right_chest_logo_sp')): ?>
                    <div class="ccd-addon-container">
                       <div class="ccd-addon-item">
                           <label class="ccd-addon-label" for="">Right Chest - Screen Print</label>
                           <select class="ccd-select" name="" id="ccd-right-chest-logo-sp" required>
                               <option value="Blank">Blank</option>
                               <option value="HFH Logo">HFH Logo</option>
                           </select>
                           <div id="ccd-addon-img-container-sp" class="ccd-hidden">
                               <img class="ccd-addon-img" src="<?php echo CCD_PLUGIN_URL . 'images/right-chest-logo.jpg'; ?>" alt="HFH Right Chest Logo">
                           </div>
                       </div>
                    </div>
                <?php endif; ?>


                <?php if (get_field('right_chest_logo_em')): ?>
                    <div class="ccd-addon-container">
                        <div class="ccd-addon-item">
                            <label class="ccd-addon-label" for="">Right Chest - Embroidery</label>
                            <select class="ccd-select" name="" id="ccd-right-chest-logo-em" required>
                                <option value="Blank">Blank</option>
                                <option value="HFH Logo">HFH Logo</option>
                            </select>
                            <div id="ccd-addon-img-container-em" class="ccd-hidden">
                                <img class="ccd-addon-img" src="<?php echo CCD_PLUGIN_URL . 'images/right-chest-logo.jpg'; ?>" alt="HFH Right Chest Logo">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!--      Add Personalized Name Left Chest           -->
                <?php if (get_field('left_chest_pn')): ?>
                    <div class="ccd-addon-container">
                        <div class="ccd-addon-item">
                            <label class="ccd-addon-label" for="">Add Personalized Name Left Chest <span class="ccd-add-on-upcharge">(+8.00)</span> </label>
                            <input name="left-chase-personal-name" class="ccd-input" type="text" placeholder="Enter Your Name...">
                        </div>
                    </div>
                <?php endif; ?>



            </div>

            <button id="ccd-submit-btn" type="submit">Add To Cart</button>
        </form>
        <?php
    }

    // Function to add variations to the WooCommerce cart
    private function add_to_cart($quantities) {
        // Check if the quantities array is not empty
        if (empty($quantities)) {
            wc_add_notice(__('Please select at least one variation.', 'ccd-product-options'), 'error');
            wp_redirect(get_permalink($this->product->get_id()));
            exit;
        }

        // Loop through each variation and quantity
        foreach ($quantities as $variation_id => $quantity) {
            // Ensure the variation ID is valid and the quantity is greater than 0
            if ($quantity > 0) {
                // Check if the variation exists
                $variation = wc_get_product($variation_id);
                if ($variation && $variation->exists() && $variation->is_in_stock()) {
                    // Add each valid variation to the cart
                    WC()->cart->add_to_cart($variation_id, $quantity);
                } else {
                    wc_add_notice(__('One or more variations are invalid or out of stock.', 'ccd-product-options'), 'error');
                    wp_redirect(get_permalink($this->product->get_id()));
                    exit;
                }
            }
        }

        // If no variations were added, show an error
        if (WC()->cart->is_empty()) {
            wc_add_notice(__('No items were added to your cart. Please try again.', 'ccd-product-options'), 'error');
            wp_redirect(get_permalink($this->product->get_id()));
            exit;
        } else {
            // Add success message
            wc_add_notice(__('Products added to your cart.', 'ccd-product-options'), 'success');
            wp_redirect(get_permalink($this->product->get_id())); // Redirect to the same page after adding
            exit;
        }
    }

    // Helper function to dump the current cart for debugging purposes
    public function dump_cart() {
        echo '<pre>';
        print_r(WC()->cart->get_cart());  // Dump the cart contents
        echo '</pre>';
    }
}

?>
