class ProductOptions {
    constructor() {
        // Provided by wp_localize_script (MainPlugin.php)
        this.route = (typeof ccdData !== 'undefined' && ccdData.restBase) ? ccdData.restBase : '';

        // Grab the main form
        this.form = document.getElementById('ccd-form');
        // If no form found (e.g. not a variable product or custom form is disabled), bail
        if (!this.form) return;

        this.parentProductID = this.form.dataset.productId;
        // If no product ID data attribute, bail
        if (!this.parentProductID) return;

        // STEP 1: Color
        this.colorOptionsSelect = document.getElementById('color-options');

        // STEP 2: Sizes
        this.sizeOptionsContainer = document.getElementById('ccd-size__block');
        this.sizeMainContainer = document.getElementById('ccd-size__container');
        this.addToCartBtn = document.getElementById('ccd-submit-btn');

        // WooCommerce product gallery
        this.wooGallery = document.querySelector('.woocommerce-product-gallery__wrapper > div');

        // For color-based data
        this.selectedColor = '';
        this.colors = [];
        this.filteredColor = null;
        this.currentImg = '';

        // Bind events
        this.events();
    }

    events() {
        // 1) When color changes (if colorOptionsSelect is present)
        if (this.colorOptionsSelect) {
            this.colorOptionsSelect.addEventListener('change', (e) => {
                this.selectedColor = e.target.value;
                const filtered = this.filterSelectedColor(this.selectedColor);
                this.buildSelectedColorSizes(filtered);

                // Update main image if we have a variation
                if (filtered && filtered.variations.length > 0) {
                    this.currentImg = filtered.variations[0].image;
                    this.updateWooGalleryImage(this.currentImg);
                }

                this.toggleAddToCartButton();
            });
        }

        // 2) Dynamic fields for Step 3 (no changes needed for new fields, just add .ccd-dynamic-field + data-field-type)
        document.querySelectorAll('.ccd-dynamic-field').forEach((fieldEl) => {
            const fieldType = fieldEl.getAttribute('data-field-type');
            const fieldName = fieldEl.name; // e.g. "right_chest_screen_print"

            // A) If fieldType = 'select'
            if (fieldType === 'select') {
                fieldEl.addEventListener('change', (e) => {
                    const val = e.target.value;
                    // Construct an ID like #ccd-addon-img-container-[fieldName], e.g. ccd-addon-img-container-right_chest_screen_print
                    const imgDivId = `ccd-addon-img-container-${fieldName}`;
                    const imgDiv = document.getElementById(imgDivId);

                    if (imgDiv) {
                        // Show if they pick something with "logo" in it, otherwise hide
                        if (val.toLowerCase().includes('logo')) {
                            imgDiv.classList.remove('ccd-hidden');
                        } else {
                            imgDiv.classList.add('ccd-hidden');
                        }
                    }
                });
            }

            // B) If fieldType = 'text'
            if (fieldType === 'text') {
                // If you want custom text field logic, do it here
                // Currently no special logic, so we skip
            }

            // C) If fieldType = 'select-and-text'
            // e.g., user picks "yes"/"Left Chest" => show the container, "none" => hide
            if (fieldType === 'select-and-text') {
                fieldEl.addEventListener('change', (e) => {
                    const val = e.target.value;
                    // Usually the container has an ID like "[fieldEl.id]-container"
                    // e.g. if fieldEl.id="ccd-department_name_back", containerId="ccd-department_name_back-container"
                    const containerId = `${fieldEl.id}-container`;
                    const containerEl = document.getElementById(containerId);

                    if (containerEl) {
                        if (val !== 'none') {
                            containerEl.classList.remove('ccd-hidden');
                            // If there's a text input inside, require it
                            const textInput = containerEl.querySelector('input[type="text"]');
                            if (textInput) {
                                textInput.required = true;
                            }
                        } else {
                            containerEl.classList.add('ccd-hidden');
                            // If there's a text input, un-require and clear it
                            const textInput = containerEl.querySelector('input[type="text"]');
                            if (textInput) {
                                textInput.required = false;
                                textInput.value = '';
                            }
                        }
                    }
                });
            }
        });
    }

    // ----------------------------------------------------------------
    // Step 1 & 2: Color + Size/Quantity
    // ----------------------------------------------------------------

    // GET request to our custom REST route
    async getAllAvailableColors(id) {
        if (!this.route) return null; // No route => do nothing

        try {
            const res = await fetch(`${this.route}${id}`);
            if (!res.ok) {
                throw new Error(`HTTP error! Status: ${res.status}`);
            }
            const data = await res.json();
            this.colors = data;
            return data;
        } catch (err) {
            console.error('Error fetching variations:', err);
            return null;
        }
    }

