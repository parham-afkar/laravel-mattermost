<?php

namespace ParhamAfkar\MattermostLogger\Console\Commands;

use Illuminate\Console\Command;
use ParhamAfkar\MattermostLogger\Services\MattermostLogger;
use Exception;

class MattermostTestCommand extends Command
{
    protected $signature = 'mattermost:test
                            {--channel= : Channel to send test message to}
                            {--type= : Connection type (bot/webhook)}
                            {--message= : Custom test message}';

    protected $description = 'Send a test message to Mattermost';

    public function handle()
    {
        $this->info('🚀 Testing Mattermost Connection...');
        $this->newLine();

        $config = config('mattermost', []);

        if ($type = $this->option('type')) {
            $config['type'] = $type;
        }

        $channel = $this->option('channel') ?? $config['default_channel'] ?? 'town-square';
        $config['default_channel'] = $channel;

        $message = $this->option('message') ??
                  "✅ Laravel Mattermost Logger Test\n" .
                  "Time: " . now()->format('Y-m-d H:i:s') . "\n" .
                  "Environment: " . app()->environment();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Connection Type', $config['type'] ?? 'bot'],
                ['Target Channel', $channel],
                ['Status', '🟡 Attempting to send...'],
            ]
        );

        $this->newLine();

        try {
            $logger = new MattermostLogger($config);
            $success = $logger->channel($channel)->send($message, [
                'test' => true,
                'command' => 'mattermost:test',
                'app' => config('app.name', 'Laravel'),
                'env' => app()->environment(),
                'timestamp' => now()->toISOString(),
            ]);

            if ($success) {
                $this->info('✅ Test message sent successfully!');
                $this->newLine();
                $this->info('📬 Message sent:');
                $this->line($message);
                return 0;
            } else {
                $this->error('❌ Failed to send message!');
                $this->checkConfiguration($config['type'] ?? 'bot');
                return 1;
            }

        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->checkConfiguration($config['type'] ?? 'bot');
            return 1;
        }
    }

    protected function checkConfiguration(string $type): void
    {
        $this->newLine();
        $this->warn('🔧 Configuration Check:');
        $this->line('');

        if ($type === 'bot') {
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
                $this->line('MATTERMOST_CHANNEL=general');
            }
        } else {
            $webhookUrl = config('mattermost.webhook_url');
            $this->line('• MATTERMOST_WEBHOOK_URL: ' . ($webhookUrl ? '✅ Set' : '❌ Missing'));

            if (!$webhookUrl) {
                $this->newLine();
                $this->info('📝 Add to your .env file:');
                $this->line('MATTERMOST_TYPE=webhook');
                $this->line('MATTERMOST_WEBHOOK_URL=https://your-mattermost-server/hooks/xxx');
                $this->line('MATTERMOST_CHANNEL=general');
            }
        }

        $defaultChannel = config('mattermost.default_channel');
        $this->line('• MATTERMOST_CHANNEL: ' . ($defaultChannel ? '✅ Set' : '⚠️  Using default (town-square)'));

        $this->newLine();
    }
}
