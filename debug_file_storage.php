<?php

require_once 'vendor/autoload.php';

use App\Models\PrintOrder;
use Illuminate\Support\Facades\Storage;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Debug Payment Proof File Storage Issue ===\n\n";

try {
    // 1. Check order 65
    echo "1. Checking order 65:\n";
    $order65 = PrintOrder::find(65);
    
    if ($order65) {
        echo "✅ Order found: {$order65->order_code}\n";
        echo "   Payment status: {$order65->payment_status}\n";
        echo "   Payment method: {$order65->payment_method}\n";
        echo "   Stored path: {$order65->payment_proof}\n";
        
        if ($order65->payment_proof) {
            $fullPath = storage_path('app/' . $order65->payment_proof);
            echo "   Full path: {$fullPath}\n";
            echo "   File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            
            // Check directory
            $directory = dirname($fullPath);
            echo "   Directory: {$directory}\n";
            echo "   Directory exists: " . (is_dir($directory) ? 'YES' : 'NO') . "\n";
            
            if (is_dir($directory)) {
                $files = scandir($directory);
                echo "   Files in directory: " . count($files) . "\n";
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        echo "     - {$file}\n";
                    }
                }
            }
        }
    } else {
        echo "❌ Order 65 not found\n";
    }
    
    // 2. Check storage directories
    echo "\n2. Checking storage structure:\n";
    $storageApp = storage_path('app');
    echo "Storage app path: {$storageApp}\n";
    echo "Storage app exists: " . (is_dir($storageApp) ? 'YES' : 'NO') . "\n";
    
    $printPaymentsDir = storage_path('app/print-payments');
    echo "Print payments dir: {$printPaymentsDir}\n";
    echo "Print payments exists: " . (is_dir($printPaymentsDir) ? 'YES' : 'NO') . "\n";
    
    if (is_dir($printPaymentsDir)) {
        $subdirs = array_filter(scandir($printPaymentsDir), function($item) use ($printPaymentsDir) {
            return is_dir($printPaymentsDir . '/' . $item) && $item !== '.' && $item !== '..';
        });
        echo "Subdirectories in print-payments: " . count($subdirs) . "\n";
        
        // Show latest few directories
        rsort($subdirs);
        foreach (array_slice($subdirs, 0, 5) as $subdir) {
            echo "  - {$subdir}\n";
        }
    }
    
    // 3. Check recent orders with payment_proof
    echo "\n3. Recent orders with payment proof:\n";
    $recentOrders = PrintOrder::whereNotNull('payment_proof')
                             ->orderBy('created_at', 'desc')
                             ->limit(10)
                             ->get(['id', 'order_code', 'payment_proof', 'created_at']);
    
    foreach ($recentOrders as $order) {
        $fullPath = storage_path('app/' . $order->payment_proof);
        $exists = file_exists($fullPath) ? '✅' : '❌';
        echo "  Order {$order->id}: {$order->order_code} | {$exists} | {$order->created_at}\n";
    }
    
    // 4. Check file upload process
    echo "\n4. Investigating upload process...\n";
    
    // Check PrintService storePaymentProof method
    $printServicePath = 'app/Services/PrintService.php';
    if (file_exists($printServicePath)) {
        $content = file_get_contents($printServicePath);
        
        if (strpos($content, 'storePaymentProof') !== false) {
            echo "✅ storePaymentProof method exists in PrintService\n";
            
            // Extract the method to see how files are stored
            if (preg_match('/private function storePaymentProof.*?(?=private function|\}$)/s', $content, $matches)) {
                echo "Method implementation found:\n";
                echo substr($matches[0], 0, 500) . "...\n";
            }
        } else {
            echo "❌ storePaymentProof method not found\n";
        }
    }
    
    // 5. Check Laravel storage permissions
    echo "\n5. Storage permissions:\n";
    $storagePermissions = substr(sprintf('%o', fileperms($storageApp)), -4);
    echo "Storage app permissions: {$storagePermissions}\n";
    
    if (is_dir($printPaymentsDir)) {
        $printPermissions = substr(sprintf('%o', fileperms($printPaymentsDir)), -4);
        echo "Print payments permissions: {$printPermissions}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";