<?php

namespace Webkul\TVCMall\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\TVCMall\Contracts\Configuration as ConfigurationContract;

class ConfigurationRepository extends Repository
{
    /**
     * {@inheritDoc}
     */
    public function model(): string
    {
        return ConfigurationContract::class;
    }
}
