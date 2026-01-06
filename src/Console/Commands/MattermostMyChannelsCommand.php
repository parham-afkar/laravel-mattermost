<?php

namespace ParhamAfkar\MattermostLogger\Console\Commands;

use Illuminate\Console\Command;
use ParhamAfkar\MattermostLogger\Services\MattermostBotService;

class MattermostMyChannelsCommand extends Command
{
    protected $signature = 'mattermost:mychannels
                            {--search= : Search in channel names}
                            {--team= : Filter by team}';

    protected $description = 'List channels that the bot is a member of';

    public function handle()
    {
        $config = config('mattermost', []);

        if (($config['type'] ?? 'bot') !== 'bot') {
            $this->error('This command only works with bot type connection');
            return 1;
        }

        $this->info('📋 Fetching channels that bot is member of...');
        $this->newLine();

        try {
            $botService = new MattermostBotService($config);

            $channels = $botService->getMyChannels();

            if (empty($channels)) {
                $this->warn('Bot is not a member of any channels.');
                return 0;
            }

            $this->info("✅ Bot is member of " . count($channels) . " channel(s)");

            if ($search = $this->option('search')) {
                $channels = array_filter($channels, function ($channel) use ($search) {
                    return stripos($channel['name'], $search) !== false ||
                        stripos($channel['display_name'], $search) !== false;
                });
            }

            if ($teamFilter = $this->option('team')) {
                $teamChannels = [];
                foreach ($channels as $channel) {
                    $teamChannels[] = $channel;
                }
                $channels = $teamChannels;
            }

            $this->table(
                ['Channel Name', 'Display Name', 'ID', 'Type', 'Team ID'],
                array_map(function ($channel) {
                    return [
                        $channel['name'],
                        $channel['display_name'],
                        $channel['id'],
                        $channel['type'],
                        $channel['team_id'] ?? 'N/A',
                    ];
                }, $channels)
            );

            $this->newLine();
            $this->info('💡 Tips:');
            $this->line('• To use a channel, use its name or ID');
            $this->line('• For private channels, make sure bot is added as a member');
            $this->line('• Test with: php artisan mattermost:test --channel=CHANNEL_NAME');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
