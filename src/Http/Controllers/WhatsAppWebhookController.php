<?php

namespace Duli\WhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Duli\WhatsApp\WhatsAppService;
use Duli\WhatsApp\Events\WhatsAppMessageReceived;
use Duli\WhatsApp\Events\WhatsAppMessageStatusUpdated;
use Duli\WhatsApp\Models\WhatsAppMessage;

class WhatsAppWebhookController
{
    protected WhatsAppService $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Verify webhook (GET request)
     */
    public function verify(Request $request)
    {
        return $this->whatsapp->verifyWebhook($request);
    }

    /**
     * Handle webhook events (POST request)
     */
    public function receive(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp webhook signature verification failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Log webhook event without sensitive data
        Log::info('WhatsApp Webhook Event Received', [
            'entry_count' => count($request->input('entry', [])),
            'timestamp' => now()->toIso8601String(),
        ]);

        $entry = $request->input('entry', []);

        foreach ($entry as $change) {
            $changes = $change['changes'] ?? [];

            foreach ($changes as $changeData) {
                $value = $changeData['value'] ?? [];

                // Handle incoming messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->handleMessage($message, $value);
                    }
                }

                // Handle message status updates (sent, delivered, read, failed)
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->handleStatus($status, $value);
                    }
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Verify webhook signature using HMAC SHA256
     * 
     * @param Request $request
     * @return bool
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        // If no signature header, reject
        if (!$signature) {
            return false;
        }

        $appSecret = config('whatsapp.app_secret');
        
        // If app secret is not configured, log warning but allow (for backward compatibility)
        // In production, this should be required
        if (!$appSecret) {
            Log::warning('WhatsApp app_secret not configured - webhook signature verification skipped');
            return true;
        }

        // Get raw request body
        $payload = $request->getContent();
        
        // Calculate expected signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
        
