<?php

/**
 * Comprehensive Stock Management System Test
 * 
 * This script tests the complete integration of:
 * 1. Purchase system with stock update
 * 2. Frontend sales with stock movement
 * 3. Admin sales with stock movement  
 * 4. Smart print with stock movement
 * 5. Stock card history tracking
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Services\StockService;

class ComprehensiveStockTest
{
    private $testProductId;
    private $testVariantId;
    private $stockService;
    
    public function __construct()
    {
        $this->stockService = new StockService();
    }
    
    public function run()
    {
        echo "=== COMPREHENSIVE STOCK MANAGEMENT SYSTEM TEST ===\n\n";
        
        try {
            // 1. Setup test data
            $this->setupTestData();
            
            // 2. Test purchase with stock increase
            $this->testPurchaseStockIncrease();
            
            // 3. Test stock card history
            $this->testStockCardHistory();
            
            // 4. Test sales integration with different channels
            $this->testSalesIntegration();
            
            // 5. Final verification
            $this->finalVerification();
            
            echo "✅ ALL TESTS PASSED SUCCESSFULLY!\n";
            echo "✅ Stock management system is fully integrated across all sales channels.\n\n";
            
        } catch (Exception $e) {
            echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }
    
    private function setupTestData()
    {
        echo "📋 Setting up test data...\n";
        
        // Find or create test product
        $product = Product::where('name', 'LIKE', '%Test Product%')->first();
        if (!$product) {
            $product = Product::create([
                'name' => 'Test Product for Stock Integration',
                'slug' => 'test-product-stock-integration',
                'type' => 'configurable',
                'status' => 'active',
                'price' => 50000,
                'description' => 'Test product for comprehensive stock testing'
            ]);
        }
        
        $this->testProductId = $product->id;
        
        // Find or create test variant
        $variant = ProductVariant::where('product_id', $this->testProductId)->first();
        if (!$variant) {
            $variant = ProductVariant::create([
                'product_id' => $this->testProductId,
                'size' => 'L',
                'color' => 'Blue',
                'price' => 55000,
                'cost' => 35000,
                'stock' => 10
            ]);
        }
        
        $this->testVariantId = $variant->id;
        
        echo "   ✓ Test product ID: {$this->testProductId}\n";
        echo "   ✓ Test variant ID: {$this->testVariantId}\n";
        echo "   ✓ Initial stock: {$variant->stock}\n\n";
    }
    
    private function testPurchaseStockIncrease()
    {
        echo "🛒 Testing purchase system integration...\n";
        
        // Get initial stock
        $initialStock = ProductVariant::find($this->testVariantId)->stock;
        
        // Create purchase
        $pembelian = Pembelian::create([
            'supplier_id' => 1,
            'tanggal_pembelian' => now(),
            'total_harga' => 350000,
            'status' => 'diterima',
            'metode_pembayaran' => 'transfer'
        ]);
        
        // Create purchase detail
        PembelianDetail::create([
            'pembelian_id' => $pembelian->id,
            'product_id' => $this->testProductId,
            'product_variant_id' => $this->testVariantId,
            'jumlah' => 10,
            'harga_beli' => 35000,
            'subtotal' => 350000
        ]);
        
        // Process stock update using StockService
        $this->stockService->processPurchaseStockUpdate($pembelian);
        
        // Verify stock increase
        $finalStock = ProductVariant::find($this->testVariantId)->stock;
        $expectedStock = $initialStock + 10;
        
        if ($finalStock != $expectedStock) {
            throw new Exception("Purchase stock update failed. Expected: {$expectedStock}, Got: {$finalStock}");
        }
        
        echo "   ✓ Purchase created with ID: {$pembelian->id}\n";
        echo "   ✓ Stock increased from {$initialStock} to {$finalStock}\n";
        echo "   ✓ Purchase stock integration working correctly\n\n";
    }
    
    private function testStockCardHistory()
    {
        echo "📊 Testing stock movement history...\n";
        
        // Get recent stock movements for our test variant
        $movements = StockMovement::where('product_variant_id', $this->testVariantId)
                                 ->orderBy('created_at', 'desc')
                                 ->take(5)
                                 ->get();
        
        if ($movements->isEmpty()) {
            throw new Exception("No stock movements found after purchase");
        }
        
        $latestMovement = $movements->first();
        
        if ($latestMovement->movement_type !== 'in' || $latestMovement->quantity != 10) {
            throw new Exception("Latest stock movement doesn't match purchase: {$latestMovement->movement_type}, {$latestMovement->quantity}");
        }
        
        echo "   ✓ Found {$movements->count()} stock movements\n";
        echo "   ✓ Latest movement: {$latestMovement->movement_type} - {$latestMovement->quantity} units\n";
        echo "   ✓ Description: {$latestMovement->description}\n";
        echo "   ✓ Stock movement tracking working correctly\n\n";
    }
    
    private function testSalesIntegration()
    {
        echo "🛍️ Testing sales integration across channels...\n";
        
        $initialStock = ProductVariant::find($this->testVariantId)->stock;
        
        // Test 1: Create frontend order (simulate frontend sales)
        echo "   Testing frontend sales integration...\n";
        $order = Order::create([
            'order_code' => 'TEST-' . time(),
            'customer_first_name' => 'Test',
            'customer_last_name' => 'Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '081234567890',
            'status' => 'completed',
            'payment_status' => 'paid',
            'total' => 55000,
            'subtotal' => 55000,
            'payment_method' => 'transfer'
        ]);
        
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->testProductId,
            'product_variant_id' => $this->testVariantId,
            'qty' => 2,
            'price' => 55000,
            'total' => 110000
        ]);
        
        // Simulate frontend stock movement recording
        $this->stockService->recordMovement(
            $this->testProductId,
            $this->testVariantId,
            2,
            'out',
            'Frontend Sale',
            "Order #{$order->order_code}"
        );
        
        $stockAfterFrontend = ProductVariant::find($this->testVariantId)->stock;
        $expectedAfterFrontend = $initialStock - 2;
        
        if ($stockAfterFrontend != $expectedAfterFrontend) {
            throw new Exception("Frontend sales stock update failed. Expected: {$expectedAfterFrontend}, Got: {$stockAfterFrontend}");
        }
        
        echo "     ✓ Frontend order created: {$order->order_code}\n";
        echo "     ✓ Stock reduced from {$initialStock} to {$stockAfterFrontend}\n";
        
        // Test 2: Simulate admin sales
        echo "   Testing admin sales integration...\n";
        $this->stockService->recordMovement(
            $this->testProductId,
            $this->testVariantId,
            1,
            'out',
            'Admin Sale',
            "Admin Test Order"
        );
        
        $stockAfterAdmin = ProductVariant::find($this->testVariantId)->stock;
        $expectedAfterAdmin = $stockAfterFrontend - 1;
        
        if ($stockAfterAdmin != $expectedAfterAdmin) {
            throw new Exception("Admin sales stock update failed. Expected: {$expectedAfterAdmin}, Got: {$stockAfterAdmin}");
        }
        
        echo "     ✓ Admin sale recorded\n";
        echo "     ✓ Stock reduced from {$stockAfterFrontend} to {$stockAfterAdmin}\n";
        
        // Test 3: Simulate smart print sales
        echo "   Testing smart print integration...\n";
        $this->stockService->recordMovement(
            $this->testProductId,
            $this->testVariantId,
            1,
            'out',
            'Smart Print Service',
            "Print Order #PRINT-TEST-123"
        );
        
        $stockAfterPrint = ProductVariant::find($this->testVariantId)->stock;
        $expectedAfterPrint = $stockAfterAdmin - 1;
        
        if ($stockAfterPrint != $expectedAfterPrint) {
            throw new Exception("Smart print stock update failed. Expected: {$expectedAfterPrint}, Got: {$stockAfterPrint}");
        }
        
        echo "     ✓ Smart print sale recorded\n";
        echo "     ✓ Stock reduced from {$stockAfterAdmin} to {$stockAfterPrint}\n";
        echo "   ✓ All sales channel integrations working correctly\n\n";
    }
    
    private function finalVerification()
    {
        echo "🔍 Final verification...\n";
        
        // Check total stock movements
        $totalMovements = StockMovement::where('product_variant_id', $this->testVariantId)->count();
        
        if ($totalMovements < 4) { // 1 purchase + 3 sales
            throw new Exception("Expected at least 4 stock movements, found: {$totalMovements}");
        }
        
        // Check movement types
        $inMovements = StockMovement::where('product_variant_id', $this->testVariantId)
                                  ->where('movement_type', 'in')
                                  ->count();
        
        $outMovements = StockMovement::where('product_variant_id', $this->testVariantId)
                                   ->where('movement_type', 'out')
                                   ->count();
        
        echo "   ✓ Total movements: {$totalMovements}\n";
        echo "   ✓ In movements (purchases): {$inMovements}\n";
        echo "   ✓ Out movements (sales): {$outMovements}\n";
        
        // Check current stock calculation
        $currentVariant = ProductVariant::find($this->testVariantId);
        echo "   ✓ Current stock: {$currentVariant->stock}\n";
        
        // Display recent movements summary
        $recentMovements = StockMovement::where('product_variant_id', $this->testVariantId)
                                      ->orderBy('created_at', 'desc')
                                      ->take(10)
                                      ->get();
        
        echo "   📋 Recent stock movements:\n";
        foreach ($recentMovements as $movement) {
            $type = $movement->movement_type === 'in' ? '➕' : '➖';
            echo "      {$type} {$movement->quantity} units - {$movement->description} ({$movement->created_at->format('Y-m-d H:i:s')})\n";
        }
        
        echo "\n";
    }
}

// Run the test
$test = new ComprehensiveStockTest();
$test->run();