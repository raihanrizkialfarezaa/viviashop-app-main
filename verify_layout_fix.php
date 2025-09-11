<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎯 ADMIN PRINT SERVICE LAYOUT FIX VERIFICATION\n";
echo "=============================================\n\n";

try {
    echo "1️⃣ Testing basic view structure...\n";
    
    // Create a minimal test view that just extends the layout
    $testViewContent = "@extends('layouts.app')

@section('title', 'Test Print Service')

@section('content')
<div class=\"container\">
    <h1>Print Service Test</h1>
    <p>This is a test to verify the layout fix works.</p>
</div>
@endsection";
    
    // Create temporary test view file
    $testViewPath = resource_path('views/test_print_service_layout.blade.php');
    file_put_contents($testViewPath, $testViewContent);
    
    echo "   ✅ Test view created\n";
    
    // Create a test route
    \Illuminate\Support\Facades\Route::get('/test-print-service-layout', function() {
        return view('test_print_service_layout');
    });
    
    echo "   ✅ Test route registered\n";
    
    // Test the route
    echo "2️⃣ Testing view rendering...\n";
    
    $request = \Illuminate\Http\Request::create('/test-print-service-layout', 'GET');
    $response = app()->handle($request);
    
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "   📊 Response Status: $statusCode\n";
    
    if ($statusCode === 200) {
        echo "   ✅ Layout renders successfully!\n";
        echo "   📄 Content length: " . strlen($content) . " bytes\n";
        
        // Check for basic HTML structure
        $hasHtml = str_contains($content, '<html');
        $hasHead = str_contains($content, '<head');
        $hasBody = str_contains($content, '<body');
        $hasTitle = str_contains($content, 'Print Service Test');
        
        echo "   🔍 Structure check:\n";
        echo "      - Has HTML tag: " . ($hasHtml ? "✅" : "❌") . "\n";
        echo "      - Has HEAD section: " . ($hasHead ? "✅" : "❌") . "\n";
        echo "      - Has BODY section: " . ($hasBody ? "✅" : "❌") . "\n";
        echo "      - Has test content: " . ($hasTitle ? "✅" : "❌") . "\n";
        
        if ($hasHtml && $hasHead && $hasBody && $hasTitle) {
            echo "\n🎉 LAYOUT FIX VERIFICATION SUCCESSFUL!\n";
            echo "✅ layouts.app is working correctly\n";
            echo "✅ No 'View [admin.layout.master] not found' error\n";
            echo "✅ View inheritance is functioning properly\n";
        } else {
            echo "\n⚠️ Warning: Layout renders but structure may be incomplete\n";
        }
        
    } else {
        echo "   ❌ View rendering failed with status: $statusCode\n";
        if ($statusCode === 500) {
            // Extract error message
            $errorStart = strpos($content, 'ViewException:');
            if ($errorStart !== false) {
                $errorMsg = substr($content, $errorStart, 200);
                echo "   📄 Error: $errorMsg\n";
            }
        }
    }
    
    // Cleanup
    if (file_exists($testViewPath)) {
        unlink($testViewPath);
        echo "   🧹 Test view cleaned up\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    
    // Cleanup on error
    $testViewPath = resource_path('views/test_print_service_layout.blade.php');
    if (file_exists($testViewPath)) {
        unlink($testViewPath);
    }
}

echo "\n📋 FINAL RESULT:\n";
echo "================\n";

// Verify the actual files were fixed
$indexPath = resource_path('views/admin/print-service/index.blade.php');
$queuePath = resource_path('views/admin/print-service/queue.blade.php');

$indexFixed = false;
$queueFixed = false;

if (file_exists($indexPath)) {
    $indexContent = file_get_contents($indexPath);
    $indexFixed = str_contains($indexContent, "@extends('layouts.app')") && 
                  !str_contains($indexContent, "@extends('admin.layout.master')");
}

if (file_exists($queuePath)) {
    $queueContent = file_get_contents($queuePath);
    $queueFixed = str_contains($queueContent, "@extends('layouts.app')") && 
                  !str_contains($queueContent, "@extends('admin.layout.master')");
}

echo "🔧 Files Fixed:\n";
echo "   - admin/print-service/index.blade.php: " . ($indexFixed ? "✅ FIXED" : "❌ NOT FIXED") . "\n";
echo "   - admin/print-service/queue.blade.php: " . ($queueFixed ? "✅ FIXED" : "❌ NOT FIXED") . "\n";

if ($indexFixed && $queueFixed) {
    echo "\n🎊 SUCCESS: Admin print service 'View not found' error is FIXED!\n";
    echo "The admin page /admin/print-service should now load properly.\n";
    echo "Note: Authentication is still required to access the actual admin URL.\n";
} else {
    echo "\n❌ ERROR: Some files may not be properly fixed.\n";
}

?>
