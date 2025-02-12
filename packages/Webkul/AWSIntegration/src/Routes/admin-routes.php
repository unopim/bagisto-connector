<?php

Route::group(['middleware' => ['web']], function () {
    Route::prefix('admin')->group(function () {
        Route::get('/aws', 'Webkul\AWSIntegration\Http\Controllers\AWSController@index')->defaults('_config', [
            'view' => 'aws::admin.index',
        ])->name('admin.aws.index');

        Route::get('/credentials', 'Webkul\AWSIntegration\Http\Controllers\AWSController@index')->defaults('_config', [
            'view' => 'aws::credentials.index',
        ])->name('admin.aws.credentials');

        Route::get('/publish-assets','Webkul\AWSIntegration\Helpers\AWSConfigure@publishAssetsToS3')->defaults('_config', [
            'view' => 'aws::admin.index',
        ])->name('admin.aws.publish.assets');

        Route::get('/sync-assets','Webkul\AWSIntegration\Helpers\AWSConfigure@syncAssets')->defaults('_config', [
            'view' => 'aws::admin.index',
        ])->name('admin.aws.sync.assets');
    });
});