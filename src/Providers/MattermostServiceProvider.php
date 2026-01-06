<?php

namespace ParhamAfkar\MattermostLogger\Providers;

use Illuminate\Support\ServiceProvider;
use ParhamAfkar\MattermostLogger\Console\Commands\MattermostTestCommand;
use ParhamAfkar\MattermostLogger\Console\Commands\MattermostListChannelsCommand;
use ParhamAfkar\MattermostLogger\Console\Commands\MattermostDebugCommand;
use ParhamAfkar\MattermostLogger\Console\Commands\MattermostMyChannelsCommand;
use ParhamAfkar\MattermostLogger\Services\MattermostLogger;

class MattermostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/mattermost.php', 'mattermost');

        $this->app->singleton('mattermost', function ($app) {
            return new MattermostLogger(config('mattermost', []));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MattermostTestCommand::class,
                MattermostListChannelsCommand::class,
                MattermostDebugCommand::class,
                MattermostMyChannelsCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../config/mattermost.php' => config_path('mattermost.php'),
            ], 'mattermost-config');
        }

        $this->app->make('log')->extend('mattermost', function ($app, array $config) {
            return (new \ParhamAfkar\MattermostLogger\LogChannel())($config);
        });
    }
}
