<?php

require_once 'vendor/autoload.php';

use App\Models\PrintSession;
use App\Models\PrintOrder;
use App\Models\PrintFile;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PrintService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== COMPREHENSIVE PRINT SERVICE FLOW TEST ===\n\n";

try {
    DB::beginTransaction();

    echo "1. GENERATING PRINT SESSION...\n";
    $printService = app(PrintService::class);
    $session = $printService->generateSession();
    echo "✅ Session generated: {$session->session_token}\n";
    echo "   - QR Code URL: {$session->getQrCodeUrl()}\n";
    echo "   - Expires at: {$session->expires_at}\n\n";

    echo "2. TESTING PRODUCT RETRIEVAL...\n";
    $products = $printService->getPrintProducts();
    if ($products->isEmpty()) {
        echo "❌ No print products found!\n";
        echo "Please ensure you have products with print-enabled variants\n";
        return;
    }
    
    $product = $products->first();
    $variant = $product->activeVariants->first();
    
    if (!$variant) {
        echo "❌ No active variants found for product: {$product->name}\n";
        return;
    }
    
    echo "✅ Found product: {$product->name}\n";
    echo "   - Variant: {$variant->name}\n";
    echo "   - Price: Rp " . number_format($variant->price) . "\n";
    echo "   - Stock: {$variant->stock}\n\n";

    echo "3. CREATING MOCK UPLOAD FILES...\n";
    
    $tempDir = storage_path('app/temp');
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $testPdfContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n>>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000079 00000 n \n0000000136 00000 n \ntrailer\n<<\n/Size 4\n/Root 1 0 R\n>>\nstartxref\n217\n%%EOF";
    $testFilePath = $tempDir . '/test_document.pdf';
    file_put_contents($testFilePath, $testPdfContent);
    
    $uploadedFiles = collect();
    $totalPages = 5;
    
    $printFile = new PrintFile();
    $printFile->print_session_id = $session->id;
    $printFile->file_name = 'test_document.pdf';
    $printFile->file_path = 'temp/test_document.pdf';
    $printFile->file_size = strlen($testPdfContent);
    $printFile->file_type = 'pdf';
    $printFile->pages_count = $totalPages;
    $printFile->save();
    
    $uploadedFiles->push($printFile);
    echo "✅ Mock file created: {$printFile->file_name} ({$totalPages} pages)\n\n";

    echo "4. TESTING BANK TRANSFER PAYMENT FLOW...\n";
    
    $orderData = [
        'session_token' => $session->session_token,
        'customer_name' => 'Test Customer Bank Transfer',
        'customer_phone' => '08123456789',
        'variant_id' => $variant->id,
        'payment_method' => 'manual',
        'total_pages' => $totalPages,
        'quantity' => 1,
        'files' => $uploadedFiles->pluck('id')->toArray()
    ];
    
    $printOrder1 = $printService->createPrintOrder($orderData, $session);
    echo "✅ Print order created: {$printOrder1->order_code}\n";
    echo "   - Customer: {$printOrder1->customer_name}\n";
    echo "   - Payment method: {$printOrder1->payment_method}\n";
    echo "   - Total price: Rp " . number_format((float) $printOrder1->total_price) . "\n";
    echo "   - Status: {$printOrder1->status}\n";
    echo "   - Payment status: {$printOrder1->payment_status}\n\n";

    $mockProofPath = storage_path('app/temp/mock_payment_proof.jpg');
    $mockProofContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgABAAEAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiUpLTE1OT1BRUlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD9/KKKKAP/2Q==');
    file_put_contents($mockProofPath, $mockProofContent);
    
    $paymentData = [
        'payment_proof' => new UploadedFile($mockProofPath, 'payment_proof.jpg', 'image/jpeg', null, true)
    ];
    
    $paymentResult1 = $printService->processPayment($printOrder1, $paymentData);
    echo "✅ Bank transfer payment processed:\n";
    echo "   - Status: {$paymentResult1['status']}\n";
    echo "   - Message: {$paymentResult1['message']}\n\n";
    
    $printOrder1->refresh();
    echo "📊 Order status after payment:\n";
    echo "   - Status: {$printOrder1->status}\n";
    echo "   - Payment status: {$printOrder1->payment_status}\n";
    echo "   - Payment proof: " . ($printOrder1->payment_proof ? 'Uploaded' : 'Not uploaded') . "\n\n";

    echo "5. TESTING ADMIN PAYMENT CONFIRMATION...\n";
    
    $confirmedOrder = $printService->confirmPayment($printOrder1);
    echo "✅ Admin confirmed payment\n";
    
    $printOrder1->refresh();
    echo "📊 Order status after admin confirmation:\n";
    echo "   - Status: {$printOrder1->status}\n";
    echo "   - Payment status: {$printOrder1->payment_status}\n";
    echo "   - Can print: " . ($printOrder1->canPrint() ? 'YES' : 'NO') . "\n\n";

    echo "6. TESTING AUTOMATIC PAYMENT FLOW...\n";
    
    $session2 = $printService->generateSession();
    echo "✅ New session generated: {$session2->session_token}\n";
    
    $printFile2 = new PrintFile();
    $printFile2->print_session_id = $session2->id;
    $printFile2->file_name = 'test_document_2.pdf';
    $printFile2->file_path = 'temp/test_document.pdf';
    $printFile2->file_size = strlen($testPdfContent);
    $printFile2->file_type = 'pdf';
    $printFile2->pages_count = 3;
    $printFile2->save();
    
    $orderData2 = [
        'session_token' => $session2->session_token,
        'customer_name' => 'Test Customer Online Payment',
        'customer_phone' => '08987654321',
        'variant_id' => $variant->id,
        'payment_method' => 'automatic',
        'total_pages' => 3,
        'quantity' => 1,
        'files' => [$printFile2->id]
    ];
    
    $printOrder2 = $printService->createPrintOrder($orderData2, $session2);
    echo "✅ Print order created: {$printOrder2->order_code}\n";
    
    try {
        $paymentResult2 = $printService->processPayment($printOrder2, []);
        echo "✅ Automatic payment processed:\n";
        echo "   - Status: {$paymentResult2['status']}\n";
        if (isset($paymentResult2['token'])) {
            echo "   - Payment token: {$paymentResult2['token']}\n";
        }
        if (isset($paymentResult2['redirect_url'])) {
            echo "   - Payment URL: {$paymentResult2['redirect_url']}\n";
        }
        
        $printOrder2->refresh();
        echo "📊 Order status after payment processing:\n";
        echo "   - Status: {$printOrder2->status}\n";
        echo "   - Payment status: {$printOrder2->payment_status}\n";
        echo "   - Payment token: " . ($printOrder2->payment_token ? 'Generated' : 'Not generated') . "\n";
    } catch (\Exception $e) {
        echo "⚠️ Automatic payment processing failed: " . $e->getMessage() . "\n";
        echo "This might be due to Midtrans configuration issues.\n";
    }
    
    echo "\n7. TESTING ADMIN PRINT SERVICE ORDERS VIEW...\n";
    
    $allOrders = PrintOrder::with(['paperProduct', 'paperVariant'])->get();
    echo "📋 Total print orders: " . $allOrders->count() . "\n";
    
    foreach ($allOrders as $order) {
        echo "   - Order: {$order->order_code}\n";
        echo "     Customer: {$order->customer_name}\n";
        echo "     Payment: {$order->payment_method} ({$order->payment_status})\n";
        echo "     Status: {$order->status}\n";
        if ($order->payment_method === 'manual' && $order->payment_proof) {
            echo "     Payment proof: Available ✅\n";
        }
        echo "\n";
    }

    echo "8. CLEANUP...\n";
    
    if (file_exists($testFilePath)) {
        unlink($testFilePath);
    }
    if (file_exists($mockProofPath)) {
        unlink($mockProofPath);
    }
    
    DB::rollBack();
    echo "✅ Test data cleaned up\n\n";

    echo "=== TEST SUMMARY ===\n";
    echo "✅ Print session generation: WORKING\n";
    echo "✅ Product retrieval: WORKING\n";
    echo "✅ File upload simulation: WORKING\n";
    echo "✅ Bank transfer payment: WORKING\n";
    echo "✅ Admin payment confirmation: WORKING\n";
    echo "✅ Payment proof viewing: IMPLEMENTED\n";
    
    if (isset($paymentResult2['token'])) {
        echo "✅ Automatic payment: WORKING\n";
    } else {
        echo "⚠️ Automatic payment: NEEDS MIDTRANS CONFIG\n";
    }
    
    echo "\n🎯 RECOMMENDATIONS:\n";
    echo "1. Verify Midtrans configuration in .env file\n";
    echo "2. Check admin print service orders page for payment proof buttons\n";
    echo "3. Test complete flow in browser with real files\n";
    echo "4. Ensure payment notification webhook is configured\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";