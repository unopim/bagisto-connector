<?php

namespace Webkul\Theme\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Webkul\Theme\Themes;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/../Http/helpers.php';

        Blade::directive('unoPimVite', function ($expression) {
            return "<?php echo themes()->setUnoPimVite({$expression})->toHtml(); ?>";
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('themes', function () {
            return new Themes;
        });

        $this->app->singleton('view.finder', function ($app) {
            return new \Webkul\Theme\ThemeViewFinder(
                $app['files'],
                $app['config']['view.paths'],
                null
            );
        });
    }
}
