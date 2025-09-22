<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            ['id' => 1, 'name' => 'Cetak', 'slug' => Str::slug('Cetak')],
            ['id' => 2, 'name' => 'Bag', 'slug' => Str::slug('Bag')],
            ['id' => 3, 'name' => 'Slingback', 'slug' => Str::slug('Slingback')],
            ['id' => 4, 'name' => 'Pouch', 'slug' => Str::slug('Pouch')],
            ['id' => 5, 'name' => 'ATK', 'slug' => Str::slug('ATK')],
            ['id' => 6, 'name' => 'Elektronik', 'slug' => Str::slug('Elektronik')],
        ];

        DB::table('categories')->insert($categories);
    }
}