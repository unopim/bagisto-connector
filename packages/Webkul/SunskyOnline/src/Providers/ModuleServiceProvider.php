<?php

namespace Webkul\SunskyOnline\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\SunskyOnline\Models\Configuration::class,
        \Webkul\SunskyOnline\Models\AttributeMapping::class,
    ];
}
