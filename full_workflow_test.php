<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🔍 FULL WORKFLOW TEST - NEW ORDER\n";
echo "==================================\n\n";

echo "1. Creating new test order via frontend workflow...\n";

echo "\n2. Checking latest order...\n";
$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
if (!$latestOrder) {
    echo "❌ No orders found\n";
    exit;
}

echo "Order: {$latestOrder->order_code}\n";
echo "Customer: {$latestOrder->customer_name}\n";
echo "Status: {$latestOrder->status}\n";
echo "Payment: {$latestOrder->payment_status}\n";
echo "Session ID: {$latestOrder->session_id}\n";
echo "Created: {$latestOrder->created_at}\n";

echo "\n3. Files directly linked to order:\n";
$orderFiles = PrintFile::where('print_order_id', $latestOrder->id)->get();
echo "Count: " . $orderFiles->count() . "\n";

foreach ($orderFiles as $file) {
    echo "  File ID: {$file->id}\n";
    echo "  Path: {$file->file_path}\n";
    echo "  Name: {$file->file_name}\n";
    echo "  Type: {$file->file_type}\n";
    echo "  Size: {$file->file_size}\n";
    echo "  Pages: {$file->pages_count}\n";
    echo "  Order ID: {$file->print_order_id}\n";
    echo "  Session ID: {$file->print_session_id}\n";
    echo "  Created: {$file->created_at}\n";
    echo "  ---\n";
}

echo "\n4. Files linked to session:\n";
if ($latestOrder->session_id) {
    $sessionFiles = PrintFile::where('print_session_id', $latestOrder->session_id)->get();
    echo "Count: " . $sessionFiles->count() . "\n";
    
    foreach ($sessionFiles as $file) {
        echo "  File ID: {$file->id}\n";
        echo "  Path: {$file->file_path}\n";
        echo "  Session ID: {$file->print_session_id}\n";
        echo "  Order ID: {$file->print_order_id}\n";
        echo "  ---\n";
    }
} else {
    echo "No session ID found\n";
}

echo "\n5. Testing model relationship:\n";
$testOrder = PrintOrder::with(['files'])->find($latestOrder->id);
echo "Files via relationship: " . $testOrder->files->count() . "\n";

echo "\n6. Checking file existence:\n";
$allFiles = PrintFile::where('print_order_id', $latestOrder->id)
    ->orWhere('print_session_id', $latestOrder->session_id)
    ->get();

foreach ($allFiles as $file) {
    $storagePath = storage_path('app/' . $file->file_path);
    $publicPath = public_path('storage/' . $file->file_path);
    
    echo "File: {$file->file_path}\n";
    echo "  Storage exists: " . (file_exists($storagePath) ? "YES" : "NO") . "\n";
    echo "  Public exists: " . (file_exists($publicPath) ? "YES" : "NO") . "\n";
    
    if (file_exists($storagePath)) {
        echo "  Storage size: " . filesize($storagePath) . " bytes\n";
    }
    if (file_exists($publicPath)) {
        echo "  Public size: " . filesize($publicPath) . " bytes\n";
    }
    echo "  ---\n";
}

echo "\n7. Testing admin API call:\n";
try {
    $controller = new App\Http\Controllers\Admin\PrintServiceController(app('App\Services\PrintService'));
    $request = new Illuminate\Http\Request();
    $response = $controller->printFiles($request, $latestOrder->id);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "Status Code: {$statusCode}\n";
    echo "Response: {$content}\n";
    
    $data = json_decode($content, true);
    if (isset($data['success']) && $data['success']) {
        echo "✅ API SUCCESS\n";
        echo "Files ready: " . count($data['files']) . "\n";
        foreach ($data['files'] as $file) {
            echo "  - {$file['name']}\n";
            echo "    View URL: {$file['view_url']}\n";
        }
    } else {
        echo "❌ API FAILED\n";
        echo "Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n8. Testing individual file access:\n";
foreach ($allFiles as $file) {
    $storagePath = storage_path('app/' . $file->file_path);
    if (file_exists($storagePath)) {
        echo "Testing file: {$file->file_path}\n";
        
        try {
            $controller = new App\Http\Controllers\Admin\PrintServiceController(app('App\Services\PrintService'));
            $response = $controller->viewFile($file->id);
            echo "  View file response: " . $response->getStatusCode() . "\n";
        } catch (Exception $e) {
            echo "  View file error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ Full workflow test complete!\n";
