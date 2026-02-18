<?php

namespace App\Http\Controllers;

use Duli\WhatsApp\Facades\WhatsApp;
use Duli\WhatsApp\Exceptions\WhatsAppException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Example controller demonstrating WhatsApp package usage
 * 
 * Copy this to your app/Http/Controllers directory and customize as needed
 */
class WhatsAppExampleController extends Controller
{
    /**
     * Send a welcome message
     */
    public function sendWelcome(Request $request)
    {
        try {
            $phone = $request->input('phone');
            $name = $request->input('name', 'there');

            $response = WhatsApp::sendMessage(
                $phone,
                "Hello {$name}! Welcome to our service. 👋"
            );

            return response()->json([
                'success' => true,
                'message' => 'Welcome message sent',
                'data' => $response
            ]);
        } catch (WhatsAppException $e) {
            Log::error('Failed to send welcome message', [
                'error' => $e->getMessage(),
                'phone' => $phone ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send an order confirmation with template
     */
    public function sendOrderConfirmation(Request $request)
    {
        try {
            $phone = $request->input('phone');
            $orderNumber = $request->input('order_number');
            $amount = $request->input('amount');

            // Make sure you have created this template in Meta Business Manager
            $response = WhatsApp::sendTemplate(
                $phone,
                'order_confirmation',
                'en',
                [$orderNumber, $amount]
            );

            return response()->json([
                'success' => true,
                'message' => 'Order confirmation sent',
                'data' => $response
            ]);
        } catch (WhatsAppException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send order confirmation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send an interactive menu
     */
    public function sendMenu(Request $request)
    {
        try {
            $phone = $request->input('phone');

            $buttons = [
                ['id' => 'view_products', 'title' => 'View Products'],
                ['id' => 'track_order', 'title' => 'Track Order'],
                ['id' => 'contact_support', 'title' => 'Contact Support'],
            ];

            $response = WhatsApp::sendButtons(
                $phone,
                'How can we help you today?',
                $buttons,
                'Main Menu',
                'Reply with your choice'
            );

            return response()->json([
                'success' => true,
                'message' => 'Menu sent',
                'data' => $response
            ]);
        } catch (WhatsAppException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send menu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a product catalog list
     */
    public function sendProductList(Request $request)
    {
        try {
            $phone = $request->input('phone');

            $sections = [
                [
                    'title' => 'Electronics',
                    'rows' => [
                        ['id' => 'prod_1', 'title' => 'Laptop', 'description' => '$999 - High performance'],
                        ['id' => 'prod_2', 'title' => 'Phone', 'description' => '$699 - Latest model'],
                    ]
                ],
                [
                    'title' => 'Accessories',
                    'rows' => [
                        ['id' => 'prod_3', 'title' => 'Headphones', 'description' => '$199 - Noise cancelling'],
                        ['id' => 'prod_4', 'title' => 'Charger', 'description' => '$29 - Fast charging'],
                    ]
                ]
            ];

            $response = WhatsApp::sendList(
                $phone,
                'Browse our product catalog and select items you\'re interested in.',
                'View Products',
                $sections,
                'Product Catalog',
                'Prices in USD'
            );

            return response()->json([
                'success' => true,
                'message' => 'Product list sent',
                'data' => $response
            ]);
        } catch (WhatsAppException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send product list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send an invoice document
     */
    public function sendInvoice(Request $request)
    {
        try {
            $phone = $request->input('phone');
            $invoiceUrl = $request->input('invoice_url');
            $invoiceNumber = $request->input('invoice_number');

            $response = WhatsApp::sendDocument(
                $phone,
                $invoiceUrl,
                "invoice_{$invoiceNumber}.pdf",
                "Your invoice #{$invoiceNumber}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice sent',
                'data' => $response
            ]);
        } catch (WhatsAppException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send location (e.g., store location)
     */
    public function sendStoreLocation(Request $request)
    {
        try {
            $phone = $request->input('phone');

            $response = WhatsApp::sendLocation(
                $phone,
                37.7749,
                -122.4194,
                'Our Store',
                '123 Market St, San Francisco, CA 94103'
            );

            return response()->json([
                'success' => true,
                'message' => 'Location sent',
                'data' => $response
            ]);
        } catch (WhatsAppException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload an image and send it
     * 
     * Example: Upload from a local file path
     */
    public function uploadAndSendImage(Request $request)
    {
        try {
            $phone = $request->input('phone');
            $imagePath = $request->input('image_path'); // e.g., storage/app/images/product.jpg

            // Upload and send in one call
            $response = WhatsApp::uploadAndSendImage(
                $phone,
                $imagePath,
                'Check out this image!'
            );

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded and sent',
                'data' => $response
            ]);
        } catch (WhatsAppException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload and send image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload an image from form request and send
     * 
     * Example: Handle image upload from a form
     */
    public function uploadFromRequest(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'image' => 'required|image|max:5120', // max 5MB
                'caption' => 'nullable|string|max:1024'
            ]);

            $phone = $request->input('phone');
            $caption = $request->input('caption', 'Here is your image');

            // Option 1: Upload and send in one call
            $response = WhatsApp::uploadAndSendImage(
                $phone,
                $request->file('image'),
                $caption
            );

            // Option 2: Upload first, then send (useful if you need the media ID)
            // $mediaId = WhatsApp::uploadImage($request->file('image'));
            // $response = WhatsApp::sendImage($phone, $mediaId, $caption, true);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded and sent successfully',
                'data' => $response
            ]);
        } catch (WhatsAppException $e) {
            Log::error('Failed to upload image from request', [
                'error' => $e->getMessage(),
                'phone' => $request->input('phone'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Upload image and get media ID for later use
     * 
     * Example: Upload image and store media ID in database
     */
    public function uploadImageOnly(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|max:5120'
            ]);

            // Upload and get media ID
            $mediaId = WhatsApp::uploadImage($request->file('image'));

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'media_id' => $mediaId,
                'note' => 'Media ID is valid for ~30 days and can be reused'
            ]);
        } catch (WhatsAppException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
