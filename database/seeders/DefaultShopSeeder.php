<?php

namespace Database\Seeders;

use App\Models\Location\Location;
use App\Models\Shop\Shop;
use App\Services\BiteshipService;
use Illuminate\Database\Seeder;

class DefaultShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultShop = Shop::first();
        if (! $defaultShop) {
            $defaultShop = Shop::create([
                'user_id' => 1,
                'name' => 'Default Shop',
                'description' => 'This is the default shop created by the seeder.',
            ]);

            $biteshipService = new BiteshipService;

            // Create location in Biteship and local database
            $biteshipLocation = $biteshipService->createLocation([
                'name' => 'Default Shop Location',
                'contact_name' => 'Default Shop Contact',
                'contact_phone' => '081234567890',
                'address' => 'Jl. Default Shop No. 1, Ngamprah, Bandung Barat, Jawa Barat. 40552',
                'note' => 'This is the default shop location created by the seeder.',
                'postal_code' => 40552,
                'latitude' => -6.8498780780658,
                'longitude' => 107.51830750045,
                'type' => 'origin',
            ]);

            Location::create([
                'user_id' => $defaultShop->user_id,
                'shop_id' => $defaultShop->id,
                'biteship_location_id' => $biteshipLocation['id'] ?? null,
                'biteship_area_id' => 'IDNP9IDNC23IDND2287IDZ40552', // Ngamprah, Bandung Barat, Jawa Barat. 40552
                'area_string' => 'Ngamprah, Bandung Barat, Jawa Barat. 40552',
                'name' => 'Default Shop Location',
                'contact_name' => 'Default Shop Contact',
                'contact_phone' => '081234567890',
                'address' => 'Jl. Default Shop No. 1, Ngamprah, Bandung Barat, Jawa Barat. 40552',
                'note' => 'This is the default shop location created by the seeder.',
                'postal_code' => '40552',
                'latitude' => '-6.8498780780658',
                'longitude' => '107.51830750045',
                'type' => 'origin',
            ]);
        }
    }
}
