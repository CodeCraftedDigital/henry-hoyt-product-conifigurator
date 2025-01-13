<?php
/**
 * A bulletproof example to ensure only ACF-enabled fields appear
 * in the form, cart, and order details.
 */
class ProductFormRenderer {
    private $product;
    private $variationsFetcher;

    public function __construct($product, ProductVariationsFetcher $variationsFetcher) {
        $this->product = $product;
        $this->variationsFetcher = $variationsFetcher;
    }

    public function render_empty_form() {
        if (!is_product() || !$this->product->is_type('variable')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['size_quantities'])) {
            $this->add_to_cart($_POST['size_quantities']);
        }

        $product_id = $this->product->get_id();

        // Hide the default WC variation form
        echo '<style>
            .variations_form.cart {
                display: none !important;
            }
        </style>';

        wc_print_notices();
        ?>

        <!-- BEGIN: Your Custom Form -->
        <form id="ccd-form" data-product-id="<?php echo esc_attr($product_id); ?>" method="POST">
            <!-- STEP 1: Choose Color -->
            <div>
                <label for="color" class="ccd-form__label">
                    <span class="ccd-step-number">1</span> Choose your color
                </label>
                <select name="color" id="color-options" class="ccd-select" required>
                    <option value="Please Choose A Color" selected>Please Choose A Color</option>
                    <!-- Color options appended by JS -->
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
                        $size_guide_file = get_field('product_size_guide', $this->product->get_id());
                        if ($size_guide_file && !empty($size_guide_file['url'])): ?>
                            <a href="<?php echo esc_url($size_guide_file['url']); ?>" target="_blank" class="ccd-size-guide-btn">
                                View Size Guide
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="ccd-size__block">
                    <!-- JS appends size input fields -->
                </div>
            </div>

            <!-- STEP 3: Product Options (conditionally rendered) -->
            <?php if (get_field('enable_po_section', $product_id)): ?>
                <div class="ccd-product-options__container">
                    <label class="ccd-form__label">
                        <span class="ccd-step-number">3</span> Product Options
                    </label>

