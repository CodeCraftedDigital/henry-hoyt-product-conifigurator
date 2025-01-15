<?php

namespace CodeCraftedDigital\ACFWooAddOns;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ProductVariationsFetcherImplementation
 *
 * Fetches and groups product variations by color for use in the front-end JS.
 */
class ProductVariationsFetcherImplementation implements ProductVariationsFetcher {

    /**
     * get_product_variations
     *
     * Receives REST request data (contains 'product_id'), fetches variations, and
     * returns them grouped by color (and includes price, stock status, image, etc.).
     *
     * @param array $data
     * @return array|WP_Error
     */
    public function get_product_variations( $data ) {
        $product_id = $data['product_id'];
        $product    = wc_get_product( $product_id );

        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            return new WP_Error(
                'invalid_product',
                'Invalid product or not a variable product',
                [ 'status' => 400 ]
            );
        }

        $variations         = $product->get_available_variations();
        $grouped_variations = [];

        foreach ( $variations as $variation ) {
            // You can adjust attribute keys (e.g. 'attribute_pa_color') per your needs:
            $color    = isset( $variation['attributes']['attribute_pa_color'] )
                ? $variation['attributes']['attribute_pa_color']
                : 'no-color';

            $size     = isset( $variation['attributes']['attribute_pa_size'] )
                ? $variation['attributes']['attribute_pa_size']
                : 'no-size';

            $image_id = $variation['image_id'];
            $image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';

            if ( ! isset( $grouped_variations[ $color ] ) ) {
                $grouped_variations[ $color ] = [
                    'color'      => $color,
                    'variations' => [],
                ];
            }

            $grouped_variations[ $color ]['variations'][] = [
                'variation_id' => $variation['variation_id'],
                'size'         => $size,
                'price'        => $variation['display_price'],
                'stock'        => $variation['is_in_stock'] ? 'In Stock' : 'Out of Stock',
                'image'        => $image_url,
            ];
        }

        // Return the grouped variations as an indexed array
        return array_values( $grouped_variations );
    }
}
