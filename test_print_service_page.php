<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎯 TESTING PRINT SERVICE CUSTOMER PAGE\n";
echo "=======================================\n";

echo "1️⃣ CREATING TEST SESSION\n";
echo "=========================\n";

try {
    $printService = app(\App\Services\PrintService::class);
    $session = $printService->generateSession();
    
    echo "✅ Session created: " . $session->session_token . "\n";
    echo "   - Expires: " . $session->expires_at->format('Y-m-d H:i:s') . "\n";
    echo "   - Active: " . ($session->is_active ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "❌ Session creation error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2️⃣ TESTING CUSTOMER PAGE ACCESS\n";
echo "================================\n";

try {
    $customerUrl = "http://127.0.0.1:8000/print-service/" . $session->session_token;
    echo "📱 Testing URL: $customerUrl\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $customerUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    echo "📊 HTTP Code: $httpCode\n";
    
    if ($httpCode === 200) {
        echo "✅ Customer page accessible\n";
        
        $checkStrings = [
            'ViVia Print Service' => 'Page title',
            'Smart Print Service' => 'Service name',
            'Upload File' => 'Upload section',
            'session_token' => 'Session token',
            'file_upload_form' => 'Upload form'
        ];
        
        foreach ($checkStrings as $needle => $description) {
            if (strpos($response, $needle) !== false) {
                echo "✅ $description found\n";
            } else {
                echo "❌ $description missing\n";
            }
        }
        
        echo "\n📄 Page Content Sample (first 500 chars):\n";
        echo substr($response, 0, 500) . "...\n";
        
    } else {
        echo "❌ Customer page error (HTTP $httpCode)\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Customer page test error: " . $e->getMessage() . "\n";
}

echo "\n3️⃣ TESTING CONTROLLER DIRECTLY\n";
echo "===============================\n";

try {
    $controller = new \App\Http\Controllers\PrintServiceController($printService);
    $response = $controller->index($session->session_token);
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✅ Controller returns view\n";
        echo "   - View name: " . $response->getName() . "\n";
        
        $viewData = $response->getData();
        echo "   - Has session: " . (isset($viewData['session']) ? 'Yes' : 'No') . "\n";
        echo "   - Has products: " . (isset($viewData['products']) ? 'Yes' : 'No') . "\n";
        
        if (isset($viewData['session'])) {
            echo "   - Session token: " . $viewData['session']->session_token . "\n";
        }
        
        if (isset($viewData['products'])) {
            echo "   - Products count: " . $viewData['products']->count() . "\n";
        }
        
    } else {
        echo "❌ Controller does not return view\n";
        echo "   - Response type: " . get_class($response) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Controller test error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n📊 PRINT SERVICE PAGE TEST SUMMARY\n";
echo "===================================\n";
