<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Frontend\HomepageController;
use Illuminate\Http\Request;

echo "=== TESTING BUTTON ATTRIBUTES ===\n\n";

$controller = new HomepageController();

ob_start();
$response = $controller->detail(117);
$content = ob_get_clean();

$content = $response->render();

if (strpos($content, 'add-to-card') !== false) {
    echo "✅ Add to cart button found\n";
    
    preg_match('/<a[^>]*class="[^"]*add-to-card[^"]*"[^>]*>/', $content, $matches);
    if (!empty($matches)) {
        echo "Button HTML: " . $matches[0] . "\n";
        
        if (strpos($matches[0], 'product-type="configurable"') !== false) {
            echo "✅ Product type set correctly\n";
        } else {
            echo "❌ Product type missing or incorrect\n";
        }
        
        if (strpos($matches[0], 'product-id="117"') !== false) {
            echo "✅ Product ID set correctly\n";
        } else {
            echo "❌ Product ID missing or incorrect\n";
        }
        
        if (strpos($matches[0], 'disabled') !== false) {
            echo "⚠️  Button is disabled\n";
        } else {
            echo "✅ Button is enabled\n";
        }
    }
} else {
    echo "❌ Add to cart button not found\n";
}

if (strpos($content, 'data-single-variant') !== false) {
    echo "✅ Single variant data found\n";
    
    preg_match('/data-single-variant="([^"]*)"/', $content, $singleMatches);
    if (!empty($singleMatches)) {
        echo "Single variant: " . $singleMatches[1] . "\n";
    }
    
    preg_match('/data-variant-id="([^"]*)"/', $content, $variantMatches);
    if (!empty($variantMatches)) {
        echo "Variant ID: " . $variantMatches[1] . "\n";
    }
} else {
    echo "❌ Single variant data not found\n";
}

if (strpos($content, 'data-selected-attributes') !== false) {
    echo "✅ Selected attributes data found\n";
    
    preg_match('/data-selected-attributes="([^"]*)"/', $content, $attrMatches);
    if (!empty($attrMatches)) {
        echo "Attributes data: " . $attrMatches[1] . "\n";
    }
} else {
    echo "ℹ️  Selected attributes data not found (expected for single variant)\n";
}

echo "\n=== TEST COMPLETED ===\n";
