<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductInventory;
use App\Services\ProductVariantService;
use Illuminate\Support\Facades\DB;

class TestVariantAttributeFormatting extends Command
{
    protected $signature = 'test:variant-attribute-formatting';
    protected $description = 'Test variant attribute formatting for print service products';
    private $productVariantService;
    
    public function __construct()
    {
        parent::__construct();
        $this->productVariantService = app(ProductVariantService::class);
    }

    public function handle()
    {
        $this->info("=== Testing Variant Attribute Formatting ===\n");
        
        try {
            DB::transaction(function () {
                $this->testWrongAttributeFormatFromUI();
            });
            
            $this->info("✅ All variant attribute formatting tests completed successfully!");
            
        } catch (\Exception $e) {
            $this->error("❌ Test failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
    
    private function testWrongAttributeFormatFromUI()
    {
        $this->info("1. Setting up test product...");
        
        $category = Category::first() ?: Category::create(['name' => 'Test Category']);
        $brand = Brand::first() ?: Brand::create(['name' => 'Test Brand', 'status' => 1]);
        
        $productData = [
            'name' => 'VINYL PAPER TEST - ' . time(),
            'sku' => 'VPT-' . time(),
            'type' => 'configurable',
            'price' => 2,
            'harga_beli' => 1,
            'weight' => 0.1,
            'category_id' => [$category->id],
            'brand_id' => $brand->id,
            'short_description' => 'Test vinyl paper',
            'description' => 'Test description',
            'is_print_service' => true,
            'is_smart_print_enabled' => true,
            'status' => 1
        ];
        
        $product = $this->productVariantService->createBaseProduct($productData);
        $product->categories()->sync($productData['category_id']);
        
        $this->info("   ✅ Test product created");
        
        $this->info("\n2. Testing UI scenario: A3 => Color (wrong format)...");
        
        $variantData = [
            'name' => 'A3 Color Variant',
            'sku' => 'A3-COLOR-' . time(),
            'price' => 3.5,
            'harga_beli' => 2,
            'stock' => 50,
            'weight' => 0.1,
        ];
        
        $wrongFormatAttributes = [
            ['attribute_name' => 'A3', 'attribute_value' => 'Color']
        ];
        
        $variant = $this->productVariantService->createVariant($product, $variantData, $wrongFormatAttributes);
        
        if ($variant->paper_size !== 'A3') {
            throw new \Exception("Variant should have paper_size=A3, got: " . ($variant->paper_size ?: 'null'));
        }
        
        if ($variant->print_type !== 'color') {
            throw new \Exception("Variant should have print_type=color, got: " . ($variant->print_type ?: 'null'));
        }
        
        $attributes = $variant->variantAttributes->keyBy('attribute_name');
        
        if (!$attributes->has('paper_size')) {
            throw new \Exception("Variant should have paper_size attribute");
        }
        
        if (!$attributes->has('print_type')) {
            throw new \Exception("Variant should have print_type attribute");
        }
        
        if ($attributes->get('paper_size')->attribute_value !== 'A3') {
            throw new \Exception("paper_size attribute should be A3, got: " . $attributes->get('paper_size')->attribute_value);
        }
        
        if ($attributes->get('print_type')->attribute_value !== 'Color') {
            throw new \Exception("print_type attribute should be Color, got: " . $attributes->get('print_type')->attribute_value);
        }
        
        $this->info("   ✅ UI format A3 => Color correctly normalized");
        $this->info("   ✅ Database columns: paper_size=A3, print_type=color");
        $this->info("   ✅ Variant attributes: paper_size: A3, print_type: Color");
        
        $this->info("\n3. Testing stock service retrieval...");
        
        $stockService = app(\App\Services\StockManagementService::class);
        $variants = $stockService->getVariantsByStock();
        
        $testVariant = $variants->where('id', $variant->id)->first();
        
        if (!$testVariant) {
            throw new \Exception("Variant should be found in stock service");
        }
        
        if (empty($testVariant->paper_size)) {
            throw new \Exception("Stock service should return variant with paper_size");
        }
        
        if (empty($testVariant->print_type)) {
            throw new \Exception("Stock service should return variant with print_type");
        }
        
        $this->info("   ✅ Variant properly appears in stock management");
        $this->info("   ✅ Paper Size: " . $testVariant->paper_size);
        $this->info("   ✅ Print Type: " . $testVariant->print_type);
        
        $this->info("\n4. Testing other edge cases...");
        
        $edgeCases = [
            ['A4', 'Black & White'],
            ['A5', 'color'],
            ['paper_size', 'Letter']
        ];
        
        foreach ($edgeCases as $i => $case) {
            $edgeVariantData = [
                'name' => 'Edge Case ' . ($i + 1),
                'sku' => 'EDGE-' . ($i + 1) . '-' . time(),
                'price' => 2.5,
                'harga_beli' => 1.5,
                'stock' => 25,
                'weight' => 0.1,
            ];
            
            $edgeAttributes = [
                ['attribute_name' => $case[0], 'attribute_value' => $case[1]]
            ];
            
            $edgeVariant = $this->productVariantService->createVariant($product, $edgeVariantData, $edgeAttributes);
            
            $this->info("   ✅ Edge case {$case[0]} => {$case[1]} handled correctly");
        }
        
        $this->info("\n5. Verifying all variants appear in stock management...");
        
        $allVariants = $stockService->getVariantsByStock()->where('product_id', $product->id);
        
        if ($allVariants->count() < 4) {
            throw new \Exception("Should have at least 4 test variants in stock service, got: " . $allVariants->count());
        }
        
        foreach ($allVariants as $v) {
            if (empty($v->paper_size) || empty($v->print_type)) {
                throw new \Exception("All variants should have paper_size and print_type. Variant {$v->id}: paper_size={$v->paper_size}, print_type={$v->print_type}");
            }
        }
        
        $this->info("   ✅ All variants properly formatted and visible in stock management");
    }
}
