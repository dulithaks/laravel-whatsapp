<?php

use Illuminate\Support\Facades\Route;
use Duli\WhatsApp\Http\Controllers\WhatsAppWebhookController;

$prefix = config('whatsapp.webhook.prefix', 'webhook');
$middleware = config('whatsapp.webhook.middleware', ['api']);

Route::prefix($prefix)->middleware($middleware)->group(function () {
    Route::get('/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
    Route::post('/whatsapp', [WhatsAppWebhookController::class, 'receive'])
        ->middleware('throttle:60,1')
        ->name('whatsapp.webhook.receive');
});
