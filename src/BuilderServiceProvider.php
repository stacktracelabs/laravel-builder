<?php


namespace StackTrace\Builder;


use Illuminate\Support\ServiceProvider;
use StackTrace\Builder\Commands\FetchCommand;
use StackTrace\Builder\Commands\RefreshCommand;

class BuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database');

        $this->mergeConfigFrom(__DIR__.'/../config/builder.php', 'builder');

        $this->loadRoutesFrom(__DIR__.'/../routes/builder.php');

        $this->app->singleton('builder.io', BuilderService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FetchCommand::class,
                RefreshCommand::class,
            ]);
        }
    }
}
