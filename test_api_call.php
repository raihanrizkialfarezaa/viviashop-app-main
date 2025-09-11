<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;
use App\Http\Controllers\Admin\PrintServiceController;
use Illuminate\Http\Request;

echo "🌐 SIMULATING WEB API CALL\n";
echo "==========================\n\n";

$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
echo "Testing Order: {$latestOrder->order_code} (ID: {$latestOrder->id})\n\n";

try {
    $controller = new PrintServiceController(app('App\Services\PrintService'));
    
    $request = new Request();
    $request->setMethod('POST');
    $request->headers->set('X-CSRF-TOKEN', 'test-token');
    $request->headers->set('Content-Type', 'application/json');
    
    echo "📡 Calling printFiles method...\n";
    $response = $controller->printFiles($request, $latestOrder->id);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
    
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        echo "\n✅ SUCCESS! Print files API working!\n";
        echo "Order Code: " . $data['order_code'] . "\n";
        echo "Customer: " . $data['customer_name'] . "\n";
        echo "Files count: " . count($data['files']) . "\n";
        
        foreach ($data['files'] as $file) {
            echo "  - " . $file['name'] . " (ID: " . $file['id'] . ")\n";
            echo "    Download: " . $file['download_url'] . "\n";
        }
    } else {
        echo "\n❌ API Error: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n✅ API test complete!\n";
