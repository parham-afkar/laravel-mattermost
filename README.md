# Laravel Mattermost Logger

This Laravel package is designed to support both **bot** and **webhook** integrations with Mattermost.  
It supports sending logs to a custom channel for each log action, allowing more control and visibility.

---

## 📦 Installation

Install the package using Composer:

```bash
composer require parhamafkar/laravel-mattermost
```

---

## ⚙️ Configuration

### 1. Publish the config file

```bash
php artisan vendor:publish --tag=config
```

### 2. Add the custom log driver to `config/logging.php`

```php
'mattermost' => [
    'driver' => 'custom',
    'via' => ParhamAfkar\MattermostLogger\Mattermost::class,
    'level' => 'debug',
    'webhook_url' => env('MATTERMOST_WEBHOOK_URL'),
    'channel' => env('MATTERMOST_CHANNEL'),
    'username' => env('MATTERMOST_USERNAME', 'Mattermost'),
],
```

### 3. Update the stack channel in `config/logging.php`

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['single', 'mattermost'],
],
```

### 4. Add the following variables to your `.env` file

```env
MATTERMOST_BASE_URL="https://xyz.mattermost.xyz"
MATTERMOST_BOT_TOKEN="<bot-token>"
MATTERMOST_BOT_USERNAME="Parham-Bot"
MATTERMOST_WEBHOOK_URL=https://xyz.mattermost.xyz/hooks/<incoming-hooks-token>
MATTERMOST_TYPE=bot # bot or webhook
MATTERMOST_CHANNEL=errors
MATTERMOST_CHANNEL_PREFIX="develop-" # output: develop-errors
```

---

## ✅ Usage

All logs will be sent to `MATTERMOST_CHANNEL=errors` by default once the stack is configured.  
However, you can also send logs manually anywhere in your application. For example, in a controller:

```php
use ParhamAfkar\MattermostLogger\Facades\Mattermost;

Mattermost::channel('errors')->error('Error', [
    'user_id' => 1,
    'request' => 'test',
]);

Mattermost::channel('exceptions')->exception($e);
Mattermost::channel('debug')->debug('Debug', []);
```
