<?php

namespace Webkul\TVCMall\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\TVCMall\Contracts\ProductAttributeMapping as ProductAttributeMappingContract;

class ProductAttributeMapping extends Model implements ProductAttributeMappingContract
{
    use HasFactory;

    protected $table = 'tvc_mall_product_attribute_mappings';

    /**
     * @var array
     */
    protected $fillable = [
        'unopim_code',
        'tvc_mall_code'
    ];
}
