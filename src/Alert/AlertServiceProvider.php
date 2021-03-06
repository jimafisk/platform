<?php

declare(strict_types=1);

namespace Orchid\Alert;

use Illuminate\Support\ServiceProvider;

/**
 * Class AlertServiceProvider.
 */
class AlertServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->bind(SessionStoreInterface::class, LaravelSessionStore::class);

        $this->app->singleton('alert', function () {
            return $this->app->make(Alert::class);
        });
    }
}
