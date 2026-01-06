<?php

namespace ParhamAfkar\MattermostLogger\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MattermostBotService
{
    protected string $baseUrl;
    protected string $botToken;
    protected string $channelPrefix;
    protected string $username;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->botToken = $config['bot_token'] ?? '';
        $this->channelPrefix = $config['channel_prefix'] ?? '';
        $this->username = $config['username'] ?? 'Laravel Logger';
    }

    public function send(string $channel, string $message, array $context = []): bool
    {
        try {
            Log::debug("Attempting to send to channel: {$channel}", [
                'prefix' => $this->channelPrefix,
                'prefixed_channel' => $this->prefixedChannel($channel),
            ]);

            $channelId = $this->resolveChannelId($this->prefixedChannel($channel));

            $payload = [
                'channel_id' => $channelId,
                'message' => $this->formatMessage($message, $context),
            ];

            Log::debug("Sending payload to Mattermost", ['channel_id' => $channelId]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->botToken,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post("{$this->baseUrl}/api/v4/posts", $payload);

            if ($response->failed()) {
                Log::error('Mattermost Bot API failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'payload' => $payload,
                ]);
                return false;
            }

            Log::debug("Message sent successfully to channel ID: {$channelId}");
            return true;
        } catch (Exception $e) {
            Log::error('Mattermost Bot Service Error: ' . $e->getMessage());
            return false;
        }
    }

    protected function prefixedChannel(string $channel): string
    {
        if (str_starts_with($channel, 'id__') || preg_match('/^[a-z0-9]{26}$/', $channel)) {
            return $channel;
        }

        if (!empty($this->channelPrefix) && !str_starts_with($channel, $this->channelPrefix)) {
            return $this->channelPrefix . $channel;
        }

        return $channel;
    }

    protected function resolveChannelId(string $channel): string
    {
        if (str_starts_with($channel, 'id__')) {
            $channelId = substr($channel, 4);
            Log::debug("Using prefixed channel ID: {$channelId}");
            return $channelId;
        }

        if (preg_match('/^[a-z0-9]{26}$/', $channel)) {
            Log::debug("Using raw channel ID: {$channel}");
            return $channel;
        }

        $baseUrl = $this->baseUrl;
        $botToken = $this->botToken;

        try {
            Log::debug("Resolving channel: '{$channel}'");

            $teamsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $botToken,
            ])->timeout(10)->get("{$baseUrl}/api/v4/users/me/teams");

            if (!$teamsResponse->successful()) {
                throw new Exception("Failed to fetch user teams: " . $teamsResponse->status());
            }

            $teams = $teamsResponse->json();

            if (empty($teams)) {
                throw new Exception("Bot is not a member of any team");
            }

            Log::debug("Bot is member of " . count($teams) . " team(s)");

            foreach ($teams as $team) {
                $teamId = $team['id'];
                $teamName = $team['display_name'];

                Log::debug("Searching in team: {$teamName} ({$teamId})");

                $searchResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $botToken,
                ])->timeout(10)->post("{$baseUrl}/api/v4/teams/{$teamId}/channels/search", [
                    'term' => $channel,
                ]);

                if ($searchResponse->successful()) {
                    $searchResults = $searchResponse->json();
                    Log::debug("Search API returned " . count($searchResults) . " results");

                    foreach ($searchResults as $channelData) {
                        Log::debug("Found channel: {$channelData['name']} (display: {$channelData['display_name']})");

                        if (
                            $channelData['name'] === $channel ||
                            $channelData['display_name'] === $channel
                        ) {

                            Log::debug("✅ Exact match found: ID = {$channelData['id']}");

                            $membershipResponse = Http::withHeaders([
                                'Authorization' => 'Bearer ' . $botToken,
                            ])->get("{$baseUrl}/api/v4/users/me/channels/{$channelData['id']}");

                            if ($membershipResponse->successful()) {
                                Log::debug("✅ Bot is a member of this private channel");
                                return $channelData['id'];
                            } else {
                                Log::warning("❌ Bot is NOT a member of channel '{$channel}' (Status: {$membershipResponse->status()})");
                                continue;
                            }
                        }
                    }
                } else {
                    Log::warning("Search API failed for team {$teamName}: " . $searchResponse->status());
                }

                $myChannelsResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $botToken,
                ])->timeout(10)->get("{$baseUrl}/api/v4/users/me/channels", [
                    'per_page' => 200,
                ]);

                if ($myChannelsResponse->successful()) {
                    $myChannels = $myChannelsResponse->json();
                    Log::debug("Bot is member of " . count($myChannels) . " channels");

                    foreach ($myChannels as $channelData) {
                        if (
                            $channelData['name'] === $channel ||
                            $channelData['display_name'] === $channel
                        ) {

                            Log::debug("✅ Found in bot's channel list: ID = {$channelData['id']}");
                            return $channelData['id'];
                        }
                    }
                }

                $channelsResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $botToken,
                ])->timeout(10)->get("{$baseUrl}/api/v4/teams/{$teamId}/channels", [
                    'per_page' => 200,
                ]);

                if ($channelsResponse->successful()) {
                    $channels = $channelsResponse->json();

                    foreach ($channels as $channelData) {
                        if (
                            $channelData['name'] === $channel ||
                            $channelData['display_name'] === $channel
                        ) {

                            Log::debug("✅ Found in team channels: ID = {$channelData['id']}");
                            return $channelData['id'];
                        }
                    }
                }
            }

            Log::debug("Trying direct channel search...");
            $directResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $botToken,
            ])->timeout(10)->post("{$baseUrl}/api/v4/channels/search", [
                'term' => $channel,
            ]);

            if ($directResponse->successful()) {
                $directResults = $directResponse->json();
                if (!empty($directResults)) {
                    Log::debug("✅ Found via direct search: ID = {$directResults[0]['id']}");
                    return $directResults[0]['id'];
                }
            }

            $teamNames = array_map(function ($team) {
                return $team['display_name'];
            }, $teams);

            throw new Exception("Channel '{$channel}' not found. Bot has access to teams: " . implode(', ', $teamNames) .
                ". Note: For private channels, bot must be explicitly added as a member.");
        } catch (Exception $e) {
            Log::error('Mattermost channel resolution error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getMyChannels(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->botToken,
            ])->timeout(10)->get("{$this->baseUrl}/api/v4/users/me/channels", [
                'per_page' => 200,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (Exception $e) {
            Log::error('Failed to get bot channels: ' . $e->getMessage());
            return [];
        }
    }

    public function isMemberOfChannel(string $channelId): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->botToken,
            ])->timeout(10)->get("{$this->baseUrl}/api/v4/users/me/channels/{$channelId}");

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to check channel membership: ' . $e->getMessage());
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

    public function getChannelsList(): array
    {
        try {
            $teamsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->botToken,
            ])->timeout(10)->get("{$this->baseUrl}/api/v4/users/me/teams");

            if (!$teamsResponse->successful()) {
                throw new Exception("Failed to fetch teams");
            }

            $teams = $teamsResponse->json();
            $allChannels = [];

            foreach ($teams as $team) {
                $teamId = $team['id'];
                $teamName = $team['display_name'];

                $channelsResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->botToken,
                ])->timeout(10)->get("{$this->baseUrl}/api/v4/teams/{$teamId}/channels", [
                    'per_page' => 200,
                ]);

                if ($channelsResponse->successful()) {
                    $channels = $channelsResponse->json();
                    foreach ($channels as $channel) {
                        $allChannels[] = [
                            'team' => $teamName,
                            'name' => $channel['name'],
                            'display_name' => $channel['display_name'],
                            'id' => $channel['id'],
                            'type' => $channel['type'],
                        ];
                    }
                }
            }

            return $allChannels;
        } catch (Exception $e) {
            Log::error('Failed to get channels list: ' . $e->getMessage());
            return [];
        }
    }
}
