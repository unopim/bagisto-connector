<?php

namespace Webkul\TVCMall\Providers;

use Konekt\Concord\BaseModuleServiceProvider;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        \Webkul\TVCMall\Models\Configuration::class,
        \Webkul\TVCMall\Models\ProductAttributeMapping::class,
    ];
}
