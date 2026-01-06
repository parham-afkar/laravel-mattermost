<?php

namespace ParhamAfkar\MattermostLogger\Services;

use Exception;

class MattermostLogger
{
    protected string $type;
    protected string $channel;
    protected array $config;
    protected ?MattermostBotService $botService = null;
    protected ?MattermostWebhookService $webhookService = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->type = $config['type'] ?? 'bot';
        $this->channel = $config['default_channel'] ?? 'town-square';

        if ($this->type === 'bot') {
            $this->botService = new MattermostBotService($config);
        } else {
            $this->webhookService = new MattermostWebhookService($config);
        }
    }

    public function channel(string $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function send(string $message, array $context = []): bool
    {
        try {
            if ($this->type === 'bot' && $this->botService) {
                return $this->botService->send($this->channel, $message, $context);
            }

            if ($this->type === 'webhook' && $this->webhookService) {
                return $this->webhookService->send($this->channel, $message, $context);
            }

            return false;

        } catch (Exception $e) {
            \Log::error('Mattermost Logger Error: ' . $e->getMessage());
            return false;
        }
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
