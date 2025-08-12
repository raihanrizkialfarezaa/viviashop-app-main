<?php

use Illuminate\Support\Facades\DB;

echo "=== UPDATING USER WITH CORRECT NEW API IDs ===\n\n";

try {
    // Get current user data
    $user = DB::table('users')->where('id', 1)->first();
    
    echo "Current user data:\n";
    echo "Province ID: {$user->province_id}\n";
    echo "City ID: {$user->city_id}\n";
    echo "District ID: {$user->district_id}\n\n";
    
    echo "Updating to correct NEW API IDs...\n";
    
    DB::table('users')
        ->where('id', 1)
        ->update([
            'province_id' => 18,  // Jawa Timur yang benar untuk API baru
            'city_id' => 388,     // Mojokerto (sudah benar)
            'district_id' => null, // Reset district untuk dipilih ulang
            'updated_at' => now()
        ]);
    
    echo "✅ Updated user data:\n";
    echo "- Province ID: 18 (Jawa Timur - NEW API)\n";
    echo "- City ID: 388 (Mojokerto)\n";
    echo "- District ID: null (to be selected)\n\n";
    
    // Verify update
    $updatedUser = DB::table('users')->where('id', 1)->first();
    echo "Verified data:\n";
    echo "Province ID: {$updatedUser->province_id}\n";
    echo "City ID: {$updatedUser->city_id}\n";
    echo "District ID: {$updatedUser->district_id}\n\n";
    
    echo "✅ SUCCESS! User data updated for NEW API KEY.\n";
    echo "Now refresh your profile page.\n";
    echo "You should see correct Mojokerto districts: Bangsal, Dawar Blandong, etc.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
