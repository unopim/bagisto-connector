<?php

namespace Webkul\TVCMall\Providers;

use Illuminate\Support\ServiceProvider;

class TVCMallServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__ . '/../Routes/routes.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'tvc_mall');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'tvc_mall');

        $this->app->register(ModuleServiceProvider::class);
    }

    /**
     * register services
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/importers.php', 'importers'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/tvcmall_product_attributes.php', 'tvcmall_product_attributes'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php',
            'acl'
        );
    }
}
