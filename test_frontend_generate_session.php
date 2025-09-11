<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🌐 TESTING FRONTEND GENERATE SESSION ACCESS\n";
echo "===========================================\n";

echo "1️⃣ TESTING CSRF TOKEN GENERATION\n";
echo "==================================\n";

try {
    $csrfToken = csrf_token();
    echo "✅ CSRF Token generated: " . substr($csrfToken, 0, 16) . "...\n";
} catch (Exception $e) {
    echo "❌ CSRF Token error: " . $e->getMessage() . "\n";
}

echo "\n2️⃣ TESTING SESSION START\n";
echo "=========================\n";

try {
    session_start();
    $_SESSION['_token'] = $csrfToken;
    echo "✅ Session started with CSRF token\n";
} catch (Exception $e) {
    echo "❌ Session start error: " . $e->getMessage() . "\n";
}

echo "\n3️⃣ SIMULATING HTTP POST REQUEST\n";
echo "=================================\n";

try {
    $request = \Illuminate\Http\Request::create('/print-service/generate-session', 'POST', [
        '_token' => $csrfToken
    ]);
    
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('X-CSRF-TOKEN', $csrfToken);
    
    $printService = app(\App\Services\PrintService::class);
    $controller = new \App\Http\Controllers\PrintServiceController($printService);
    
    $response = $controller->generateSession($request);
    $responseData = $response->getData(true);
    
    echo "✅ HTTP POST simulation successful\n";
    echo "   - Status: " . $response->getStatusCode() . "\n";
    echo "   - Success: " . ($responseData['success'] ? 'Yes' : 'No') . "\n";
    echo "   - Token length: " . strlen($responseData['token']) . "\n";
    echo "   - QR URL accessible: " . (isset($responseData['qr_code_url']) ? 'Yes' : 'No') . "\n";
    
    if ($response->getStatusCode() === 200 && $responseData['success']) {
        echo "\n📱 FRONTEND TEST - REDIRECT SIMULATION\n";
        echo "======================================\n";
        
        $redirectUrl = '/print-service/' . $responseData['token'];
        echo "✅ Frontend would redirect to: $redirectUrl\n";
        
        $fullUrl = url($redirectUrl);
        echo "✅ Full URL: $fullUrl\n";
        
        try {
            $testSession = \App\Models\PrintSession::where('session_token', $responseData['token'])->first();
            if ($testSession) {
                echo "✅ Session exists in database\n";
                echo "   - Token: " . $testSession->session_token . "\n";
                echo "   - Active: " . ($testSession->is_active ? 'Yes' : 'No') . "\n";
                echo "   - Expires: " . $testSession->expires_at->format('Y-m-d H:i:s') . "\n";
            } else {
                echo "❌ Session not found in database\n";
            }
        } catch (Exception $e) {
            echo "❌ Database check error: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Response not successful\n";
        if (isset($responseData['error'])) {
            echo "   Error: " . $responseData['error'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ HTTP simulation error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 TESTING ACTUAL ROUTE RESOLUTION\n";
echo "===================================\n";

try {
    $router = app('router');
    $request = \Illuminate\Http\Request::create('/print-service/generate-session', 'POST');
    $route = $router->getRoutes()->match($request);
    
    echo "✅ Route resolved successfully\n";
    echo "   - Name: " . ($route->getName() ?? 'No name') . "\n";
    echo "   - Controller: " . $route->getController()::class . "\n";
    echo "   - Method: " . $route->getActionMethod() . "\n";
    
} catch (Exception $e) {
    echo "❌ Route resolution error: " . $e->getMessage() . "\n";
}

echo "\n📊 FRONTEND ACCESS TEST COMPLETE\n";
echo "=================================\n";
