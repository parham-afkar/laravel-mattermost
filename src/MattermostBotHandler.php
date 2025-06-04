<?php

namespace ParhamAfkar\MattermostLogger;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Illuminate\Support\Facades\Http;
use Exception;

class MattermostBotHandler extends AbstractProcessingHandler
{
    protected string $baseUrl;
    protected string $botToken;
    protected string $channelPrefix;
    protected string $defaultChannel;
    protected string $username;

    public function __construct(array $config, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->botToken = $config['bot_token'] ?? '';
        $this->channelPrefix = $config['channel_prefix'] ?? '';
        $this->defaultChannel = $config['default_channel'] ?? '';
        $this->username = $config['username'] ?? 'Laravel Logger';
    }

    protected function write(LogRecord $record): void
    {
        if (empty($this->baseUrl) || empty($this->botToken)) {
            return;
        }

        $channel = $record->context['channel'] ?? $this->defaultChannel;
        if (empty($channel)) {
            return;
        }

        try {
            $channelId = $this->resolveChannelId($this->prefixedChannel($channel));
            $message = $this->formatMessage($record);

            $payload = [
                'channel_id' => $channelId,
                'message' => $message,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->botToken,
            ])->post("{$this->baseUrl}/api/v4/posts", $payload);

            if ($response->failed()) {
                \Log::channel("single")->error('Mattermost Bot API request failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
            }
        } catch (Exception $e) {
            \Log::channel("single")->error('Mattermost Bot Error: ' . $e->getMessage());
        }
    }

    protected function prefixedChannel(string $channel): string
    {
        if (str_starts_with($channel, 'id__') || preg_match('/^[a-z0-9]{26}$/', $channel)) {
            return $channel;
        }

        return $this->channelPrefix . $channel;
    }

    protected function resolveChannelId(string $channel): string
    {
        if (str_starts_with($channel, 'id__')) {
            return str_replace('id__', '', $channel);
        }

        if (preg_match('/^[a-z0-9]{26}$/', $channel)) {
            return $channel;
        }

        try {
            // Direct channel lookup
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->botToken,
            ])->post("{$this->baseUrl}/api/v4/channels/search", [
                'term' => $channel,
                'per_page' => 1,
            ]);

            if ($response->successful() && !empty($response->json())) {
                return $response->json()[0]['id'];
            }

            // Team-based search
            $teamResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->botToken,
            ])->get("{$this->baseUrl}/api/v4/teams");

            if ($teamResponse->successful()) {
                foreach ($teamResponse->json() as $team) {
                    $teamSearchResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->botToken,
                    ])->post("{$this->baseUrl}/api/v4/teams/{$team['id']}/channels/search", [
                        'term' => $channel,
                        'per_page' => 1,
                    ]);

                    if ($teamSearchResponse->successful() && !empty($teamSearchResponse->json())) {
                        return $teamSearchResponse->json()[0]['id'];
                    }
                }
            }

            throw new Exception("Channel '{$channel}' not found or bot has no access.");
        } catch (Exception $e) {
            \Log::channel("single")->error("Mattermost channel resolution failed: " . $e->getMessage());
            throw $e;
        }
    }

    protected function formatMessage(LogRecord $record): string
    {
        $message = $record->formatted ?? $record->message;
        $level = strtoupper($record->level->getName());
        $context = $record->context;

        $text = "**[$level]** {$message}";

        if (!empty($context) && !isset($context['channel'])) {
            $text .= "\n```json\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n```";
        }

        return $text;
    }
}