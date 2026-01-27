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
}