    // Populate color dropdown
    buildSelectOptions(colors) {
        if (!this.colorOptionsSelect) return;
        colors.forEach((cObj) => {
            const opt = document.createElement('option');
            opt.value = cObj.color;
            opt.textContent = cObj.color;
            this.colorOptionsSelect.appendChild(opt);
        });
    }

    filterSelectedColor(selectedColor) {
        const filtered = this.colors.filter((c) => c.color === selectedColor);
        this.filteredColor = filtered[0] || null;
        return this.filteredColor;
    }

    buildSelectedColorSizes(item) {
        if (!this.sizeOptionsContainer) return;
        this.sizeOptionsContainer.innerHTML = '';

        if (item) {
            // Sort by size in a logical order
            item.variations.sort((a, b) => this.compareSizes(a.size, b.size));

            item.variations.forEach((v) => {
                const sizeItem = document.createElement('div');
                sizeItem.classList.add('sizes__item');

                // Price
                const priceDiv = document.createElement('div');
                priceDiv.classList.add('sizes__price');
                priceDiv.textContent = `$${v.price}`;
                sizeItem.append(priceDiv);

                // Quantity input
                const sizeBox = document.createElement('div');
                sizeBox.classList.add('sizes__box');
                const sizeInput = document.createElement('input');
                sizeInput.type = 'number';
                sizeInput.name = `size_quantities[${v.variation_id}]`;
                sizeInput.min = 0;
                sizeInput.step = 1;
                sizeInput.value = 0;
                sizeInput.classList.add('sizes__input');

                sizeInput.addEventListener('input', () => {
                    this.toggleAddToCartButton();
                });

                sizeBox.append(sizeInput);
                sizeItem.append(sizeBox);

                // Label (the actual size)
                const sizeLabel = document.createElement('div');
                sizeLabel.classList.add('sizes__label');
                sizeLabel.textContent = v.size;
                sizeItem.append(sizeLabel);

                this.sizeOptionsContainer.append(sizeItem);
            });
        }
    }

    // "Logical" size comparison
    compareSizes(a, b) {
        const order = ['XS','S','M','L','XL','XXL','XXXL','4XL','5XL'];
        const aU = a.toUpperCase();
        const bU = b.toUpperCase();

        const iA = order.indexOf(aU);
        const iB = order.indexOf(bU);

        if (iA !== -1 && iB !== -1) {
            return iA - iB;
        }
        if (iA !== -1 && iB === -1) return -1;
        if (iA === -1 && iB !== -1) return 1;

        const nA = parseInt(a, 10);
        const nB = parseInt(b, 10);
        if (!isNaN(nA) && !isNaN(nB)) {
            return nA - nB;
        }
        if (!isNaN(nA)) return -1;
        if (!isNaN(nB)) return 1;

        return a.localeCompare(b);
    }

    updateWooGalleryImage(imgUrl) {
        if (!this.wooGallery || !imgUrl) return;
        this.wooGallery.setAttribute('data-thumb', imgUrl);
        this.wooGallery.setAttribute('data-thumb-srcset', imgUrl);
        this.wooGallery.classList.remove('flex-active-slide');

        if (this.wooGallery.firstChild) {
            this.wooGallery.firstChild.setAttribute('href', imgUrl);
            if (this.wooGallery.firstChild.firstChild) {
                const fc = this.wooGallery.firstChild.firstChild;
                fc.setAttribute('href', imgUrl);
                fc.setAttribute('data-src', imgUrl);
                fc.setAttribute('data-large_image', imgUrl);
                fc.setAttribute('srcset', imgUrl);
            }
        }
    }

    toggleAddToCartButton() {
        if (!this.sizeOptionsContainer || !this.addToCartBtn) return;
        const sizeInputs = this.sizeOptionsContainer.querySelectorAll('.sizes__input');
        const anySelected = Array.from(sizeInputs).some(inp => parseInt(inp.value) > 0);
        this.addToCartBtn.disabled = !anySelected;
    }

    async init() {
        // Fetch colors from the endpoint
        const colors = await this.getAllAvailableColors(this.parentProductID);
        if (colors && Array.isArray(colors)) {
            this.buildSelectOptions(colors);
        }

        // Initially disable add-to-cart
        if (this.addToCartBtn) {
            this.addToCartBtn.disabled = true;
        }
    }
}

// Initialize
const productOptions = new ProductOptions();
productOptions.init();
