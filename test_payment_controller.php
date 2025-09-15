<?php

require_once 'vendor/autoload.php';

use App\Models\PrintOrder;
use App\Http\Controllers\Admin\PrintServiceController;
use Illuminate\Http\Request;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test Payment Proof Controller Method ===\n\n";

try {
    // 1. Check order 65
    echo "1. Checking order 65:\n";
    $order65 = PrintOrder::find(65);
    
    if ($order65) {
        echo "✅ Order found: {$order65->order_code}\n";
        echo "   Payment proof: {$order65->payment_proof}\n";
        
        if ($order65->payment_proof) {
            $fullPath = storage_path('app/' . $order65->payment_proof);
            echo "   Full path: {$fullPath}\n";
            echo "   File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        }
    }
    
    // 2. Test controller method directly
    echo "\n2. Testing downloadPaymentProof method:\n";
    
    try {
        $printService = new \App\Services\PrintService();
        $controller = new PrintServiceController($printService);
        $response = $controller->downloadPaymentProof(65);
        
        echo "Controller response type: " . get_class($response) . "\n";
        
        if (method_exists($response, 'getStatusCode')) {
            echo "Status code: " . $response->getStatusCode() . "\n";
        }
        
        if (method_exists($response, 'getContent')) {
            $content = $response->getContent();
            if (is_string($content) && strlen($content) < 500) {
                echo "Response content: " . $content . "\n";
            } else {
                echo "Response content type: " . (is_string($content) ? 'String (' . strlen($content) . ' chars)' : gettype($content)) . "\n";
            }
        }
        
        if (method_exists($response, 'headers') && method_exists($response->headers, 'all')) {
            $headers = $response->headers->all();
            echo "Response headers:\n";
            foreach ($headers as $key => $value) {
                echo "  {$key}: " . (is_array($value) ? implode(', ', $value) : $value) . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Controller error: " . $e->getMessage() . "\n";
        echo "Error type: " . get_class($e) . "\n";
    }
    
    // 3. Create a dummy payment proof file for testing
    echo "\n3. Creating dummy payment proof file:\n";
    
    if ($order65 && $order65->payment_proof) {
        $targetPath = $order65->payment_proof;
        $fullPath = storage_path('app/' . $targetPath);
        
        // Create directory if needed
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            echo "✅ Created directory: {$directory}\n";
        }
        
        // Create dummy file
        $dummyContent = "Dummy payment proof image content for testing";
        file_put_contents($fullPath, $dummyContent);
        
        echo "✅ Created dummy file: {$fullPath}\n";
        echo "   File size: " . filesize($fullPath) . " bytes\n";
        
        // Test controller again
        echo "\n4. Testing controller with dummy file:\n";
        
        try {
            $printService = new \App\Services\PrintService();
            $controller = new PrintServiceController($printService);
            $response = $controller->downloadPaymentProof(65);
            
            echo "Controller response type: " . get_class($response) . "\n";
            
            if (method_exists($response, 'getStatusCode')) {
                echo "Status code: " . $response->getStatusCode() . "\n";
            }
            
            if (method_exists($response, 'headers') && method_exists($response->headers, 'get')) {
                echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
                echo "Content-Disposition: " . $response->headers->get('Content-Disposition') . "\n";
            }
            
            echo "✅ Controller now returns file response successfully!\n";
            
        } catch (Exception $e) {
            echo "❌ Controller still has error: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";