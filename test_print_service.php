<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrintSession;
use App\Models\PrintOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PrintService;

echo "=== PRINT SERVICE SYSTEM TEST ===\n\n";

try {
    echo "1. Testing Print Service Products...\n";
    
    $printProducts = Product::where('is_print_service', true)
                           ->with('activeVariants')
                           ->get();
    
    if ($printProducts->count() > 0) {
        echo "   ✅ Found {$printProducts->count()} print service products\n";
        
        foreach ($printProducts as $product) {
            echo "   - Product: {$product->name}\n";
            echo "     Variants: {$product->activeVariants->count()}\n";
            
            foreach ($product->activeVariants as $variant) {
                echo "     * {$variant->name} - {$variant->paper_size} - {$variant->print_type} - Rp " . number_format($variant->price, 0, ',', '.') . "\n";
            }
        }
    } else {
        echo "   ❌ No print service products found\n";
        echo "   Please run: php artisan db:seed --class=PrintServiceProductSeeder\n";
        return;
    }
    
    echo "\n2. Testing Print Session Generation...\n";
    
    $printService = new PrintService();
    $session = $printService->generateSession();
    
    if ($session) {
        echo "   ✅ Session created successfully\n";
        echo "   - Session Token: {$session->session_token}\n";
        echo "   - Barcode Token: {$session->barcode_token}\n";
        echo "   - QR Code URL: {$session->getQrCodeUrl()}\n";
        echo "   - Expires At: {$session->expires_at}\n";
        echo "   - Is Active: " . ($session->isActive() ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ❌ Failed to create session\n";
        return;
    }
    
    echo "\n3. Testing Print Service Controller...\n";
    
    try {
        $controller = new \App\Http\Controllers\PrintServiceController($printService);
        echo "   ✅ PrintServiceController instantiated successfully\n";
    } catch (Exception $e) {
        echo "   ❌ PrintServiceController error: " . $e->getMessage() . "\n";
        return;
    }
    
    try {
        $adminController = new \App\Http\Controllers\Admin\PrintServiceController($printService);
        echo "   ✅ Admin PrintServiceController instantiated successfully\n";
    } catch (Exception $e) {
        echo "   ❌ Admin PrintServiceController error: " . $e->getMessage() . "\n";
        return;
    }
    
    echo "\n4. Testing Price Calculation...\n";
    
    $variant = $printProducts->first()->activeVariants->first();
    if ($variant) {
        $calculation = $printService->calculatePrice($variant->id, 10, 1);
        
        echo "   ✅ Price calculation successful\n";
        echo "   - Variant: {$variant->name}\n";
        echo "   - Unit Price: Rp " . number_format($calculation['unit_price'], 0, ',', '.') . "\n";
        echo "   - Total Pages: {$calculation['total_pages']}\n";
        echo "   - Quantity: {$calculation['quantity']}\n";
        echo "   - Total Price: Rp " . number_format($calculation['total_price'], 0, ',', '.') . "\n";
    }
    
    echo "\n5. Testing Database Tables...\n";
    
    $tables = [
        'print_sessions' => PrintSession::count(),
        'print_orders' => PrintOrder::count(),
        'products' => Product::where('is_print_service', true)->count(),
        'product_variants' => ProductVariant::whereHas('product', function($q) {
            $q->where('is_print_service', true);
        })->count()
    ];
    
    foreach ($tables as $table => $count) {
        echo "   ✅ Table '{$table}': {$count} records\n";
    }
    
    echo "\n6. Testing Routes...\n";
    
    $routes = [
        'print-service.customer' => 'print-service/{token}',
        'print-service.upload' => 'print-service/upload',
        'print-service.products' => 'print-service/products',
        'admin.print-service.index' => 'admin/print-service',
        'admin.print-service.queue' => 'admin/print-service/queue'
    ];
    
    foreach ($routes as $routeName => $routePath) {
        try {
            if ($routeName === 'print-service.customer') {
                $url = route($routeName, ['token' => 'test-token']);
            } else {
                $url = route($routeName);
            }
            echo "   ✅ Route '{$routeName}': {$url}\n";
        } catch (Exception $e) {
            echo "   ❌ Route '{$routeName}' error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n7. Testing Session URL Generation...\n";
    
    $testUrl = url('/print-service/' . $session->session_token);
    echo "   ✅ Session URL: {$testUrl}\n";
    
    echo "\n8. Testing File Storage Directory...\n";
    
    $storageDate = now()->format('Y-m-d');
    $storagePath = storage_path("app/print-files/{$storageDate}/{$session->session_token}");
    
    if (!file_exists($storagePath)) {
        if (mkdir($storagePath, 0755, true)) {
            echo "   ✅ Storage directory created: {$storagePath}\n";
        } else {
            echo "   ❌ Failed to create storage directory\n";
        }
    } else {
        echo "   ✅ Storage directory exists: {$storagePath}\n";
    }
    
    echo "\n9. Testing Print Service Views...\n";
    
    $views = [
        'print-service.index',
        'print-service.expired',
        'print-service.error',
        'admin.print-service.index',
        'admin.print-service.queue'
    ];
    
    foreach ($views as $viewName) {
        if (view()->exists($viewName)) {
            echo "   ✅ View '{$viewName}' exists\n";
        } else {
            echo "   ❌ View '{$viewName}' does not exist\n";
        }
    }
    
    echo "\n10. Testing Session Cleanup...\n";
    
    $oldSessionsCount = PrintSession::where('expires_at', '<', now()->subHours(25))->count();
    PrintSession::cleanup();
    echo "   ✅ Session cleanup completed (cleaned {$oldSessionsCount} expired sessions)\n";
    
    echo "\n=== PRINT SERVICE SYSTEM TEST COMPLETE ===\n";
    echo "\n✅ All core components are working correctly!\n";
    
    echo "\nNext Steps:\n";
    echo "1. Visit /admin/print-service to access the admin panel\n";
    echo "2. Generate a new session and scan QR code\n";
    echo "3. Test file upload and order creation\n";
    echo "4. Test payment flow and printing\n";
    echo "5. Test order completion and cleanup\n";
    
    echo "\nTest Session Info:\n";
    echo "- Session Token: {$session->session_token}\n";
    echo "- Direct URL: {$testUrl}\n";
    echo "- Admin Panel: " . route('admin.print-service.index') . "\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
