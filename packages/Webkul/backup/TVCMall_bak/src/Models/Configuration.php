<?php

namespace Webkul\TVCMall\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\TVCMall\Contracts\Configuration as ConfigurationContract;

class Configuration extends Model implements ConfigurationContract
{
    use HasFactory;

    protected $table = 'tvc_mall_configurations';

    /**
     * @var array
     */
    protected $fillable = [
        'baseUrl',
        'email',
        'password',
        'token'
    ];
}
