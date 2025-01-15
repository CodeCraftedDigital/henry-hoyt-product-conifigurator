class ProductOptions {
    constructor() {
        // URL for your REST endpoint
        this.route = 'http://localhost:10319/wp-json/code/v1/get-variations/';

        // Grab elements
        this.form = document.getElementById('ccd-form');
        this.parentProductID = this.form.dataset.productId;

        this.colorOptionsSelect = document.getElementById('color-options');
        this.sizeOptionsContainer = document.getElementById('ccd-size__block');
        this.sizeMainContainer = document.getElementById('ccd-size__container');
        this.addToCartBtn = document.getElementById('ccd-submit-btn');
        this.wooGallery = document.querySelector('.woocommerce-product-gallery__wrapper > div');

        // For color images
        this.selectedColor = '';
        this.colors = [];
        this.filteredColor = null;
        this.currentImg = '';

        // Set up events
        this.events();
    }

    events() {
        // 1) When user changes the color, fetch the corresponding size variations
        this.colorOptionsSelect.addEventListener('change', (e) => {
            this.selectedColor = e.target.value;
            const filtered = this.filterSelectedColor(this.selectedColor);
            this.buildSelectedColorSizes(filtered);

            // If there's a product image, update the gallery
            if (filtered && filtered.variations.length > 0) {
                this.currentImg = filtered.variations[0].image;
                this.updateWooGalleryImage(this.currentImg);
            }

            this.toggleAddToCartButton();
        });

        // 2) BIND all dynamic fields from Step 3 (with class "ccd-dynamic-field")
        document.querySelectorAll('.ccd-dynamic-field').forEach((fieldEl) => {
            const fieldType = fieldEl.getAttribute('data-field-type');

            // If it's a select, handle "HFH Logo" => show image or not
            if (fieldType === 'select') {
                fieldEl.addEventListener('change', (e) => {
                    const val = e.target.value;
                    const imgContainerID = `ccd-addon-img-container-${e.target.name}`;
                    const imgDiv = document.getElementById(imgContainerID);
                    if (imgDiv) {
                        if (val === 'HFH Logo') {
                            imgDiv.classList.remove('ccd-hidden');
                        } else {
                            imgDiv.classList.add('ccd-hidden');
                        }
                    }
                });
            }

            // If it's select-and-text
            if (fieldType === 'select-and-text') {
                fieldEl.addEventListener('change', (e) => {
                    const val = e.target.value;
                    // The hidden container is "#ccd-[post_field]-container"
                    const containerID = `${e.target.id}-container`;
                    const containerEl = document.getElementById(containerID);
                    if (containerEl) {
                        if (val === 'yes' || val === 'Left Chest') {
                            containerEl.classList.remove('ccd-hidden');
                            // Optionally mark input required:
                            containerEl.querySelector('input').required = true;
                        } else {
                            containerEl.classList.add('ccd-hidden');
                            containerEl.querySelector('input').required = false;
                        }
                    }
                });
            }

            // text type => no special logic needed unless you want something
        });
    }

    // For updating the gallery image in Step 1
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

    // The color/variation logic
    async getAllAvailableColors(id) {
        try {
            const res = await fetch(`${this.route}${id}`);
            const data = await res.json();
            this.colors = data;
            return data;
        } catch (e) {
            console.error('Error fetching variations:', e);
        }
    }

    buildSelectOptions(colors) {
        colors.forEach((colorObj) => {
            const opt = document.createElement('option');
            opt.setAttribute('value', colorObj.color);
            opt.innerText = colorObj.color;
            this.colorOptionsSelect.append(opt);
        });
    }

    filterSelectedColor(selectedColor) {
        const filtered = this.colors.filter((c) => c.color === selectedColor);
        this.filteredColor = filtered[0] || null;
        return this.filteredColor;
    }

    buildSelectedColorSizes(item) {
        this.sizeOptionsContainer.innerHTML = ''; // Clear previous

        if (item) {
            item.variations.forEach((variation) => {
                // Container
                const sizeItem = document.createElement('div');
                sizeItem.classList.add('sizes__item');

                // Price
                const priceDiv = document.createElement('div');
                priceDiv.classList.add('sizes__price');
                priceDiv.textContent = `$${variation.price}`;
                sizeItem.append(priceDiv);

                // Input
                const sizeBox = document.createElement('div');
                sizeBox.classList.add('sizes__box');
                const sizeInput = document.createElement('input');
                sizeInput.type = 'number';
                sizeInput.name = `size_quantities[${variation.variation_id}]`;
                sizeInput.min = 0;
                sizeInput.step = 1;
                sizeInput.value = 0;
                sizeInput.classList.add('sizes__input');
                sizeInput.addEventListener('input', () => {
                    this.toggleAddToCartButton();
                });
                sizeBox.append(sizeInput);
                sizeItem.append(sizeBox);

                // Label
                const sizeLabel = document.createElement('div');
                sizeLabel.classList.add('sizes__label');
                sizeLabel.textContent = variation.size;
                sizeItem.append(sizeLabel);

                this.sizeOptionsContainer.append(sizeItem);
            });
        }
    }

    // If no size is selected, disable "Add to Cart"
    toggleAddToCartButton() {
        const sizeInputs = this.sizeOptionsContainer.querySelectorAll('.sizes__input');
        const isAnySelected = Array.from(sizeInputs).some(inp => parseInt(inp.value) > 0);
        this.addToCartBtn.disabled = !isAnySelected;
    }

    async init() {
        const colors = await this.getAllAvailableColors(this.parentProductID);
        this.buildSelectOptions(colors);
        this.addToCartBtn.disabled = true;
    }
}

// Initialize
const productOptions = new ProductOptions();
productOptions.init();
