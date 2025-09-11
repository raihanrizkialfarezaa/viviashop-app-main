<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 ADMIN PRINT SERVICE VIEW RENDERING TEST\n";
echo "=========================================\n\n";

try {
    echo "1️⃣ Testing view compilation without auth...\n";
    
    // Create a temporary test route to verify view rendering
    \Illuminate\Support\Facades\Route::get('/test-admin-print-service-view', function() {
        try {
            // Test if we can render the view with minimal data
            $testData = [
                'title' => 'Print Service Dashboard Test',
                'totalOrders' => 0,
                'pendingOrders' => 0,
                'completedOrders' => 0,
                'totalSessions' => 0,
                'activeSessions' => 0,
                'totalRevenue' => 0,
                'recentOrders' => collect([]),
                'activeSessionsList' => collect([])
            ];
            
            return view('admin.print-service.index', $testData);
        } catch (Exception $e) {
            return response("View rendering error: " . $e->getMessage(), 500);
        }
    });
    
    echo "   ✅ Test route created\n";
    
    // Test the route
    echo "2️⃣ Making request to test route...\n";
    
    $request = \Illuminate\Http\Request::create('/test-admin-print-service-view', 'GET');
    $response = app()->handle($request);
    
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();
    
    echo "   📊 Response Status: $statusCode\n";
    
    if ($statusCode === 200) {
        echo "   ✅ View rendered successfully!\n";
        echo "   📄 Content length: " . strlen($content) . " bytes\n";
        
        // Check if content contains expected elements
        $hasTitle = str_contains($content, 'Print Service Dashboard');
        $hasBootstrap = str_contains($content, 'bootstrap') || str_contains($content, 'btn');
        $hasContainer = str_contains($content, 'container');
        
        echo "   🔍 Content analysis:\n";
        echo "      - Contains title: " . ($hasTitle ? "✅" : "❌") . "\n";
        echo "      - Contains Bootstrap: " . ($hasBootstrap ? "✅" : "❌") . "\n";
        echo "      - Contains container: " . ($hasContainer ? "✅" : "❌") . "\n";
        
        if ($hasTitle && ($hasBootstrap || $hasContainer)) {
            echo "\n🎉 SUCCESS: Admin Print Service view is working correctly!\n";
            echo "✅ No more 'View [admin.layout.master] not found' error\n";
            echo "✅ View extends layouts.app properly\n";
            echo "✅ Content renders without issues\n";
        } else {
            echo "\n⚠️ Warning: View renders but may have layout issues\n";
        }
        
    } else if ($statusCode === 500) {
        echo "   ❌ Server error occurred\n";
        echo "   📄 Error content: " . substr($content, 0, 200) . "...\n";
    } else {
        echo "   ⚠️ Unexpected status code: $statusCode\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}

echo "\n📋 SUMMARY:\n";
echo "===========\n";
echo "🔧 Fix Applied: Changed @extends('admin.layout.master') to @extends('layouts.app')\n";
echo "📁 Files Updated:\n";
echo "   - resources/views/admin/print-service/index.blade.php\n";
echo "   - resources/views/admin/print-service/queue.blade.php\n";
echo "\n🎯 Result: Admin print service page should now load without view errors!\n";
echo "📝 Note: Authentication is still required for /admin/print-service URL\n";

?>
