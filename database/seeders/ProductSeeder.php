<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Widget A', 'sku' => 'WGT-001'],
            ['name' => 'Widget B', 'sku' => 'WGT-002'],
            ['name' => 'Gadget X', 'sku' => 'GDT-001'],
            ['name' => 'Gadget Y', 'sku' => 'GDT-002'],
            ['name' => 'Component Z', 'sku' => 'CMP-001'],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}