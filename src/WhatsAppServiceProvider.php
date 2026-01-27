<?php

namespace Duli\WhatsApp;

use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/whatsapp.php', 'whatsapp');

        $this->app->singleton(WhatsAppService::class, function () {
            return new WhatsAppService;
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/whatsapp.php' => config_path('whatsapp.php'),
        ], 'config');

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
