<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\PrintOrder;
use App\Models\PrintFile;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ANALYZING BANK TRANSFER vs OTHER PAYMENT METHODS ===\n\n";

// Compare different payment methods
$paymentMethods = ['manual', 'automatic', 'toko'];

foreach ($paymentMethods as $method) {
    echo "=== PAYMENT METHOD: " . strtoupper($method) . " ===\n";
    
    $orders = PrintOrder::where('payment_method', $method)
        ->with('files')
        ->get();
    
    echo "Total orders: " . $orders->count() . "\n";
    
    if ($orders->count() > 0) {
        echo "Order Details:\n";
        foreach ($orders as $order) {
            echo "  Order: {$order->order_code}\n";
            echo "    Customer: {$order->customer_name}\n";
            echo "    Total Pages (DB): {$order->total_pages}\n";
            echo "    Files Count: " . $order->files->count() . "\n";
            echo "    Payment Proof: " . ($order->payment_proof ? 'Yes' : 'No') . "\n";
            
            $calculatedPages = $order->files->sum('pages_count');
            echo "    Calculated Pages: {$calculatedPages}\n";
            
            if ($order->files->count() > 0) {
                echo "    Files:\n";
                foreach ($order->files as $file) {
                    echo "      - {$file->file_name} ({$file->pages_count} pages)\n";
                    echo "        Path: {$file->file_path}\n";
                    echo "        ID: {$file->id}\n";
                }
            }
            
            // Check for duplicates in file names or paths
            $fileNames = $order->files->pluck('file_name')->toArray();
            $filePaths = $order->files->pluck('file_path')->toArray();
            
            if (count($fileNames) !== count(array_unique($fileNames))) {
                echo "    🔴 DUPLICATE FILE NAMES DETECTED!\n";
                $duplicates = array_diff_assoc($fileNames, array_unique($fileNames));
                foreach ($duplicates as $duplicate) {
                    echo "      Duplicate: {$duplicate}\n";
                }
            }
            
            if (count($filePaths) !== count(array_unique($filePaths))) {
                echo "    🔴 DUPLICATE FILE PATHS DETECTED!\n";
                $duplicatePaths = array_diff_assoc($filePaths, array_unique($filePaths));
                foreach ($duplicatePaths as $duplicate) {
                    echo "      Duplicate path: {$duplicate}\n";
                }
            }
            
            if ($calculatedPages != $order->total_pages) {
                echo "    ⚠️  MISMATCH: Expected {$order->total_pages} pages, but files have {$calculatedPages} pages\n";
            } else {
                echo "    ✅ Pages match\n";
            }
            
            echo "\n";
        }
    } else {
        echo "  No orders found for this payment method.\n";
    }
    
    echo "\n";
}

// Check if there are any files that might be incorrectly associated
echo "=== CHECKING FOR FILE ASSOCIATION ISSUES ===\n";

// Look for files that might be associated with multiple orders
$suspiciousFiles = PrintFile::select('file_name', 'file_path')
    ->groupBy('file_name', 'file_path')
    ->havingRaw('COUNT(*) > 1')
    ->get();

if ($suspiciousFiles->count() > 0) {
    echo "Found files that appear multiple times:\n";
    
    foreach ($suspiciousFiles as $suspiciousFile) {
        $duplicates = PrintFile::where('file_name', $suspiciousFile->file_name)
            ->where('file_path', $suspiciousFile->file_path)
            ->with('printOrder')
            ->get();
        
        echo "  File: {$suspiciousFile->file_name}\n";
        echo "  Path: {$suspiciousFile->file_path}\n";
        echo "  Appears in:\n";
        
        foreach ($duplicates as $duplicate) {
            echo "    - Order: " . ($duplicate->printOrder ? $duplicate->printOrder->order_code : 'No order') . "\n";
            echo "      Payment method: " . ($duplicate->printOrder ? $duplicate->printOrder->payment_method : 'Unknown') . "\n";
            echo "      File ID: {$duplicate->id}\n";
        }
        echo "\n";
    }
} else {
    echo "✅ No duplicate files found across orders.\n";
}

echo "\n=== ANALYSIS COMPLETE ===\n";