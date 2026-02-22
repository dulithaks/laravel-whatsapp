<?php

namespace Duli\WhatsApp\Jobs;

use Carbon\Carbon;
use Duli\WhatsApp\Events\WhatsAppMessageStatusUpdated;
use Duli\WhatsApp\Models\WhatsAppMessage;
use Duli\WhatsApp\Support\WebhookHelpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppStatusUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WebhookHelpers;

    public int $tries = 3;

    /**
     * @param array $status  The single status object from the webhook payload.
     * @param array $value   The parent 'value' object.
     */
    public function __construct(
        protected array $status,
        protected array $value
    ) {}

    public function handle(): void
    {
        $messageId   = $this->status['id']           ?? null;
        $newStatus   = $this->status['status']       ?? null;
        $recipientId = $this->status['recipient_id'] ?? null;
        $timestamp   = $this->status['timestamp']    ?? null;

        // ── Validate required fields ─────────────────────────────────────────
        if (!$messageId || !$newStatus) {
            Log::warning('WhatsApp webhook: Missing required status fields');
            return;
        }

        if ($recipientId && !$this->isValidPhoneNumber($recipientId)) {
            Log::warning('WhatsApp webhook: Invalid recipient phone number format');
            return;
        }

        $validStatuses = ['sent', 'delivered', 'read', 'failed'];
        if (!in_array($newStatus, $validStatuses)) {
            Log::warning('WhatsApp webhook: Invalid status value', ['status' => $newStatus]);
            return;
        }

        if ($timestamp && (!is_numeric($timestamp) || $timestamp < 0)) {
            Log::warning('WhatsApp webhook: Invalid timestamp in status update');
            return;
        }

        $statusData = [
            'message_id'   => $messageId,
            'recipient_id' => $recipientId,
            'status'       => $newStatus,
            'timestamp'    => $timestamp,
        ];

        if (isset($this->status['errors'])) {
            $statusData['errors'] = $this->status['errors'];
        }

        Log::info('WhatsApp Message Status Update', [
            'message_id' => $messageId,
            'status'     => $newStatus,
            'timestamp'  => $timestamp,
            'has_errors' => isset($statusData['errors']),
        ]);

        // ── Resolve the message record ────────────────────────────────────────
        $message = WhatsAppMessage::where('wa_message_id', $messageId)->first();

        if (!$message) {
            // The status webhook arrived before (or instead of) the message webhook.
            // This happens when:
            //   • The initial message delivery was delayed / timed out
            //   • Meta sends a 'read' receipt very quickly after the message
            //
            // Solution: persist a placeholder row so the status is never lost.
            // ProcessIncomingWhatsAppMessage will fill in body/payload when the
            // message eventually arrives (or has already arrived and lost the race).
            Log::info('WhatsApp Status Update: message not yet in DB — creating placeholder', [
                'wa_message_id' => $messageId,
                'status'        => $newStatus,
            ]);

            $message = WhatsAppMessage::create([
                'wa_message_id' => $messageId,
                // recipientId is the *receiving* phone for outgoing messages.
                // For status webhooks we don't know the sender, so leave from_phone null.
                'from_phone'    => null,
                'to_phone'      => $recipientId,
                'direction'     => 'outgoing',   // status webhooks are always for outgoing msgs
                'message_type'  => null,
                'body'          => null,
                'status'        => $newStatus,
                'status_updated_at' => $timestamp
                    ? Carbon::createFromTimestamp((int) $timestamp)
                    : now(),
                'payload'       => $statusData,
            ]);

            event(new WhatsAppMessageStatusUpdated($message, null, $newStatus));
            return;
        }

        // ── Prevent status downgrades ─────────────────────────────────────────
        // Guard both directions: a late 'delivered' must not overwrite 'read' on
        // an incoming placeholder row, and equally not on an outgoing message.
        if (!$this->statusShouldUpdate($message->status, $newStatus)) {
            Log::info('WhatsApp Status Downgrade Prevented', [
                'message_id'       => $messageId,
                'current_status'   => $message->status,
                'attempted_status' => $newStatus,
            ]);
            return;
        }

        $oldStatus = $message->status;

        $message->update([
            'status'            => $newStatus,
            'status_updated_at' => $timestamp
                ? Carbon::createFromTimestamp((int) $timestamp)
                : now(),
            'payload'           => $statusData,
        ]);

        event(new WhatsAppMessageStatusUpdated($message, $oldStatus, $newStatus));
    }
}
