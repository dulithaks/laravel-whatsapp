<?php

namespace Duli\WhatsApp\Facades;

use Illuminate\Support\Facades\Facade;

class WhatsApp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Duli\WhatsApp\WhatsAppService::class;
    }
}
