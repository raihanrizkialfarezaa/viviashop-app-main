<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🧪 TESTING SIMPLIFIED VIEW FILES\n";
echo "=================================\n\n";

$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
echo "Testing Order: {$latestOrder->order_code}\n";

$files = PrintFile::where('print_order_id', $latestOrder->id)->get();
echo "Files count: " . $files->count() . "\n\n";

foreach ($files as $file) {
    $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->file_path));
    echo "File: {$file->file_path}\n";
    echo "Full path: {$fullPath}\n";
    echo "Exists: " . (file_exists($fullPath) ? "YES" : "NO") . "\n";
    if (file_exists($fullPath)) {
        echo "Size: " . number_format(filesize($fullPath)) . " bytes\n";
        echo "Mime type: " . (function_exists('mime_content_type') ? mime_content_type($fullPath) : 'N/A') . "\n";
    }
    echo "\n";
}

echo "🌐 Testing new simplified API...\n";
try {
    $controller = new App\Http\Controllers\Admin\PrintServiceController(app('App\Services\PrintService'));
    $request = new Illuminate\Http\Request();
    $response = $controller->printFiles($request, $latestOrder->id);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        echo "✅ API SUCCESS\n";
        echo "Order: {$data['order_code']}\n";
        echo "Customer: {$data['customer_name']}\n";
        echo "Files ready: " . count($data['files']) . "\n";
        
        foreach ($data['files'] as $file) {
            echo "  - {$file['name']}\n";
            echo "    View URL: {$file['view_url']}\n";
        }
    } else {
        echo "❌ API FAILED: " . ($data['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ Simplified view files test complete!\n";
