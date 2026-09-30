<?php

namespace Database\Seeders;

use App\Models\Content\HeaderLink;
use App\Models\Content\Page;
use Illuminate\Database\Seeder;

class HeaderLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['tentang-ecommerce', 'Tentang Ecommerce', 'left'],
            ['mitra-ecommerce', 'Mitra Ecommerce', 'left'],
            ['promo', 'Promo', 'right'],
            ['bantuan', 'Bantuan', 'right'],
        ];

        foreach ($links as $order => [$slug, $label, $position]) {
            $page = Page::query()->where('slug', $slug)->first();
            if ($page === null) {
                continue;
            }

            HeaderLink::query()->firstOrCreate(['key' => $slug], [
                'label' => $label, 'position' => $position, 'destination' => 'page',
                'page_id' => $page->id, 'active' => true, 'sort_order' => $order,
            ]);
        }
    }
}
