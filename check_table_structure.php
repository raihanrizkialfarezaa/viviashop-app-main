<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    $columns = Schema::getColumnListing('product_attribute_values');
    echo "Columns in product_attribute_values table:\n";
    foreach ($columns as $column) {
        echo "- {$column}\n";
    }
    
    $sample = DB::table('product_attribute_values')->first();
    if ($sample) {
        echo "\nSample data:\n";
        print_r($sample);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
