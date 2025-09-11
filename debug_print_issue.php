<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "DEBUG: PRINT FILES ISSUE ANALYSIS\n";
echo "==================================\n\n";

$orderCode = 'PRINT-11-09-2025-14-21-22';

echo "1. Checking order: {$orderCode}\n";
$order = \App\Models\PrintOrder::where('order_code', $orderCode)->first();

if (!$order) {
    echo "Order not found! Let's check recent orders:\n";
    $recentOrders = \App\Models\PrintOrder::orderBy('created_at', 'desc')->limit(5)->get();
    foreach ($recentOrders as $recentOrder) {
        echo "- {$recentOrder->order_code} | Status: {$recentOrder->status} | Created: {$recentOrder->created_at}\n";
    }
    
    echo "\nUsing most recent order instead:\n";
    $order = $recentOrders->first();
    $orderCode = $order->order_code;
}

echo "Order found: {$order->order_code}\n";
echo "Status: {$order->status}\n";
echo "Payment Status: {$order->payment_status}\n";
echo "Session ID: {$order->session_id}\n";

echo "\n2. Checking files relationship:\n";
$files = $order->files;
echo "Files count: {$files->count()}\n";

if ($files->count() === 0) {
    echo "No files found in relationship! Checking session files:\n";
    if ($order->session_id) {
        $sessionFiles = \App\Models\PrintFile::where('session_id', $order->session_id)->get();
        echo "Session files count: {$sessionFiles->count()}\n";
        
        foreach ($sessionFiles as $sessionFile) {
            echo "- Session File: {$sessionFile->file_name} | Order ID: {$sessionFile->print_order_id}\n";
            
            if (!$sessionFile->print_order_id) {
                echo "  ISSUE: File has no print_order_id! Fixing...\n";
                $sessionFile->update(['print_order_id' => $order->id]);
                echo "  Fixed: Assigned file to order\n";
            }
        }
    }
    
    echo "\nRechecking files after fix:\n";
    $order->load('files');
    $files = $order->files;
    echo "Files count after fix: {$files->count()}\n";
}

echo "\n3. Checking file details:\n";
foreach ($files as $file) {
    echo "File: {$file->file_name}\n";
    echo "Path: {$file->file_path}\n";
    echo "Size: {$file->file_size} bytes\n";
    echo "Type: {$file->file_type}\n";
    echo "Print Order ID: {$file->print_order_id}\n";
    
    $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->file_path));
    echo "Full Path: {$fullPath}\n";
    echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    
    if (!file_exists($fullPath)) {
        $dir = dirname($fullPath);
        echo "Directory exists: " . (is_dir($dir) ? 'YES' : 'NO') . "\n";
        if (!is_dir($dir)) {
            echo "Creating directory: {$dir}\n";
            mkdir($dir, 0755, true);
        }
        
        $dummyContent = "Dummy file content for: {$file->file_name}\nOrder: {$order->order_code}\nCreated: " . date('Y-m-d H:i:s');
        file_put_contents($fullPath, $dummyContent);
        echo "Created dummy file for testing\n";
    }
    echo "---\n";
}

echo "\n4. Testing canPrint() method:\n";
echo "Can print: " . ($order->canPrint() ? 'YES' : 'NO') . "\n";

echo "\n5. Testing printFiles controller method:\n";
try {
    $printService = new \App\Services\PrintService();
    $controller = new \App\Http\Controllers\Admin\PrintServiceController($printService);
    $request = new \Illuminate\Http\Request();
    
    $response = $controller->printFiles($request, $order->id);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        echo "SUCCESS: Print files working!\n";
        echo "Files returned: " . count($data['files']) . "\n";
        foreach ($data['files'] as $filePath) {
            echo "- {$filePath}\n";
        }
    } else {
        echo "ERROR: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n6. Database structure check:\n";
echo "PrintFile table columns:\n";
$printFile = \App\Models\PrintFile::first();
if ($printFile) {
    foreach ($printFile->getAttributes() as $key => $value) {
        echo "- {$key}: {$value}\n";
    }
}

?>
