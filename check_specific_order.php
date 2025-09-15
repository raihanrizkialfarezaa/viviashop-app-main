<?php

require_once 'vendor/autoload.php';

use App\Models\PrintOrder;
use App\Models\PrintFile;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Checking Specific Order Status ===\n\n";

$orderCode = 'PRINT-15-09-2025-23-23-27';

try {
    $order = PrintOrder::where('order_code', $orderCode)->first();
    
    if (!$order) {
        echo "❌ Order not found: {$orderCode}\n";
        exit;
    }
    
    echo "📋 Order Details:\n";
    echo "Order Code: {$order->order_code}\n";
    echo "Customer: {$order->customer_name} ({$order->customer_phone})\n";
    echo "Status: {$order->status}\n";
    echo "Payment Status: {$order->payment_status}\n";
    echo "Payment Method: {$order->payment_method}\n";
    echo "Total: Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
    echo "Created: {$order->created_at}\n";
    echo "Updated: {$order->updated_at}\n\n";
    
    // Check files
    echo "📁 Files uploaded:\n";
    $files = PrintFile::where('print_order_id', $order->id)->get();
    
    if ($files->count() > 0) {
        foreach ($files as $file) {
            echo "  - {$file->original_name} ({$file->file_type})\n";
            echo "    Pages: {$file->pages}, Size: {$file->file_size} bytes\n";
            echo "    Path: {$file->file_path}\n";
        }
    } else {
        echo "  No files found\n";
    }
    
    echo "\n🔍 Expected vs Actual:\n";
    echo "Expected after successful payment:\n";
    echo "  Status: payment_confirmed or ready_to_print\n";
    echo "  Payment Status: paid\n\n";
    
    echo "Actual current state:\n";
    echo "  Status: {$order->status}\n";
    echo "  Payment Status: {$order->payment_status}\n\n";
    
    if ($order->payment_status === 'unpaid') {
        echo "❌ PROBLEM: Payment status masih 'unpaid' padahal payment sudah berhasil\n";
        echo "🔧 SOLUTION: Perlu update payment status melalui callback atau manual confirmation\n";
    }
    
    // Check logs untuk callback
    echo "\n📋 Checking recent logs for this order...\n";
    $logPath = storage_path('logs/laravel.log');
    
    if (file_exists($logPath)) {
        $logContent = file_get_contents($logPath);
        $orderLines = [];
        
        $lines = explode("\n", $logContent);
        foreach ($lines as $line) {
            if (strpos($line, $orderCode) !== false) {
                $orderLines[] = $line;
            }
        }
        
        if (!empty($orderLines)) {
            echo "Found " . count($orderLines) . " log entries for this order:\n";
            foreach (array_slice($orderLines, -5) as $line) {
                echo "  " . trim($line) . "\n";
            }
        } else {
            echo "No log entries found for this order\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Analysis Complete ===\n";