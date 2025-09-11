<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 INVESTIGATING REMAINING ACTIVE SESSIONS\n";
echo "==========================================\n\n";

$activeSessions = \App\Models\PrintSession::active()->with('printOrders')->get();

echo "Found " . $activeSessions->count() . " active sessions:\n\n";

foreach ($activeSessions as $session) {
    echo "Session: {$session->session_token}\n";
    echo "Created: {$session->created_at}\n";
    echo "Age: " . $session->created_at->diffForHumans() . "\n";
    echo "Orders: " . $session->printOrders->count() . "\n";
    echo "Files: " . $session->printFiles()->count() . "\n";
    
    if ($session->printOrders->count() > 0) {
        foreach ($session->printOrders as $order) {
            echo "  Order #{$order->id}: {$order->status} (Total: Rp " . number_format($order->total_amount) . ")\n";
        }
    }
    
    echo "---\n";
}

echo "\n🧹 AGGRESSIVE CLEANUP (keeping only sessions with orders from today)\n";
echo "===================================================================\n";

$sessionsToKeep = \App\Models\PrintSession::active()
    ->whereHas('printOrders')
    ->where('created_at', '>=', now()->startOfDay())
    ->get();

echo "Sessions to keep: " . $sessionsToKeep->count() . "\n";

foreach ($sessionsToKeep as $session) {
    echo "  Keeping: {$session->session_token} (has " . $session->printOrders->count() . " orders)\n";
}

$sessionsToDeactivate = \App\Models\PrintSession::active()
    ->where(function($query) {
        $query->doesntHave('printOrders')
              ->orWhere('created_at', '<', now()->startOfDay());
    })
    ->get();

echo "\nSessions to deactivate: " . $sessionsToDeactivate->count() . "\n";

foreach ($sessionsToDeactivate as $session) {
    echo "  Deactivating: {$session->session_token} (age: " . $session->created_at->diffForHumans() . ")\n";
    $session->update(['is_active' => false]);
}

echo "\n📊 FINAL STATUS:\n";
echo "================\n";
$finalActive = \App\Models\PrintSession::active()->count();
echo "Active sessions now: $finalActive\n";

if ($finalActive <= 2) {
    echo "✅ Perfect! Active sessions count is now optimal\n";
} else {
    echo "⚠️ Still need investigation for remaining sessions\n";
}

?>
