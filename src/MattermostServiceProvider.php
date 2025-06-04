<?php

namespace ParhamAfkar\MattermostLogger;

use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Monolog\Handler\HandlerInterface;

class MattermostServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/mattermost-logger.php' => config_path('mattermost-logger.php'),
        ], 'config');

        // Extend the logging system
        $this->app['log']->extend('mattermost', function ($app, array $config) {
            $handler = new MattermostHandler([
                'webhook_url' => $config['webhook_url'] ?? config('mattermost-logger.webhook_url'),
                'channel' => $config['channel'] ?? config('mattermost-logger.channel'),
                'username' => $config['username'] ?? config('mattermost-logger.username'),
                'icon_url' => $config['icon_url'] ?? config('mattermost-logger.icon_url'),
            ], Logger::toMonologLevel($config['level'] ?? 'debug'));

            $logger = new Logger('mattermost');
            $logger->pushHandler($handler);

            return $logger;
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mattermost-logger.php', 'mattermost-logger');
    }
}
