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

echo "=== FIXING DUPLICATE FILES IN PRINT ORDERS ===\n\n";

try {
    // Get all print orders with their files
    $orders = DB::table('print_orders')
        ->leftJoin('print_files', 'print_orders.id', '=', 'print_files.print_order_id')
        ->select(
            'print_orders.id as order_id',
            'print_orders.order_code',
            'print_orders.payment_method',
            'print_orders.total_pages as expected_pages',
            'print_files.id as file_id',
            'print_files.file_name',
            'print_files.file_path',
            'print_files.pages_count'
        )
        ->orderBy('print_orders.id')
        ->get();

    // Group by order
    $orderGroups = [];
    foreach ($orders as $order) {
        if (!isset($orderGroups[$order->order_id])) {
            $orderGroups[$order->order_id] = [
                'order_code' => $order->order_code,
                'payment_method' => $order->payment_method,
                'expected_pages' => $order->expected_pages,
                'files' => []
            ];
        }
        
        if ($order->file_id) {
            $orderGroups[$order->order_id]['files'][] = [
                'id' => $order->file_id,
                'name' => $order->file_name,
                'path' => $order->file_path,
                'pages' => $order->pages_count
            ];
        }
    }

    $duplicatesFound = 0;
    $duplicatesRemoved = 0;

    foreach ($orderGroups as $orderId => $orderData) {
        if (count($orderData['files']) <= 1) {
            continue; // No duplicates possible
        }

        echo "Checking Order: {$orderData['order_code']} (Payment: {$orderData['payment_method']})\n";
        echo "  Expected pages: {$orderData['expected_pages']}\n";
        echo "  Files found: " . count($orderData['files']) . "\n";

        // Group files by name to find duplicates
        $filesByName = [];
        foreach ($orderData['files'] as $file) {
            if (!isset($filesByName[$file['name']])) {
                $filesByName[$file['name']] = [];
            }
            $filesByName[$file['name']][] = $file;
        }

        // Find and handle duplicates
        foreach ($filesByName as $fileName => $duplicateFiles) {
            if (count($duplicateFiles) > 1) {
                $duplicatesFound++;
                echo "  🔴 DUPLICATE FOUND: {$fileName} ({" . count($duplicateFiles) . "} copies)\n";
                
                // Keep the first file (usually the original), remove the rest
                $keepFile = array_shift($duplicateFiles);
                echo "    ✅ KEEPING: File ID {$keepFile['id']} ({$keepFile['pages']} pages)\n";
                
                foreach ($duplicateFiles as $duplicateFile) {
                    echo "    🗑️  REMOVING: File ID {$duplicateFile['id']} ({$duplicateFile['pages']} pages)\n";
                    
                    // Delete the duplicate file record from database
                    $deleted = DB::table('print_files')
                        ->where('id', $duplicateFile['id'])
                        ->delete();
                    
                    if ($deleted) {
                        $duplicatesRemoved++;
                        
                        // Also try to delete the physical file if it exists
                        $physicalPath = __DIR__ . '/storage/app/' . $duplicateFile['path'];
                        if (file_exists($physicalPath)) {
                            if (unlink($physicalPath)) {
                                echo "      💾 Physical file deleted: {$physicalPath}\n";
                            } else {
                                echo "      ⚠️  Could not delete physical file: {$physicalPath}\n";
                            }
                        }
                    }
                }
            }
        }

        // Recalculate total pages after removing duplicates
        $totalPagesAfter = 0;
        $uniqueFiles = [];
        foreach ($filesByName as $fileName => $duplicateFiles) {
            if (!empty($duplicateFiles)) {
                $file = $duplicateFiles[0]; // Only count the kept file
                $totalPagesAfter += (int)$file['pages'];
                $uniqueFiles[] = $file;
            }
        }

        echo "  📊 Pages after cleanup: {$totalPagesAfter} (Expected: {$orderData['expected_pages']})\n";
        
        if ($totalPagesAfter != $orderData['expected_pages']) {
            echo "  ⚠️  PAGE MISMATCH: Database shows {$orderData['expected_pages']} but files have {$totalPagesAfter}\n";
            
            // Update the order's total_pages to match actual files
            $updated = DB::table('print_orders')
                ->where('id', $orderId)
                ->update(['total_pages' => $totalPagesAfter]);
            
            if ($updated) {
                echo "  🔧 FIXED: Updated order total_pages to {$totalPagesAfter}\n";
            }
        }
        
        echo "\n";
    }

    echo "=== CLEANUP SUMMARY ===\n";
    echo "Orders checked: " . count($orderGroups) . "\n";
    echo "Duplicate file sets found: {$duplicatesFound}\n";
    echo "Duplicate files removed: {$duplicatesRemoved}\n";
    echo "\n";

    // Now let's check for any orphaned files (files without orders)
    echo "=== CHECKING FOR ORPHANED FILES ===\n";
    $orphanedFiles = DB::table('print_files')
        ->leftJoin('print_orders', 'print_files.print_order_id', '=', 'print_orders.id')
        ->whereNull('print_orders.id')
        ->select('print_files.*')
        ->get();

    if ($orphanedFiles->count() > 0) {
        echo "Found {$orphanedFiles->count()} orphaned files:\n";
        foreach ($orphanedFiles as $orphan) {
            echo "  - File ID {$orphan->id}: {$orphan->file_name} (Order ID: {$orphan->print_order_id})\n";
            
            // Delete orphaned files
            $deleted = DB::table('print_files')
                ->where('id', $orphan->id)
                ->delete();
            
            if ($deleted) {
                echo "    🗑️  Removed orphaned file record\n";
                
                // Try to delete physical file
                $physicalPath = __DIR__ . '/storage/app/' . $orphan->file_path;
                if (file_exists($physicalPath)) {
                    if (unlink($physicalPath)) {
                        echo "    💾 Physical file deleted\n";
                    }
                }
            }
        }
    } else {
        echo "No orphaned files found.\n";
    }

    echo "\n=== FIX COMPLETE ===\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}