<?php

namespace Webkul\BagistoPlugin\Listeners;

use Illuminate\Support\Facades\Cache;
use Webkul\BagistoPlugin\Enums\Export\CacheType;

class Export
{
    public function afterUpdate($export)
    {
        if ($export->entity_type == 'bagisto_categories') {
            // if exist in cache then remove from cache
            Cache::forget(CacheType::CREDENTIAL->value);
        }
    }
}
