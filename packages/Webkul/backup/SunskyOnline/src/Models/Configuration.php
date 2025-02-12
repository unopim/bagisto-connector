<?php

namespace Webkul\SunskyOnline\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\SunskyOnline\Contracts\Configurations as ConfigurationContract;

class Configuration extends Model implements ConfigurationContract, HistoryContract, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    protected $table = 'sunsky_online_configurations';

    protected $historyTags = ['sunsky_online_configurations'];

    protected $auditExclude = ['secret'];

    /**
     * @var array
     */
    protected $fillable = [
        'baseUrl', 'key', 'secret', 'additional',
    ];

    /**
     * custom history presenters to be used while displaying the history for that column
     */
    public static function getPresenters(): array
    {
        return [
            \Webkul\SunskyOnline\Models\Configuration::class,
        ];
    }
}
