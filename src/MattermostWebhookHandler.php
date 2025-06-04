<?php

namespace ParhamAfkar\MattermostLogger;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use GuzzleHttp\Client;

class MattermostWebhookHandler extends AbstractProcessingHandler
{
    protected string $webhookUrl;
    protected string $channel;
    protected string $username;
    protected string $iconUrl;
    protected Client $client;

    public function __construct(array $config, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->webhookUrl = $config['webhook_url'] ?? '';
        $this->channel = $config['channel'] ?? '';
        $this->username = $config['username'] ?? 'Laravel Logger';
        $this->iconUrl = $config['icon_url'] ?? '';
        $this->client = new Client();
    }

    protected function write(LogRecord $record): void
    {
        if (empty($this->webhookUrl)) {
            return;
        }

        $message = $this->formatMessage($record);

        $payload = [
            'text' => $message,
            'username' => $this->username,
        ];

        if (!empty($this->channel)) {
            $payload['channel'] = $this->channel;
        }
        if (!empty($this->iconUrl)) {
            $payload['icon_url'] = $this->iconUrl;
        }

        try {
            $this->client->post($this->webhookUrl, [
                'json' => $payload,
                'timeout' => 2,
            ]);
        } catch (\Exception $e) {
            \Log::channel("single")->error('Mattermost Webhook Error: ' . $e->getMessage());
        }
    }

    protected function formatMessage(LogRecord $record): string
    {
        $message = $record->formatted ?? $record->message;
        $level = strtoupper($record->level->getName());
        $context = $record->context;

        $text = "**[$level]** {$message}";

        if (!empty($context)) {
            $text .= "\n```json\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n```";
        }

        return $text;
    }
}