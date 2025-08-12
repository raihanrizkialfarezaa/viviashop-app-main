<?php

use Illuminate\Support\Facades\DB;

echo "=== FIXING USER DATA CORRECTLY ===\n\n";

try {
    // Get current user data
    $user = DB::table('users')->where('id', 1)->first();
    
    echo "Current user data:\n";
    echo "ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "Province ID: {$user->province_id}\n";
    echo "City ID: {$user->city_id}\n";
    echo "District ID: {$user->district_id}\n\n";
    
    // Update to correct Mojokerto data
    echo "Updating to correct Mojokerto data...\n";
    
    DB::table('users')
        ->where('id', 1)
        ->update([
            'province_id' => 18, // Jawa Timur yang benar
            'city_id' => 388,    // Mojokerto yang benar  
            'district_id' => null, // Reset district
            'updated_at' => now()
        ]);
    
    echo "✅ Updated user data:\n";
    echo "- Province ID: 18 (Jawa Timur)\n";
    echo "- City ID: 388 (Mojokerto)\n";
    echo "- District ID: null (to be selected)\n\n";
    
    // Verify update
    $updatedUser = DB::table('users')->where('id', 1)->first();
    echo "Verified data:\n";
    echo "Province ID: {$updatedUser->province_id}\n";
    echo "City ID: {$updatedUser->city_id}\n";
    echo "District ID: {$updatedUser->district_id}\n\n";
    
    echo "✅ SUCCESS! Now refresh your profile page.\n";
    echo "You should see Mojokerto (not Bangkalan) with correct districts.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
