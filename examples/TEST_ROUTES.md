# Test Routes for Development

This file contains example test routes that were removed from the package for security reasons. These routes can be useful during development and testing, but should **NEVER** be enabled in production environments.

## Security Concerns

These routes expose the following security risks in production:
- `/test-whatsapp` - Configuration disclosure (exposes WhatsApp configuration details)
- `/send-whatsapp-test` - Send messages without authentication (allows unauthorized message sending)

## Usage

If you need these routes for local development or testing, you can add them to your application's `routes/web.php` or `routes/api.php` file.

**Important:** Make sure to protect these routes with proper authentication and authorization middleware in production, or only enable them in non-production environments.

## Test Route Examples

### 1. Configuration Test Route

This route checks if WhatsApp is properly configured and returns the configuration status:

```php
Route::get('/test-whatsapp', function () {
    try {
        if (!config('whatsapp.phone_id') || !config('whatsapp.token')) {
            return response()->json([
                'status' => 'error',
                'message' => 'WhatsApp not configured. Please set WHATSAPP_PHONE_ID and WHATSAPP_TOKEN in .env'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'WhatsApp package installed successfully!',
            'config' => [
                'phone_id' => config('whatsapp.phone_id') ? 'Configured' : 'Not set',
                'token' => config('whatsapp.token') ? 'Configured' : 'Not set',
                'api_version' => config('whatsapp.api_version'),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->name('whatsapp.test.status');
```

### 2. Send Test Message Route

This route sends a test WhatsApp template message:

```php
Route::get('/send-whatsapp-test', function () {
    try {
        $phone = request('phone');

        if (!$phone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please provide phone parameter. Example: /send-whatsapp-test?phone=1234567890'
            ], 400);
        }

        $response = \Duli\WhatsApp\Facades\WhatsApp::sendTemplate(
            $phone,
            'hello_world',
            'en_US'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'WhatsApp template message sent successfully!',
            'response' => $response
        ]);
    } catch (\Duli\WhatsApp\Exceptions\WhatsAppException $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'error_code' => $e->getErrorCode(),
            'response' => $e->getResponse()
        ], 500);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->name('whatsapp.test.send');
```

## Recommended Secure Implementation

If you want to use these routes in your application, consider protecting them with authentication:

```php
// Only enable in non-production environments
if (app()->environment('local', 'development')) {
    Route::middleware(['auth'])->group(function () {
        // Add the test routes here
    });
}
```

Or use environment-based routing:

```php
// In routes/api.php or routes/web.php
if (config('app.env') !== 'production') {
    // Add test routes here with appropriate middleware
    Route::middleware(['auth', 'admin'])->group(function () {
        // Test routes
    });
}
```
