<?php

namespace ParhamAfkar\MattermostLogger;

use Monolog\Logger;
use ParhamAfkar\MattermostLogger\Handlers\MattermostBotHandler;
use ParhamAfkar\MattermostLogger\Handlers\MattermostWebhookHandler;

class LogChannel
{
    public function __invoke(array $config): Logger
    {
        $type = $config['type'] ?? config('mattermost.type', 'bot');
        $level = Logger::toMonologLevel($config['level'] ?? config('mattermost.level', 'debug'));
        
        $handlerConfig = array_merge(config('mattermost', []), $config);
        
        if ($type === 'bot') {
            $handler = new MattermostBotHandler($handlerConfig, $level);
        } else {
            $handler = new MattermostWebhookHandler($handlerConfig, $level);
        }
        
        $logger = new Logger('mattermost');
        $logger->pushHandler($handler);
        
        return $logger;
    }
}
