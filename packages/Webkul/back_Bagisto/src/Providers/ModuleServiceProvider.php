<?php

namespace Webkul\Bagisto\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Bagisto\Models\Credential::class,
        \Webkul\Bagisto\Models\AttributeMapping::class,
        \Webkul\Bagisto\Models\CategoryFieldMapping::class,
        \Webkul\Bagisto\Models\BagistoDataMapping::class,
    ];
}
