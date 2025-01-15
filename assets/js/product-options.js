class ProductOptions {
    constructor() {
        // REST route from wp_localize_script
        this.route = ccdData.restBase || '';

        // Optional nonce if you want to send it:
        // this.nonce = ccdData.nonce;

        this.form = document.getElementById('ccd-form');
        this.parentProductID = this.form.dataset.productId;

        // Step 1: color
        this.colorOptionsSelect = document.getElementById('color-options');
        // Step 2: sizes
        this.sizeOptionsContainer = document.getElementById('ccd-size__block');
        this.sizeMainContainer = document.getElementById('ccd-size__container');
        this.addToCartBtn = document.getElementById('ccd-submit-btn');
        // Product gallery
        this.wooGallery = document.querySelector('.woocommerce-product-gallery__wrapper > div');

        // Color logic
        this.selectedColor = '';
        this.colors = [];
        this.filteredColor = null;
        this.currentImg = '';

        this.events();
    }

    events() {
        // 1) Color changed => fetch or filter variations
        this.colorOptionsSelect.addEventListener('change', (e) => {
            this.selectedColor = e.target.value;
            const filtered = this.filterSelectedColor(this.selectedColor);
            this.buildSelectedColorSizes(filtered);

            // Update main product image if available
            if (filtered && filtered.variations.length > 0) {
                this.currentImg = filtered.variations[0].image;
                this.updateWooGalleryImage(this.currentImg);
            }

            this.toggleAddToCartButton();
        });

        // 2) Bind dynamic Step 3 fields
        // We find all elements with class "ccd-dynamic-field" in the DOM
        document.querySelectorAll('.ccd-dynamic-field').forEach((fieldEl) => {
            const fieldType = fieldEl.getAttribute('data-field-type');
            // ID or name
            const fieldName = fieldEl.name; // e.g. "right_chest_screen_print"

            // If it's a simple select with image, show/hide based on "HFH Logo" or "Blank"
            if (fieldType === 'select') {
                fieldEl.addEventListener('change', (e) => {
                    const val = e.target.value;
                    // Show/hide image container if present
                    const imgDivId = `ccd-addon-img-container-${fieldName}`;
                    const imgDiv = document.getElementById(imgDivId);
                    if (imgDiv) {
                        if (val.toLowerCase().includes('logo')) {
                            imgDiv.classList.remove('ccd-hidden');
                        } else {
                            imgDiv.classList.add('ccd-hidden');
                        }
                    }
                });
            }

            // If it's just a text input => no special logic unless you want some
            if (fieldType === 'text') {
                // e.g. you could do some validation or char counting
            }

            // If it's "select-and-text" => the user picks "yes"/"Left Chest" => show text field
            if (fieldType === 'select-and-text') {
                fieldEl.addEventListener('change', (e) => {
                    const val = e.target.value;
                    const containerId = `${fieldEl.id}-container`; // e.g. "ccd-department_name_back-container"
                    const containerEl = document.getElementById(containerId);
                    if (containerEl) {
                        // If they pick something other than 'none', show the text field
                        if (val !== 'none') {
                            containerEl.classList.remove('ccd-hidden');
                            // Optionally require the text input
                            const textInput = containerEl.querySelector('input[type="text"]');
                            if (textInput) {
                                textInput.required = true;
                            }
                        } else {
                            containerEl.classList.add('ccd-hidden');
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

    // Step 1: fetch color variations
    async getAllAvailableColors(id) {
        try {
            // If you want to send the nonce:
            // const res = await fetch(`${this.route}${id}`, {
            //   headers: { 'X-WP-Nonce': this.nonce }
            // });
            const res = await fetch(`${this.route}${id}`);
            const data = await res.json();
            this.colors = data;
            return data;
        } catch (err) {
            console.error('Error fetching variations:', err);
        }
    }

    buildSelectOptions(colors) {
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
        this.sizeOptionsContainer.innerHTML = '';

        if (item) {
            // Sort sizes logically
            item.variations.sort((a, b) => this.compareSizes(a.size, b.size));

            item.variations.forEach((v) => {
                const sizeItem = document.createElement('div');
                sizeItem.classList.add('sizes__item');

                const priceDiv = document.createElement('div');
                priceDiv.classList.add('sizes__price');
                priceDiv.textContent = `$${v.price}`;
                sizeItem.append(priceDiv);

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

                const sizeLabel = document.createElement('div');
                sizeLabel.classList.add('sizes__label');
                sizeLabel.textContent = v.size;
                sizeItem.append(sizeLabel);

                this.sizeOptionsContainer.append(sizeItem);
            });
        }
    }

    // "Logical" size sorting
    compareSizes(a, b) {
        const order = ['XS','S','M','L','XL','XXL','XXXL','4XL','5XL'];
        const aUp = a.toUpperCase();
        const bUp = b.toUpperCase();

        const iA = order.indexOf(aUp);
        const iB = order.indexOf(bUp);

        if (iA !== -1 && iB !== -1) {
            return iA - iB;
        }
        if (iA !== -1 && iB === -1) return -1;
        if (iA === -1 && iB !== -1) return 1;

        // Check numeric
        const numA = parseInt(a, 10);
        const numB = parseInt(b, 10);

        if (!isNaN(numA) && !isNaN(numB)) {
            return numA - numB;
        }
        if (!isNaN(numA)) return -1;
        if (!isNaN(numB)) return 1;

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
        const sizeInputs = this.sizeOptionsContainer.querySelectorAll('.sizes__input');
        const anySelected = Array.from(sizeInputs).some((inp) => parseInt(inp.value) > 0);
        this.addToCartBtn.disabled = !anySelected;
    }

    async init() {
        const colors = await this.getAllAvailableColors(this.parentProductID);
        if (colors && Array.isArray(colors)) {
            this.buildSelectOptions(colors);
        }
        this.addToCartBtn.disabled = true;
    }
}

// Start it up
const productOptions = new ProductOptions();
productOptions.init();
