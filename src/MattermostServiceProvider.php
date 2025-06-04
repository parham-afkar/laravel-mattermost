<?php

namespace ParhamAfkar\MattermostLogger;

use Illuminate\Support\ServiceProvider;
use Monolog\Logger;

class MattermostServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/mattermost.php' => config_path('mattermost.php'),
        ], 'config');

        $this->app['log']->extend('mattermost', function ($app, array $config) {
            return (new Mattermost())($config);
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mattermost.php', 'mattermost');

        $this->app->singleton('mattermost', function ($app) {
            return new \ParhamAfkar\MattermostLogger\MattermostLogger();
        });
    }
}