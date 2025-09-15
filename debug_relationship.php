<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "Debugging whereDoesntHave issue...\n\n";

// Check if there's a different relation name or issue
$kertas_padang = Product::where('name', 'Kertas Padang')->first();

echo "Product: {$kertas_padang->name} (ID: {$kertas_padang->id})\n";

// Check different methods to get variants
echo "\nMethod 1 - Direct query:\n";
$variants1 = ProductVariant::where('product_id', $kertas_padang->id)->get();
echo "Count: {$variants1->count()}\n";

echo "\nMethod 2 - Using relationship:\n";
$variants2 = $kertas_padang->variants;
echo "Count: {$variants2->count()}\n";

echo "\nMethod 3 - Using relationship with load:\n";
$kertas_padang->load('variants');
$variants3 = $kertas_padang->variants;
echo "Count: {$variants3->count()}\n";

echo "\nMethod 4 - Fresh instance:\n";
$fresh = Product::with('variants')->find($kertas_padang->id);
echo "Count: {$fresh->variants->count()}\n";

// Check what whereDoesntHave actually does
echo "\n=== Testing whereDoesntHave ===\n";
$testQuery = Product::where('name', 'Kertas Padang')->whereDoesntHave('variants');
echo "SQL: " . $testQuery->toSql() . "\n";
$result = $testQuery->get();
echo "Result count: {$result->count()}\n";

// Check if there's a specific issue with variants relationship
echo "\n=== Checking ProductVariant model relationship ===\n";
$variant = ProductVariant::where('product_id', $kertas_padang->id)->first();
if($variant) {
    echo "Variant: {$variant->name}\n";
    echo "Product from variant: {$variant->product->name}\n";
}

// Check Product model relationship method
echo "\n=== Checking Product model relationship ===\n";
$reflection = new ReflectionClass($kertas_padang);
$methods = $reflection->getMethods();
$relationshipMethods = [];
foreach($methods as $method) {
    if(strpos($method->name, 'variant') !== false) {
        $relationshipMethods[] = $method->name;
    }
}
echo "Relationship methods found: " . implode(', ', $relationshipMethods) . "\n";