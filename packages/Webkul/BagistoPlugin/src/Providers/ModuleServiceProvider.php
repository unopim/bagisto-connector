<?php

namespace Webkul\BagistoPlugin\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\BagistoPlugin\Models\Credential::class,
        \Webkul\BagistoPlugin\Models\AttributeMapping::class,
        \Webkul\BagistoPlugin\Models\CategoryFieldMapping::class,
        \Webkul\BagistoPlugin\Models\BagistoDataMapping::class,
    ];
}
