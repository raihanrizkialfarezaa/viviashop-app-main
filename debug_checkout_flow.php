<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKOUT DEBUG ANALYSIS ===\n\n";

echo "1. ROUTES ANALYSIS:\n";
$routes = Route::getRoutes();
$checkoutRoutes = [];

foreach($routes as $route) {
    $uri = $route->uri();
    if (strpos($uri, 'checkout') !== false) {
        $checkoutRoutes[] = [
            'uri' => $uri,
            'methods' => implode('|', $route->methods()),
            'name' => $route->getName(),
            'action' => $route->getActionName()
        ];
    }
}

foreach($checkoutRoutes as $route) {
    echo "URI: {$route['uri']}\n";
    echo "Methods: {$route['methods']}\n";
    echo "Name: {$route['name']}\n";
    echo "Action: {$route['action']}\n";
    echo "---\n";
}

echo "\n2. PAYMENT SLIP LOGIC ANALYSIS:\n";
echo "Checking current payment slip logic in checkout blade...\n";

$checkoutBlade = file_get_contents('./resources/views/frontend/orders/checkout.blade.php');

if (strpos($checkoutBlade, 'payment-slip-section') !== false) {
    echo "✓ Payment slip section found in checkout page\n";
    
    if (strpos($checkoutBlade, 'selectedPayment === \'manual\'') !== false) {
        echo "✓ Payment slip section is conditional based on manual payment\n";
    } else {
        echo "❌ Payment slip section logic might be wrong\n";
    }
} else {
    echo "❌ Payment slip section not found\n";
}

echo "\n3. FORM ACTION ANALYSIS:\n";
if (strpos($checkoutBlade, 'route(\'orders.checkout\')') !== false) {
    echo "✓ Form action uses named route 'orders.checkout'\n";
    echo "This should POST to doCheckout method\n";
} else {
    echo "❌ Form action might be incorrect\n";
}

echo "\n4. VALIDATION RULES ANALYSIS:\n";
echo "Checking if payment_slip is in validation rules...\n";

$controllerContent = file_get_contents('./app/Http/Controllers/Frontend/OrderController.php');

if (strpos($controllerContent, 'payment_slip') !== false) {
    echo "✓ payment_slip handling found in controller\n";
} else {
    echo "❌ payment_slip might not be handled in controller\n";
}

echo "\n5. REDIRECT ANALYSIS:\n";
echo "Looking for redirect patterns in doCheckout...\n";

$redirectPatterns = [
    'redirect(\'orders/received/',
    'redirect()->back()',
    'redirect(\'carts\')'
];

foreach($redirectPatterns as $pattern) {
    if (strpos($controllerContent, $pattern) !== false) {
        echo "✓ Found redirect pattern: $pattern\n";
    }
}

echo "\n6. ERROR HANDLING ANALYSIS:\n";
if (strpos($controllerContent, 'withErrors') !== false) {
    echo "✓ Error handling with withErrors found\n";
}

if (strpos($controllerContent, 'withInput') !== false) {
    echo "✓ Input preservation with withInput found\n";
}

echo "\n=== DEBUG COMPLETE ===\n";