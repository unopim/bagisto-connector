<?php

use Illuminate\Support\Facades\Route;
use Webkul\SunskyOnline\Http\Controllers\AttributeController;
use Webkul\SunskyOnline\Http\Controllers\ConfigurationController;

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix'     => config('app.admin_url'),
], function () {

    Route::prefix('sunsky-online')->group(function () {

        // Configuration Routes
        Route::prefix('configuration')
            ->controller(ConfigurationController::class)
            ->group(function () {
                Route::get('', 'index')->name('sunsky_online.configuration.index');
                Route::put('', 'update')->name('sunsky_online.configuration.update');
                Route::get('locales', 'listLocale')->name('sunsky_online.locales.index');
            });

        // Attribute Mapping Routes
        Route::prefix('attribute-mapping')
            ->controller(AttributeController::class)
            ->group(function () {
                Route::get('/{id}', 'index')->name('sunsky_online.mappings.attributes.index');
                Route::post('store-or-update', 'storeOrUpdate')->name('sunsky_online.mappings.attributes.store_or_update');
                Route::post('add-attributes', 'addAdditionalAttributes')->name('sunsky_online.attributes.add_attributes');
                Route::post('remove-attributes', 'removeAdditionalAttributes')->name('sunsky_online.attributes.remove_attributes');
            });
    });

});
