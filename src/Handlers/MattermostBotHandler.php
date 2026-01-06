<?php

namespace ParhamAfkar\MattermostLogger\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use ParhamAfkar\MattermostLogger\Services\MattermostBotService;

class MattermostBotHandler extends AbstractProcessingHandler
{
    protected MattermostBotService $botService;
    protected string $defaultChannel;
    protected string $levelName;

    public function __construct(array $config, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->botService = new MattermostBotService($config);
        $this->defaultChannel = $config['default_channel'] ?? 'town-square';
    }

    protected function write(LogRecord $record): void
    {
        $channel = $record->context['channel'] ?? $this->defaultChannel;
        $message = $record->formatted ?? $record->message;
        $level = strtoupper($record->level->name);

        $formattedMessage = "**[$level]** {$message}";

        $context = $record->context;
        unset($context['channel']);

        $this->botService->send($channel, $formattedMessage, $context);
    }
}
