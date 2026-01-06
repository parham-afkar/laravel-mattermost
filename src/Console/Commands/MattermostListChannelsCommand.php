<?php

namespace ParhamAfkar\MattermostLogger\Console\Commands;

use Illuminate\Console\Command;
use ParhamAfkar\MattermostLogger\Services\MattermostBotService;

class MattermostListChannelsCommand extends Command
{
    protected $signature = 'mattermost:channels
                            {--team= : Filter by team name}
                            {--search= : Search in channel names}';

    protected $description = 'List available Mattermost channels';

    public function handle()
    {
        $config = config('mattermost', []);

        if (($config['type'] ?? 'bot') !== 'bot') {
            $this->error('This command only works with bot type connection');
            return 1;
        }

        $this->info('📋 Fetching channels from Mattermost...');
        $this->newLine();

        try {
            $botService = new MattermostBotService($config);
            $channels = $botService->getChannelsList();

            if (empty($channels)) {
                $this->warn('No channels found or failed to fetch.');
                return 0;
            }

            if ($teamFilter = $this->option('team')) {
                $channels = array_filter($channels, function ($channel) use ($teamFilter) {
                    return stripos($channel['team'], $teamFilter) !== false;
                });
            }

            if ($searchTerm = $this->option('search')) {
                $channels = array_filter($channels, function ($channel) use ($searchTerm) {
                    return stripos($channel['name'], $searchTerm) !== false ||
                        stripos($channel['display_name'], $searchTerm) !== false;
                });
            }

            $this->table(
                ['Team', 'Channel Name', 'Display Name', 'ID', 'Type'],
                array_map(function ($channel) {
                    return [
                        $channel['team'],
                        $channel['name'],
                        $channel['display_name'],
                        $channel['id'],
                        $channel['type'],
                    ];
                }, $channels)
            );

            $this->newLine();
            $this->info('💡 Usage tips:');
            $this->line('• Use channel name in config: ' . config('mattermost.default_channel', 'town-square'));
            $this->line('• Or use channel ID directly (prefixed with id__): id__' . ($channels[0]['id'] ?? 'channel-id'));
            $this->line('• Command: php artisan mattermost:test --channel=channel-name');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line('');
            $this->checkConfiguration();
            return 1;
        }
    }

    protected function checkConfiguration(): void
    {
        $this->warn('🔧 Configuration Check:');

        $baseUrl = config('mattermost.base_url');
        $botToken = config('mattermost.bot_token');

        $this->line('• MATTERMOST_BASE_URL: ' . ($baseUrl ? '✅ Set' : '❌ Missing'));
        $this->line('• MATTERMOST_BOT_TOKEN: ' . ($botToken ? '✅ Set' : '❌ Missing'));

        if (!$baseUrl || !$botToken) {
            $this->newLine();
            $this->info('📝 Add to your .env file:');
            $this->line('MATTERMOST_TYPE=bot');
            $this->line('MATTERMOST_BASE_URL=https://your-mattermost-server.com');
            $this->line('MATTERMOST_BOT_TOKEN=your-bot-token-here');
        }

        $this->newLine();
    }
}
