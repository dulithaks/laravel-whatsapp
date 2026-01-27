# WhatsApp Cloud API for Laravel

A comprehensive Laravel package for integrating WhatsApp Cloud API. Send and receive messages, media files, interactive buttons, lists, and handle webhooks with ease.

## Features

- ✅ Send text messages with URL previews
- ✅ Send media (images, videos, documents, audio)
- ✅ Send locations
- ✅ Send template messages
- ✅ Send interactive buttons and lists
- ✅ Send reactions to messages
- ✅ Mark messages as read
- ✅ Handle incoming webhooks (messages, status updates)
- ✅ Automatic retry on failure
- ✅ Comprehensive error handling
- ✅ Support for multiple message types
- ✅ Full webhook payload parsing

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- WhatsApp Business API access
- Meta Business App with WhatsApp product

## Installation

### For Production (via Packagist)

Once published to Packagist, install via Composer:

```bash
composer require dulithaks/whatsapp
```

### For Local Development

If developing locally or using from the packages directory:

1. Add the repository to your `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/dulithaks/laravel-whatsapp"
    }
]
```

2. Require the package:

```bash
composer require dulithaks/laravel-whatsapp:v1.0.x
```

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Duli\WhatsApp\WhatsAppServiceProvider" --tag="config"
```

## Configuration

Add your WhatsApp credentials to your `.env` file:

```env
WHATSAPP_PHONE_ID=your_phone_number_id
WHATSAPP_TOKEN=your_permanent_access_token
WHATSAPP_VERIFY_TOKEN=your_webhook_verify_token
WHATSAPP_API_VERSION=v20.0
WHATSAPP_TIMEOUT=30
WHATSAPP_RETRY_TIMES=3
WHATSAPP_RETRY_DELAY=100
WHATSAPP_MARK_AS_READ=false
```

### Getting Your Credentials

1. **Phone Number ID**: Find this in your Meta Business Manager > WhatsApp > API Setup
2. **Access Token**: Generate a permanent token from Meta Business App > Settings > WhatsApp > Access Token
3. **Verify Token**: Create a secure random string for webhook verification

## Usage

### Sending Messages

#### Text Messages

```php
use Duli\WhatsApp\Facades\WhatsApp;

// Simple text message
WhatsApp::sendMessage('1234567890', 'Hello from Laravel!');

// Text message with URL preview
WhatsApp::sendMessage('1234567890', 'Check this out: https://example.com', true);
```

#### Template Messages

```php
// Send template without parameters
WhatsApp::sendTemplate('1234567890', 'hello_world');

// Send template with parameters and custom language
WhatsApp::sendTemplate(
    '1234567890',
    'order_confirmation',
    'en',
    ['John Doe', 'ORD-12345', '$99.99']
);
```

#### Media Messages

```php
// Send image
WhatsApp::sendImage('1234567890', 'https://example.com/image.jpg', 'Image caption');

// Send document
WhatsApp::sendDocument(
    '1234567890',
    'https://example.com/document.pdf',
    'invoice.pdf',
    'Your invoice'
);

// Send video
WhatsApp::sendVideo('1234567890', 'https://example.com/video.mp4', 'Video caption');

// Send audio
WhatsApp::sendAudio('1234567890', 'https://example.com/audio.mp3');

// Send using media ID (after uploading)
WhatsApp::sendImage('1234567890', 'media_id_here', null, true);
```

#### Location Messages

```php
WhatsApp::sendLocation(
    '1234567890',
    37.4847483695049,
    -122.1473373086664,
    'Meta Headquarters',
    '1 Hacker Way, Menlo Park, CA 94025'
);
```

#### Interactive Buttons

```php
$buttons = [
    ['id' => 'btn_1', 'title' => 'Option 1'],
    ['id' => 'btn_2', 'title' => 'Option 2'],
    ['id' => 'btn_3', 'title' => 'Option 3'],
];

WhatsApp::sendButtons(
    '1234567890',
    'Please select an option:',
    $buttons,
    'Interactive Menu',  // header (optional)
    'Powered by Laravel' // footer (optional)
);
```

#### Interactive Lists

```php
$sections = [
    [
        'title' => 'Section 1',
        'rows' => [
            ['id' => 'row_1', 'title' => 'Row 1', 'description' => 'Description 1'],
            ['id' => 'row_2', 'title' => 'Row 2', 'description' => 'Description 2'],
        ]
    ],
    [
        'title' => 'Section 2',
        'rows' => [
            ['id' => 'row_3', 'title' => 'Row 3', 'description' => 'Description 3'],
        ]
    ]
];

WhatsApp::sendList(
    '1234567890',
    'Choose an option from the list:',
    'View Options',
    $sections,
    'Menu Header',
    'Menu Footer'
);
```

#### Reactions

```php
// Send reaction
WhatsApp::sendReaction('1234567890', 'message_id_here', '👍');

