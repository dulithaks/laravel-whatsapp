<?php

namespace Duli\WhatsApp\Http\Controllers;

use Duli\WhatsApp\Jobs\ProcessIncomingWhatsAppMessage;
use Duli\WhatsApp\Jobs\ProcessWhatsAppStatusUpdate;
use Duli\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     * Handle webhook events (POST request).
     *
     * This method does as little work as possible: verify the signature, then
     * immediately dispatch queued jobs and return 200 OK to WhatsApp.
     *
     * Why queues?
     *   WhatsApp expects a 200 response within ~20 seconds. If the response is
     *   late (e.g. due to DB latency under load), WhatsApp marks the delivery
     *   failed and retries with exponential back-off — causing hours-long delays
     *   and orphaned status updates (the "read" receipt arrives while the message
     *   is still missing from the DB). Moving processing off-request eliminates
     *   the timeout risk entirely.
     */
    public function receive(Request $request)
    {
        // Verify webhook signature — the only blocking work we do here
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp webhook signature verification failed', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $entry = $request->input('entry', []);

        Log::info('WhatsApp Webhook Event Received', [
            'entry_count' => count($entry),
            'timestamp'   => now()->toIso8601String(),
        ]);

        $connection = config('whatsapp.queue.connection');
        $queue      = config('whatsapp.queue.name', 'default');

        foreach ($entry as $change) {
            foreach ($change['changes'] ?? [] as $changeData) {
                $value = $changeData['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    ProcessIncomingWhatsAppMessage::dispatch($message, $value)
                        ->onConnection($connection)
                        ->onQueue($queue);
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    ProcessWhatsAppStatusUpdate::dispatch($status, $value)
                        ->onConnection($connection)
                        ->onQueue($queue);
                }
            }
        }

        // Return immediately — processing happens in the queue worker
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Verify webhook signature using HMAC SHA256.
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            Log::error('WhatsApp webhook: Missing X-Hub-Signature-256 header');
            return false;
        }

        $appSecret = config('whatsapp.app_secret');

        if (!$appSecret) {
            Log::error('WhatsApp webhook: app_secret not configured — set WHATSAPP_APP_SECRET in .env');
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
