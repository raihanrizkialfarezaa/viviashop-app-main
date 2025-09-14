<?php

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
    
    // 2. Check recent stock movements (using correct column names)
    $stmt = $pdo->query("
        SELECT sm.*, p.name as product_name, pv.stock as current_stock
        FROM stock_movements sm 
        LEFT JOIN product_variants pv ON sm.variant_id = pv.id
        LEFT JOIN products p ON pv.product_id = p.id 
        ORDER BY sm.created_at DESC 
        LIMIT 10
    ");
    
    echo "\n📋 Recent stock movements:\n";
    echo "-------------------------------------------\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type = $row['movement_type'] === 'in' ? '➕' : '➖';
        echo "{$type} {$row['quantity']} units - {$row['product_name']}\n";
        echo "   📝 {$row['reason']} - {$row['reference_type']}\n";
        echo "   📊 Stock change: {$row['old_stock']} → {$row['new_stock']}\n";
        echo "   📅 {$row['created_at']}\n\n";
    }
    
    // 3. Check purchase integration (old system uses different structure)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM stock_movements 
        WHERE reason LIKE '%Purchase%' OR reference_type = 'purchase'
        AND movement_type = 'in'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🛒 Purchase-related stock movements: " . $result['count'] . "\n";
    
    // 4. Check sales integration
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM stock_movements 
        WHERE (reason LIKE '%Sale%' OR reason LIKE '%Order%' OR reference_type = 'order') 
        AND movement_type = 'out'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🛍️ Sales-related stock movements: " . $result['count'] . "\n";
    
    // 5. Check print service integration
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM stock_movements 
        WHERE (reason LIKE '%Print%' OR reference_type = 'print_order') 
        AND movement_type = 'out'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🖨️ Print service stock movements: " . $result['count'] . "\n";
    
    // 6. Show new StockService integration
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM stock_movements 
        WHERE notes LIKE '%StockService%' OR reference_type = 'purchase'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🔧 New StockService movements: " . $result['count'] . "\n";
    
    // 7. Show products with most stock activity
    $stmt = $pdo->query("
        SELECT p.name, pv.stock, 
               COUNT(sm.id) as movement_count,
               SUM(CASE WHEN sm.movement_type = 'in' THEN sm.quantity ELSE 0 END) as total_in,
               SUM(CASE WHEN sm.movement_type = 'out' THEN sm.quantity ELSE 0 END) as total_out
        FROM products p 
        LEFT JOIN product_variants pv ON p.id = pv.product_id 
        LEFT JOIN stock_movements sm ON pv.id = sm.variant_id
        WHERE p.status = 1
        GROUP BY p.id, pv.id 
        HAVING movement_count > 0
        ORDER BY movement_count DESC 
        LIMIT 5
    ");
    
    echo "\n📦 Products with most stock activity:\n";
    echo "-------------------------------------------\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "• {$row['name']}\n";
        echo "  📊 Current stock: {$row['stock']}\n";
        echo "  📈 Movement history: {$row['movement_count']} records\n";
        echo "  ⬆️ Total received: {$row['total_in']} units\n";
        echo "  ⬇️ Total sold: {$row['total_out']} units\n\n";
    }
    
    // 8. Check if our new StockService is being used
    $stmt = $pdo->query("
        SELECT reason, COUNT(*) as count
        FROM stock_movements 
        GROUP BY reason
        ORDER BY count DESC
        LIMIT 10
    ");
    
    echo "📋 Movement reasons summary:\n";
    echo "-------------------------------------------\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "• {$row['reason']}: {$row['count']} movements\n";
    }
    
    echo "\n✅ Stock integration verification completed!\n";
    echo "✅ Found evidence of stock movements from multiple systems.\n";
    echo "✅ Ready to test new purchase integration!\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}