<?php

namespace Webkul\AWSIntegration;

use Illuminate\Support\Facades\Storage;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\ProductImage as ProductImageFacade;

class ProductImage extends ProductImageFacade
{
    /**
     * ProductRepository instance
     *
     * @var \Webkul\Product\Repositories\ProductRepository
     */
    protected $productRepository;

    /**
     * Create a new helper instance.
     *
     * @param  \Webkul\Product\Repositories\ProductRepository  $productRepository
     * @return void
     */
    public function __construct(
        ProductRepository $productRepository
    )
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Retrieve collection of gallery images
     *
     * @param  \Webkul\Product\Contracts\Product|\Webkul\Product\Contracts\ProductFlat  $product
     * @return array
     */
    public function getGalleryImages($product)
    {
        if (! $product) {
            return [];
        }

        $images = [];

        foreach ($product->images as $image) {
            $bucketURL = \Config::get('app.asset_url');

            $images[] = [
                'small_image_url'    => $bucketURL .'/'. $image->path,
                'medium_image_url'   => $bucketURL .'/'. $image->path,
                'large_image_url'    => $bucketURL .'/'. $image->path,
                'original_image_url' => $bucketURL .'/'. $image->path,
            ];
        }

        if (! $product->parent_id && ! count($images) && ! count($product->videos)) {
            $images[] = [
                'small_image_url'    => asset('vendor/webkul/ui/assets/images/product/small-product-placeholder.webp'),
                'medium_image_url'   => asset('vendor/webkul/ui/assets/images/product/meduim-product-placeholder.webp'),
                'large_image_url'    => asset('vendor/webkul/ui/assets/images/product/large-product-placeholder.webp'),
                'original_image_url' => asset('vendor/webkul/ui/assets/images/product/large-product-placeholder.webp'),
            ];
        }

        return $images;
    }

    /**
     * Get product's base image
     *
     * @param  \Webkul\Product\Contracts\Product|\Webkul\Product\Contracts\ProductFlat  $product
     * @return array
     */
    public function getProductBaseImage($product, array $galleryImages = null)
    {
        $images = $product ? $product->images : null;

        $bucketURL = \Config::get('app.asset_url');

        if ($images && $images->count()) {
            $image = [
                'small_image_url'    => $bucketURL .'/'. $images[0]->path,
                'medium_image_url'   => $bucketURL .'/'. $images[0]->path,
                'large_image_url'    => $bucketURL .'/'. $images[0]->path,
                'original_image_url' => $bucketURL .'/'. $images[0]->path,
            ];
        } else {
            $image = [
                'small_image_url'    => asset('vendor/webkul/ui/assets/images/product/small-product-placeholder.webp'),
                'medium_image_url'   => asset('vendor/webkul/ui/assets/images/product/meduim-product-placeholder.webp'),
                'large_image_url'    => asset('vendor/webkul/ui/assets/images/product/large-product-placeholder.webp'),
                'original_image_url' => asset('vendor/webkul/ui/assets/images/product/large-product-placeholder.webp'),
            ];
        }

        return $image;
    }

    /**
     * Get product varient image if available otherwise product base image
     *
     * @param  \Webkul\Customer\Contracts\Wishlist  $item
     * @return array
     */
    public function getProductImage($item)
    {
        if ($item instanceof \Webkul\Customer\Contracts\Wishlist) {
            if (isset($item->additional['selected_configurable_option'])) {
                $product = $this->productRepository->find($item->additional['selected_configurable_option']);
            } else {
                $product = $item->product;
            }
        } else {
            $product = $item->product;
        }

        return $this->getProductBaseImage($product);
    }
}