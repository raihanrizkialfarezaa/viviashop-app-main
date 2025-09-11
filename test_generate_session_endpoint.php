<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 TESTING GENERATE SESSION ENDPOINT\n";
echo "====================================\n";

echo "1️⃣ CHECKING DEPENDENCIES\n";
echo "=========================\n";

try {
    $printService = app(\App\Services\PrintService::class);
    echo "✅ PrintService class accessible\n";
} catch (Exception $e) {
    echo "❌ PrintService error: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $qrCodeClass = class_exists('SimpleSoftwareIO\QrCode\Facades\QrCode');
    echo "✅ QrCode class accessible: " . ($qrCodeClass ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo "❌ QrCode error: " . $e->getMessage() . "\n";
}

echo "\n2️⃣ TESTING SESSION GENERATION\n";
echo "==============================\n";

try {
    $session = $printService->generateSession();
    echo "✅ Session generated successfully\n";
    echo "   - Token: " . $session->session_token . "\n";
    echo "   - Expires: " . $session->expires_at->format('Y-m-d H:i:s') . "\n";
    echo "   - Active: " . ($session->is_active ? 'Yes' : 'No') . "\n";
    echo "   - Step: " . $session->current_step . "\n";
} catch (Exception $e) {
    echo "❌ Session generation error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n3️⃣ TESTING QR CODE GENERATION\n";
echo "==============================\n";

try {
    $qrCodeUrl = $session->getQrCodeUrl();
    echo "✅ QR Code URL: $qrCodeUrl\n";
    
    $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($qrCodeUrl);
    echo "✅ QR Code SVG generated (length: " . strlen($qrCode) . " chars)\n";
} catch (Exception $e) {
    echo "❌ QR Code error: " . $e->getMessage() . "\n";
}

echo "\n4️⃣ TESTING CONTROLLER METHOD\n";
echo "=============================\n";

try {
    $controller = new \App\Http\Controllers\PrintServiceController($printService);
    
    $request = new \Illuminate\Http\Request();
    $response = $controller->generateSession($request);
    
    $responseData = $response->getData(true);
    
    echo "✅ Controller method executed\n";
    echo "   - Response status: " . $response->getStatusCode() . "\n";
    echo "   - Has success field: " . (isset($responseData['success']) ? 'Yes' : 'No') . "\n";
    echo "   - Has token field: " . (isset($responseData['token']) ? 'Yes' : 'No') . "\n";
    echo "   - Has session field: " . (isset($responseData['session']) ? 'Yes' : 'No') . "\n";
    echo "   - Has qr_code_url field: " . (isset($responseData['qr_code_url']) ? 'Yes' : 'No') . "\n";
    echo "   - Has qr_code_svg field: " . (isset($responseData['qr_code_svg']) ? 'Yes' : 'No') . "\n";
    
    if (isset($responseData['token'])) {
        echo "   - Generated token: " . $responseData['token'] . "\n";
    }
    
    if (isset($responseData['error'])) {
        echo "❌ Controller error: " . $responseData['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Controller test error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n5️⃣ TESTING ROUTE ACCESS\n";
echo "========================\n";

try {
    $route = \Illuminate\Support\Facades\Route::getRoutes()->match(
        \Illuminate\Http\Request::create('/print-service/generate-session', 'POST')
    );
    echo "✅ Route exists\n";
    echo "   - URI: " . $route->uri() . "\n";
    echo "   - Methods: " . implode(', ', $route->methods()) . "\n";
    echo "   - Action: " . $route->getActionName() . "\n";
} catch (Exception $e) {
    echo "❌ Route error: " . $e->getMessage() . "\n";
}

echo "\n📊 ENDPOINT DIAGNOSIS COMPLETE\n";
echo "==============================\n";
