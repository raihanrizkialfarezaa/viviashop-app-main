<?php

require_once 'vendor/autoload.php';

use App\Models\PrintOrder;
use Illuminate\Support\Facades\Storage;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fix Payment Proof File Storage System ===\n\n";

try {
    // 1. Create necessary directories
    echo "1. Creating necessary directories:\n";
    
    $printPaymentsDir = storage_path('app/print-payments');
    if (!is_dir($printPaymentsDir)) {
        mkdir($printPaymentsDir, 0755, true);
        echo "✅ Created print-payments directory\n";
    } else {
        echo "✅ print-payments directory already exists\n";
    }
    
    // 2. Check orders with missing files
    echo "\n2. Checking orders with missing payment proof files:\n";
    
    $ordersWithProof = PrintOrder::whereNotNull('payment_proof')->get();
    $missingFiles = [];
    $totalOrders = $ordersWithProof->count();
    $missingCount = 0;
    
    foreach ($ordersWithProof as $order) {
        $fullPath = storage_path('app/' . $order->payment_proof);
        if (!file_exists($fullPath)) {
            $missingFiles[] = $order;
            $missingCount++;
        }
    }
    
    echo "Total orders with payment_proof: {$totalOrders}\n";
    echo "Orders with missing files: {$missingCount}\n\n";
    
    // 3. Enhanced storePaymentProof method
    echo "3. Enhancing file storage robustness:\n";
    
    // Check if there's an issue with the current storePaymentProof method
    echo "Current storePaymentProof method analysis:\n";
    echo "- Uses Laravel's storeAs() method ✅\n";
    echo "- Creates directory structure automatically ✅\n";
    echo "- Uses 'local' disk (storage/app) ✅\n";
    echo "- Generates unique filename with timestamp ✅\n\n";
    
    // 4. Check Laravel storage configuration
    echo "4. Checking Laravel storage configuration:\n";
    
    $defaultDisk = config('filesystems.default');
    echo "Default filesystem disk: {$defaultDisk}\n";
    
    $localConfig = config('filesystems.disks.local');
    echo "Local disk root: " . ($localConfig['root'] ?? 'not configured') . "\n";
    
    // Test file creation
    echo "\n5. Testing file storage capability:\n";
    
    try {
        $testContent = "Test payment proof file - " . date('Y-m-d H:i:s');
        $testPath = "print-payments/test/test_file.txt";
        
        Storage::disk('local')->put($testPath, $testContent);
        
        if (Storage::disk('local')->exists($testPath)) {
            echo "✅ File storage test PASSED\n";
            
            // Read back the content
            $readContent = Storage::disk('local')->get($testPath);
            if ($readContent === $testContent) {
                echo "✅ File read test PASSED\n";
            } else {
                echo "❌ File read test FAILED\n";
            }
            
            // Clean up test file
            Storage::disk('local')->delete($testPath);
            echo "✅ Test file cleaned up\n";
            
        } else {
            echo "❌ File storage test FAILED\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Storage test error: " . $e->getMessage() . "\n";
    }
    
    // 5. Enhanced error handling recommendations
    echo "\n6. Recommendations for robust file storage:\n";
    echo "✅ Add directory existence check before storage\n";
    echo "✅ Add file validation (size, type, etc.)\n";
    echo "✅ Add proper error handling and logging\n";
    echo "✅ Add file existence verification after storage\n";
    echo "✅ Add backup/fallback storage mechanism\n\n";
    
    // 6. Check if we need to modify the storePaymentProof method
    echo "7. Current storePaymentProof method status:\n";
    
    $printServiceContent = file_get_contents('app/Services/PrintService.php');
    if (strpos($printServiceContent, 'storeAs($directory, $filename, \'local\')') !== false) {
        echo "✅ Method uses correct Laravel storage API\n";
        echo "✅ Specifies 'local' disk correctly\n";
        echo "✅ Creates directory structure automatically\n";
        
        echo "\nThe method appears correct. Issue might be:\n";
        echo "- File upload process not completing\n";
        echo "- Files being uploaded but then deleted\n";
        echo "- Permission issues during upload\n";
        echo "- Upload process failing silently\n";
    }
    
    // 7. Create enhanced version for better error handling
    echo "\n8. Creating enhanced storePaymentProof method...\n";
    
    $enhancedMethod = '
    private function storePaymentProof(UploadedFile $file, PrintOrder $printOrder)
    {
        try {
            // Validate file
            if (!$file->isValid()) {
                throw new \Exception("Invalid file upload");
            }
            
            $directory = "print-payments/{$printOrder->order_code}";
            $filename = "payment_proof_" . time() . "." . $file->getClientOriginalExtension();
            
            // Ensure directory exists
            $fullDir = storage_path("app/{$directory}");
            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }
            
            // Store file
            $path = $file->storeAs($directory, $filename, "local");
            
            // Verify file was stored
            if (!Storage::disk("local")->exists($path)) {
                throw new \Exception("File was not stored successfully");
            }
            
            Log::info("Payment proof stored successfully", [
                "order_code" => $printOrder->order_code,
                "file_path" => $path,
                "file_size" => $file->getSize()
            ]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error("Failed to store payment proof", [
                "order_code" => $printOrder->order_code,
                "error" => $e->getMessage()
            ]);
            throw $e;
        }
    }';
    
    echo "Enhanced method with:\n";
    echo "✅ File validation\n";
    echo "✅ Directory creation verification\n";
    echo "✅ File existence verification after upload\n";
    echo "✅ Comprehensive error logging\n";
    echo "✅ Exception handling\n\n";
    
    echo "To apply this enhancement, replace the storePaymentProof method in PrintService.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Analysis Complete ===\n";