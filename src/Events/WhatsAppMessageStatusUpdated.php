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

    /**
     * The status before this update (e.g. 'sent').
     */
    public string $oldStatus;

    /**
     * The status after this update (e.g. 'delivered', 'read', 'failed', 'deleted').
     */
    public string $newStatus;

    /**
     * Whether this is a delivery receipt (status === 'delivered').
     */
    public bool $isDelivered;

    /**
     * Whether this is a read receipt (status === 'read').
     */
    public bool $isRead;

    /**
     * Whether the message was deleted by the recipient.
     */
    public bool $isDeleted;

    /**
     * Whether the message failed to deliver.
     */
    public bool $isFailed;

    /**
     * Create a new event instance.
     *
     * @param WhatsAppMessage $message  The updated message model.
     * @param string          $oldStatus Status before the update.
     * @param string          $newStatus Status after the update.
     */
    public function __construct(WhatsAppMessage $message, string $oldStatus, string $newStatus)
    {
        $this->message    = $message;
        $this->oldStatus  = $oldStatus;
        $this->newStatus  = $newStatus;
        $this->isDelivered = $newStatus === 'delivered';
        $this->isRead      = $newStatus === 'read';
        $this->isDeleted   = $newStatus === 'deleted';
        $this->isFailed    = $newStatus === 'failed';
    }
}
