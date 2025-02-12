<?php

namespace Webkul\Bagisto\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BagistoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Routes/bagisto-routes.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'bagisto');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'bagisto');

        Event::listen('unopim.admin.layout.head', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('bagisto::style');
        });

        $this->publishes([
            __DIR__.'/../../publishable' => public_path('themes'),
        ], 'unopim-bagisto-connector');

        $this->app->register(ModuleServiceProvider::class);

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register any application services
     */
    public function register()
    {
        $this->registerConfig();
    }

    /**
     * Register package configurations
     */
    public function registerConfig()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        /** API EndPoint Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/api-end-point.php', 'bagisto-api-end-point');

        /** Bagisto Attributes Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/bagisto-attributes.php', 'bagisto-attributes');

        /** Bagisto Category Fields Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/bagisto-category-fields.php', 'bagisto-category-fields');

        /** Bagisto export Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/exporters.php', 'exporters');

        /** ACL Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        /** Bagisto Unopim Vite Config */
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/unopim-vite.php', 'unopim-vite.viters');
    }
}
