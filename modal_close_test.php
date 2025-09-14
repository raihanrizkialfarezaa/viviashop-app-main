<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MODAL CLOSE BUTTON DIAGNOSTIC ===\n\n";

echo "1. Checking modal structure...\n";

$modalFile = __DIR__ . '/resources/views/admin/pembelian_detail/produk.blade.php';
if (file_exists($modalFile)) {
    $content = file_get_contents($modalFile);
    
    echo "✓ Modal file found\n";
    
    // Check for close buttons
    if (strpos($content, 'data-dismiss="modal"') !== false) {
        echo "✓ data-dismiss=\"modal\" found in close buttons\n";
    } else {
        echo "✗ data-dismiss=\"modal\" NOT found\n";
    }
    
    // Count close buttons
    $closeButtonCount = substr_count($content, 'data-dismiss="modal"');
    echo "✓ Close buttons found: {$closeButtonCount}\n";
    
    // Check for modal IDs
    if (strpos($content, 'id="modal-produk"') !== false) {
        echo "✓ modal-produk ID found\n";
    }
    if (strpos($content, 'id="modal-variant"') !== false) {
        echo "✓ modal-variant ID found\n";
    }
    
} else {
    echo "✗ Modal file not found\n";
}

echo "\n2. Checking layout file for Bootstrap JS...\n";

$layoutFile = __DIR__ . '/resources/views/layouts/app.blade.php';
if (file_exists($layoutFile)) {
    $layoutContent = file_get_contents($layoutFile);
    
    if (strpos($layoutContent, 'bootstrap.min.js') !== false) {
        echo "✓ Bootstrap JS found in layout\n";
    } else {
        echo "✗ Bootstrap JS NOT found in layout\n";
    }
    
    // Check jQuery loading
    if (strpos($layoutContent, 'jquery') !== false || strpos($layoutContent, 'jQuery') !== false) {
        echo "✓ jQuery reference found\n";
    } else {
        echo "✗ jQuery reference NOT found\n";
    }
    
} else {
    echo "✗ Layout file not found\n";
}

echo "\n3. Checking for potential JavaScript conflicts...\n";

$indexFile = __DIR__ . '/resources/views/admin/pembelian_detail/index.blade.php';
if (file_exists($indexFile)) {
    $indexContent = file_get_contents($indexFile);
    
    // Check for event preventDefault
    if (strpos($indexContent, 'preventDefault') !== false) {
        echo "⚠ preventDefault() usage found - may interfere with modal close\n";
    }
    
    // Check for stopPropagation
    if (strpos($indexContent, 'stopPropagation') !== false) {
        echo "⚠ stopPropagation() usage found - may interfere with modal close\n";
    }
    
    // Check for return false
    if (strpos($indexContent, 'return false') !== false) {
        echo "⚠ 'return false' usage found - may interfere with modal close\n";
    }
    
    // Check for custom modal hide calls
    $hideCallCount = substr_count($indexContent, "modal('hide')");
    echo "✓ Custom modal hide calls: {$hideCallCount}\n";
    
} else {
    echo "✗ Index file not found\n";
}

echo "\n=== SOLUTION RECOMMENDATIONS ===\n";
echo "1. Add explicit close button event handlers\n";
echo "2. Ensure proper event delegation\n";
echo "3. Check z-index conflicts\n";
echo "4. Verify Bootstrap JS loading order\n";

?>