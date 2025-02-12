<?php

namespace Webkul\BagistoPlugin\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\BagistoPlugin\Contracts\BagistoDataMapping as BagistoDataMappingContract;

class BagistoDataMapping extends Model implements BagistoDataMappingContract
{
    protected $table = 'wk_bagisto_data_mapping';

    protected $fillable = [
        'entity_type',
        'external_id',
        'job_instance_id',
        'related_id',
        'credential_id',
    ];
}
