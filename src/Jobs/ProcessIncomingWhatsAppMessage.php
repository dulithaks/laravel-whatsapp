<?php

namespace Duli\WhatsApp\Jobs;

use Carbon\Carbon;
use Duli\WhatsApp\Events\WhatsAppMessageReceived;
use Duli\WhatsApp\Models\WhatsAppMessage;
use Duli\WhatsApp\Support\WebhookHelpers;
use Duli\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WebhookHelpers;

    /**
     * Number of times the job may be attempted.
     * WhatsApp retries are idempotent via the unique DB constraint, so a few
     * retries here are safe.
     */
    public int $tries = 3;

    /**
     * @param array $message  The single message object from the webhook payload.
     * @param array $value    The parent 'value' object (contains metadata, contacts…).
     */
    public function __construct(
        protected array $message,
        protected array $value
    ) {}

    public function handle(WhatsAppService $whatsapp): void
    {
        $messageId = $this->message['id']        ?? null;
        $from      = $this->message['from']      ?? null;
        $timestamp = $this->message['timestamp'] ?? null;
        $type      = $this->message['type']      ?? null;

        // ── Validate required fields ─────────────────────────────────────────
        if (!$messageId || !$from || !$timestamp || !$type) {
            Log::warning('WhatsApp webhook: Missing required message fields');
            return;
        }

        if (!$this->isValidPhoneNumber($from)) {
            Log::warning('WhatsApp webhook: Invalid phone number format', [
                'from' => substr($from, 0, 5) . '***',
            ]);
            return;
        }

        $validTypes = [
            'text',
            'image',
            'video',
            'audio',
            'document',
            'location',
            'contacts',
            'interactive',
            'button',
            'reaction',
        ];
        if (!in_array($type, $validTypes)) {
            Log::warning('WhatsApp webhook: Invalid message type', ['type' => $type]);
            return;
        }

        if (!is_numeric($timestamp) || $timestamp < 0) {
            Log::warning('WhatsApp webhook: Invalid timestamp', ['timestamp' => $timestamp]);
            return;
        }

        // ── Build messageData payload ─────────────────────────────────────────
        $messageData = [
            'message_id'   => $messageId,
            'from'         => $from,
            'timestamp'    => $timestamp,
            'type'         => $type,
            'profile_name' => isset($this->value['contacts'][0]['profile']['name'])
                ? $this->sanitizeInput($this->value['contacts'][0]['profile']['name'])
                : null,
        ];

        switch ($type) {
            case 'text':
                $textBody = $this->message['text']['body'] ?? null;
                if ($textBody && strlen($textBody) > 4096) {
                    Log::warning('WhatsApp webhook: Text message truncated to 4096 characters');
                    $textBody = substr($textBody, 0, 4096);
                }
                $messageData['text'] = $textBody ? $this->sanitizeInput($textBody) : null;
                break;

            case 'image':
                $messageData['image'] = [
                    'id'        => $this->message['image']['id']        ?? null,
                    'mime_type' => $this->message['image']['mime_type'] ?? null,
                    'sha256'    => $this->message['image']['sha256']    ?? null,
                    'caption'   => isset($this->message['image']['caption'])
                        ? $this->sanitizeInput($this->message['image']['caption'])
                        : null,
                ];
                break;

            case 'video':
                $messageData['video'] = [
                    'id'        => $this->message['video']['id']        ?? null,
                    'mime_type' => $this->message['video']['mime_type'] ?? null,
                    'sha256'    => $this->message['video']['sha256']    ?? null,
                    'caption'   => isset($this->message['video']['caption'])
                        ? $this->sanitizeInput($this->message['video']['caption'])
                        : null,
                ];
                break;

            case 'audio':
                $messageData['audio'] = [
                    'id'        => $this->message['audio']['id']        ?? null,
                    'mime_type' => $this->message['audio']['mime_type'] ?? null,
                    'sha256'    => $this->message['audio']['sha256']    ?? null,
                ];
                break;

            case 'document':
                $messageData['document'] = [
                    'id'        => $this->message['document']['id']        ?? null,
                    'mime_type' => $this->message['document']['mime_type'] ?? null,
                    'sha256'    => $this->message['document']['sha256']    ?? null,
                    'filename'  => isset($this->message['document']['filename'])
                        ? $this->sanitizeInput($this->message['document']['filename'])
                        : null,
                    'caption'   => isset($this->message['document']['caption'])
                        ? $this->sanitizeInput($this->message['document']['caption'])
                        : null,
                ];
                break;

            case 'location':
                $messageData['location'] = [
                    'latitude'  => $this->message['location']['latitude']  ?? null,
                    'longitude' => $this->message['location']['longitude'] ?? null,
                    'name'      => isset($this->message['location']['name'])
                        ? $this->sanitizeInput($this->message['location']['name'])
                        : null,
                    'address'   => isset($this->message['location']['address'])
                        ? $this->sanitizeInput($this->message['location']['address'])
                        : null,
                ];
                break;

            case 'contacts':
                $messageData['contacts'] = $this->message['contacts'] ?? [];
                break;

            case 'interactive':
                $interactive = $this->message['interactive'] ?? [];
                $messageData['interactive'] = ['type' => $interactive['type'] ?? null];

                if (($interactive['type'] ?? null) === 'button_reply') {
                    $messageData['interactive']['button_reply'] = [
                        'id'    => $interactive['button_reply']['id']    ?? null,
                        'title' => isset($interactive['button_reply']['title'])
                            ? $this->sanitizeInput($interactive['button_reply']['title'])
                            : null,
                    ];
                } elseif (($interactive['type'] ?? null) === 'list_reply') {
                    $messageData['interactive']['list_reply'] = [
                        'id'          => $interactive['list_reply']['id']          ?? null,
                        'title'       => isset($interactive['list_reply']['title'])
                            ? $this->sanitizeInput($interactive['list_reply']['title'])
                            : null,
                        'description' => isset($interactive['list_reply']['description'])
                            ? $this->sanitizeInput($interactive['list_reply']['description'])
                            : null,
                    ];
                }
                break;

            case 'button':
                $messageData['button'] = [
                    'text'    => isset($this->message['button']['text'])
                        ? $this->sanitizeInput($this->message['button']['text'])
                        : null,
                    'payload' => isset($this->message['button']['payload'])
                        ? $this->sanitizeInput($this->message['button']['payload'])
                        : null,
                ];
                break;

            case 'reaction':
                $messageData['reaction'] = [
                    'message_id' => $this->message['reaction']['message_id'] ?? null,
                    'emoji'      => isset($this->message['reaction']['emoji'])
                        ? $this->sanitizeInput($this->message['reaction']['emoji'])
                        : null,
                ];
                break;
        }

        Log::info('WhatsApp Message Received', [
            'message_id'  => $messageId,
            'type'        => $type,
            'timestamp'   => $timestamp,
            'has_content' => !empty($messageData['text'] ?? $messageData[$type] ?? null),
        ]);

        // ── Upsert — idempotent against duplicate webhook deliveries ─────────
        //
        // WhatsApp may re-deliver the same webhook hours later (e.g. after a
        // 20 s timeout). The UNIQUE constraint on wa_message_id prevents a
        // second DB row, and we also avoid downgrading a status that was
        // already set by a previously-arrived status update (e.g. placeholder
        // row created by ProcessWhatsAppStatusUpdate with status='read').
        $existing = WhatsAppMessage::where('wa_message_id', $messageId)->first();

        $body = $type === 'text' ? ($messageData['text'] ?? null) : json_encode($messageData);
        $toPhone = $this->value['metadata']['display_phone_number'] ?? config('whatsapp.phone_id');

        if ($existing) {
            // Preserve higher-priority status (e.g. placeholder already has 'read')
            $statusToUse = $this->statusShouldUpdate($existing->status, 'delivered')
                ? 'delivered'
                : $existing->status;

            $isNewRecord = false;

            $existing->update([
                'from_phone'   => $from,
                'to_phone'     => $toPhone,
                'direction'    => 'incoming',
                'message_type' => $type,
                'body'         => $body,
                'status'       => $statusToUse,
                'payload'      => $messageData,
            ]);

            $waMessage = $existing->fresh();
        } else {
            $isNewRecord = true;

            $waMessage = WhatsAppMessage::create([
                'wa_message_id' => $messageId,
                'from_phone'    => $from,
                'to_phone'      => $toPhone,
                'direction'     => 'incoming',
                'message_type'  => $type,
                'body'          => $body,
                'status'        => 'delivered',
                'payload'       => $messageData,
            ]);
        }

        // Fire event (even for re-deliveries — listeners can detect via wasRecentlyCreated)
        event(new WhatsAppMessageReceived($waMessage));

        // ── Optionally mark as read ───────────────────────────────────────────
        if ($messageId && config('whatsapp.mark_messages_as_read', false)) {
            try {
                $whatsapp->markAsRead($messageId);
            } catch (\Exception $e) {
                Log::error('Failed to mark message as read', [
                    'message_id' => $messageId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
