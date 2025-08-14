<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Category;
use Illuminate\Http\Request;

echo "=== DEBUGGING SHOP SEARCH ISSUE ===\n\n";

// Simulate global request
request()->merge(['search' => 'dummy']);

echo "🔍 Global request has search: " . (request()->has('search') ? 'Yes' : 'No') . "\n";
echo "📝 Global search value: '" . request()->get('search', 'null') . "'\n\n";

// Step by step debugging
echo "📊 Step 1: Getting parent products\n";
$produk = Product::where(function($query) {
    $query->where('type', 'simple')
          ->whereNull('parent_id')
          ->orWhere('type', 'configurable');
})->get()->pluck('id');
echo "   Found {$produk->count()} parent products\n";

echo "\n📦 Step 2: Getting ProductCategory records\n";
$produkss = array($produk);
$products = ProductCategory::with(['products', 'categories'])->whereIn('product_id', $produkss[0])->get();
echo "   Found {$products->count()} ProductCategory records\n";

echo "\n🔍 Step 3: Filtering by search term\n";
if (request()->has('search')) {
    $searchTerm = request()->get('search', '');
    echo "   Search term: '{$searchTerm}'\n";
    $filteredProducts = collect();
    
    foreach ($products as $row) {
        if ($row->products && stripos($row->products->name, $searchTerm) !== false) {
            echo "   ✅ Found: {$row->products->name}\n";
            
            if ($row->products->parent_id) {
                $parentProduct = Product::find($row->products->parent_id);
                if ($parentProduct) {
                    $existingProduct = $filteredProducts->first(function($item) use ($parentProduct) {
                        return $item->products && $item->products->id === $parentProduct->id;
                    });
                    
                    if (!$existingProduct) {
                        $parentProductCategory = ProductCategory::where('product_id', $parentProduct->id)->first();
                        if ($parentProductCategory) {
                            echo "      -> Added parent: {$parentProduct->name}\n";
                            $filteredProducts->push($parentProductCategory);
                        }
                    }
                }
            } else {
                echo "      -> Added directly\n";
                $filteredProducts->push($row);
            }
        }
    }
    
    echo "\n🏆 Final filtered count: {$filteredProducts->count()}\n";
    $producted = $filteredProducts;
} else {
    echo "   No search term found\n";
    $producted = $products;
}

echo "\n✅ Result: {$producted->count()} products\n";

echo "\n=== TEST COMPLETED ===\n";
