<?php

namespace ParhamAfkar\MattermostLogger;

use Exception;

class MattermostLogger
{
    protected string $channel;
    protected string $type;

    public function __construct(?string $channel = null, ?string $type = null)
    {
        $this->channel = $channel ?? config('mattermost.default_channel');
        $this->type = $type ?? config('mattermost.type', 'bot');
    }

    public function channel(string $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function exception(Exception $e, array $context = []): void
    {
        $message = "Exception: " . $e->getMessage();
        $context = array_merge($context, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->error($message, $context);
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $context['channel'] = $this->channel;
        
        if ($this->type === 'bot') {
            logger()->channel('mattermost')->$level($message, $context);
        } else {
            $logger = logger()->channel('mattermost');
            $logger->$level($message, $context);
        }
    }
}