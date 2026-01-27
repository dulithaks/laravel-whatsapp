# Installation & Setup Guide

This guide will walk you through setting up the WhatsApp Cloud API package in your Laravel application.

## Prerequisites

Before you begin, ensure you have:

1. A Meta Business Account
2. A WhatsApp Business Account
3. A registered phone number for WhatsApp Business API
4. A Laravel 10+ or 11+ application

## Step 1: Meta Business Setup

### 1.1 Create Meta Business App

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Click **My Apps** → **Create App**
3. Select **Business** as the app type
4. Fill in app details and create

### 1.2 Add WhatsApp Product

1. In your app dashboard, click **Add Product**
2. Find **WhatsApp** and click **Set Up**
3. Select or create a Business Account

### 1.3 Get Credentials

**Phone Number ID:**

1. Go to WhatsApp → API Setup
2. Copy the **Phone Number ID** (starts with a long number)

**Access Token:**

1. In API Setup, you'll see a temporary token
2. For production, generate a permanent token:
    - Go to App Settings → Basic
    - Copy your App ID and App Secret
    - Use the System User token or generate a permanent token via Graph API

**Note:** The temporary token expires in 24 hours. For production, you must create a permanent token.

### 1.4 Add Test Phone Number (Optional for Development)

1. In WhatsApp → API Setup
2. Under **To**, add your phone number
3. Verify with the code sent to your WhatsApp

## Step 2: Install Package

### 2.1 Add Repository (Local Development Only)

If installing from a local packages directory, add to your `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/Duli/WhatsApp"
    }
]
```

### 2.2 Install via Composer

For production (once published to Packagist):

```bash
composer require dulithaks/whatsapp
```

For local development:

```bash
composer require dulithaks/whatsapp:@dev
```

### 2.3 Publish Configuration

```bash
php artisan vendor:publish --provider="Duli\WhatsApp\WhatsAppServiceProvider" --tag="config"
```

This creates `config/whatsapp.php`

## Step 3: Configure Environment

Add to your `.env` file:

```env
# Required
WHATSAPP_PHONE_ID=your_phone_number_id_here
WHATSAPP_TOKEN=your_access_token_here
WHATSAPP_VERIFY_TOKEN=create_a_random_secure_string

# Optional (with defaults)
WHATSAPP_API_VERSION=v20.0
WHATSAPP_TIMEOUT=30
WHATSAPP_RETRY_TIMES=3
WHATSAPP_RETRY_DELAY=100
WHATSAPP_MARK_AS_READ=false
WHATSAPP_WEBHOOK_PREFIX=webhook
```

**Important:**

- `WHATSAPP_VERIFY_TOKEN` should be a random, secure string you create (e.g., `your-secret-verify-token-2026`)
- Never commit these credentials to version control

## Step 4: Configure Webhook

### 4.1 Make Your Webhook Accessible

For **local development**, use ngrok or similar:

```bash
ngrok http 80
```

Copy the HTTPS URL provided (e.g., `https://abc123.ngrok.io`)

For **production**, use your domain (e.g., `https://yourdomain.com`)

### 4.2 Configure in Meta

1. Go to WhatsApp → Configuration in your Meta app
2. Click **Edit** in the Webhook section
3. Enter your webhook details:
    - **Callback URL:** `https://yourdomain.com/webhook/whatsapp`
    - **Verify Token:** Same value as your `WHATSAPP_VERIFY_TOKEN`
4. Click **Verify and Save**

### 4.3 Subscribe to Webhook Fields

1. In the same Webhook section
2. Click **Manage**
3. Subscribe to these fields:
    - `messages` - To receive incoming messages
    - `message_status` - To receive delivery/read receipts

## Step 5: Test Your Setup

### 5.1 Verify Installation

The package automatically registers test routes. Visit:

```
http://yourdomain.test/test-whatsapp
```

This will verify the package is installed and show configuration status.

### 5.2 Test Sending a Message

Once you've added your credentials to `.env`, test sending a message:

```
http://yourdomain.test/send-whatsapp-test?phone=1234567890
```

Replace `1234567890` with your phone number in international format (no + or 00).

### 5.3 Custom Test Route

You can also create custom test routes in `routes/web.php`:

```php
use Duli\WhatsApp\Facades\WhatsApp;

Route::get('/my-whatsapp-test', function () {
    try {
        $response = WhatsApp::sendMessage(
            '1234567890', // Replace with your phone number (international format, no +)
            'Hello from Laravel! 👋'
        );

        return response()->json($response);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
```

1. Send a message to your WhatsApp Business number
2. Check your logs:
    ```bash
    tail -f storage/logs/laravel.log
    ```
3. You should see logged webhook events

## Step 6: Create Message Templates (Optional)

To send template messages, you need to create templates in Meta Business Manager:

1. Go to [Meta Business Suite](https://business.facebook.com/)
2. Click **All Tools** → **WhatsApp Manager**
3. Select your WhatsApp Business Account
4. Go to **Message Templates**
5. Click **Create Template**
6. Follow the wizard to create your template
7. Wait for approval (usually within 24 hours)

Once approved, use in code:

```php
WhatsApp::sendTemplate('1234567890', 'your_template_name', 'en', ['param1', 'param2']);
```

## Step 7: Production Checklist

Before going live:

- [ ] Generate permanent access token (not temporary)
- [ ] Set up proper webhook URL (HTTPS required)
- [ ] Configure webhook verify token
- [ ] Subscribe to necessary webhook fields
- [ ] Test sending messages
- [ ] Test receiving messages
- [ ] Set up error monitoring/logging
- [ ] Review rate limits in Meta Business Manager
- [ ] Add business verification (for higher limits)
- [ ] Create and approve message templates
- [ ] Implement proper error handling in your code
- [ ] Set up message queue for high volume (optional)

## Troubleshooting

### "Invalid OAuth access token"

- Your token may have expired (use permanent token)
- Check if token has correct permissions
- Verify you're using the correct phone number ID

### "Webhook verification failed"

- Ensure verify tokens match exactly
- Check webhook URL is publicly accessible
- Verify URL uses HTTPS (required)
- Check server logs for incoming requests

### "Phone number not registered"

- Add recipient to test numbers (development)
- Verify recipient has WhatsApp installed
- Use international format without + or 00

### "Rate limit exceeded"

- Check your messaging limits in Meta Business Manager
- Implement message queuing
- Request higher limits (requires business verification)

### Messages not being received

- Check webhook subscriptions
- Verify webhook URL is correct
- Check application logs
- Ensure middleware isn't blocking requests

## Support & Resources

- [WhatsApp Cloud API Docs](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Meta Business Help Center](https://www.facebook.com/business/help)
- [Package README](README.md)
- [API Reference](https://developers.facebook.com/docs/whatsapp/cloud-api/reference)

## Next Steps

1. Review the [README.md](README.md) for full usage examples
2. Check [examples/WhatsAppExampleController.php](examples/WhatsAppExampleController.php) for code samples
3. Customize the webhook controller for your needs
4. Set up message templates in Meta Business Manager
5. Implement your business logic

## Common Use Cases

- **Customer Support:** Automated responses, interactive menus
- **Notifications:** Order confirmations, shipping updates
- **Marketing:** Promotional messages (requires opt-in)
- **Authentication:** OTP verification codes
- **Alerts:** System notifications, reminders

Happy messaging! 🚀
