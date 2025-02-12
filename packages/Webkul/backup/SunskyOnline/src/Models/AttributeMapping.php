<?php

namespace Webkul\SunskyOnline\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\SunskyOnline\Contracts\AttributeMapping as AttributeMappingContract;
use Webkul\SunskyOnline\Presenters\JsonDataPresenter;

class AttributeMapping extends Model implements AttributeMappingContract, HistoryContract, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $historyTags = ['sunsky_attribute_mapping'];

    /**
     * The database table used by model
     *
     * @var string
     */
    protected $table = 'sunsky_attribute_mapping';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'section',
        'mapped_value',
        'fixed_value',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'mapped_value' => 'json',
        'fixed_value'  => 'json',
    ];

    public static function getPresenters(): array
    {
        return [
            'fixed_value'  => JsonDataPresenter::class,
            'mapped_value' => JsonDataPresenter::class,
        ];
    }
}
