<?php

namespace Database\Seeders;

use App\Models\Content\FooterGroup;
use Illuminate\Database\Seeder;

class FooterGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['brand' => 'Ecommerce', 'buy' => 'Beli', 'sell' => 'Jual'] as $key => $name) {
            FooterGroup::query()->firstOrCreate(['key' => $key], [
                'name' => $name, 'active' => true,
                'sort_order' => array_search($key, ['brand', 'buy', 'sell']),
            ]);
        }
    }
}
