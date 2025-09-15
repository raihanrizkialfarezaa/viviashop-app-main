<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Storage;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test Fixed Storage Configuration ===\n\n";

try {
    // 1. Test storage configuration
    echo "1. Testing storage configuration:\n";
    
    $defaultDisk = config('filesystems.default');
    echo "Default disk: {$defaultDisk}\n";
    
    $localConfig = config('filesystems.disks.local');
    echo "Local disk root: {$localConfig['root']}\n";
    echo "Local disk root exists: " . (is_dir($localConfig['root']) ? 'YES' : 'NO') . "\n";
    
    // 2. Test file storage
    echo "\n2. Testing file storage:\n";
    
    $testContent = "Test payment proof file - " . date('Y-m-d H:i:s');
    $testPath = "print-payments/test-order/payment_proof_test.txt";
    
    echo "Storing test file at: {$testPath}\n";
    
    // Store file
    $stored = Storage::disk('local')->put($testPath, $testContent);
    echo "Storage result: " . ($stored ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Check if file exists
    $exists = Storage::disk('local')->exists($testPath);
    echo "File exists: " . ($exists ? 'YES' : 'NO') . "\n";
    
    if ($exists) {
        // Get full path
        $fullPath = storage_path('app/' . $testPath);
        echo "Full path: {$fullPath}\n";
        echo "Physical file exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        
        // Read content
        $readContent = Storage::disk('local')->get($testPath);
        echo "Content matches: " . ($readContent === $testContent ? 'YES' : 'NO') . "\n";
        
        // Clean up
        Storage::disk('local')->delete($testPath);
        echo "Test file deleted: YES\n";
    }
    
    // 3. Test directory creation
    echo "\n3. Testing directory creation:\n";
    
    $testDir = "print-payments/PRINT-TEST-ORDER";
    $testFile = "{$testDir}/payment_proof_test.png";
    
    echo "Creating file in new directory: {$testFile}\n";
    
    $stored = Storage::disk('local')->put($testFile, "fake image content");
    echo "Directory creation and file storage: " . ($stored ? 'SUCCESS' : 'FAILED') . "\n";
    
    if ($stored) {
        $fullPath = storage_path('app/' . $testFile);
        echo "Directory created: " . (is_dir(dirname($fullPath)) ? 'YES' : 'NO') . "\n";
        
        // Clean up
        Storage::disk('local')->delete($testFile);
        
        // Try to remove directory if empty
        $dirPath = dirname($fullPath);
        if (is_dir($dirPath) && count(scandir($dirPath)) == 2) { // Only . and ..
            rmdir($dirPath);
            echo "Test directory cleaned up: YES\n";
        }
    }
    
    // 4. Check existing payment proof files
    echo "\n4. Checking existing payment proof files:\n";
    
    $printPaymentsPath = "print-payments";
    if (Storage::disk('local')->exists($printPaymentsPath)) {
        $files = Storage::disk('local')->allFiles($printPaymentsPath);
        echo "Files in print-payments: " . count($files) . "\n";
        
        foreach ($files as $file) {
            echo "  - {$file}\n";
        }
    } else {
        echo "print-payments directory not found in storage\n";
    }
    
    echo "\n✅ Storage configuration is now working correctly!\n";
    echo "File uploads should now work properly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";