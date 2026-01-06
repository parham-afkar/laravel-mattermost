# Laravel Mattermost Logger

A Laravel logging package that supports **Mattermost Bot** and **Incoming Webhook** integrations.

This package allows you to:
- Send Laravel logs directly to Mattermost
- Dynamically choose a Mattermost channel per log entry
- Use either Bot API or Webhook connections
- Control log visibility and structure with context data

---

## 📦 Installation

```bash
composer require parhamafkar/laravel-mattermost
```

---

## ⚙️ Configuration

### 1. Publish configuration file

```bash
php artisan vendor:publish --tag=mattermost-config
```

---

### 2. Add custom log driver

```php
'mattermost' => [
    'driver' => 'custom',
    'via' => ParhamAfkar\MattermostLogger\LogChannel::class,
    'level' => env('MATTERMOST_LOG_LEVEL', 'debug'),
    'channel' => env('MATTERMOST_CHANNEL', 'town-square'),
    'type' => env('MATTERMOST_TYPE', 'bot'),
],
```

---

### 3. Add Mattermost to stack channel

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['single', 'mattermost'],
],
```

---

### 4. Environment variables

```env
MATTERMOST_TYPE=bot
MATTERMOST_BASE_URL=https://xyz.mattermost.xyz
MATTERMOST_BOT_TOKEN=your-bot-token-here
MATTERMOST_CHANNEL_PREFIX="develop-"

MATTERMOST_WEBHOOK_URL=https://xyz.mattermost.xyz/hooks/your-webhook-token

MATTERMOST_CHANNEL=town-square
MATTERMOST_USERNAME="Laravel Logger"
MATTERMOST_ICON_URL=
MATTERMOST_LOG_LEVEL=debug
```

---

## ✅ Usage

### Facade

```php
Mattermost::send('Hello from Laravel!');
Mattermost::channel('errors')->send('Error message', [
    'user_id' => 1,
]);
```

---

### Laravel Log

```php
Log::channel('mattermost')->info('User logged in', [
    'channel' => 'user-activity',
]);
```

---

## 🧪 Artisan Commands

```bash
php artisan mattermost:test
php artisan mattermost:test --channel=errors
php artisan mattermost:channels
```

---

## 📋 Available Methods

```php
Mattermost::send(string $message, array $context = []);
Mattermost::channel(string $channel)->send(string $message, array $context = []);
Mattermost::type('webhook')->send(string $message, array $context = []);
```

---

## 🔧 Channel Resolution

Supports:
- Channel name
- Channel ID
- Prefixed channel ID

Private channels require bot membership.

---

## 📄 License

MIT
