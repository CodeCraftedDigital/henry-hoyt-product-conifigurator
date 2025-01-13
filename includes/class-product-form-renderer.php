<?php
/**
 * Example: ProductFormRenderer class with a custom form (unchanged from your original),
 * plus the needed hooks to store additional fields in the cart and order,
 * WITHOUT storing "color" or "size" as custom meta.
 */

class ProductFormRenderer {
    private $product;
    private $variationsFetcher;

    public function __construct($product, ProductVariationsFetcher $variationsFetcher) {
        $this->product = $product;
        $this->variationsFetcher = $variationsFetcher;
    }

    /**
     * Renders the exact same HTML form you provided.
     */
    public function render_empty_form() {
        if (!is_product() || !$this->product->is_type('variable')) {
            return;
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['size_quantities'])) {
            $this->add_to_cart($_POST['size_quantities']);
        }

        $product_id = $this->product->get_id();

        // Hide default WooCommerce variation form
        echo '<style>
            .variations_form.cart {
                display: none !important;
            }
        </style>';

        // WooCommerce notices
        wc_print_notices();
        ?>

        <!-- BEGIN: The EXACT form you shared -->
        <form id="ccd-form" data-product-id="<?php echo esc_attr($product_id); ?>" method="POST">
            <div>
                <label for="color" class="ccd-form__label">
                    <span class="ccd-step-number">1</span> Choose your color
                </label>
                <select name="color" id="color-options" class="ccd-select" required>
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

            <?php if (get_field('enable_po_section')): ?>
                <div class="ccd-product-options__container">
                    <label class="ccd-form__label">
                        <span class="ccd-step-number">3</span> Product Options
                    </label>
                    <!-- Product Add Ons -->
                    <?php if (get_field('right_chest_logo_sp')): ?>
                        <div class="ccd-addon-container">
                            <div class="ccd-addon-item">
                                <label class="ccd-addon-label" for="">Right Chest - Screen Print</label>
                                <select class="ccd-select" name="right_chest_screen_print" id="ccd-right-chest-logo-sp" required>
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
                                <select class="ccd-select" name="right_chest_embroidery" id="ccd-right-chest-logo-em" required>
                                    <option value="Blank">Blank</option>
                                    <option value="HFH Logo">HFH Logo</option>
                                </select>
                                <div id="ccd-addon-img-container-em" class="ccd-hidden">
                                    <img class="ccd-addon-img" src="<?php echo CCD_PLUGIN_URL . 'images/right-chest-logo.jpg'; ?>" alt="HFH Right Chest Logo">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (get_field('left_chest_pn')): ?>
                        <div class="ccd-addon-container">
                            <div class="ccd-addon-item">
                                <label class="ccd-addon-label" for="">
                                    Add Personalized Name Left Chest
                                    <span class="ccd-add-on-upcharge">(+8.00)</span>
                                </label>
                                <input name="personalized_name_left_chest" class="ccd-input" type="text" placeholder="Enter Your Name...">
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (get_field('dp_name_left_chest')): ?>
                        <div class="ccd-addon-container">
                            <div class="ccd-addon-item">
                                <label class="ccd-addon-label" for="">
                                    Add Department Name Left Chest
                                    <span class="ccd-add-on-upcharge">(+4.00)</span>
                                </label>
                                <select class="ccd-select" name="department_name_left_chest" id="department-name-left-chest" required>
                                    <option value="none">No Department Name</option>
                                    <option value="Left Chest">Left Chest</option>
                                </select>
                                <div id="ccd-addon-department-name-container" class="ccd-hidden">
                                    <input
                                            id="ccd-department-name-left-chest"
                                            name="department_name_left_chest_value"
                                            class="ccd-input"
                                            type="text"
                                            placeholder="Enter Department Name..."
                                    >
                                    <img
                                            class="ccd-addon-img"
                                            src="<?php echo CCD_PLUGIN_URL . 'images/Department-name-left-chest.jpg'; ?>"
                                            alt="HFH Right Chest Logo"
                                    >
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (get_field('dp_name_back')): ?>
                        <div class="ccd-addon-container">
                            <div class="ccd-addon-item">
                                <label class="ccd-addon-label" for="">
                                    Add Department Name Back
                                </label>
                                <select class="ccd-select" name="department_name_back" id="department-name-back" required>
                                    <option value="none">No Department Name</option>
                                    <option value="yes">Add Department Name - Back</option>
                                </select>
                                <div id="ccd-addon-department-name-back-container" class="ccd-hidden">
                                    <input
                                            id="ccd-department-name-back"
                                            name="department_name_back_value"
                                            class="ccd-input"
                                            type="text"
                                            placeholder="Enter Department Name..."
                                    >
                                    <img
                                            id="ccd-department-img-back"
                                            class="ccd-addon-img"
                                            src="<?php echo CCD_PLUGIN_URL . 'images/Department-name-back.jpg'; ?>"
                                            alt="HFH Right Chest Logo"
                                    >
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

