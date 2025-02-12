<?php

namespace Webkul\TVCMall\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\TVCMall\Contracts\ProductAttributeMapping as Contract;

class ProductAttributeMappingRepository extends Repository
{
    /**
     * {@inheritDoc}
     */
    public function model(): string
    {
        return Contract::class;
    }
}
