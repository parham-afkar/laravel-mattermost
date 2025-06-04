<?php

namespace ParhamAfkar\MattermostLogger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \ParhamAfkar\MattermostLogger\MattermostLogger channel(string $channel)
 * @method static \ParhamAfkar\MattermostLogger\MattermostLogger type(string $type)
 * @method static void debug(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void exception(\Exception $e, array $context = [])
 * 
 * @see \ParhamAfkar\MattermostLogger\MattermostLogger
 */
class Mattermost extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mattermost';
    }
}