// Remove reaction (empty emoji)
WhatsApp::sendReaction('1234567890', 'message_id_here', '');
```

#### Mark as Read

```php
WhatsApp::markAsRead('message_id_from_webhook');
```

### Receiving Messages (Webhooks)

The package automatically registers webhook routes at `/webhook/whatsapp`. Configure this URL in your Meta Business Manager.

#### Webhook Setup

1. Go to Meta Business Manager > WhatsApp > Configuration
2. Set Webhook URL: `https://yourdomain.com/webhook/whatsapp`
3. Set Verify Token: Same as your `WHATSAPP_VERIFY_TOKEN`
4. Subscribe to webhook fields: `messages`, `message_status`

#### Handling Incoming Messages

The webhook controller automatically logs all incoming messages. To handle them, you can:

**Option 1: Extend the Controller**

```php
namespace App\Http\Controllers;

use Duli\WhatsApp\Http\Controllers\WhatsAppWebhookController as BaseController;
use Illuminate\Support\Facades\Log;

class CustomWebhookController extends BaseController
{
    protected function handleMessage(array $message, array $value): void
    {
        parent::handleMessage($message, $value);

        // Your custom logic here
        $from = $message['from'];
        $type = $message['type'];

        if ($type === 'text') {
            $text = $message['text']['body'];
            // Process text message
        }
    }
}
```

Then update your routes:

```php
Route::get('/webhook/whatsapp', [CustomWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [CustomWebhookController::class, 'receive']);
```

**Option 2: Listen to Logs**

All messages are logged to `Log::info()`. You can process them via log monitoring or create custom log handlers.

#### Message Types Received

The webhook handles these message types:

- Text messages
- Images
- Videos
- Audio
- Documents
- Locations
- Contacts
- Interactive button replies
- Interactive list replies
- Reactions

#### Status Updates

Message status updates (sent, delivered, read, failed) are also logged:

```php
protected function handleStatus(array $status, array $value): void
{
    // Access status: sent, delivered, read, failed
    $messageId = $status['id'];
    $statusType = $status['status'];
}
```

## Error Handling

All API calls throw `WhatsAppException` on failure:

```php
use Duli\WhatsApp\Facades\WhatsApp;
use Duli\WhatsApp\Exceptions\WhatsAppException;

try {
    WhatsApp::sendMessage('1234567890', 'Hello!');
} catch (WhatsAppException $e) {
    Log::error('WhatsApp Error: ' . $e->getMessage());

    // Get full response
    $response = $e->getResponse();

    // Get error details
    $errorCode = $e->getErrorCode();
    $errorType = $e->getErrorType();
}
```

## Testing

### Quick Installation Test

The package includes built-in test routes (enabled by default in development):

```bash
# Check package installation and configuration status
http://yourdomain.test/test-whatsapp
```

This will show the package status and configuration.

### Send Test Template Message

To send a test message using the `hello_world` template (requires configuration):

```bash
# Sends hello_world template to the specified phone number
http://yourdomain.test/send-whatsapp-test?phone=1234567890
```

**Note:** Replace `1234567890` with your phone number in international format (no + or 00).

### Disable Test Routes in Production

Test routes are automatically disabled in production (`APP_DEBUG=false`). To manually control:

```env
# In your .env file
WHATSAPP_ENABLE_TEST_ROUTES=false
```

### Webhook Integration Testing

To test webhook integration locally:

1. Use ngrok or similar tool to expose your local server:

    ```bash
    ngrok http 80
    ```

2. Configure the ngrok URL in Meta Business Manager

3. Monitor logs:
    ```bash
    tail -f storage/logs/laravel.log
    ```

## Advanced Configuration

### Custom Routes

Disable automatic route registration in the service provider and define your own:

```php
// config/whatsapp.php
'webhook' => [
    'prefix' => 'api/webhooks',
    'middleware' => ['api', 'verify.signature'],
],
```

### API Version

Update the API version if needed:

```env
WHATSAPP_API_VERSION=v21.0
```

### Timeout & Retries

Adjust timeout and retry behavior:

```env
WHATSAPP_TIMEOUT=60
WHATSAPP_RETRY_TIMES=5
WHATSAPP_RETRY_DELAY=200
```

## Common Issues

### Message Not Delivered

- Verify the phone number is in international format (no + or 00)
- Ensure the recipient has an active WhatsApp account
- Check rate limits in Meta Business Manager

### Webhook Not Receiving Events

- Verify webhook URL is publicly accessible (HTTPS required)
- Check verify token matches
- Review webhook subscription fields

### Authentication Errors

- Ensure access token is permanent (not temporary)
- Verify phone number ID is correct
- Check token permissions include `whatsapp_business_messaging`

## Resources

- [WhatsApp Cloud API Documentation](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Meta Business Manager](https://business.facebook.com/)
- [Getting Started Guide](https://developers.facebook.com/docs/whatsapp/cloud-api/get-started)

## License

MIT License - see LICENSE file for details

## Contributing

Contributions are welcome! Please submit pull requests or open issues on GitHub.

## Support

For issues and questions:

- [GitHub Issues](https://github.com/dulithaks/laravel-whatsapp/issues)
- [Documentation](https://github.com/dulithaks/laravel-whatsapp/wiki)
