<?php

use Illuminate\Support\Facades\DB;

echo "=== UPDATING USER WITH CORRECT NEW API IDs ===\n\n";

try {
    // Update user with correct IDs for new API
    $updated = DB::table('users')
        ->where('id', 1)
        ->update([
            'province_id' => 18,  // Jawa Timur (correct for new API)
            'city_id' => 388,     // Mojokerto (same ID)
            'district_id' => null, // Reset district to be selected
            'updated_at' => now()
        ]);
    
    echo "✅ Updated user data:\n";
    echo "- Province ID: 18 (Jawa Timur - correct for new API)\n";
    echo "- City ID: 388 (Mojokerto)\n";
    echo "- District ID: null (to be selected)\n\n";
    
    // Verify the update
    $user = DB::table('users')->where('id', 1)->first();
    echo "Verified user data:\n";
    echo "Province ID: {$user->province_id}\n";
    echo "City ID: {$user->city_id}\n";
    echo "District ID: {$user->district_id}\n\n";
    
    echo "✅ SUCCESS! User data updated for new API.\n";
    echo "Now refresh your profile page.\n";
    echo "You should see:\n";
    echo "- Provinces loaded correctly\n";
    echo "- Jawa Timur selected\n";
    echo "- Cities loaded correctly (JEMBER, BANYUWANGI, MOJOKERTO, etc.)\n";
    echo "- Mojokerto selected\n";
    echo "- Districts loaded correctly (BANGSAL, DAWAR BLANDONG, DLANGGU, etc.)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
