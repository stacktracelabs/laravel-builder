<?php


namespace StackTrace\Builder;


use Illuminate\Support\ServiceProvider;

class BuilderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/builder.php');

        $this->loadMigrationsFrom(__DIR__.'/../database');

        $this->mergeConfigFrom(__DIR__.'/../config/builder.php', 'builder');

        $this->app->singleton('builder.io', BuilderService::class);
    }

}
