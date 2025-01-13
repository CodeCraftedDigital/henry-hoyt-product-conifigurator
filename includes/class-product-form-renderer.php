<?php
/**
 * Example: ProductFormRenderer with conditional "none" or typed values
 * plus upcharges only if user typed something.
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

        // Hide default variation form
        echo '<style>
            .variations_form.cart {
                display: none !important;
            }
        </style>';

        wc_print_notices();
        ?>

        <!-- BEGIN: Your exact custom form -->
        <form id="ccd-form" data-product-id="<?php echo esc_attr($product_id); ?>" method="POST">

            <!-- Step 1: Choose Color -->
            <div>
                <label for="color" class="ccd-form__label">
                    <span class="ccd-step-number">1</span> Choose your color
                </label>
                <select name="color" id="color-options" class="ccd-select" required>
                    <option value="Please Choose A Color" selected>Please Choose A Color</option>
                    <!-- Color options will be appended here by JS -->
                </select>
            </div>

            <!-- Step 2: Select sizes and quantities -->
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
                    <!-- JS appends sizes -->
                </div>
            </div>

            <!-- Step 3: Product Options (Add-ons) -->
            <?php if (get_field('enable_po_section')): ?>
                <div class="ccd-product-options__container">
                    <label class="ccd-form__label">
                        <span class="ccd-step-number">3</span> Product Options
                    </label>

                    <!-- Right Chest - Screen Print (always "Blank" or "HFH Logo") -->
                    <?php if (get_field('right_chest_logo_sp')): ?>
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

                    <!-- Right Chest - Embroidery (always "Blank" or "HFH Logo") -->
                    <?php if (get_field('right_chest_logo_em')): ?>
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

                    <!-- Add Personalized Name Left Chest (+8.00)
                         => "none" if empty, typed value if not empty -->
                    <?php if (get_field('left_chest_pn')): ?>
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

                    <!-- Add Department Name Left Chest (+4.00)
                         => "none" if dropdown=none or typed field empty,
                            typed value if "Left Chest" + text filled
                    -->
                    <?php if (get_field('dp_name_left_chest')): ?>
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
                                            alt="HFH Right Chest Logo"
                                    >
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Add Department Name Back => "none" if "No Department Name",
                                                     typed value otherwise
                         (no upcharge example)
                    -->
                    <?php if (get_field('dp_name_back')): ?>
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
     * Add to cart logic: store "none" if user leaves it blank,
     * store typed value if not empty, add upcharges accordingly.
     */
    private function add_to_cart($quantities) {
        if (empty($quantities)) {
            wc_add_notice(__('Please select at least one variation.', 'ccd-product-options'), 'error');
            return;
        }

        // Grab form inputs
        $right_chest_screen_print       = isset($_POST['right_chest_screen_print'])
            ? sanitize_text_field($_POST['right_chest_screen_print']) : 'Blank';
        $right_chest_embroidery         = isset($_POST['right_chest_embroidery'])
            ? sanitize_text_field($_POST['right_chest_embroidery']) : 'Blank';
        $personalized_name_left_chest   = isset($_POST['personalized_name_left_chest'])
            ? sanitize_text_field($_POST['personalized_name_left_chest']) : '';
        $department_name_left_chest_opt = isset($_POST['department_name_left_chest'])
            ? sanitize_text_field($_POST['department_name_left_chest']) : 'none';
        $department_name_left_chest_val = isset($_POST['department_name_left_chest_value'])
            ? sanitize_text_field($_POST['department_name_left_chest_value']) : '';
        $department_name_back_opt       = isset($_POST['department_name_back'])
            ? sanitize_text_field($_POST['department_name_back']) : 'none';
        $department_name_back_val       = isset($_POST['department_name_back_value'])
            ? sanitize_text_field($_POST['department_name_back_value']) : '';

        foreach ($quantities as $variation_id => $quantity) {
            if ($quantity > 0) {
                $variation = wc_get_product($variation_id);
                if ($variation && $variation->exists() && $variation->is_in_stock()) {

                    $cart_item_data = [];

                    // Right Chest - Screen Print
                    // Always store either "Blank" or "HFH Logo"
                    if (empty($right_chest_screen_print)) {
                        $cart_item_data['right_chest_screen_print'] = 'Blank';
                    } else {
                        $cart_item_data['right_chest_screen_print'] = $right_chest_screen_print;
                    }

                    // Right Chest - Embroidery
                    if (empty($right_chest_embroidery)) {
                        $cart_item_data['right_chest_embroidery'] = 'Blank';
                    } else {
                        $cart_item_data['right_chest_embroidery'] = $right_chest_embroidery;
                    }

                    // Personalized Name Left Chest (+$8 if not empty)
                    if ($personalized_name_left_chest !== '') {
                        // User typed something
                        $cart_item_data['personalized_name_left_chest'] = $personalized_name_left_chest;
                        $cart_item_data['personalized_name_left_chest_upcharge'] = 8;
                    } else {
                        // If empty
                        $cart_item_data['personalized_name_left_chest'] = 'none';
                    }

                    // Department Name Left Chest (+$4 if "Left Chest" + typed value)
                    if ($department_name_left_chest_opt === 'Left Chest' && $department_name_left_chest_val !== '') {
                        $cart_item_data['department_name_left_chest_value'] = $department_name_left_chest_val;
                        $cart_item_data['department_name_left_chest_upcharge'] = 4;
                    } else {
                        // either user selected "none" or left text blank => "none"
                        $cart_item_data['department_name_left_chest_value'] = 'none';
                    }

                    // Department Name Back (no upcharge)
                    // If "yes" + typed => store typed
                    // else => "none"
                    if ($department_name_back_opt === 'yes' && $department_name_back_val !== '') {
                        $cart_item_data['department_name_back_value'] = $department_name_back_val;
                    } else {
                        $cart_item_data['department_name_back_value'] = 'none';
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

        wp_redirect(get_permalink($this->product->get_id()));
        exit;
    }
}


/* ======================== WOOCOMMERCE HOOKS ========================== */

/**
 * 1) Capture item data if user adds to cart in other ways (optional).
 *    We'll mimic the same "none" or typed logic here for safety.
 */
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {

    // Right Chest - Screen Print
    if (isset($_POST['right_chest_screen_print'])) {
        $value = sanitize_text_field($_POST['right_chest_screen_print']);
        $cart_item_data['right_chest_screen_print'] = $value ?: 'Blank';
    }

    // Right Chest - Embroidery
    if (isset($_POST['right_chest_embroidery'])) {
        $value = sanitize_text_field($_POST['right_chest_embroidery']);
        $cart_item_data['right_chest_embroidery'] = $value ?: 'Blank';
    }

    // Personalized Name Left Chest
    if (isset($_POST['personalized_name_left_chest'])) {
        $name = sanitize_text_field($_POST['personalized_name_left_chest']);
        if ($name !== '') {
            $cart_item_data['personalized_name_left_chest'] = $name;
            $cart_item_data['personalized_name_left_chest_upcharge'] = 8;
        } else {
            $cart_item_data['personalized_name_left_chest'] = 'none';
        }
    }

    // Department Name Left Chest
    if (isset($_POST['department_name_left_chest']) || isset($_POST['department_name_left_chest_value'])) {
        $opt = sanitize_text_field($_POST['department_name_left_chest'] ?? 'none');
        $val = sanitize_text_field($_POST['department_name_left_chest_value'] ?? '');

        if ($opt === 'Left Chest' && $val !== '') {
            $cart_item_data['department_name_left_chest_value'] = $val;
            $cart_item_data['department_name_left_chest_upcharge'] = 4;
        } else {
            $cart_item_data['department_name_left_chest_value'] = 'none';
        }
    }

    // Department Name Back
    if (isset($_POST['department_name_back']) || isset($_POST['department_name_back_value'])) {
        $opt = sanitize_text_field($_POST['department_name_back'] ?? 'none');
        $val = sanitize_text_field($_POST['department_name_back_value'] ?? '');
        if ($opt === 'yes' && $val !== '') {
            $cart_item_data['department_name_back_value'] = $val;
        } else {
            $cart_item_data['department_name_back_value'] = 'none';
        }
    }

    return $cart_item_data;
}, 10, 2);


/**
 * 2) Add upcharges to product prices before totals are calculated.
 */
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

        // Department Name Back upcharge if you ever want it, e.g.:
        // if (isset($cart_item['department_name_back_upcharge'])) {
        //     ...
        // }
    }
});


