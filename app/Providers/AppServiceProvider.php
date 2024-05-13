<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // determine the length of email string
        Schema::defaultStringLength(191);

        // everything should be strict, all the time on the database Models
        Model::shouldBeStrict();

        // in production, log lazy loading violation instead.

        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        if ($app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
                $class = get_class($model);

                info("Attempted to lazy load [{$relation} on model [{$class}]]");
            });
        }
    }
}
