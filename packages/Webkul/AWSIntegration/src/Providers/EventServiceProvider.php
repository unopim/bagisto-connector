<?php

namespace Webkul\AWSIntegration\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen('core.configuration.save.after', 'Webkul\AWSIntegration\Helpers\AWSConfigure@uploadToS3');
    }
}