<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Database configuration
$capsule = new DB;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'u875841990_viviashop',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== VERIFYING BANK TRANSFER ORDER FILES ===\n\n";

try {
    // Get bank transfer (manual) orders with files
    $bankTransferOrders = DB::table('print_orders')
        ->where('payment_method', 'manual')
        ->where('payment_status', '!=', 'pending')
        ->select('*')
        ->get();

    foreach ($bankTransferOrders as $order) {
        $files = DB::table('print_files')
            ->where('print_order_id', $order->id)
            ->select('*')
            ->get();

        echo "Order: {$order->order_code}\n";
        echo "  Customer: {$order->customer_name}\n";
        echo "  Payment Method: {$order->payment_method}\n";
        echo "  Payment Status: {$order->payment_status}\n";
        echo "  Status: {$order->status}\n";
        echo "  Expected Pages: {$order->total_pages}\n";
        echo "  Files Count: " . $files->count() . "\n";
        
        if ($files->count() > 0) {
            echo "  Files:\n";
            foreach ($files as $file) {
                echo "    - {$file->file_name} ({$file->pages_count} pages) [ID: {$file->id}]\n";
            }
            
            if ($files->count() === 1) {
                echo "  ✅ GOOD: Single file will display correctly\n";
            } else {
                echo "  📋 MULTI: {$files->count()} files will show in popup list\n";
            }
        } else {
            echo "  ❌ NO FILES: Order has no files attached\n";
        }
        
        echo "\n";
    }

    echo "=== SUMMARY ===\n";
    echo "Total bank transfer orders checked: " . $bankTransferOrders->count() . "\n";
    
    $ordersWithFiles = 0;
    $ordersWithSingleFile = 0;
    $ordersWithMultipleFiles = 0;
    
    foreach ($bankTransferOrders as $order) {
        $fileCount = DB::table('print_files')->where('print_order_id', $order->id)->count();
        if ($fileCount > 0) {
            $ordersWithFiles++;
            if ($fileCount === 1) {
                $ordersWithSingleFile++;
            } else {
                $ordersWithMultipleFiles++;
            }
        }
    }
    
    echo "Orders with files: {$ordersWithFiles}\n";
    echo "Orders with single file (direct open): {$ordersWithSingleFile}\n";
    echo "Orders with multiple files (popup list): {$ordersWithMultipleFiles}\n";
    
    if ($ordersWithMultipleFiles === 0) {
        echo "\n🎉 SUCCESS: All bank transfer orders now have 0 or 1 file(s).\n";
        echo "   The double file popup issue should be resolved!\n";
    } else {
        echo "\n⚠️  Some bank transfer orders still have multiple files.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}