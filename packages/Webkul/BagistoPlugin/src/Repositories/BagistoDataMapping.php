<?php

namespace Webkul\BagistoPlugin\Repositories;

use Webkul\Core\Eloquent\Repository;

class BagistoDataMapping extends Repository
{
    public function model(): string
    {
        return 'Webkul\BagistoPlugin\Contracts\BagistoDataMapping';
    }
}