                    <!-- 1) Right Chest - Screen Print -->
                    <?php if (get_field('right_chest_logo_sp', $product_id)): ?>
                        <div class="ccd-addon-container">
                            <div class="ccd-addon-item">
                                <label class="ccd-addon-label" for="ccd-right-chest-logo-sp">
                                    Right Chest - Screen Print
                                </label>
                                <select class="ccd-select" name="right_chest_screen_print" id="ccd-right-chest-logo-sp">
                                    <option value="Blank">Blank</option>
                                    <option value="HFH Logo">HFH Logo</option>
                                </select>
                                <div id="ccd-addon-img-container-sp" class="ccd-hidden">
                                    <img
                                            class="ccd-addon-img"
                                            src="<?php echo CCD_PLUGIN_URL . 'images/right-chest-logo.jpg'; ?>"
                                            alt="HFH Right Chest Logo"
                                    >
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 2) Right Chest - Embroidery -->
                    <?php if (get_field('right_chest_logo_em', $product_id)): ?>
                        <div class="ccd-addon-container">
                            <div class="ccd-addon-item">
                                <label class="ccd-addon-label" for="ccd-right-chest-logo-em">
                                    Right Chest - Embroidery
                                </label>
                                <select class="ccd-select" name="right_chest_embroidery" id="ccd-right-chest-logo-em">
                                    <option value="Blank">Blank</option>
                                    <option value="HFH Logo">HFH Logo</option>
                                </select>
                                <div id="ccd-addon-img-container-em" class="ccd-hidden">
                                    <img
                                            class="ccd-addon-img"
                                            src="<?php echo CCD_PLUGIN_URL . 'images/right-chest-logo.jpg'; ?>"
                                            alt="HFH Right Chest Logo"
                                    >
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 3) Personalized Name Left Chest (+$8) -->
                    <?php if (get_field('left_chest_pn', $product_id)): ?>
                        <div class="ccd-addon-container">
                            <div class="ccd-addon-item">
                                <label class="ccd-addon-label">
                                    Add Personalized Name Left Chest
                                    <span class="ccd-add-on-upcharge">(+8.00)</span>
                                </label>
                                <input
                                        name="personalized_name_left_chest"
                                        class="ccd-input"
                                        type="text"
                                        placeholder="Enter Your Name..."
                                >
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 4) Department Name Left Chest (+$4) -->
                    <?php if (get_field('dp_name_left_chest', $product_id)): ?>
                        <div class="ccd-addon-container">
                            <div class="ccd-addon-item">
                                <label class="ccd-addon-label" for="department-name-left-chest">
                                    Add Department Name Left Chest
                                    <span class="ccd-add-on-upcharge">(+4.00)</span>
                                </label>
                                <select class="ccd-select" name="department_name_left_chest" id="department-name-left-chest">
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
                                            alt="Department Name Left Chest"
                                    >
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 5) Department Name Back (no upcharge example) -->
                    <?php if (get_field('dp_name_back', $product_id)): ?>
                        <div class="ccd-addon-container">
                            <div class="ccd-addon-item">
                                <label class="ccd-addon-label" for="department-name-back">
                                    Add Department Name Back
                                </label>
                                <select class="ccd-select" name="department_name_back" id="department-name-back">
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
                                            alt="Department Name Back"
                                    >
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

            <button id="ccd-submit-btn" type="submit">Add To Cart</button>
        </form>
        <!-- END: Custom Form -->
        <?php
    }

    /**
     * add_to_cart() - only sets cart data for fields that are ACF-enabled.
     * If field is disabled, we skip it entirely (so it won't even show "none").
     */
    private function add_to_cart($quantities) {
        if (empty($quantities)) {
            wc_add_notice(__('Please select at least one variation.', 'ccd-product-options'), 'error');
            return;
        }

        // Grab posted data (we won't store them unless ACF is ON)
        $rc_screen_print     = isset($_POST['right_chest_screen_print'])
            ? sanitize_text_field($_POST['right_chest_screen_print']) : 'Blank';
        $rc_embroidery       = isset($_POST['right_chest_embroidery'])
            ? sanitize_text_field($_POST['right_chest_embroidery']) : 'Blank';
        $personalized_name   = isset($_POST['personalized_name_left_chest'])
            ? sanitize_text_field($_POST['personalized_name_left_chest']) : '';
        $dept_left_opt       = isset($_POST['department_name_left_chest'])
            ? sanitize_text_field($_POST['department_name_left_chest']) : 'none';
        $dept_left_val       = isset($_POST['department_name_left_chest_value'])
            ? sanitize_text_field($_POST['department_name_left_chest_value']) : '';
        $dept_back_opt       = isset($_POST['department_name_back'])
            ? sanitize_text_field($_POST['department_name_back']) : 'none';
        $dept_back_val       = isset($_POST['department_name_back_value'])
            ? sanitize_text_field($_POST['department_name_back_value']) : '';

        $product_id = $this->product->get_id();

        foreach ($quantities as $variation_id => $quantity) {
            if ($quantity > 0) {
                $variation = wc_get_product($variation_id);
                if (!$variation || !$variation->exists() || !$variation->is_in_stock()) {
                    wc_add_notice(__('One or more variations are invalid or out of stock.', 'ccd-product-options'), 'error');
                    return;
                }

                $cart_item_data = [];

                // 1) Right Chest - Screen Print
                if ( get_field('right_chest_logo_sp', $product_id) ) {
                    // If user didn't pick anything, we default to "Blank."
                    $cart_item_data['right_chest_screen_print'] = $rc_screen_print ?: 'Blank';
                }

                // 2) Right Chest - Embroidery
                if ( get_field('right_chest_logo_em', $product_id) ) {
                    $cart_item_data['right_chest_embroidery'] = $rc_embroidery ?: 'Blank';
                }

                // 3) Personalized Name Left Chest (+$8 if not empty)
                if ( get_field('left_chest_pn', $product_id) ) {
                    if ($personalized_name !== '') {
                        $cart_item_data['personalized_name_left_chest'] = $personalized_name;
                        $cart_item_data['personalized_name_left_chest_upcharge'] = 8;
                    } else {
                        // if field was offered but left blank => store "none"
                        $cart_item_data['personalized_name_left_chest'] = 'none';
                    }
                }

                // 4) Department Name Left Chest (+$4 if "Left Chest" + typed text)
                if ( get_field('dp_name_left_chest', $product_id) ) {
                    if ($dept_left_opt === 'Left Chest' && $dept_left_val !== '') {
                        $cart_item_data['department_name_left_chest_value'] = $dept_left_val;
                        $cart_item_data['department_name_left_chest_upcharge'] = 4;
                    } else {
                        // if field was offered but user selected none or typed nothing => store "none"
                        $cart_item_data['department_name_left_chest_value'] = 'none';
                    }
                }

                // 5) Department Name Back (no upcharge in example)
                if ( get_field('dp_name_back', $product_id) ) {
                    if ($dept_back_opt === 'yes' && $dept_back_val !== '') {
                        $cart_item_data['department_name_back_value'] = $dept_back_val;
                    } else {
                        $cart_item_data['department_name_back_value'] = 'none';
                    }
                }

                // Finally, add to cart
                WC()->cart->add_to_cart($variation_id, $quantity, 0, [], $cart_item_data);
            }
        }

        if (WC()->cart->is_empty()) {
            wc_add_notice(__('No items were added to your cart. Please try again.', 'ccd-product-options'), 'error');
        } else {
            wc_add_notice(__('Products added to your cart.', 'ccd-product-options'), 'success');
        }

        wp_redirect(get_permalink($product_id));
        exit;
    }
}

/* ======================== HOOKS & FILTERS ========================== */

