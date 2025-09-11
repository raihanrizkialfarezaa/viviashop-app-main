<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;
use App\Models\PrintSession;

echo "🔍 CHECKING UNPROCESSED FILES\n";
echo "==============================\n\n";

echo "📋 Files still linked to sessions (not orders):\n";
$sessionFiles = PrintFile::whereNotNull('print_session_id')
    ->whereNull('print_order_id')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

foreach ($sessionFiles as $file) {
    echo "File ID: {$file->id}\n";
    echo "Session: {$file->print_session_id}\n";
    echo "Path: {$file->file_path}\n";
    echo "Created: {$file->created_at}\n";
    
    $session = PrintSession::find($file->print_session_id);
    if ($session) {
        echo "Session Token: {$session->session_token}\n";
        
        $order = PrintOrder::where('print_session_id', $session->id)->first();
        if ($order) {
            echo "Related Order: {$order->order_code} (ID: {$order->id})\n";
            echo "🔧 Linking file to order...\n";
            
            $file->update([
                'print_order_id' => $order->id,
                'print_session_id' => null
            ]);
            echo "✅ File linked to order\n";
        } else {
            echo "❌ No order found for this session\n";
        }
    }
    echo "-------------------\n";
}

echo "\n🧪 Testing latest order after fixing...\n";
$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
echo "Order: {$latestOrder->order_code}\n";

$files = PrintFile::where('print_order_id', $latestOrder->id)->get();
echo "Files linked: " . $files->count() . "\n";

foreach ($files as $file) {
    $storagePath = storage_path('app/' . $file->file_path);
    $publicPath = public_path('storage/' . $file->file_path);
    
    echo "File: {$file->file_path}\n";
    echo "Storage exists: " . (file_exists($storagePath) ? "YES" : "NO") . "\n";
    
    if (!file_exists($storagePath) && file_exists($publicPath)) {
        echo "🔧 Copying file...\n";
        $dir = dirname($storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        copy($publicPath, $storagePath);
        echo "✅ File copied\n";
    }
}

echo "\n🌐 Final API test...\n";
try {
    $controller = new App\Http\Controllers\Admin\PrintServiceController(app('App\Services\PrintService'));
    $request = new Illuminate\Http\Request();
    $response = $controller->printFiles($request, $latestOrder->id);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        echo "✅ SUCCESS! Files ready: " . count($data['files']) . "\n";
        foreach ($data['files'] as $file) {
            echo "  - {$file['name']}\n";
            echo "    View: {$file['view_url']}\n";
        }
    } else {
        echo "❌ FAILED: " . ($data['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ Complete fix applied!\n";
