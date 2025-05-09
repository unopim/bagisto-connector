<?php

namespace Webkul\Bagisto\Listeners;

use Illuminate\Support\Facades\Cache;
use Webkul\Bagisto\Enums\Export\CacheType;

class Export
{
    public function afterUpdate($export)
    {
        $types = [
            'bagisto_product',
            'bagisto_categories',
            'bagisto_attribute',
            'bagisto_attribute_families',
        ];

        if (in_array($export->entity_type, $types)) {
            // if exist in cache then remove from cache
            Cache::forget(CacheType::CREDENTIAL->value);
            Cache::forget(CacheType::JOB_FILTERS->value);
        }
    }
}
