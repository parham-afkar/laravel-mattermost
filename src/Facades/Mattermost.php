<?php

namespace ParhamAfkar\MattermostLogger\Facades;

use Illuminate\Support\Facades\Facade;

class Mattermost extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mattermost';
    }
}
