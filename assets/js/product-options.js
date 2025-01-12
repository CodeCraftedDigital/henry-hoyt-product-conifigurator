 class ProductOptions {
    constructor() {
        this.route = 'http://localhost:10319/wp-json/code/v1/get-variations/';
        this.form = document.getElementById('ccd-form');
        this.parentProductID = this.form.dataset.productId;
        this.colorOptionsSelect = document.getElementById('color-options');
        this.sizeOptionsContainer = document.getElementById('ccd-size__block');
        this.sizeMainContainer = document.getElementById('ccd-size__container');
        this.currentImg = '';
        this.addToCartBtn = document.getElementById('ccd-submit-btn');
        this.selectedColor = '';
        this.colors = [];
        this.filteredColor = null;

        //Product Add Ons
        // this.rightChestLogoEnabled = '';
        this.rightChestLogoSelectorSp = document.getElementById('ccd-right-chest-logo-sp');
        this.rightChestLogoContainerSp = document.getElementById('ccd-addon-img-container-sp');

        this.rightChestLogoSelectorEm = document.getElementById('ccd-right-chest-logo-em');
        this.rightChestLogoContainerEm = document.getElementById('ccd-addon-img-container-em');


        this.events();
        this.wooGallery = document.querySelector('.woocommerce-product-gallery__wrapper > div');
        // this.wooGalleryImg = document.querySelector('.woocommerce-product-gallery__wrapper > div');
        // console.log(this.wooGallery);



    }

    events() {
        this.colorOptionsSelect.addEventListener('change', (e) => {
            this.selectedColor = e.target.value;
            console.log('Selected color:', this.selectedColor);

            // Call filter and log the filtered result
            const filtered = this.filterSelectedColor(this.selectedColor);

            this.buildSelectedColorSizes(filtered);
            // Woo Img
            this.currentImg = filtered.variations[0].image;
            console.log(this.currentImg);
            this.wooGallery.setAttribute('data-thumb', `${this.currentImg}`)
            this.wooGallery.setAttribute('data-thumb-srcset', `${this.currentImg}`)
            this.wooGallery.classList.remove('flex-active-slide');
            this.wooGallery.firstChild.setAttribute('href', `${this.currentImg}`);
            this.wooGallery.firstChild.firstChild.setAttribute('href', `${this.currentImg}`);
            this.wooGallery.firstChild.firstChild.setAttribute('data-src', `${this.currentImg}`);
            this.wooGallery.firstChild.firstChild.setAttribute('data-large_image', `${this.currentImg}`);
            this.wooGallery.firstChild.firstChild.setAttribute('srcset', `${this.currentImg}`);
            // Handle Button State
            this.toggleAddToCartButton();
        });

        this.rightChestLogoSelectorSp.addEventListener('change', (e) => {
            this.handleRightChestLogoSp(e.target.value);
        });

        this.rightChestLogoSelectorEm.addEventListener('change', (e) => {
            this.handleRightChestLogoEm(e.target.value);
        });




    }

     handleRightChestLogoSp(selectedOption) {
         if (selectedOption === 'HFH Logo') {
             this.rightChestLogoContainerSp.classList.remove('ccd-hidden');
         } else {
             this.rightChestLogoContainerSp.classList.add('ccd-hidden');
         }
     }


     handleRightChestLogoEm(selectedOption) {
         if (selectedOption === 'HFH Logo') {
             this.rightChestLogoContainerEm.classList.remove('ccd-hidden');
         } else {
             this.rightChestLogoContainerEm.classList.add('ccd-hidden');
         }
     }

    async getAllAvailableColors(id) {
        try {
            const res = await fetch(`${this.route}${id}`);
            const data = await res.json();
            console.log(data);
            this.colors = data;
            return data;
        } catch (e) {
            console.log(e.message);
        }
    }

    buildSelectOptions(colors) {
        colors.forEach((color) => {
            const colorOptions = document.createElement('option');
            colorOptions.setAttribute("name", `${color.color}`);
            colorOptions.innerText = color.color;
            colorOptions.value = color.color;
            this.colorOptionsSelect.append(colorOptions);
        });
    }

    filterSelectedColor(selectedColor) {
        const filtered = this.colors.filter((color) => color.color === selectedColor);
        this.filteredColor = filtered[0] || null;
        return this.filteredColor;
    }


    buildSelectedColorSizes(item) {
        this.sizeOptionsContainer.innerHTML = ''; // Clear previous sizes

        if (item) {
            console.log('Available sizes for', item.color);
            item.variations.forEach((variation) => {
                // Create the wrapper for each size item
                const sizeItem = document.createElement('div');
                sizeItem.classList.add('sizes__item');

                // Create and append the price div
                const priceDiv = document.createElement('div');
                priceDiv.classList.add('sizes__price');
                priceDiv.textContent = `$${variation.price}`; // Assuming price is available
                sizeItem.append(priceDiv);

                // Create the box for the input (quantity)
                const sizeBox = document.createElement('div');
                sizeBox.classList.add('sizes__box');

                const sizeInput = document.createElement('input');
                sizeInput.setAttribute("type", "number");
                sizeInput.setAttribute("name", `size_quantities[${variation.variation_id}]`);
                sizeInput.setAttribute("min", "0");
                sizeInput.setAttribute("step", "1");
                sizeInput.value = 0; // Default value is 0
                sizeInput.classList.add('sizes__input');

                // Add change event listener to monitor the input
                sizeInput.addEventListener('input', () => {
                    this.toggleAddToCartButton();  // Check the button state whenever a size input changes
                });

                sizeBox.append(sizeInput);
                sizeItem.append(sizeBox);

                // Create and append the label for the size
                const sizeLabel = document.createElement('div');
                sizeLabel.classList.add('sizes__label');
                sizeLabel.textContent = variation.size; // Display size name
                sizeItem.append(sizeLabel);

                // Append the size item to the container
                this.sizeOptionsContainer.append(sizeItem);
            });
        } else {
            console.log('No color selected or color not found');
        }
    }

    // Toggle the "Add to Cart" button based on size quantities
    toggleAddToCartButton() {
        const sizeInputs = this.sizeOptionsContainer.querySelectorAll('.sizes__input');
        const isAnySizeSelected = Array.from(sizeInputs).some(input => input.value > 0);

        // Enable or disable the button based on quantity input values
        if (isAnySizeSelected) {
            this.addToCartBtn.disabled = false;
        } else {
            this.addToCartBtn.disabled = true;
        }
    }

    async init() {
        const colors = await this.getAllAvailableColors(this.parentProductID);
        this.buildSelectOptions(colors);
        this.addToCartBtn.disabled = true;  // Disable button initially
    }
}

 const productOptions = new ProductOptions();
 productOptions.init();
