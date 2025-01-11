<?php

class ProductVariationsFetcherImplementation implements ProductVariationsFetcher {

    public function get_product_variations($data) {
        $product_id = $data['product_id'];
        $product = wc_get_product($product_id);

        if (!$product || !$product->is_type('variable')) {
            return new WP_Error('invalid_product', 'Invalid product or not a variable product', ['status' => 400]);
        }

        $variations = $product->get_available_variations();
        $grouped_variations = [];

        foreach ($variations as $variation) {
            $color = $variation['attributes']['attribute_pa_color'];
            $size = $variation['attributes']['attribute_pa_size'];
            $image_id = $variation['image_id'];
            $image_url = wp_get_attachment_url($image_id);

            if (!isset($grouped_variations[$color])) {
                $grouped_variations[$color] = ['color' => $color, 'variations' => []];
            }

            $grouped_variations[$color]['variations'][] = [
                'variation_id' => $variation['variation_id'],
                'size' => $size,
                'price' => $variation['display_price'],
                'stock' => $variation['is_in_stock'] ? 'In Stock' : 'Out of Stock',
                'image' => $image_url,
            ];
        }

        return array_values($grouped_variations);
    }
}
