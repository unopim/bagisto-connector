<?php

namespace Webkul\AWSIntegration\Providers;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;
use Webkul\AWSIntegration\Helpers\AWSConfigure;
use Illuminate\Foundation\AliasLoader;
use Webkul\Product\Facades\ProductImage as ProductImageFacade;
use Webkul\Product\ProductImage;
use Illuminate\Routing\Router;

class AWSIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin-routes.php');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'aws');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'aws');

        $awsConfigure = new AWSConfigure;

        $awsConfigure->uploadToS3();
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        include __DIR__ . '/../Http/helpers.php';

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/admin-menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/acl.php', 'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php', 'core'
        );

        $this->registerFacades();
    }

    /**
     * Register facades
     */
    protected function registerFacades()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('productimage', ProductImageFacade::class);

        $this->app->singleton('productimage', function () {
            return app()->make(ProductImage::class);
        });

        $this->app->bind('productimage', 'Webkul\AWSIntegration\ProductImage');
    }
}