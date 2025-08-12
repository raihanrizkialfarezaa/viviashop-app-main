<?php

use Illuminate\Support\Facades\DB;

// Update user city_id from 290 to 388 (correct Mojokerto)
echo "=== UPDATING USER CITY ID ===\n\n";

try {
    // Update user with city_id 290 to 388 (Mojokerto yang benar)
    $updated = DB::table('users')
        ->where('city_id', 290)
        ->update([
            'city_id' => 388,
            'district_id' => null, // Reset district karena berubah kota
            'updated_at' => now()
        ]);
    
    echo "Updated {$updated} user(s)\n";
    echo "Changed city_id from 290 to 388 (correct Mojokerto)\n";
    echo "Reset district_id to null (will be selected again)\n\n";
    
    // Verify the update
    $users = DB::table('users')
        ->where('city_id', 388)
        ->select('id', 'name', 'email', 'province_id', 'city_id', 'district_id')
        ->get();
    
    echo "Users with city_id 388 (Mojokerto):\n";
    foreach($users as $user) {
        echo "- ID: {$user->id}, Name: {$user->name}, Province: {$user->province_id}, City: {$user->city_id}, District: {$user->district_id}\n";
    }
    
    echo "\n✅ SUCCESS! User data updated.\n";
    echo "Now refresh your profile page and check districts.\n";
    echo "You should see Mojokerto districts (Bangsal, Dawar Blandong, etc.)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
