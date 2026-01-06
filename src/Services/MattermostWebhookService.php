<?php

namespace ParhamAfkar\MattermostLogger\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MattermostWebhookService
{
    protected string $webhookUrl;
    protected string $username;
    protected ?string $iconUrl;

    public function __construct(array $config)
    {
        $this->webhookUrl = $config['webhook_url'] ?? '';
        $this->username = $config['username'] ?? 'Laravel Logger';
        $this->iconUrl = $config['icon_url'] ?? null;
    }

    public function send(string $channel, string $message, array $context = []): bool
    {
        try {
            $payload = [
                'text' => $this->formatMessage($message, $context),
                'username' => $this->username,
            ];

            if (!empty($channel)) {
                $payload['channel'] = $channel;
            }

            if ($this->iconUrl) {
                $payload['icon_url'] = $this->iconUrl;
            }

            $response = Http::timeout(10)->post($this->webhookUrl, $payload);
            
            if ($response->failed()) {
                Log::error('Mattermost Webhook failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Mattermost Webhook Service Error: ' . $e->getMessage());
            return false;
        }
    }

    protected function formatMessage(string $message, array $context = []): string
    {
        $text = $message;
        
        if (!empty($context)) {
            $text .= "\n```json\n" . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n```";
        }
        
        return $text;
    }
}
