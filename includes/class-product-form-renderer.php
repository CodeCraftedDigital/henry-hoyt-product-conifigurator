<?php

class ProductFormRenderer {
    private $product;
    private $variationsFetcher;

    public function __construct($product, ProductVariationsFetcher $variationsFetcher) {
        $this->product = $product;
        $this->variationsFetcher = $variationsFetcher;
    }

    public function render_empty_form() {
        // Ensure we are on a product page and it's a variable product
        if (!is_product() || !$this->product->is_type('variable')) {
            return;
        }

        // Check if the form has been submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['size_quantities'])) {
            // Process the form data and print it
            echo '<pre>';
            print_r($_POST);  // Display the submitted data
            echo '</pre>';
        }

        // Get the product ID dynamically
        $product_id = $this->product->get_id();

        // Add custom CSS to hide the WooCommerce default variations form
        echo '<style>
            .variations_form.cart {
                display: none !important;
            }
        </style>';

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
                <label class="ccd-form__label">
                    <span class="ccd-step-number">2</span> Select sizes and quantities
                </label>
                <div id="ccd-size__block">
                    <!-- Sizes Will Append Here -->
                </div>
            </div>

            <div class="ccd-product-options__container">
                <label class="ccd-form__label">
                    <span class="ccd-step-number">3</span> Product Options
                </label>
            </div>
            <button id="ccd-submit-btn" type="submit">Add To Cart</button>
        </form>
        <?php
    }
}
?>
