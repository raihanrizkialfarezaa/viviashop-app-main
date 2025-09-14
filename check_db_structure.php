<?php

$dsn = "mysql:host=127.0.0.1;dbname=u875841990_viviashop;charset=utf8mb4";
$username = "root";
$password = "";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CHECKING TABLE STRUCTURES ===\n\n";
    
    // Check stock_movements table structure
    $stmt = $pdo->query("DESCRIBE stock_movements");
    echo "📋 stock_movements table structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n";
    
    // Check products table structure  
    $stmt = $pdo->query("DESCRIBE products");
    echo "📋 products table structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n";
    
    // Just show some sample data
    $stmt = $pdo->query("SELECT * FROM stock_movements LIMIT 5");
    echo "📊 Sample stock movements:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}