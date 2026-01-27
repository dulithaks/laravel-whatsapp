<?php

namespace Duli\WhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Duli\WhatsApp\WhatsAppService;

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
        Log::info('WhatsApp Webhook Event', $request->all());

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
                $messageData['text'] = $message['text']['body'] ?? null;
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

        Log::info('WhatsApp Message Received', $messageData);

        // Fire an event or call a handler here
        // event(new WhatsAppMessageReceived($messageData));

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

        Log::info('WhatsApp Message Status', $statusData);

        // Fire an event or call a handler here
        // event(new WhatsAppMessageStatusUpdated($statusData));
    }
}
