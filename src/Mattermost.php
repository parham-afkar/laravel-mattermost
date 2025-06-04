<?php

namespace ParhamAfkar\MattermostLogger;

use Monolog\Logger;

class Mattermost
{
    public function __invoke(array $config)
    {
        $handler = new MattermostHandler([
            'webhook_url' => $config['webhook_url'] ?? config('mattermost-logger.webhook_url'),
            'channel' => $config['channel'] ?? config('mattermost-logger.channel'),
            'username' => $config['username'] ?? config('mattermost-logger.username'),
            'icon_url' => $config['icon_url'] ?? config('mattermost-logger.icon_url'),
        ], Logger::toMonologLevel($config['level'] ?? 'debug'));

        $logger = new Logger('mattermost');
        $logger->pushHandler($handler);

        return $logger;
    }
}
