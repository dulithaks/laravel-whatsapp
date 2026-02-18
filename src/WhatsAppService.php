<?php

namespace Duli\WhatsApp;

use Duli\WhatsApp\Exceptions\WhatsAppException;
use Duli\WhatsApp\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;

class WhatsAppService
{
    protected string $phoneId;
    protected string $token;
    protected string $apiUrl;
    protected string $apiVersion;

    public function __construct()
    {
        $this->phoneId = config('whatsapp.phone_id');
        $this->token = config('whatsapp.token');
        $this->apiVersion = config('whatsapp.api_version', 'v20.0');

        $this->apiUrl = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneId}/messages";
    }

    /**
     * Send a simple text message
     * 
     * @param string $to Phone number in international format (e.g., 1234567890)
     * @param string $message Message text (max 4096 characters)
     * @param bool $preview_url Enable URL preview
     * @return array Response from WhatsApp API
     * @throws WhatsAppException
     */
    public function sendMessage(string $to, string $message, bool $preview_url = false): array
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => $preview_url,
                'body' => $message
            ],
        ]);
    }

    /**
     * Send a template message
     * 
     * @param string $to Phone number in international format
     * @param string $template Template name
     * @param string $language Language code (default: en_US)
     * @param array $params Body text parameters (array of scalar values)
     * @param array $header Optional header component descriptor.
     *                      Shape: ['type' => 'image'|'document'|'video'|'text', 'id' => '...']
     *                      Use 'id' for an uploaded media ID or 'url' for a public link.
     *                      For text headers use 'text' => '...' instead.
     * @param array $buttons Optional button components. Each entry:
     *                       ['sub_type' => 'quick_reply'|'url', 'index' => 0, 'parameters' => [...]]
     * @return array Response from WhatsApp API
     * @throws WhatsAppException
     */
    public function sendTemplate(
        string $to,
        string $template,
        string $language = 'en_US',
        array $params = [],
        array $header = [],
        array $buttons = []
    ): array {
        $components = [];

        // Build header component
        if (!empty($header)) {
            $headerParameter = ['type' => $header['type']];

            if ($header['type'] === 'image') {
                $headerParameter['image'] = isset($header['id'])
                    ? ['id' => $header['id']]
                    : ['link' => $header['url']];
            } elseif ($header['type'] === 'document') {
                $headerParameter['document'] = isset($header['id'])
                    ? ['id' => $header['id']]
                    : ['link' => $header['url']];
            } elseif ($header['type'] === 'video') {
                $headerParameter['video'] = isset($header['id'])
                    ? ['id' => $header['id']]
                    : ['link' => $header['url']];
            } elseif ($header['type'] === 'text') {
                $headerParameter['text'] = $header['text'];
            }

            $components[] = [
                'type' => 'header',
                'parameters' => [$headerParameter],
            ];
        }

        // Build body component
        if (!empty($params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => collect($params)->map(function ($p) {
                    return ['type' => 'text', 'text' => (string) $p];
                })->toArray(),
            ];
        }

        // Build button components
        foreach ($buttons as $index => $button) {
            $components[] = [
                'type' => 'button',
                'sub_type' => $button['sub_type'] ?? 'quick_reply',
                'index' => (string) ($button['index'] ?? $index),
                'parameters' => $button['parameters'] ?? [],
            ];
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name'     => $template,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);
    }

    /**
     * Send an image
     * 
     * @param string $to Phone number
     * @param string $imageUrl URL or media ID of the image
     * @param string|null $caption Optional caption
     * @param bool $isMediaId Whether the image parameter is a media ID
     * @return array
     * @throws WhatsAppException
     */
    public function sendImage(string $to, string $imageUrl, ?string $caption = null, bool $isMediaId = false): array
    {
        $imageData = $isMediaId ? ['id' => $imageUrl] : ['link' => $imageUrl];

        if ($caption) {
            $imageData['caption'] = $caption;
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => $imageData,
        ]);
    }

    /**
     * Send a document
     * 
     * @param string $to Phone number
     * @param string $documentUrl URL or media ID of the document
     * @param string|null $filename Filename
     * @param string|null $caption Optional caption
     * @param bool $isMediaId Whether the document parameter is a media ID
     * @return array
     * @throws WhatsAppException
     */
    public function sendDocument(string $to, string $documentUrl, ?string $filename = null, ?string $caption = null, bool $isMediaId = false): array
    {
        $documentData = $isMediaId ? ['id' => $documentUrl] : ['link' => $documentUrl];

        if ($filename) {
            $documentData['filename'] = $filename;
        }

        if ($caption) {
            $documentData['caption'] = $caption;
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => $documentData,
        ]);
    }

    /**
     * Send a video
     * 
     * @param string $to Phone number
     * @param string $videoUrl URL or media ID of the video
     * @param string|null $caption Optional caption
     * @param bool $isMediaId Whether the video parameter is a media ID
     * @return array
     * @throws WhatsAppException
     */
    public function sendVideo(string $to, string $videoUrl, ?string $caption = null, bool $isMediaId = false): array
    {
        $videoData = $isMediaId ? ['id' => $videoUrl] : ['link' => $videoUrl];

        if ($caption) {
            $videoData['caption'] = $caption;
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'video',
            'video' => $videoData,
        ]);
    }

    /**
     * Send an audio file
     * 
     * @param string $to Phone number
     * @param string $audioUrl URL or media ID of the audio
     * @param bool $isMediaId Whether the audio parameter is a media ID
     * @return array
     * @throws WhatsAppException
     */
    public function sendAudio(string $to, string $audioUrl, bool $isMediaId = false): array
    {
        $audioData = $isMediaId ? ['id' => $audioUrl] : ['link' => $audioUrl];

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'audio',
            'audio' => $audioData,
        ]);
    }

    /**
     * Upload an image file and get media ID
     * 
     * @param string|UploadedFile $file File path or UploadedFile instance
     * @return string Media ID that can be used with sendImage()
     * @throws WhatsAppException
     */
    public function uploadImage(string|UploadedFile $file): string
    {
        try {
            $fileData = $this->prepareFileForUpload($file);

            $mediaUrl = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneId}/media";

            $response = Http::withToken($this->token)
                ->timeout(config('whatsapp.timeout', 30))
                ->retry(config('whatsapp.retry_times', 3), config('whatsapp.retry_delay', 100))
                ->asMultipart()
                ->attach('file', $fileData['content'], $fileData['filename'])
                ->post($mediaUrl, [
                    'messaging_product' => 'whatsapp',
                    'type' => $fileData['mimeType'],
                ]);

            $result = $this->handleResponse($response);

            if (!isset($result['id'])) {
                throw new WhatsAppException('Failed to get media ID from upload response');
            }

            return $result['id'];
        } catch (WhatsAppException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new WhatsAppException(
                'Failed to upload image: ' . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * Upload and send an image in one call
     * 
     * @param string $to Phone number
     * @param string|UploadedFile $file File path or UploadedFile instance
     * @param string|null $caption Optional caption
     * @return array Response from WhatsApp API
     * @throws WhatsAppException
     */
    public function uploadAndSendImage(string $to, $file, ?string $caption = null): array
    {
        $mediaId = $this->uploadImage($file);
        return $this->sendImage($to, $mediaId, $caption, true);
    }

    /**
     * Send location
     * 
     * @param string $to Phone number
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param string|null $name Location name
     * @param string|null $address Location address
     * @return array
     * @throws WhatsAppException
     */
    public function sendLocation(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null): array
    {
        $locationData = [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if ($name) {
            $locationData['name'] = $name;
        }

        if ($address) {
            $locationData['address'] = $address;
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'location',
            'location' => $locationData,
        ]);
    }

    /**
     * Send interactive buttons
     * 
     * @param string $to Phone number
     * @param string $bodyText Button message body
     * @param array $buttons Array of buttons [['id' => '1', 'title' => 'Button 1'], ...]
     * @param string|null $headerText Optional header
     * @param string|null $footerText Optional footer
     * @return array
     * @throws WhatsAppException
     */
    public function sendButtons(string $to, string $bodyText, array $buttons, ?string $headerText = null, ?string $footerText = null): array
    {
        $interactive = [
            'type' => 'button',
            'body' => ['text' => $bodyText],
            'action' => [
                'buttons' => collect($buttons)->take(3)->map(function ($button) {
                    return [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $button['id'],
                            'title' => $button['title'],
                        ]
                    ];
                })->toArray(),
            ],
        ];

        if ($headerText) {
            $interactive['header'] = ['type' => 'text', 'text' => $headerText];
        }

        if ($footerText) {
            $interactive['footer'] = ['text' => $footerText];
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => $interactive,
        ]);
    }

    /**
     * Send interactive list
     * 
     * @param string $to Phone number
     * @param string $bodyText List message body
     * @param string $buttonText Button text to open list
     * @param array $sections Array of sections with rows
     * @param string|null $headerText Optional header
     * @param string|null $footerText Optional footer
     * @return array
     * @throws WhatsAppException
     */
    public function sendList(string $to, string $bodyText, string $buttonText, array $sections, ?string $headerText = null, ?string $footerText = null): array
    {
        $interactive = [
            'type' => 'list',
            'body' => ['text' => $bodyText],
            'action' => [
                'button' => $buttonText,
                'sections' => $sections,
            ],
        ];

        if ($headerText) {
            $interactive['header'] = ['type' => 'text', 'text' => $headerText];
        }

        if ($footerText) {
            $interactive['footer'] = ['text' => $footerText];
        }

        return $this->send([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => $interactive,
        ]);
    }

    /**
     * Mark a message as read
     * 
     * @param string $messageId Message ID from webhook
     * @return array
     * @throws WhatsAppException
     */
    public function markAsRead(string $messageId): array
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ]);
    }

    /**
     * Send a reaction to a message
     * 
     * @param string $to Phone number
     * @param string $messageId Message ID to react to
     * @param string $emoji Emoji to react with (empty string to remove reaction)
     * @return array
     * @throws WhatsAppException
     */
    public function sendReaction(string $to, string $messageId, string $emoji): array
    {
        return $this->send([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $messageId,
                'emoji' => $emoji,
            ],
        ]);
    }

    /**
     * Generic send wrapper for all requests
     * 
     * @param array $payload Request payload
     * @return array Response data
     * @throws WhatsAppException
     */
    protected function send(array $payload): array
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(config('whatsapp.timeout', 30))
                ->retry(config('whatsapp.retry_times', 3), config('whatsapp.retry_delay', 100))
                ->post($this->apiUrl, $payload);

            $result = $this->handleResponse($response);

            // Log outgoing message to database
            $this->logOutgoingMessage($payload, $result);

            return $result;
        } catch (\Exception $e) {
            // Log failed message
            $this->logOutgoingMessage($payload, null, 'failed');

            throw new WhatsAppException(
                'Failed to send WhatsApp message: ' . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * Handle API response
     * 
     * @param Response $response HTTP response
     * @return array Response data
     * @throws WhatsAppException
     */
    protected function handleResponse(Response $response): array
    {
        $data = $response->json();

        if (!$response->successful()) {
            $errorMessage = $data['error']['message'] ?? 'Unknown error occurred';
            $errorCode = $data['error']['code'] ?? $response->status();

            throw new WhatsAppException(
                "WhatsApp API Error: {$errorMessage}",
                $errorCode,
                $data
            );
        }

        return $data;
    }

    /**
     * Log outgoing message to database
     * 
     * @param array $payload Request payload
     * @param array|null $response API response
     * @param string $status Message status
     */
    protected function logOutgoingMessage(array $payload, ?array $response, string $status = 'sent'): void
    {
        try {
            $messageType = $payload['type'] ?? 'unknown';
            $body = null;

            // Extract body based on type
            if ($messageType === 'text') {
                $body = $payload['text']['body'] ?? null;
            } elseif (in_array($messageType, ['image', 'video', 'document', 'audio'])) {
                $body = json_encode($payload[$messageType] ?? []);
            } elseif ($messageType === 'location') {
                $body = json_encode($payload['location'] ?? []);
            } elseif ($messageType === 'template') {
                $body = $payload['template']['name'] ?? null;
            } elseif ($messageType === 'interactive') {
                $body = json_encode($payload['interactive'] ?? []);
            } elseif ($messageType === 'reaction') {
                $body = json_encode($payload['reaction'] ?? []);
            }

            WhatsAppMessage::create([
                'wa_message_id' => $response['messages'][0]['id'] ?? null,
                'from_phone' => $this->phoneId,
                'to_phone' => $payload['to'] ?? null,
                'direction' => 'outgoing',
                'message_type' => $messageType,
                'body' => $body,
                'status' => $status,
                'payload' => [
                    'request' => $payload,
                    'response' => $response,
                ],
            ]);
        } catch (\Exception $e) {
            // Silently fail logging to not interrupt message sending
            Log::error('Failed to log WhatsApp message', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Prepare file for upload (validate and extract file data)
     * 
     * @param string|UploadedFile $file File path or UploadedFile instance
     * @return array ['content' => string, 'filename' => string, 'mimeType' => string]
     * @throws WhatsAppException
     */
    protected function prepareFileForUpload($file): array
    {
        if ($file instanceof UploadedFile) {
            // Handle Laravel UploadedFile
            if (!$file->isValid()) {
                throw new WhatsAppException('Invalid uploaded file');
            }

            $mimeType = $file->getMimeType();
            if (!str_starts_with($mimeType, 'image/')) {
                throw new WhatsAppException("File must be an image. Got: {$mimeType}");
            }

            return [
                'content' => file_get_contents($file->getRealPath()),
                'filename' => $file->getClientOriginalName(),
                'mimeType' => $mimeType,
            ];
        }

        // Handle file path (string)
        if (!is_string($file)) {
            throw new WhatsAppException('File must be a file path or UploadedFile instance');
        }

        if (!file_exists($file)) {
            throw new WhatsAppException("File not found: {$file}");
        }

        $mimeType = mime_content_type($file);
        if (!str_starts_with($mimeType, 'image/')) {
            throw new WhatsAppException("File must be an image. Got: {$mimeType}");
        }

        return [
            'content' => file_get_contents($file),
            'filename' => basename($file),
            'mimeType' => $mimeType,
        ];
    }

    /**
     * Verify webhook from WhatsApp (GET request)
     * 
     * @param mixed $request Request object
     * @return mixed Challenge string or error response
     */
    public function verifyWebhook($request)
    {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($mode === 'subscribe' && $token === config('whatsapp.verify_token')) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Invalid Verify Token', 403);
    }
}
