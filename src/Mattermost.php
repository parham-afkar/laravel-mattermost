<?php

namespace ParhamAfkar\MattermostLogger;

use Monolog\Logger;

class Mattermost
{
    public function __invoke(array $config)
    {
        $type = $config['type'] ?? config('mattermost.type', 'bot');
        
        if ($type === 'bot') {
            $handler = new MattermostBotHandler([
                'base_url' => $config['base_url'] ?? config('mattermost.base_url'),
                'bot_token' => $config['bot_token'] ?? config('mattermost.bot_token'),
                'channel_prefix' => $config['channel_prefix'] ?? config('mattermost.channel_prefix'),
                'default_channel' => $config['default_channel'] ?? config('mattermost.default_channel'),
                'username' => $config['username'] ?? config('mattermost.username'),
            ], Logger::toMonologLevel($config['level'] ?? 'debug'));
        } else {
            $handler = new MattermostWebhookHandler([
                'webhook_url' => $config['webhook_url'] ?? config('mattermost.webhook_url'),
                'channel' => $config['channel'] ?? config('mattermost.default_channel'),
                'username' => $config['username'] ?? config('mattermost.username'),
                'icon_url' => $config['icon_url'] ?? config('mattermost.icon_url'),
            ], Logger::toMonologLevel($config['level'] ?? 'debug'));
        }

        $logger = new Logger('mattermost');
        $logger->pushHandler($handler);

        return $logger;
    }
}