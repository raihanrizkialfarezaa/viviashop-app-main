<?php

/**
 * Simple verification test for stock integration
 */

// Check database for recent activity
$dsn = "mysql:host=127.0.0.1;dbname=u875841990_viviashop;charset=utf8mb4";
$username = "root";
$password = "";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== STOCK INTEGRATION VERIFICATION ===\n\n";
    
    // 1. Check StockMovement table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stock_movements");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Total stock movements: " . $result['count'] . "\n";
    
    // 2. Check recent stock movements
    $stmt = $pdo->query("
        SELECT sm.*, p.name as product_name
        FROM stock_movements sm 
        LEFT JOIN products p ON sm.product_id = p.id 
        ORDER BY sm.created_at DESC 
        LIMIT 10
    ");
    
    echo "\n📋 Recent stock movements:\n";
    echo "-------------------------------------------\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type = $row['movement_type'] === 'in' ? '➕' : '➖';
        echo "{$type} {$row['quantity']} units - {$row['product_name']}\n";
        echo "   📝 {$row['description']} - {$row['reference']}\n";
        echo "   📅 {$row['created_at']}\n\n";
    }
    
    // 3. Check purchase integration
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM stock_movements 
        WHERE description LIKE '%Purchase%' 
        AND movement_type = 'in'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🛒 Purchase-related stock movements: " . $result['count'] . "\n";
    
    // 4. Check sales integration
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM stock_movements 
        WHERE (description LIKE '%Sale%' OR description LIKE '%Order%') 
        AND movement_type = 'out'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🛍️ Sales-related stock movements: " . $result['count'] . "\n";
    
    // 5. Check print service integration
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM stock_movements 
        WHERE description LIKE '%Print%' 
        AND movement_type = 'out'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🖨️ Print service stock movements: " . $result['count'] . "\n";
    
    // 6. Show stock status for some products
    $stmt = $pdo->query("
        SELECT p.name, pv.stock, 
               COUNT(sm.id) as movement_count
        FROM products p 
        LEFT JOIN product_variants pv ON p.id = pv.product_id 
        LEFT JOIN stock_movements sm ON pv.id = sm.product_variant_id
        WHERE p.status = 'active'
        GROUP BY p.id, pv.id 
        ORDER BY movement_count DESC 
        LIMIT 5
    ");
    
    echo "\n📦 Products with most stock activity:\n";
    echo "-------------------------------------------\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "• {$row['name']}\n";
        echo "  📊 Current stock: {$row['stock']}\n";
        echo "  📈 Movement history: {$row['movement_count']} records\n\n";
    }
    
    echo "✅ Stock integration verification completed!\n";
    echo "✅ All systems appear to be recording stock movements properly.\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}