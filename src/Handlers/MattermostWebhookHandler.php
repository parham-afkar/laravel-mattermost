<?php

namespace ParhamAfkar\MattermostLogger\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use ParhamAfkar\MattermostLogger\Services\MattermostWebhookService;

class MattermostWebhookHandler extends AbstractProcessingHandler
{
    protected MattermostWebhookService $webhookService;
    protected string $defaultChannel;

    public function __construct(array $config, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->webhookService = new MattermostWebhookService($config);
        $this->defaultChannel = $config['channel'] ?? $config['default_channel'] ?? 'town-square';
    }

    protected function write(LogRecord $record): void
    {
        $channel = $record->context['channel'] ?? $this->defaultChannel;
        $message = $record->formatted ?? $record->message;
        $level = strtoupper($record->level->name);

        $formattedMessage = "**[$level]** {$message}";

        $context = $record->context;
        unset($context['channel']);

        $this->webhookService->send($channel, $formattedMessage, $context);
    }
}
