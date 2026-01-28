<?php

namespace Duli\WhatsApp\Events;

use Duli\WhatsApp\Models\WhatsAppMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppMessageReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WhatsAppMessage $message;

    /**
     * Create a new event instance.
     *
     * @param WhatsAppMessage $message
     */
    public function __construct(WhatsAppMessage $message)
    {
        $this->message = $message;
    }
}
