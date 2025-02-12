<?php

namespace Webkul\SunskyOnline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SunskyLanguage extends Model
{
    /**
     * Static array to hold the list of languages.
     */
    private static $languages = [
        'en'     => 'English',
        'ru'     => 'русский язык',
        'fr'     => 'Français',
        'es'     => 'Español',
        'pt'     => 'Português',
        'de'     => 'Deutsche',
        'it'     => 'Italiano',
        'nl'     => 'Nederlands',
        'ar'     => 'عربي',
        'vi'     => 'Tiếng Việt',
        'th'     => 'ไทย',
        'ko'     => '한국어',
        'ja'     => '日本語',
        'zh_CN'  => '中文简体',
        'zh_TW'  => '中文繁体',
    ];

    /**
     * Get all languages as a Collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAll()
    {
        return self::toCollection(self::$languages);
    }

    /**
     * Get a specific language by code as a Collection.
     *
     * @param  string  $code
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByCode($code)
    {
        $result = isset(self::$languages[$code])
            ? [$code => self::$languages[$code]]
            : [];

        return self::toCollection($result);
    }

    /**
     * Convert an array to Eloquent Collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function toCollection(array $data)
    {
        return (new static)->newCollection(
            collect($data)->map(function ($value, $key) {
                return (object) ['id' => $key, 'code' => $key, 'name' => $value.' - '.$key];
            })->values()->all()
        );
    }
}
