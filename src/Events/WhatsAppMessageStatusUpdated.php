<?php

namespace Duli\WhatsApp\Events;

use Duli\WhatsApp\Models\WhatsAppMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppMessageStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WhatsAppMessage $message;

    /** Status before this webhook arrived (e.g. 'sent'). */
    public string $oldStatus;

    /** Status after this webhook (e.g. 'delivered', 'read', 'failed'). */
    public string $newStatus;

    /** Convenience: true when this is a delivery receipt. */
    public bool $isDelivered;

    /** Convenience: true when this is a read receipt. */
    public bool $isRead;

    /** Convenience: true when the message failed to deliver. */
    public bool $isFailed;

    /**
     * Create a new event instance.
     *
     * @param WhatsAppMessage $message   The updated message model.
     * @param string          $oldStatus Status before the update.
     * @param string          $newStatus Status after the update.
     */
    public function __construct(WhatsAppMessage $message, string $oldStatus, string $newStatus)
    {
        $this->message     = $message;
        $this->oldStatus   = $oldStatus;
        $this->newStatus   = $newStatus;
        $this->isDelivered = $newStatus === 'delivered';
        $this->isRead      = $newStatus === 'read';
        $this->isFailed    = $newStatus === 'failed';
    }
}