            <button id="ccd-submit-btn" type="submit">Add To Cart</button>
        </form>
        <!-- END: EXACT form -->
        <?php
    }

    /**
     * Process form submission, add items to cart, apply upcharges for relevant fields.
     */
    private function add_to_cart($quantities) {
        if (empty($quantities)) {
            wc_add_notice(__('Please select at least one variation.', 'ccd-product-options'), 'error');
            return;
        }

        // Capture posted fields (excluding color/size for custom meta).
        // We'll handle color/size as normal WC attributes, not custom meta.
        $right_chest_screen_print       = isset($_POST['right_chest_screen_print']) ? sanitize_text_field($_POST['right_chest_screen_print']) : '';
        $right_chest_embroidery         = isset($_POST['right_chest_embroidery']) ? sanitize_text_field($_POST['right_chest_embroidery']) : '';
        $personalized_name_left_chest   = isset($_POST['personalized_name_left_chest']) ? sanitize_text_field($_POST['personalized_name_left_chest']) : '';
        $department_name_left_chest_opt = isset($_POST['department_name_left_chest']) ? sanitize_text_field($_POST['department_name_left_chest']) : '';
        $department_name_left_chest_val = isset($_POST['department_name_left_chest_value']) ? sanitize_text_field($_POST['department_name_left_chest_value']) : '';
        $department_name_back_opt       = isset($_POST['department_name_back']) ? sanitize_text_field($_POST['department_name_back']) : '';
        $department_name_back_val       = isset($_POST['department_name_back_value']) ? sanitize_text_field($_POST['department_name_back_value']) : '';

        foreach ($quantities as $variation_id => $quantity) {
            if ($quantity > 0) {
                $variation = wc_get_product($variation_id);
                if ($variation && $variation->exists() && $variation->is_in_stock()) {

                    $cart_item_data = [];

                    // Right Chest - Screen Print (no upcharge)
                    if (!empty($right_chest_screen_print) && $right_chest_screen_print !== 'Blank') {
                        $cart_item_data['right_chest_screen_print'] = $right_chest_screen_print;
                    }

                    // Right Chest - Embroidery (no upcharge)
                    if (!empty($right_chest_embroidery) && $right_chest_embroidery !== 'Blank') {
                        $cart_item_data['right_chest_embroidery'] = $right_chest_embroidery;
                    }

                    // Personalized Name Left Chest (+ $8)
                    if (!empty($personalized_name_left_chest)) {
                        $cart_item_data['personalized_name_left_chest'] = $personalized_name_left_chest;
                        $cart_item_data['personalized_name_left_chest_upcharge'] = 8;
                    }

                    // Department Name Left Chest (+ $4)
                    if ($department_name_left_chest_opt === 'Left Chest' && !empty($department_name_left_chest_val)) {
                        $cart_item_data['department_name_left_chest_value'] = $department_name_left_chest_val;
                        $cart_item_data['department_name_left_chest_upcharge'] = 4;
                    }

                    // Department Name Back (no upcharge in this example)
                    if ($department_name_back_opt === 'yes' && !empty($department_name_back_val)) {
                        $cart_item_data['department_name_back_value'] = $department_name_back_val;
                    }

                    WC()->cart->add_to_cart($variation_id, $quantity, 0, [], $cart_item_data);

                } else {
                    wc_add_notice(__('One or more variations are invalid or out of stock.', 'ccd-product-options'), 'error');
                    return;
                }
            }
        }

        if (WC()->cart->is_empty()) {
            wc_add_notice(__('No items were added to your cart. Please try again.', 'ccd-product-options'), 'error');
            return;
        } else {
            wc_add_notice(__('Products added to your cart.', 'ccd-product-options'), 'success');
        }

        // Redirect back to product page
        wp_redirect(get_permalink($this->product->get_id()));
        exit;
    }
}

// =========================== HOOKS & FILTERS ===========================

