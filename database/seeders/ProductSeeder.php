<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Quelques noms fixes + du fake
        $default = [
            ['name' => 'Burgers'],
            ['name' => 'Pizzas'],
            ['name' => 'Drinks'],
            ['name' => 'Desserts'],
            ['name' => 'Salads'],
        ];

        // Insert fixe
        foreach ($default as $cat) {
            Category::create($cat);
        }
        DB::table('products')->insert([
            [
                'category_id' => 1,
                'name'        => 'Chicken Wings',
                'image_url'   => 'https://picsum.photos/400/300?random=1',
                'price'       => 20.00,
                'type'        => 'food',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'category_id' => 1,
                'name'        => 'Summer Salad',
                'image_url'   => 'https://picsum.photos/400/300?random=2',
                'price'       => 10.00,
                'type'        => 'food',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'category_id' => 2,
                'name'        => 'Fresh Lemon Juice',
                'image_url'   => 'https://picsum.photos/400/300?random=3',
                'price'       => 5.50,
                'type'        => 'drink',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
        DB::table('accompaniments')->insert([
            [
                'name' => 'French Fries',
                'price' => 0.00, // inclus dans le menu
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Green Salad',
                'price' => 0.00, // inclus dans le menu
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Coleslaw',
                'price' => 1.50, // supplément payant
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Garlic Bread',
                'price' => 2.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rice Pilaf',
                'price' => 0.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('accompaniment_product')->insert([
            // Burger Menu (product_id = 1)
            ['product_id' => 1, 'accompaniment_id' => 1, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()], // Fries
            ['product_id' => 1, 'accompaniment_id' => 2, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()], // Salad
            ['product_id' => 1, 'accompaniment_id' => 3, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()], // Coleslaw

            // Chicken Menu (product_id = 2)
            ['product_id' => 2, 'accompaniment_id' => 1, 'is_default' => true, 'created_at' => now(), 'updated_at' => now()], // Fries
            ['product_id' => 2, 'accompaniment_id' => 5, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()], // Rice Pilaf
            ['product_id' => 2, 'accompaniment_id' => 4, 'is_default' => false, 'created_at' => now(), 'updated_at' => now()], // Garlic Bread
        ]);
    }
}