/**
 * 3) Display the item data in the cart & checkout.
 *    We simply show whatever we stored. If we stored "none", user sees "none".
 */
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {

    // Right Chest - Screen Print
    if (isset($cart_item['right_chest_screen_print'])) {
        $item_data[] = [
            'key'   => 'Right Chest - Screen Print',
            'value' => wc_clean($cart_item['right_chest_screen_print'])
        ];
    }

    // Right Chest - Embroidery
    if (isset($cart_item['right_chest_embroidery'])) {
        $item_data[] = [
            'key'   => 'Right Chest - Embroidery',
            'value' => wc_clean($cart_item['right_chest_embroidery'])
        ];
    }

    // Personalized Name Left Chest
    if (isset($cart_item['personalized_name_left_chest'])) {
        $item_data[] = [
            'key'   => 'Personalized Name (Left Chest)',
            'value' => wc_clean($cart_item['personalized_name_left_chest'])
        ];
    }

    // Department Name Left Chest
    if (isset($cart_item['department_name_left_chest_value'])) {
        $item_data[] = [
            'key'   => 'Department Name (Left Chest)',
            'value' => wc_clean($cart_item['department_name_left_chest_value'])
        ];
    }

    // Department Name Back
    if (isset($cart_item['department_name_back_value'])) {
        $item_data[] = [
            'key'   => 'Department Name (Back)',
            'value' => wc_clean($cart_item['department_name_back_value'])
        ];
    }

    return $item_data;
}, 10, 2);


