<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

echo "🔍 DEBUGGING PRODUCTS ENDPOINT RESPONSE\n";
echo "=======================================\n\n";

try {
    echo "1. 🧪 Testing Internal Service...\n";
    $printService = new App\Services\PrintService();
    $products = $printService->getPrintProducts();
    echo "   ✅ Service works: " . $products->count() . " products found\n\n";

    echo "2. 🌐 Testing Controller Direct Call...\n";
    $controller = new App\Http\Controllers\PrintServiceController($printService);
    $request = new Illuminate\Http\Request();
    $response = $controller->getProducts($request);
    
    echo "   Status Code: " . $response->getStatusCode() . "\n";
    echo "   Content-Type: " . $response->headers->get('content-type') . "\n";
    $content = $response->getContent();
    echo "   Content Length: " . strlen($content) . " bytes\n";
    echo "   First 200 chars: " . substr($content, 0, 200) . "\n\n";

    echo "3. 🔗 Testing Route Registration...\n";
    $routes = Route::getRoutes();
    $found = false;
    
    foreach ($routes as $route) {
        if (str_contains($route->uri, 'print-service/products')) {
            echo "   ✅ Route found: " . $route->methods[0] . " " . $route->uri . "\n";
            echo "   Controller: " . $route->action['controller'] . "\n";
            echo "   Middleware: " . implode(', ', $route->middleware()) . "\n";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "   ❌ Route NOT FOUND!\n";
    }
    echo "\n";

    echo "4. 🔧 Testing Route Simulation...\n";
    
    $request = \Illuminate\Http\Request::create('/print-service/products', 'GET');
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');
    
    try {
        $app = app();
        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
        
        $response = $kernel->handle($request);
        
        echo "   Status: " . $response->getStatusCode() . "\n";
        echo "   Headers: " . json_encode($response->headers->all()) . "\n";
        $responseContent = $response->getContent();
        echo "   Content (first 300 chars): " . substr($responseContent, 0, 300) . "\n";
        
        if (str_contains($responseContent, 'DOCTYPE')) {
            echo "   ❌ PROBLEM: Returns HTML instead of JSON!\n";
            echo "   This suggests middleware redirect or error page\n";
        } else {
            echo "   ✅ Returns proper JSON response\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Route Error: " . $e->getMessage() . "\n";
    }
    echo "\n";

    echo "5. 🛡️ Testing Middleware Issues...\n";
    
    $middlewareGroups = config('auth.guards');
    echo "   Auth Guards: " . implode(', ', array_keys($middlewareGroups)) . "\n";
    
    $webMiddleware = Route::getMiddlewareGroups()['web'] ?? [];
    echo "   Web Middleware: " . implode(', ', $webMiddleware) . "\n\n";

    echo "🎯 DIAGNOSIS COMPLETE!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n\n";
    exit(1);
}
