<?php

use Webkul\AWSIntegration\ProductImage;

if (! function_exists('productimage')) {
    function productimage() {
        return app()->make(ProductImage::class);
    }
}
?>