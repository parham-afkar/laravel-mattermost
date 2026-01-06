<?php

namespace ParhamAfkar\MattermostLogger\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MattermostDebugCommand extends Command
{
    protected $signature = 'mattermost:debug {channel? : Channel name to debug}';
    protected $description = 'Debug Mattermost connection and channel access';

    public function handle()
    {
        $config = config('mattermost', []);

        $this->info('🔍 Debugging Mattermost Connection...');
        $this->newLine();

        $this->table(['Setting', 'Value'], [
            ['Base URL', $config['base_url'] ?? '❌ Not set'],
            ['Bot Token', $config['bot_token'] ? '✅ Set' : '❌ Not set'],
            ['Type', $config['type'] ?? 'bot'],
            ['Default Channel', $config['default_channel'] ?? 'town-square'],
            ['Channel Prefix', $config['channel_prefix'] ?? 'None'],
        ]);

        $this->newLine();

        if (empty($config['base_url']) || empty($config['bot_token'])) {
            $this->error('Missing required configuration!');
            return 1;
        }

        $baseUrl = rtrim($config['base_url'], '/');
        $botToken = $config['bot_token'];

        try {
            $this->info('📊 Testing API Connection...');
            $userResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $botToken,
            ])->timeout(10)->get("{$baseUrl}/api/v4/users/me");

            if ($userResponse->successful()) {
                $user = $userResponse->json();
                $this->info("✅ Connected as: {$user['username']} ({$user['email']})");
                $this->info("   User ID: {$user['id']}");
                $this->info("   Roles: {$user['roles']}");
            } else {
                $this->error("❌ Failed to get user info: " . $userResponse->status());
                $this->line("Response: " . $userResponse->body());
                return 1;
            }

            $this->newLine();

            $this->info('🏢 Fetching accessible teams...');
            $teamsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $botToken,
            ])->timeout(10)->get("{$baseUrl}/api/v4/users/me/teams");

            if ($teamsResponse->successful()) {
                $teams = $teamsResponse->json();
                $this->info("✅ Found " . count($teams) . " team(s):");

                foreach ($teams as $team) {
                    $this->line("   • {$team['display_name']} ({$team['name']}) - ID: {$team['id']}");
                }

                if (empty($teams)) {
                    $this->error('❌ Bot has no team access!');
                    return 1;
                }
            } else {
                $this->error("❌ Failed to get teams: " . $teamsResponse->status());
                return 1;
            }

            $this->newLine();

            if ($channel = $this->argument('channel')) {
                $this->info("🔎 Searching for channel: '{$channel}'");

                foreach ($teams as $team) {
                    $teamId = $team['id'];
                    $teamName = $team['display_name'];

                    $this->line("   Searching in team: {$teamName}");

                    $channelsResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $botToken,
                    ])->timeout(10)->get("{$baseUrl}/api/v4/teams/{$teamId}/channels", [
                        'per_page' => 200,
                    ]);

                    if ($channelsResponse->successful()) {
                        $channels = $channelsResponse->json();

                        $found = false;
                        foreach ($channels as $channelData) {
                            if (
                                $channelData['name'] === $channel ||
                                $channelData['display_name'] === $channel
                            ) {

                                $this->info("   ✅ Found in team '{$teamName}':");
                                $this->line("      Name: {$channelData['name']}");
                                $this->line("      Display: {$channelData['display_name']}");
                                $this->line("      ID: {$channelData['id']}");
                                $this->line("      Type: {$channelData['type']}");

                                $memberResponse = Http::withHeaders([
                                    'Authorization' => 'Bearer ' . $botToken,
                                ])->get("{$baseUrl}/api/v4/users/me/channels/{$channelData['id']}");

                                if ($memberResponse->successful()) {
                                    $this->info("      ✅ Bot is a member of this channel");
                                } else {
                                    $this->warn("      ⚠️ Bot is NOT a member (needs to be added)");
                                }

                                $found = true;
                            }
                        }

                        if (!$found) {
                            $this->line("   ❌ Not found in public channels");
                        }
                    } else {
                        $this->warn("   ⚠️ Could not fetch channels for team {$teamName}");
                    }

                    $searchResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $botToken,
                    ])->timeout(10)->post("{$baseUrl}/api/v4/teams/{$teamId}/channels/search", [
                        'term' => $channel,
                    ]);

                    if ($searchResponse->successful()) {
                        $privateChannels = $searchResponse->json();

                        foreach ($privateChannels as $channelData) {
                            if (
                                $channelData['name'] === $channel ||
                                $channelData['display_name'] === $channel
                            ) {

                                $this->info("   ✅ Found in PRIVATE channels of team '{$teamName}':");
                                $this->line("      Name: {$channelData['name']}");
                                $this->line("      Display: {$channelData['display_name']}");
                                $this->line("      ID: {$channelData['id']}");
                                $this->line("      Type: {$channelData['type']}");
                            }
                        }
                    }
                }
            }

            $this->newLine();
            $this->info('✅ Debug completed!');
        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            $this->line('Trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