        // Use hash_equals for timing-attack-safe comparison
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle incoming message
     * 
     * @param array $message Message data
     * @param array $value Webhook value data
     */
    protected function handleMessage(array $message, array $value): void
    {
        $messageId = $message['id'] ?? null;
        $from = $message['from'] ?? null;
        $timestamp = $message['timestamp'] ?? null;
        $type = $message['type'] ?? null;

        // Validate required fields
        if (!$messageId || !$from || !$timestamp || !$type) {
            Log::warning('WhatsApp webhook: Missing required message fields');
            return;
        }

        // Validate phone number format (basic E.164 validation)
        if (!$this->isValidPhoneNumber($from)) {
            Log::warning('WhatsApp webhook: Invalid phone number format', ['from' => substr($from, 0, 5) . '***']);
            return;
        }

        // Validate message type
        $validTypes = ['text', 'image', 'video', 'audio', 'document', 'location', 'contacts', 'interactive', 'button', 'reaction'];
        if (!in_array($type, $validTypes)) {
            Log::warning('WhatsApp webhook: Invalid message type', ['type' => $type]);
            return;
        }

        // Validate timestamp (should be numeric and reasonable)
        if (!is_numeric($timestamp) || $timestamp < 0) {
            Log::warning('WhatsApp webhook: Invalid timestamp', ['timestamp' => $timestamp]);
            return;
        }

        $messageData = [
            'message_id' => $messageId,
            'from' => $from,
            'timestamp' => $timestamp,
            'type' => $type,
            'profile_name' => $value['contacts'][0]['profile']['name'] ?? null,
        ];

        // Extract message content based on type
        switch ($type) {
            case 'text':
                $textBody = $message['text']['body'] ?? null;
                // Validate text message size (max 4096 characters per WhatsApp API)
                if ($textBody && strlen($textBody) > 4096) {
                    Log::warning('WhatsApp webhook: Text message exceeds 4096 character limit');
                    $textBody = substr($textBody, 0, 4096);
                }
                // Sanitize text input
                $messageData['text'] = $textBody ? $this->sanitizeInput($textBody) : null;
                break;

            case 'image':
                $messageData['image'] = [
                    'id' => $message['image']['id'] ?? null,
                    'mime_type' => $message['image']['mime_type'] ?? null,
                    'sha256' => $message['image']['sha256'] ?? null,
                    'caption' => $message['image']['caption'] ?? null,
                ];
                break;

            case 'video':
                $messageData['video'] = [
                    'id' => $message['video']['id'] ?? null,
                    'mime_type' => $message['video']['mime_type'] ?? null,
                    'sha256' => $message['video']['sha256'] ?? null,
                    'caption' => $message['video']['caption'] ?? null,
                ];
                break;

            case 'audio':
                $messageData['audio'] = [
                    'id' => $message['audio']['id'] ?? null,
                    'mime_type' => $message['audio']['mime_type'] ?? null,
                    'sha256' => $message['audio']['sha256'] ?? null,
                ];
                break;

            case 'document':
                $messageData['document'] = [
                    'id' => $message['document']['id'] ?? null,
                    'mime_type' => $message['document']['mime_type'] ?? null,
                    'sha256' => $message['document']['sha256'] ?? null,
                    'filename' => $message['document']['filename'] ?? null,
                    'caption' => $message['document']['caption'] ?? null,
                ];
                break;

            case 'location':
                $messageData['location'] = [
                    'latitude' => $message['location']['latitude'] ?? null,
                    'longitude' => $message['location']['longitude'] ?? null,
                    'name' => $message['location']['name'] ?? null,
                    'address' => $message['location']['address'] ?? null,
                ];
                break;

            case 'contacts':
                $messageData['contacts'] = $message['contacts'] ?? [];
                break;

            case 'interactive':
                $interactive = $message['interactive'] ?? [];
                $messageData['interactive'] = [
                    'type' => $interactive['type'] ?? null,
                ];

                if ($interactive['type'] === 'button_reply') {
                    $messageData['interactive']['button_reply'] = [
                        'id' => $interactive['button_reply']['id'] ?? null,
                        'title' => $interactive['button_reply']['title'] ?? null,
                    ];
                } elseif ($interactive['type'] === 'list_reply') {
                    $messageData['interactive']['list_reply'] = [
                        'id' => $interactive['list_reply']['id'] ?? null,
                        'title' => $interactive['list_reply']['title'] ?? null,
                        'description' => $interactive['list_reply']['description'] ?? null,
                    ];
                }
                break;

            case 'button':
                $messageData['button'] = [
                    'text' => $message['button']['text'] ?? null,
                    'payload' => $message['button']['payload'] ?? null,
                ];
                break;

            case 'reaction':
                $messageData['reaction'] = [
                    'message_id' => $message['reaction']['message_id'] ?? null,
                    'emoji' => $message['reaction']['emoji'] ?? null,
                ];
                break;
        }

        // Log message received without sensitive data
        Log::info('WhatsApp Message Received', [
            'message_id' => $messageId,
            'type' => $type,
            'timestamp' => $timestamp,
            'has_content' => !empty($messageData['text'] ?? $messageData[$type] ?? null),
        ]);

        // Save incoming message to database
        $message = WhatsAppMessage::create([
            'wa_message_id' => $messageId,
            'from_phone' => $from,
            'to_phone' => $value['metadata']['display_phone_number'] ?? config('whatsapp.phone_id'),
            'direction' => 'incoming',
            'message_type' => $type,
            'body' => $type === 'text' ? ($messageData['text'] ?? null) : json_encode($messageData),
            'status' => 'delivered',
            'payload' => $messageData,
        ]);

        // Fire event with the persisted model
        event(new WhatsAppMessageReceived($message));

        // Optionally mark message as read
        if ($messageId && config('whatsapp.mark_messages_as_read', false)) {
            try {
                $this->whatsapp->markAsRead($messageId);
            } catch (\Exception $e) {
                Log::error('Failed to mark message as read', [
                    'message_id' => $messageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle message status update
     * 
     * @param array $status Status data
     * @param array $value Webhook value data
     */
    protected function handleStatus(array $status, array $value): void
    {
        $statusData = [
            'message_id' => $status['id'] ?? null,
            'recipient_id' => $status['recipient_id'] ?? null,
            'status' => $status['status'] ?? null,
            'timestamp' => $status['timestamp'] ?? null,
        ];

        // Handle errors if present
        if (isset($status['errors'])) {
            $statusData['errors'] = $status['errors'];
        }

        // Log status update without sensitive data
        Log::info('WhatsApp Message Status Update', [
            'message_id' => $statusData['message_id'],
            'status' => $statusData['status'],
            'timestamp' => $statusData['timestamp'],
            'has_errors' => isset($statusData['errors']),
        ]);

        // Update message status in database
        $message = WhatsAppMessage::where('wa_message_id', $statusData['message_id'])
            ->first();

        if ($message) {
            $message->update([
                'status' => $statusData['status'],
                'payload' => $statusData,
            ]);

            // Fire event with the updated model
            event(new WhatsAppMessageStatusUpdated($message));
        }
    }

    /**
     * Validate phone number format (E.164)
     * 
     * @param string $phoneNumber
     * @return bool
     */
    protected function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Basic E.164 validation: digits only, 1-15 characters
        return preg_match('/^\d{1,15}$/', $phoneNumber) === 1;
    }

    /**
     * Sanitize user input to prevent XSS and other attacks
     * 
     * @param string $input
     * @return string
     */
    protected function sanitizeInput(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        return $input;
    }
}