/**
 * If you want to bulletproof the "add to cart" outside your custom form,
 * you can replicate the same ACF checks in this filter. If you rarely do
 * other add-to-cart flows, it's optional. But let's be safe:
 */
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {

    // Access the product
    $product = wc_get_product($product_id);
    if (!$product) {
        return $cart_item_data;
    }

    // 1) Right Chest - Screen Print
    if ( get_field('right_chest_logo_sp', $product_id) && isset($_POST['right_chest_screen_print']) ) {
        $value = sanitize_text_field($_POST['right_chest_screen_print']) ?: 'Blank';
        $cart_item_data['right_chest_screen_print'] = $value;
    }

    // 2) Right Chest - Embroidery
    if ( get_field('right_chest_logo_em', $product_id) && isset($_POST['right_chest_embroidery']) ) {
        $value = sanitize_text_field($_POST['right_chest_embroidery']) ?: 'Blank';
        $cart_item_data['right_chest_embroidery'] = $value;
    }

    // 3) Personalized Name Left Chest
    if ( get_field('left_chest_pn', $product_id) && isset($_POST['personalized_name_left_chest']) ) {
        $name = sanitize_text_field($_POST['personalized_name_left_chest']);
        if ($name !== '') {
            $cart_item_data['personalized_name_left_chest'] = $name;
            $cart_item_data['personalized_name_left_chest_upcharge'] = 8;
        } else {
            $cart_item_data['personalized_name_left_chest'] = 'none';
        }
    }

    // 4) Department Name Left Chest
    if ( get_field('dp_name_left_chest', $product_id) && isset($_POST['department_name_left_chest']) ) {
        $opt = sanitize_text_field($_POST['department_name_left_chest']);
        $val = isset($_POST['department_name_left_chest_value'])
            ? sanitize_text_field($_POST['department_name_left_chest_value']) : '';
        if ($opt === 'Left Chest' && $val !== '') {
            $cart_item_data['department_name_left_chest_value'] = $val;
            $cart_item_data['department_name_left_chest_upcharge'] = 4;
        } else {
            $cart_item_data['department_name_left_chest_value'] = 'none';
        }
    }

    // 5) Department Name Back
    if ( get_field('dp_name_back', $product_id) && isset($_POST['department_name_back']) ) {
        $opt = sanitize_text_field($_POST['department_name_back']);
        $val = isset($_POST['department_name_back_value'])
            ? sanitize_text_field($_POST['department_name_back_value']) : '';
        if ($opt === 'yes' && $val !== '') {
            $cart_item_data['department_name_back_value'] = $val;
        } else {
            $cart_item_data['department_name_back_value'] = 'none';
        }
    }

    return $cart_item_data;
}, 10, 2);

/**
 * Add upcharges to product prices before totals are calculated.
 */
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item) {

        // Personalized Name (+$8)
        if (isset($cart_item['personalized_name_left_chest_upcharge'])) {
            $upcharge = floatval($cart_item['personalized_name_left_chest_upcharge']);
            $cart_item['data']->set_price($cart_item['data']->get_price() + $upcharge);
        }

        // Department Name Left Chest (+$4)
        if (isset($cart_item['department_name_left_chest_upcharge'])) {
            $upcharge = floatval($cart_item['department_name_left_chest_upcharge']);
            $cart_item['data']->set_price($cart_item['data']->get_price() + $upcharge);
        }

        // If you ever want a Department Name Back upcharge, do similarly:
        // if (isset($cart_item['department_name_back_upcharge'])) { ... }
    }
});

/**
 * Show custom data in cart/checkout.
 * Only displays fields that exist in the cart_item data.
 * If the code never set them, they won't appear!
 */
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {

    if (isset($cart_item['right_chest_screen_print'])) {
        $item_data[] = [
            'key'   => 'Right Chest - Screen Print',
            'value' => wc_clean($cart_item['right_chest_screen_print']),
        ];
    }

    if (isset($cart_item['right_chest_embroidery'])) {
        $item_data[] = [
            'key'   => 'Right Chest - Embroidery',
            'value' => wc_clean($cart_item['right_chest_embroidery']),
        ];
    }

    if (isset($cart_item['personalized_name_left_chest'])) {
        $item_data[] = [
            'key'   => 'Personalized Name (Left Chest)',
            'value' => wc_clean($cart_item['personalized_name_left_chest']),
        ];
    }

    if (isset($cart_item['department_name_left_chest_value'])) {
        $item_data[] = [
            'key'   => 'Department Name (Left Chest)',
            'value' => wc_clean($cart_item['department_name_left_chest_value']),
        ];
    }

    if (isset($cart_item['department_name_back_value'])) {
        $item_data[] = [
            'key'   => 'Department Name (Back)',
            'value' => wc_clean($cart_item['department_name_back_value']),
        ];
    }

    return $item_data;
}, 10, 2);

/**
 * Save the data to order line items so it appears in the admin and emails.
 * Again, only the keys we actually set appear.
 */
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

    // Upcharge breakdown (optional)
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

}, 10, 4);
