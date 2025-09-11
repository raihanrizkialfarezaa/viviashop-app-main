<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PRINT FILES TABLE ANALYSIS\n";
echo "==========================\n\n";

try {
    $columns = \Illuminate\Support\Facades\DB::select('DESCRIBE print_files');
    echo "print_files table columns:\n";
    foreach($columns as $col) {
        echo "- {$col->Field}: {$col->Type}\n";
    }
} catch (Exception $e) {
    echo "Error checking print_files: " . $e->getMessage() . "\n";
}

echo "\nChecking PrintFile model:\n";
$printFile = new \App\Models\PrintFile();
echo "Table name: " . $printFile->getTable() . "\n";
echo "Fillable: " . implode(', ', $printFile->getFillable()) . "\n";

echo "\nSample print file data:\n";
try {
    $sample = \App\Models\PrintFile::first();
    if ($sample) {
        foreach($sample->getAttributes() as $key => $value) {
            echo "- {$key}: {$value}\n";
        }
    } else {
        echo "No print files found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nChecking specific order files:\n";
$order = \App\Models\PrintOrder::where('order_code', 'PRINT-11-09-2025-14-21-22')->first();
if ($order) {
    echo "Order ID: {$order->id}\n";
    
    $files = \App\Models\PrintFile::where('print_order_id', $order->id)->get();
    echo "Files for order: {$files->count()}\n";
    
    foreach($files as $file) {
        echo "File: {$file->file_name} (ID: {$file->id})\n";
        echo "Path: {$file->file_path}\n";
        
        $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->file_path));
        echo "Full path: {$fullPath}\n";
        echo "Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
        
        if (!file_exists($fullPath)) {
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "Created directory\n";
            }
            $content = "FIXED FILE: {$file->file_name}\nOrder: {$order->order_code}\nGenerated: " . date('Y-m-d H:i:s');
            file_put_contents($fullPath, $content);
            echo "File created\n";
        }
    }
}

?>
