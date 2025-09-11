<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧹 CLEANING UP PRINT SERVICE DATA\n";
echo "=================================\n\n";

echo "1️⃣ Before cleanup status...\n";
$activeSessions = \App\Models\PrintSession::active()->count();
$totalSessions = \App\Models\PrintSession::count();
$sessionsWithoutOrders = \App\Models\PrintSession::doesntHave('printOrders')->count();

echo "   Total sessions: $totalSessions\n";
echo "   Active sessions: $activeSessions\n";
echo "   Sessions without orders: $sessionsWithoutOrders\n";

echo "\n2️⃣ Cleaning up sessions without orders older than 1 hour...\n";
$oldEmptySessions = \App\Models\PrintSession::doesntHave('printOrders')
    ->where('created_at', '<', now()->subHour())
    ->get();

echo "   Found " . $oldEmptySessions->count() . " old empty sessions to clean\n";

foreach ($oldEmptySessions as $session) {
    // Delete associated files first
    $filesDeleted = $session->printFiles()->delete();
    if ($filesDeleted > 0) {
        echo "   Deleted $filesDeleted files from session {$session->session_token}\n";
    }
    
    // Mark session as inactive
    $session->update(['is_active' => false]);
    echo "   Marked session {$session->session_token} as inactive\n";
}

echo "\n3️⃣ Cleaning up very old sessions (older than 24 hours)...\n";
$veryOldSessions = \App\Models\PrintSession::where('created_at', '<', now()->subDay())
    ->where('is_active', true)
    ->get();

echo "   Found " . $veryOldSessions->count() . " very old sessions\n";

foreach ($veryOldSessions as $session) {
    $session->update(['is_active' => false]);
    echo "   Deactivated old session {$session->session_token}\n";
}

echo "\n4️⃣ After cleanup status...\n";
$activeSessionsAfter = \App\Models\PrintSession::active()->count();
$totalSessionsAfter = \App\Models\PrintSession::count();
$sessionsWithoutOrdersAfter = \App\Models\PrintSession::doesntHave('printOrders')->count();

echo "   Total sessions: $totalSessionsAfter\n";
echo "   Active sessions: $activeSessionsAfter\n";
echo "   Sessions without orders: $sessionsWithoutOrdersAfter\n";

echo "\n📊 CLEANUP SUMMARY:\n";
echo "===================\n";
echo "✅ Sessions deactivated: " . ($activeSessions - $activeSessionsAfter) . "\n";
echo "✅ Files cleaned: " . $oldEmptySessions->sum(function($s) { return $s->printFiles()->count(); }) . "\n";

if ($activeSessionsAfter < 5) {
    echo "✅ Active sessions count is now normal ($activeSessionsAfter)\n";
} else {
    echo "⚠️ Active sessions still high ($activeSessionsAfter) - may need further investigation\n";
}

echo "\n🎯 Next: Test the admin dashboard to see updated counts\n";

?>