/**
 * 4) Store data in the order line items.
 *    Again, we show exactly what's stored ("none" or typed text).
 */
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {

    // Right Chest - Screen Print
    if (isset($values['right_chest_screen_print'])) {
        $item->add_meta_data('Right Chest - Screen Print', $values['right_chest_screen_print'], true);
    }

    // Right Chest - Embroidery
    if (isset($values['right_chest_embroidery'])) {
        $item->add_meta_data('Right Chest - Embroidery', $values['right_chest_embroidery'], true);
    }

    // Personalized Name (Left Chest)
    if (isset($values['personalized_name_left_chest'])) {
        $item->add_meta_data('Personalized Name (Left Chest)', $values['personalized_name_left_chest'], true);
    }

    // Department Name (Left Chest)
    if (isset($values['department_name_left_chest_value'])) {
        $item->add_meta_data('Department Name (Left Chest)', $values['department_name_left_chest_value'], true);
    }

    // Department Name (Back)
    if (isset($values['department_name_back_value'])) {
        $item->add_meta_data('Department Name (Back)', $values['department_name_back_value'], true);
    }

    // Upcharge - Personalized Name
    if (isset($values['personalized_name_left_chest_upcharge'])) {
        $item->add_meta_data(
            'Upcharge - Personalized Name (Left Chest)',
            wc_price($values['personalized_name_left_chest_upcharge']),
            true
        );
    }

    // Upcharge - Department Name (Left Chest)
    if (isset($values['department_name_left_chest_upcharge'])) {
        $item->add_meta_data(
            'Upcharge - Department Name (Left Chest)',
            wc_price($values['department_name_left_chest_upcharge']),
            true
        );
    }

    // If you add a department_name_back_upcharge later, handle it here
}, 10, 4);