// 1) Capture item data if added other ways (optional, but good practice).
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    // Right Chest - Screen Print
    if (!empty($_POST['right_chest_screen_print']) && $_POST['right_chest_screen_print'] !== 'Blank') {
        $cart_item_data['right_chest_screen_print'] = sanitize_text_field($_POST['right_chest_screen_print']);
    }

    // Right Chest - Embroidery
    if (!empty($_POST['right_chest_embroidery']) && $_POST['right_chest_embroidery'] !== 'Blank') {
        $cart_item_data['right_chest_embroidery'] = sanitize_text_field($_POST['right_chest_embroidery']);
    }

    // Personalized Name Left Chest (+ $8)
    if (!empty($_POST['personalized_name_left_chest'])) {
        $cart_item_data['personalized_name_left_chest'] = sanitize_text_field($_POST['personalized_name_left_chest']);
        $cart_item_data['personalized_name_left_chest_upcharge'] = 8;
    }

    // Department Name Left Chest (+ $4)
    if (
        isset($_POST['department_name_left_chest'])
        && $_POST['department_name_left_chest'] === 'Left Chest'
        && !empty($_POST['department_name_left_chest_value'])
    ) {
        $cart_item_data['department_name_left_chest_value'] = sanitize_text_field($_POST['department_name_left_chest_value']);
        $cart_item_data['department_name_left_chest_upcharge'] = 4;
    }

    // Department Name Back (no upcharge in this example)
    if (
        isset($_POST['department_name_back'])
        && $_POST['department_name_back'] === 'yes'
        && !empty($_POST['department_name_back_value'])
    ) {
        $cart_item_data['department_name_back_value'] = sanitize_text_field($_POST['department_name_back_value']);
    }

    // NOTE: We do NOT store color/size as custom meta. Those remain standard variations.

    return $cart_item_data;
}, 10, 2);

// 2) Add upcharges to product prices before totals are calculated
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item) {

        // Personalized Name Left Chest
        if (isset($cart_item['personalized_name_left_chest_upcharge'])) {
            $upcharge = floatval($cart_item['personalized_name_left_chest_upcharge']);
            $cart_item['data']->set_price($cart_item['data']->get_price() + $upcharge);
        }

        // Department Name Left Chest
        if (isset($cart_item['department_name_left_chest_upcharge'])) {
            $upcharge = floatval($cart_item['department_name_left_chest_upcharge']);
            $cart_item['data']->set_price($cart_item['data']->get_price() + $upcharge);
        }

        // If you ever want a Department Name Back upcharge, add it here:
        // if (isset($cart_item['department_name_back_upcharge'])) {
        //     ...
        // }
    }
});

// 3) Display the item data in the cart & checkout
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {

    if (isset($cart_item['right_chest_screen_print'])) {
        $item_data[] = [
            'key'   => 'Right Chest - Screen Print',
            'value' => wc_clean($cart_item['right_chest_screen_print'])
        ];
    }

    if (isset($cart_item['right_chest_embroidery'])) {
        $item_data[] = [
            'key'   => 'Right Chest - Embroidery',
            'value' => wc_clean($cart_item['right_chest_embroidery'])
        ];
    }

    if (isset($cart_item['personalized_name_left_chest'])) {
        $item_data[] = [
            'key'   => 'Personalized Name (Left Chest)',
            'value' => wc_clean($cart_item['personalized_name_left_chest'])
        ];
    }

    if (isset($cart_item['department_name_left_chest_value'])) {
        $item_data[] = [
            'key'   => 'Department Name (Left Chest)',
            'value' => wc_clean($cart_item['department_name_left_chest_value'])
        ];
    }

    if (isset($cart_item['department_name_back_value'])) {
        $item_data[] = [
            'key'   => 'Department Name (Back)',
            'value' => wc_clean($cart_item['department_name_back_value'])
        ];
    }

    return $item_data;
}, 10, 2);

// 4) Store the data in the order line items
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {

    if (isset($values['right_chest_screen_print'])) {
        $item->add_meta_data('Right Chest - Screen Print', $values['right_chest_screen_print'], true);
    }

    if (isset($values['right_chest_embroidery'])) {
        $item->add_meta_data('Right Chest - Embroidery', $values['right_chest_embroidery'], true);
    }

    if (isset($values['personalized_name_left_chest'])) {
        $item->add_meta_data('Personalized Name (Left Chest)', $values['personalized_name_left_chest'], true);
    }

    if (isset($values['department_name_left_chest_value'])) {
        $item->add_meta_data('Department Name (Left Chest)', $values['department_name_left_chest_value'], true);
    }

    if (isset($values['department_name_back_value'])) {
        $item->add_meta_data('Department Name (Back)', $values['department_name_back_value'], true);
    }

    // Upcharges
    if (isset($values['personalized_name_left_chest_upcharge'])) {
        $item->add_meta_data(
            'Upcharge - Personalized Name (Left Chest)',
            wc_price($values['personalized_name_left_chest_upcharge']),
            true
        );
    }

    if (isset($values['department_name_left_chest_upcharge'])) {
        $item->add_meta_data(
            'Upcharge - Department Name (Left Chest)',
            wc_price($values['department_name_left_chest_upcharge']),
            true
        );
    }

    // If you add a department_name_back_upcharge, handle it here similarly.
}, 10, 4);
