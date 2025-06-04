<?php

return [
    'webhook_url' => env('MATTERMOST_WEBHOOK_URL', ''),
    'channel' => env('MATTERMOST_CHANNEL', ''),
    'username' => env('MATTERMOST_USERNAME', 'Laravel Logger'),
    'icon_url' => env('MATTERMOST_ICON_URL', ''),
];
