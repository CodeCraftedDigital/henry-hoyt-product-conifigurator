<?php

namespace CodeCraftedDigital\ACFWooAddOns;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface ProductVariationsFetcher
 *
 * Any class responsible for retrieving grouped variations
 * for a variable product should implement this interface.
 */
interface ProductVariationsFetcher {
    /**
     * get_product_variations
     *
     * @param array $data (usually contains 'product_id')
     * @return array|\WP_Error
     */
    public function get_product_variations( $data );
}
