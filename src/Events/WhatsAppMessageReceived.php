<?php

namespace Duli\WhatsApp\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppMessageReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $messageData;

    /**
     * Create a new event instance.
     *
     * @param array $messageData
     */
    public function __construct(array $messageData)
    {
        $this->messageData = $messageData;
    }
}
