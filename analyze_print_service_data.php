<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ADMIN PRINT SERVICE DATA ANALYSIS\n";
echo "===================================\n\n";

echo "1️⃣ Checking Print Sessions...\n";
$allSessions = \App\Models\PrintSession::count();
$activeSessions = \App\Models\PrintSession::active()->count();
$expiredSessions = \App\Models\PrintSession::where('expires_at', '<', now())->count();
$inactiveSessions = \App\Models\PrintSession::where('is_active', false)->count();

echo "   Total Sessions: $allSessions\n";
echo "   Active Sessions: $activeSessions\n";
echo "   Expired Sessions: $expiredSessions\n";
echo "   Inactive Sessions: $inactiveSessions\n";

echo "\n2️⃣ Checking Print Orders...\n";
$allOrders = \App\Models\PrintOrder::count();
$pendingPayment = \App\Models\PrintOrder::where('payment_status', \App\Models\PrintOrder::PAYMENT_WAITING)->count();
$paidOrders = \App\Models\PrintOrder::where('payment_status', \App\Models\PrintOrder::PAYMENT_PAID)->count();
$todayOrders = \App\Models\PrintOrder::whereDate('created_at', today())->count();

echo "   Total Orders: $allOrders\n";
echo "   Pending Payment: $pendingPayment\n";
echo "   Paid Orders: $paidOrders\n";
echo "   Today Orders: $todayOrders\n";

echo "\n3️⃣ Checking Print Queue...\n";
$printQueue = \App\Models\PrintOrder::printQueue()->count();
$paymentConfirmed = \App\Models\PrintOrder::where('status', \App\Models\PrintOrder::STATUS_PAYMENT_CONFIRMED)->count();
$readyToPrint = \App\Models\PrintOrder::where('status', \App\Models\PrintOrder::STATUS_READY_TO_PRINT)->count();

echo "   Print Queue Count: $printQueue\n";
echo "   Payment Confirmed: $paymentConfirmed\n";
echo "   Ready to Print: $readyToPrint\n";

echo "\n4️⃣ Recent Orders Details...\n";
$recentOrders = \App\Models\PrintOrder::with(['paperProduct', 'paperVariant'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

if ($recentOrders->count() > 0) {
    foreach ($recentOrders as $order) {
        echo "   Order: {$order->order_code}\n";
        echo "     Status: {$order->status}\n";
        echo "     Payment: {$order->payment_status}\n";
        echo "     Customer: {$order->customer_name}\n";
        echo "     Created: {$order->created_at}\n";
        echo "     Product: " . ($order->paperProduct ? $order->paperProduct->name : 'N/A') . "\n";
        echo "     Variant: " . ($order->paperVariant ? $order->paperVariant->paper_size . ' ' . $order->paperVariant->print_type : 'N/A') . "\n";
        echo "     ---\n";
    }
} else {
    echo "   No recent orders found\n";
}

echo "\n5️⃣ Session Issues Analysis...\n";
$sessionsWithoutExpiry = \App\Models\PrintSession::whereNull('expires_at')->count();
$sessionsWithOrders = \App\Models\PrintSession::has('printOrders')->count();
$sessionsWithoutOrders = \App\Models\PrintSession::doesntHave('printOrders')->count();

echo "   Sessions without expiry: $sessionsWithoutExpiry\n";
echo "   Sessions with orders: $sessionsWithOrders\n";
echo "   Sessions without orders: $sessionsWithoutOrders\n";

echo "\n6️⃣ Data Integrity Check...\n";
$orphanedFiles = \App\Models\PrintFile::whereNull('print_session_id')->whereNull('print_order_id')->count();
$filesWithSession = \App\Models\PrintFile::whereNotNull('print_session_id')->count();
$filesWithOrder = \App\Models\PrintFile::whereNotNull('print_order_id')->count();

echo "   Orphaned files: $orphanedFiles\n";
echo "   Files with session: $filesWithSession\n";
echo "   Files with order: $filesWithOrder\n";

echo "\n📊 SUMMARY:\n";
echo "==========\n";

if ($activeSessions > 10) {
    echo "⚠️ High active sessions count ($activeSessions) - may need cleanup\n";
}

if ($printQueue === 0 && $paidOrders > 0) {
    echo "❌ Print queue empty but paid orders exist - status issue\n";
}

if ($recentOrders->count() === 0) {
    echo "❌ No recent orders found - may need data sync\n";
}

echo "\n🔧 Recommended Actions:\n";
echo "=======================\n";
echo "1. Clean up expired sessions\n";
echo "2. Fix print queue status logic\n";
echo "3. Create missing orders view\n";
echo "4. Fix data loading in dashboard\n";

?>
