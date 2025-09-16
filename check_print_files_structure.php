<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Database configuration
$capsule = new DB;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'u875841990_viviashop',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== CHECKING PRINT_FILES TABLE STRUCTURE ===\n\n";

try {
    $columns = DB::select('DESCRIBE print_files');
    
    echo "Columns in print_files table:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }
    
    echo "\n=== SAMPLE DATA ===\n";
    $sample = DB::table('print_files')->limit(3)->get();
    
    foreach ($sample as $row) {
        echo "File ID: {$row->id}\n";
        foreach ((array)$row as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}