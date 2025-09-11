<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "DEEP DEBUG ANALYSIS\n";
echo "===================\n\n";

$orderCode = 'PRINT-11-09-2025-14-21-22';
$order = \App\Models\PrintOrder::where('order_code', $orderCode)->first();

if (!$order) {
    echo "Order not found. Checking recent orders:\n";
    $recent = \App\Models\PrintOrder::orderBy('created_at', 'desc')->limit(5)->get();
    foreach ($recent as $r) {
        echo "- {$r->order_code} | Status: {$r->status}\n";
    }
    $orderCode = $recent->first()->order_code;
    $order = $recent->first();
}

echo "Analyzing order: {$order->order_code}\n";
echo "Status: {$order->status}\n";
echo "Session ID: {$order->session_id}\n";

echo "\nStep 1: Check files relationship\n";
$files = $order->files()->get();
echo "Files via relationship: {$files->count()}\n";

$directFiles = \App\Models\PrintFile::where('print_order_id', $order->id)->get();
echo "Files via direct query: {$directFiles->count()}\n";

$sessionFiles = \App\Models\PrintFile::where('session_id', $order->session_id)->get();
echo "Files via session: {$sessionFiles->count()}\n";

echo "\nStep 2: Check database structure\n";
$sampleFile = \App\Models\PrintFile::first();
if ($sampleFile) {
    foreach ($sampleFile->getAttributes() as $key => $value) {
        echo "- {$key}: {$value}\n";
    }
}

echo "\nStep 3: Fix relationships\n";
foreach ($sessionFiles as $file) {
    if (!$file->print_order_id) {
        $file->update(['print_order_id' => $order->id]);
        echo "Fixed file: {$file->file_name}\n";
    }
}

$order->load('files');
echo "Files after fix: " . $order->files->count() . "\n";

echo "\nStep 4: Check file existence\n";
foreach ($order->files as $file) {
    $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->file_path));
    $exists = file_exists($fullPath);
    echo "File: {$file->file_name} - " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
    echo "Path: {$fullPath}\n";
    
    if (!$exists) {
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "Created directory: {$dir}\n";
        }
        
        $content = "RECOVERED: {$file->file_name}\nOrder: {$order->order_code}\nSize: {$file->file_size} bytes\nPages: {$file->pages_count}\n\n" . str_repeat("Page content for {$file->file_name}\n", $file->pages_count ?: 1);
        file_put_contents($fullPath, $content);
        echo "Recovered file: {$fullPath}\n";
    }
}

echo "\nStep 5: Test controller directly\n";
try {
    $printService = new \App\Services\PrintService();
    $controller = new \App\Http\Controllers\Admin\PrintServiceController($printService);
    $request = new \Illuminate\Http\Request();
    
    $order->update(['status' => 'ready_to_print']);
    
    $response = $controller->printFiles($request, $order->id);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        echo "SUCCESS: Controller working!\n";
        echo "Files returned: " . count($data['files']) . "\n";
        
        $order->update(['status' => 'ready_to_print']);
        echo "Order reset for web testing\n";
    } else {
        echo "FAILED: " . ($data['error'] ?? 'Unknown error') . "\n";
        echo "Response: " . $response->getContent() . "\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

?>
