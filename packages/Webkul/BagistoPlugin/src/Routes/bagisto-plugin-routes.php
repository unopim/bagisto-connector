<?php

use Illuminate\Support\Facades\Route;
use Webkul\BagistoPlugin\Http\Controllers\CredentialController;
use Webkul\BagistoPlugin\Http\Controllers\Mappings\AttributeController;
use Webkul\BagistoPlugin\Http\Controllers\Mappings\CategoryFieldController;
use Webkul\BagistoPlugin\Http\Controllers\OptionController;

/**
 * bagisto plugin routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::prefix('bagisto-plugin')->group(function () {
        Route::controller(CredentialController::class)->group(function () {
            Route::prefix('credentials')->group(function () {
                Route::get('', 'index')->name('admin.bagisto_plugin.credentials.index');

                Route::get('create', 'create')->name('admin.bagisto_plugin.credentials.create');

                Route::post('create', 'store')->name('admin.bagisto_plugin.credentials.store');

                Route::get('edit/{id}', 'edit')->name('admin.bagisto_plugin.credentials.edit');

                Route::put('update/{id}', 'update')->name('admin.bagisto_plugin.credentials.update');

                Route::delete('{id}', 'destroy')->name('admin.bagisto_plugin.credentials.destroy');
            });
        });

        /** Attribute Mapping */
        Route::controller(AttributeController::class)->group(function () {
            Route::prefix('attributes-mapping')->group(function () {
                Route::get('/{id}', 'index')->name('admin.bagisto_plugin.mappings.attributes.index');
                Route::post('storeOrUpdate', 'storeOrUpdate')->name('admin.bagisto_plugin.mappings.attributes.store');
                Route::post('add-attributes', 'addAdditionalAttributes')->name('admin.bagisto_plugin.attributes.add');
                Route::post('remove-attributes', 'removeAdditionalAttributes')->name('admin.bagisto_plugin.attributes.remove');
            });
        });

        /** Category Fields Mapping */
        Route::controller(CategoryFieldController::class)->group(function () {
            Route::prefix('category-fields-mapping')->group(function () {
                Route::get('/{id}', 'index')->name('admin.bagisto_plugin.mappings.category_fields.index');
                Route::post('storeOrUpdate', 'storeOrUpdate')->name('admin.bagisto_plugin.mappings.category_fields.store');
            });
        });

        /** Get option data */
        Route::controller(OptionController::class)->group(function () {
            Route::get('get-bagisto-credentials', 'listBagistoCredential')->name('bagisto.credential.fetch-all');

            Route::get('get-bagisto-channel', 'listChannel')->name('bagisto.channel.fetch-all');

            Route::get('get-bagisto-currency', 'listCurrency')->name('bagisto.currency.fetch-all');

            Route::get('get-bagisto-locale', 'listLocale')->name('bagisto.locale.fetch-all');
        });

    });
});
