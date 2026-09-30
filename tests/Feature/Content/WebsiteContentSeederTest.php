<?php

use App\Models\Content\FooterGroup;
use App\Models\Content\HeaderLink;
use App\Models\Content\Page;
use App\Models\Menu\Menu;
use App\Models\Spatie\Role;
use App\Models\User;
use Database\Seeders\WebsiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('seeds complete footer columns and header destinations with one command', function () {
    $this->seed(WebsiteContentSeeder::class);

    expect(FooterGroup::query()->orderBy('sort_order')->pluck('name')->all())->toBe(['Ecommerce', 'Beli', 'Jual']);

    $footerMenus = [
        'brand' => ['Tentang Ecommerce', 'Hak Kekayaan Intelektual', 'Karir', 'Blog'],
        'buy' => ['Tagihan & Top Up', 'Tukar Tambah Handphone'],
        'sell' => ['Pusat Edukasi Seller', 'Daftar Official Store'],
    ];

    foreach ($footerMenus as $key => $titles) {
        $pages = FooterGroup::query()->where('key', $key)->firstOrFail()->pages()->orderBy('sort_order')->get();
        expect($pages->pluck('title')->all())->toBe($titles);
        foreach ($pages as $page) {
            expect($page->published)->toBeTrue()->and($page->body)->not->toBeEmpty();
            $this->get(route('content.page', ['slug' => $page->slug]))->assertSuccessful()->assertSee($page->title);
        }
    }

    $links = HeaderLink::query()->with('page')->orderBy('sort_order')->get();
    expect($links->pluck('label')->all())->toBe(['Tentang Ecommerce', 'Mitra Ecommerce', 'Promo', 'Bantuan'])
        ->and($links->pluck('position')->all())->toBe(['left', 'left', 'right', 'right']);

    foreach ($links as $link) {
        expect($link->active)->toBeTrue()->and($link->destination)->toBe('page')
            ->and($link->page->published)->toBeTrue();
    }

    $this->get('/')->assertSuccessful()->assertSee('Pusat Edukasi Seller')->assertSee('Tagihan &amp; Top Up', false);
});

it('fills missing defaults without duplicating or replacing customized content', function () {
    $this->seed(WebsiteContentSeeder::class);
    $group = FooterGroup::query()->where('key', 'buy')->firstOrFail();
    $group->update(['name' => 'Belanja', 'active' => false, 'sort_order' => 7]);
    $page = Page::query()->where('slug', 'tagihan-top-up')->firstOrFail();
    $page->update(['title' => 'Panduan Tagihan', 'body' => '<p>Isi dari admin.</p>', 'published' => false, 'footer_group' => 'sell', 'sort_order' => 9]);
    $link = HeaderLink::query()->where('key', 'promo')->firstOrFail();
    $link->update(['label' => 'Penawaran', 'destination' => 'url', 'page_id' => null, 'url' => 'https://example.test/promo', 'active' => false, 'sort_order' => 8]);
    Page::query()->where('slug', 'blog')->delete();

    $this->seed(WebsiteContentSeeder::class);
    $this->seed(WebsiteContentSeeder::class);

    expect(FooterGroup::query()->count())->toBe(3)->and(Page::query()->count())->toBe(11)->and(HeaderLink::query()->count())->toBe(4)
        ->and($group->fresh()->only(['name', 'active', 'sort_order']))->toBe(['name' => 'Belanja', 'active' => false, 'sort_order' => 7])
        ->and($page->fresh()->only(['title', 'body', 'published', 'footer_group', 'sort_order']))->toBe([
            'title' => 'Panduan Tagihan', 'body' => '<p>Isi dari admin.</p>', 'published' => false, 'footer_group' => 'sell', 'sort_order' => 9,
        ])
        ->and($link->fresh()->only(['label', 'destination', 'page_id', 'url', 'active', 'sort_order']))->toBe([
            'label' => 'Penawaran', 'destination' => 'url', 'page_id' => null, 'url' => 'https://example.test/promo', 'active' => false, 'sort_order' => 8,
        ])
        ->and(Page::query()->where('slug', 'blog')->firstOrFail()->footer_group)->toBe('brand');
});

it('updates existing CMS labels and order while preserving menu settings and clearing cached navigation', function () {
    $role = Role::create(['name' => 'superadmin', 'guard_name' => 'api']);
    $owner = Role::create(['name' => 'shopowner', 'guard_name' => 'api']);
    $actor = User::factory()->create();
    $actor->assignRole([$role, $owner]);
    $menu = Menu::create(['role_id' => $role->id, 'name' => 'Konten Website', 'url' => '#', 'active_pattern' => 'cms.content', 'order' => 400, 'status' => 1]);
    $pageMenu = $menu->subMenu()->create(['role_id' => $role->id, 'name' => 'Halaman', 'url' => 'cms.content.pages', 'active_pattern' => 'custom.pages', 'icon' => 'document-text', 'order' => 2, 'status' => 0]);
    $custom = $menu->subMenu()->create(['role_id' => $role->id, 'name' => 'Custom', 'url' => 'cms.dashboard', 'active_pattern' => 'custom', 'order' => 9, 'status' => 1]);
    Cache::put('menu:'.$role->id, ['stale']);
    Cache::put('menu:'.$actor->roles->pluck('id')->implode(','), ['stale']);

    $this->seed(WebsiteContentSeeder::class);
    $this->seed(WebsiteContentSeeder::class);

    expect($menu->fresh()->subMenu->pluck('name')->all())->toBe([
        'Identitas Website', 'Banner Homepage', 'Menu Header', 'Grup Footer', 'Menu Footer', 'Custom',
    ])
        ->and($pageMenu->fresh()->name)->toBe('Menu Footer')->and($pageMenu->fresh()->order)->toBe(5)
        ->and($pageMenu->fresh()->status->value)->toBe(0)->and($pageMenu->fresh()->active_pattern)->toBe('custom.pages')
        ->and($pageMenu->fresh()->icon)->toBe('document-text')->and($custom->fresh()->name)->toBe('Custom')
        ->and(Cache::has('menu:'.$role->id))->toBeFalse()
        ->and(Cache::has('menu:'.$actor->roles->pluck('id')->implode(',')))->toBeFalse();
});

it('uses the sidebar name as the CMS page heading', function (string $path, string $title) {
    $role = Role::create(['name' => 'superadmin', 'guard_name' => 'api']);
    $actor = User::factory()->create();
    $actor->assignRole($role);
    $this->seed(WebsiteContentSeeder::class);

    $this->actingAs($actor)->get('/cms/content/'.$path)->assertSuccessful()->assertSee($title);
})->with([
    ['identity', 'Identitas Website'], ['banners', 'Banner Homepage'], ['header-links', 'Menu Header'],
    ['footer-groups', 'Grup Footer'], ['pages', 'Menu Footer'],
]);
