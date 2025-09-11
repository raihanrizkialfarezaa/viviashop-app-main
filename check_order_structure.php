<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::create('/', 'GET'));

echo "📋 CHECKING PRINT_ORDERS TABLE STRUCTURE\n";
echo "========================================\n";

try {
    $columns = DB::select('DESCRIBE print_orders');
    
    echo "Print Orders Table Columns:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }
    
    echo "\n📋 CHECKING EXISTING ORDER TO GET CORRECT STRUCTURE\n";
    echo "==================================================\n";
    
    $existingOrder = \App\Models\PrintOrder::first();
    if ($existingOrder) {
        echo "Found existing order, attributes:\n";
        foreach ($existingOrder->getAttributes() as $key => $value) {
            echo "- $key: $value\n";
        }
    } else {
        echo "No existing orders found\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
