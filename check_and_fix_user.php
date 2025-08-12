<?php

use Illuminate\Support\Facades\DB;

echo "=== CHECKING AND FIXING USER DATA ===\n\n";

try {
    // Get current user data
    $user = DB::table('users')->where('id', 1)->first();
    
    echo "Current user data:\n";
    echo "Province ID: {$user->province_id}\n";
    echo "City ID: {$user->city_id}\n";
    echo "District ID: {$user->district_id}\n\n";
    
    // If province is 18, update to 11 (Jawa Timur yang benar menurut API sebelumnya)
    if ($user->province_id == 18) {
        echo "⚠️ Province ID is 18, but from previous tests, Jawa Timur should be 11\n";
        echo "Updating to correct Jawa Timur province (ID: 11) and Mojokerto city (ID: 388)...\n";
        
        DB::table('users')
            ->where('id', 1)
            ->update([
                'province_id' => 11, // Jawa Timur yang benar
                'city_id' => 388,    // Mojokerto yang benar  
                'district_id' => null, // Reset district
                'updated_at' => now()
            ]);
        
        echo "✅ Updated user data:\n";
        echo "- Province ID: 11 (Jawa Timur)\n";
        echo "- City ID: 388 (Mojokerto)\n";
        echo "- District ID: null (to be selected)\n\n";
    } else {
        echo "✅ Province ID looks correct\n\n";
    }
    
    // Verify final data
    $updatedUser = DB::table('users')->where('id', 1)->first();
    echo "Final user data:\n";
    echo "Province ID: {$updatedUser->province_id}\n";
    echo "City ID: {$updatedUser->city_id}\n";
    echo "District ID: {$updatedUser->district_id}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
