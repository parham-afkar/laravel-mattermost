<?php

return [
    'type' => env('MATTERMOST_type', 'bot'), // 'bot' or 'webhook'
    
    'webhook_url' => env('MATTERMOST_WEBHOOK_URL', ''),
    
    'base_url' => env('MATTERMOST_BASE_URL', ''),
    'bot_token' => env('MATTERMOST_BOT_TOKEN', ''),
    'channel_prefix' => env('MATTERMOST_CHANNEL_PREFIX', ''),
    
    'default_channel' => env('MATTERMOST_CHANNEL', ''),
    'username' => env('MATTERMOST_USERNAME', 'Laravel Logger'),
    'icon_url' => env('MATTERMOST_ICON_URL', ''),
];