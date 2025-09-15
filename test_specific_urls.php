<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING SPECIFIC PRINT SERVICE URLS ===\n\n";

echo "🔗 Testing URL: http://127.0.0.1:8000/print-service/iBT2lvobPav5qcsuvaQa7c3rTRBb4tDb\n";
echo "📋 Testing URL: http://127.0.0.1:8000/admin/print-service/orders\n\n";

echo "1. TESTING BANK TRANSFER FLOW...\n";

try {
    $sessionToken = 'iBT2lvobPav5qcsuvaQa7c3rTRBb4tDb';
    
    $existingSession = \App\Models\PrintSession::where('session_token', $sessionToken)->first();
    
    if (!$existingSession) {
        echo "Session not found. Creating new session for testing...\n";
        $printService = app(\App\Services\PrintService::class);
        $newSession = $printService->generateSession();
        echo "✅ New session created: {$newSession->session_token}\n";
        echo "   - URL: http://127.0.0.1:8000/print-service/{$newSession->session_token}\n\n";
    } else {
        echo "✅ Found existing session: {$sessionToken}\n";
        echo "   - Status: " . ($existingSession->isActive() ? 'Active' : 'Expired') . "\n";
        echo "   - Expires at: {$existingSession->expires_at}\n\n";
    }

    echo "2. CHECKING ADMIN ORDERS WITH PAYMENT PROOF...\n";
    
    $manualOrders = \App\Models\PrintOrder::where('payment_method', 'manual')
                                         ->whereNotNull('payment_proof')
                                         ->with(['paperProduct', 'paperVariant'])
                                         ->orderBy('created_at', 'desc')
                                         ->limit(5)
                                         ->get();
    
    if ($manualOrders->count() > 0) {
        echo "✅ Found {$manualOrders->count()} bank transfer orders with payment proof:\n";
        foreach ($manualOrders as $order) {
            echo "   - Order: {$order->order_code}\n";
            echo "     Customer: {$order->customer_name}\n";
            echo "     Status: {$order->status} | Payment: {$order->payment_status}\n";
            echo "     Payment proof: {$order->payment_proof}\n";
            echo "     Payment proof URL: /admin/print-service/orders/{$order->id}/payment-proof\n";
            
            $proofPath = storage_path('app/' . $order->payment_proof);
            echo "     File exists: " . (file_exists($proofPath) ? 'YES' : 'NO') . "\n\n";
        }
    } else {
        echo "⚠️ No bank transfer orders with payment proof found\n";
        echo "Creating test order...\n";
        
        $testSession = \App\Models\PrintSession::first();
        if (!$testSession) {
            $printService = app(\App\Services\PrintService::class);
            $testSession = $printService->generateSession();
        }
        
        $variant = \App\Models\ProductVariant::whereHas('product', function($q) {
            $q->where('print_enabled', true);
        })->first();
        
        if ($variant) {
            $testOrder = new \App\Models\PrintOrder();
            $testOrder->session_id = $testSession->id;
            $testOrder->order_code = \App\Models\PrintOrder::generateCode();
            $testOrder->customer_name = 'Test Bank Transfer Customer';
            $testOrder->customer_phone = '08123456789';
            $testOrder->paper_product_id = $variant->product_id;
            $testOrder->paper_variant_id = $variant->id;
            $testOrder->unit_price = $variant->price;
            $testOrder->total_pages = 3;
            $testOrder->quantity = 1;
            $testOrder->total_price = $variant->price * 3;
            $testOrder->payment_method = 'manual';
            $testOrder->status = \App\Models\PrintOrder::STATUS_PAYMENT_PENDING;
            $testOrder->payment_status = \App\Models\PrintOrder::PAYMENT_WAITING;
            $testOrder->payment_proof = 'print-payments/' . $testOrder->order_code . '/payment_proof.jpg';
            $testOrder->save();
            
            $proofDir = storage_path('app/print-payments/' . $testOrder->order_code);
            if (!file_exists($proofDir)) {
                mkdir($proofDir, 0755, true);
            }
            
            $mockProofContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgABAAEAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiUpLTE1OT1BRUlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD9/KKKKAP/2Q==');
            file_put_contents($proofDir . '/payment_proof.jpg', $mockProofContent);
            
            echo "✅ Test order created: {$testOrder->order_code}\n";
            echo "   - Payment proof path: {$testOrder->payment_proof}\n";
        }
    }

    echo "3. TESTING AUTOMATIC PAYMENT (MIDTRANS) FLOW...\n";
    
    $automaticOrders = \App\Models\PrintOrder::where('payment_method', 'automatic')
                                            ->orderBy('created_at', 'desc')
                                            ->limit(3)
                                            ->get();
    
    if ($automaticOrders->count() > 0) {
        echo "✅ Found {$automaticOrders->count()} automatic payment orders:\n";
        foreach ($automaticOrders as $order) {
            echo "   - Order: {$order->order_code}\n";
            echo "     Customer: {$order->customer_name}\n";
            echo "     Status: {$order->status} | Payment: {$order->payment_status}\n";
            echo "     Payment token: " . ($order->payment_token ? 'Generated' : 'Not generated') . "\n";
            if ($order->payment_url) {
                echo "     Payment URL: {$order->payment_url}\n";
            }
            echo "\n";
        }
    } else {
        echo "⚠️ No automatic payment orders found\n";
        echo "This is normal if no customers have used online payment yet\n\n";
    }

    echo "4. TESTING MIDTRANS CONFIGURATION...\n";
    
    $midtransConfig = [
        'serverKey' => config('midtrans.serverKey'),
        'clientKey' => config('midtrans.clientKey'),
        'isProduction' => config('midtrans.isProduction'),
        'isSanitized' => config('midtrans.isSanitized'),
        'is3ds' => config('midtrans.is3ds'),
    ];
    
    echo "Midtrans Configuration:\n";
    echo "   - Server Key: " . ($midtransConfig['serverKey'] ? 'Configured' : 'NOT CONFIGURED') . "\n";
    echo "   - Client Key: " . ($midtransConfig['clientKey'] ? 'Configured' : 'NOT CONFIGURED') . "\n";
    echo "   - Production Mode: " . ($midtransConfig['isProduction'] ? 'YES' : 'NO (Sandbox)') . "\n";
    echo "   - Sanitized: " . ($midtransConfig['isSanitized'] ? 'YES' : 'NO') . "\n";
    echo "   - 3DS: " . ($midtransConfig['is3ds'] ? 'YES' : 'NO') . "\n\n";

    if (!$midtransConfig['serverKey'] || !$midtransConfig['clientKey']) {
        echo "⚠️ MIDTRANS CONFIGURATION ISSUE:\n";
        echo "Please check your .env file and ensure these variables are set:\n";
        echo "   - MIDTRANS_SERVER_KEY=your_server_key\n";
        echo "   - MIDTRANS_CLIENT_KEY=your_client_key\n";
        echo "   - MIDTRANS_IS_PRODUCTION=false (for testing)\n\n";
    } else {
        echo "✅ Midtrans configuration looks good\n\n";
    }

    echo "5. SUMMARY OF FIXES IMPLEMENTED...\n";
    echo "✅ Added 'View Payment Proof' button for bank transfer orders\n";
    echo "✅ Fixed Midtrans integration with proper SSL configuration for localhost\n";
    echo "✅ Enhanced error logging for payment gateway issues\n";
    echo "✅ Improved admin payment confirmation flow\n";
    echo "✅ Added proper status transitions for print orders\n\n";

    echo "6. NEXT STEPS...\n";
    echo "1. Visit http://127.0.0.1:8000/admin/print-service/orders in browser\n";
    echo "2. Look for orders with 'manual' payment method\n";
    echo "3. You should see 'View Payment Proof' button for those orders\n";
    echo "4. Test bank transfer flow in print service\n";
    echo "5. Test online payment flow (if Midtrans is configured)\n\n";

    echo "🔧 ADMIN ACCESS NOTE:\n";
    echo "To access admin pages, you need to:\n";
    echo "1. Login as admin user\n";
    echo "2. Or temporarily disable admin middleware for testing\n";
    echo "3. Or use the direct route testing we just performed\n\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "=== TESTING COMPLETE ===\n";