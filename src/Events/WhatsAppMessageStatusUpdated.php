<?php

namespace Duli\WhatsApp\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppMessageStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $statusData;

    /**
     * Create a new event instance.
     *
     * @param array $statusData
     */
    public function __construct(array $statusData)
    {
        $this->statusData = $statusData;
    }
}
