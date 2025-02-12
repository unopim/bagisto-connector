<?php

use Illuminate\Support\Facades\Route;
use Webkul\TVCMall\Http\Controllers\ConfigurationController;
use Webkul\TVCMall\Http\Controllers\ProductAttributeMappingController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => config('app.admin_url')], function () {

    Route::controller(ConfigurationController::class)->prefix('tvc-mall/configure')->group(function () {
        Route::get('', 'index')->name('tvc_mall.configuration.index');

        Route::put('', 'update')->name('tvc_mall.configuration.update');
    });

    Route::controller(ProductAttributeMappingController::class)->prefix('tvc-mall/product-attribute-mapping')->group(function () {
        Route::get('', 'index')->name('tvc_mall.product-attribute-mapping.index');

        Route::post('/store', 'store')->name('tvc_mall.product-attribute-mapping.store');

        Route::delete('/delete/{id}', 'destroy')->name('tvc_mall.product-attribute-mapping.delete');

        Route::post('/mass-delete', 'massDestroy')->name('tvc_mall.product-attribute-mapping.mass-delete');
    });
});
