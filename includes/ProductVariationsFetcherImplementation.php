<?php

namespace CodeCraftedDigital\ACFWooAddOns;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ProductVariationsFetcherImplementation
 *
 * Fetches available variations for a variable product and groups them by color.
 * It checks for either `attribute_pa_color` or `attribute_pa_color-options`.
 * If neither is found, defaults to `no-color`.
 */
class ProductVariationsFetcherImplementation implements ProductVariationsFetcher {

    /**
     * get_product_variations
     *
     * Receives REST request data (contains 'product_id'), fetches variations,
     * and returns them grouped by color (including fallback logic).
     *
     * @param array $data
     * @return array|\WP_Error
     */
    public function get_product_variations( $data ) {
        $product_id = $data['product_id'];
        $product    = wc_get_product( $product_id );

        // Validate product
        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return new WP_Error(
                'invalid_product',
                'Invalid product or not a variable product',
                [ 'status' => 400 ]
            );
        }

        // Fetch available variations
        $variations = $product->get_available_variations();
        $grouped    = [];

        foreach ( $variations as $variation ) {
            // 1) Attempt to get color from "attribute_pa_color"
            $color = isset( $variation['attributes']['attribute_pa_color'] )
                ? $variation['attributes']['attribute_pa_color']
                : '';

            // 2) If empty, try "attribute_pa_color-options" (slug: color-options)
            if ( ! $color ) {
                if ( isset( $variation['attributes']['attribute_pa_color-options'] )
                    && $variation['attributes']['attribute_pa_color-options'] ) {
                    $color = $variation['attributes']['attribute_pa_color-options'];
                } else {
                    $color = 'no-color';
                }
            }

            // 3) Assume size is always "attribute_pa_size"
            $size = isset( $variation['attributes']['attribute_pa_size'] )
                ? $variation['attributes']['attribute_pa_size']
                : 'no-size';

            // 4) Image URL
            $image_id  = $variation['image_id'];
            $image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';

            // 5) Group by $color
            if ( ! isset( $grouped[ $color ] ) ) {
                $grouped[ $color ] = [
                    'color'      => $color,
                    'variations' => [],
                ];
            }

            $grouped[ $color ]['variations'][] = [
                'variation_id' => $variation['variation_id'],
                'size'         => $size,
                'price'        => $variation['display_price'],
                'stock'        => $variation['is_in_stock'] ? 'In Stock' : 'Out of Stock',
                'image'        => $image_url,
            ];
        }

        // Return as an indexed array of color groups
        return array_values( $grouped );
    }
}
