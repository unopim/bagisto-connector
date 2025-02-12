<?php

namespace Webkul\SunskyOnline\Providers;

use Illuminate\Support\ServiceProvider;

class SunskyOnlineProvider extends ServiceProvider
{
    /**
     * Bootstrap services
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/routes.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'sunsky-online');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'sunsky_online');

    }

    /**
     * register services
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/importers.php', 'importers'
        );

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/sunskyAttributes.php', 'sunsky-attributes');
    }
}
