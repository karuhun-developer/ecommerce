<?php

use App\Actions\Cms\Content\SavePageAction;
use App\Data\Content\PageData;
use App\Models\Content\Banner;
use App\Models\Content\Page;
use App\Models\Menu\Menu;
use App\Models\Setting\Setting;
use App\Models\Spatie\Role;
use App\Models\User;
use Database\Seeders\WebsiteContentSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('seeds website permission and navigation without deleting existing menus', function () {
    $role = Role::create(['name' => 'superadmin', 'guard_name' => 'api']);
    $owner = Role::create(['name' => 'shopowner', 'guard_name' => 'api']);
    $menu = Menu::create(['role_id' => $role->id, 'name' => 'Custom', 'url' => 'cms.dashboard', 'active_pattern' => 'custom', 'order' => 1, 'status' => 1]);
    $this->seed(WebsiteContentSeeder::class);
    $this->seed(WebsiteContentSeeder::class);
    expect($role->fresh()->hasPermissionTo('manageWebsiteContent'))->toBeTrue()
        ->and($owner->fresh()->hasPermissionTo('manageWebsiteContent'))->toBeFalse()
        ->and(Menu::find($menu->id))->not->toBeNull()
        ->and(Menu::where('active_pattern', 'cms.content')->count())->toBe(1)
        ->and(Menu::where('active_pattern', 'cms.content')->first()->subMenu)->toHaveCount(5);
});

it('blocks content pages and direct mutations for unauthorized users', function () {
    $this->seed(WebsiteContentSeeder::class);
    $actor = User::factory()->create();
    $initialPageCount = Page::count();
    foreach (['banners', 'pages', 'footer-groups', 'header-links', 'identity'] as $path) {
        $this->actingAs($actor)->get('/cms/content/'.$path)->assertForbidden();
    }
    expect(fn () => app(SavePageAction::class)->handle(new PageData('Test', 'test', '<p>Test</p>', true, null, 0), $actor))->toThrow(AuthorizationException::class);
    expect(Page::count())->toBe($initialPageCount);
});

it('creates edits publishes and deletes a sanitized page through a form DTO', function () {
    Gate::before(fn () => true);
    $actor = User::factory()->create();
    $component = Livewire::actingAs($actor)->test('cms.content.page.create-update')->dispatch('set-action')
        ->set('form.title', 'Tentang Kami')->set('form.slug', 'tentang-kami')
        ->set('form.body', '<h2>Brand</h2><p>Aman</p><script>alert(1)</script><a href="javascript:alert(1)">Bahaya</a>')
        ->set('form.published', true)->set('form.footer_group', 'brand')->call('submit')->assertHasNoErrors();
    $page = Page::firstOrFail();
    expect($page->body)->toContain('<h2>Brand</h2>')->not->toContain('<script', 'javascript:');
    $this->get(route('content.page', ['slug' => $page->slug]))->assertSuccessful()->assertSee('Tentang Kami')->assertSee('<h2>Brand</h2>', false);
    $component->call('setAction', $page->id)->set('form.title', 'Tentang Brand')->call('submit')->assertHasNoErrors();
    expect($page->fresh()->title)->toBe('Tentang Brand');
    Livewire::actingAs($actor)->test('cms.content.page.table')->dispatch('delete', id: $page->id);
    expect(Page::count())->toBe(0);
});

it('keeps drafts private and validates unique slugs and unsafe links', function () {
    Gate::before(fn () => true);
    $actor = User::factory()->create();
    $page = Page::factory()->create(['slug' => 'draft']);
    $this->get('/pages/draft')->assertNotFound();
    Livewire::actingAs($actor)->test('cms.content.page.create-update')->dispatch('set-action')->set('form.title', 'Other')->set('form.slug', 'draft')->set('form.body', 'Content')->call('submit')->assertHasErrors(['form.slug']);
    Livewire::actingAs($actor)->test('cms.content.identity')->set('form.website_url', 'javascript:alert(1)')->call('save')->assertHasErrors(['form.website_url']);
});

it('renders only published footer pages and configured genuine social icons', function () {
    Page::factory()->published()->create(['title' => 'Tentang Brand', 'slug' => 'about', 'footer_group' => 'brand']);
    Page::factory()->create(['title' => 'Draft Secret', 'footer_group' => 'brand']);
    Livewire::test('ecommerce.component.footer')->assertSee('Tentang Brand')->assertDontSee('Draft Secret')->assertDontSee('aria-label="Instagram"', false);
    Gate::before(fn () => true);
    Livewire::actingAs(User::factory()->create())->test('cms.content.identity')->set('form.brand_name', 'Brand Baru')->set('form.instagram_url', 'https://www.instagram.com/brand')->set('form.whatsapp_url', 'https://wa.me/621234')->call('save')->assertHasNoErrors()->call('save');
    expect(Setting::where('key', 'storefront')->count())->toBe(1);
    Livewire::test('ecommerce.component.footer')->assertSee('Brand Baru')->assertSee('aria-label="Instagram"', false)->assertSee('aria-label="WhatsApp"', false)->assertDontSee('aria-label="Website"', false)->assertSee(route('content.page', ['slug' => 'about']), false);
});

it('creates and replaces a banner image and validates schedule and paired CTA', function () {
    Gate::before(fn () => true);
    Storage::fake('public');
    $component = Livewire::actingAs(User::factory()->create())->test('cms.content.banner.create-update')->dispatch('set-action')
        ->set('form.title', 'Promosi')->set('form.image_alt', 'Koleksi baru')->set('form.active', true)
        ->set('form.image', UploadedFile::fake()->image('banner.jpg'))->call('submit')->assertHasNoErrors();
    $banner = Banner::firstOrFail();
    expect($banner->hasMedia('banner'))->toBeTrue();
    $old = $banner->getFirstMedia('banner')->id;
    $component->call('setAction', $banner->id)->set('form.image', UploadedFile::fake()->image('replacement.png'))->call('submit')->assertHasNoErrors();
    expect($banner->fresh()->getMedia('banner'))->toHaveCount(1)->and($banner->fresh()->getFirstMedia('banner')->id)->not->toBe($old);
    $component->call('setAction', $banner->id)->set('form.cta_label', 'Belanja')->call('submit')->assertHasErrors(['form.cta_url']);
    $component->set('form.cta_url', 'https://example.test')->set('form.starts_at', '2026-10-02T10:00')->set('form.ends_at', '2026-10-01T10:00')->call('submit')->assertHasErrors(['form.ends_at']);
    Livewire::test('cms.content.banner.table')->dispatch('delete', id: $banner->id);
    expect(Banner::count())->toBe(0);
});

it('shows banners by schedule and order with controls only for multiple slides', function () {
    Storage::fake('public');
    $this->travelTo(now()->startOfDay());
    Livewire::test('ecommerce.component.banner')->assertDontSee('aria-roledescription="carousel"', false);
    $first = Banner::factory()->create(['title' => 'First', 'active' => true, 'sort_order' => 1]);
    $first->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('banner');
    Livewire::test('ecommerce.component.banner')->assertSee('First')->assertDontSee('Banner berikutnya');
    $second = Banner::factory()->create(['title' => 'Second', 'active' => true, 'sort_order' => 0]);
    $second->addMedia(UploadedFile::fake()->image('second.jpg'))->toMediaCollection('banner');
    $future = Banner::factory()->create(['title' => 'Future', 'active' => true, 'starts_at' => now()->addDay()]);
    $future->addMedia(UploadedFile::fake()->image('future.jpg'))->toMediaCollection('banner');
    $expired = Banner::factory()->create(['title' => 'Expired', 'active' => true, 'ends_at' => now()->subSecond()]);
    $expired->addMedia(UploadedFile::fake()->image('expired.jpg'))->toMediaCollection('banner');
    Banner::factory()->create(['title' => 'No image', 'active' => true]);
    Livewire::test('ecommerce.component.banner')->assertSeeInOrder(['Second', 'First'])->assertSee('Banner berikutnya')->assertDontSee('Future')->assertDontSee('Expired')->assertDontSee('No image');
});

it('escapes malicious textarea markup in the editor', function () {
    Livewire::test('jodit-text-editor', ['identifier' => 'safe-editor', 'value' => '</textarea><img src=x onerror=alert(1)><script>alert(2)</script>'])
        ->assertDontSee('<img src=x onerror=', false)->assertDontSee('<script>alert(2)</script>', false);
});

it('opens page forms through events and clears editor state when closed or reopened', function () {
    Gate::before(fn () => true);
    $page = Page::factory()->published()->create(['body' => '<p>Existing content</p>', 'footer_group' => 'brand']);
    $component = Livewire::actingAs(User::factory()->create())->test('cms.content.page.create-update')
        ->dispatch('set-action', id: $page->id)
        ->assertSet('id', $page->id)->assertSet('isUpdate', true)
        ->assertSet('form.body', $page->body)->assertSet('formReady', true)
        ->assertSee('Existing content')->assertSet('editorRevision', 1)
        ->set('form.title', '')->call('submit')->assertHasErrors(['form.title'])
        ->call('closeModal')->assertHasNoErrors()
        ->assertSet('id', null)->assertSet('isUpdate', false)
        ->assertSet('form.body', '')->assertSet('form.published', false)
        ->assertSet('formReady', false)->assertDontSee('Existing content')
        ->dispatch('set-action')->assertSet('editorRevision', 2)
        ->set('form.title', 'New page')->set('form.slug', 'new-page')->set('form.body', '<p>New content</p>')
        ->call('submit')->assertHasNoErrors()
        ->assertDispatchedTo('cms.content.page.table', 'reset-parent-page')
        ->assertSet('formReady', false)->assertSet('form.title', '');

    expect(Page::count())->toBe(2)->and($page->fresh()->body)->toBe('<p>Existing content</p>');
    $component->dispatch('set-action', id: $page->id)->assertSet('editorRevision', 3)
        ->dispatch('set-action')->assertSet('id', null)->assertSet('form.body', '');
});

it('clears banner image preview uploads and validation between modal sessions', function () {
    Gate::before(fn () => true);
    Storage::fake('public');
    $banner = Banner::factory()->create(['title' => 'Existing banner', 'active' => true]);
    $banner->addMedia(UploadedFile::fake()->image('existing.jpg'))->toMediaCollection('banner');

    $component = Livewire::actingAs(User::factory()->create())->test('cms.content.banner.create-update')
        ->dispatch('set-action', id: $banner->id)
        ->assertSet('id', $banner->id)->assertSet('isUpdate', true)
        ->assertSet('imageUrl', $banner->getFirstMediaUrl('banner'))
        ->set('form.title', '')->call('submit')->assertHasErrors(['form.title'])
        ->set('form.image', UploadedFile::fake()->image('unsaved.png'))
        ->call('closeModal')->assertHasNoErrors()
        ->assertSet('id', null)->assertSet('isUpdate', false)->assertSet('imageUrl', null)
        ->assertSet('form.image', null)->assertSet('form.title', '')->assertSet('form.active', false)
        ->dispatch('set-action')->set('form.title', 'New banner')->set('form.image_alt', 'New collection')
        ->set('form.image', UploadedFile::fake()->image('new.jpg'))->call('submit')->assertHasNoErrors()
        ->assertDispatchedTo('cms.content.banner.table', 'reset-parent-page')
        ->assertSet('form.image', null)->assertSet('id', null);

    expect(Banner::count())->toBe(2)->and($banner->fresh()->title)->toBe('Existing banner');
    $component->dispatch('set-action', id: $banner->id)->dispatch('set-action')
        ->assertSet('id', null)->assertSet('imageUrl', null)->assertSet('form.title', '');
});

it('filters sorts and resets pagination in content tables', function (string $componentName, string $model) {
    Gate::before(fn () => true);
    $model::factory()->count(11)->create(['title' => 'Other content', 'sort_order' => 10]);
    $model::factory()->create(['title' => 'Alpha promotion', 'sort_order' => 0]);
    $model::factory()->create(['title' => 'Beta promotion', 'sort_order' => 1]);

    $component = Livewire::actingAs(User::factory()->create())->test($componentName)
        ->assertSeeInOrder(['Alpha promotion', 'Beta promotion'])
        ->call('setPage', 2)->assertSet('paginators.page', 2)
        ->set('search', 'promotion')->assertSet('paginators.page', 1)
        ->assertSee('Alpha promotion')->assertSee('Beta promotion')->assertDontSee('Other content')
        ->call('changeOrder', 'title')->assertSeeInOrder(['Alpha promotion', 'Beta promotion'])
        ->call('changeOrder', 'title')->assertSeeInOrder(['Beta promotion', 'Alpha promotion'])
        ->set('search', '')->call('setPage', 2)
        ->set('paginate', 25)->assertSet('paginators.page', 1);

    $component->call('setPage', 2)->dispatch('reset-parent-page')->assertSet('paginators.page', 1);
})->with([
    'banners' => ['cms.content.banner.table', Banner::class],
    'pages' => ['cms.content.page.table', Page::class],
]);

it('protects content components from direct unauthorized access', function (string $component) {
    Livewire::actingAs(User::factory()->create())->test($component)->assertForbidden();
})->with([
    'cms.content.banner.table', 'cms.content.banner.create-update',
    'cms.content.page.table', 'cms.content.page.create-update',
]);

it('renders the content table and modal components on their CMS pages', function (string $path, string $component) {
    Gate::before(fn () => true);
    $this->actingAs(User::factory()->create())->get('/cms/content/'.$path)
        ->assertSuccessful()->assertSeeLivewire($component.'.table')->assertSeeLivewire($component.'.create-update');
})->with([
    'banners' => ['banners', 'cms.content.banner'],
    'pages' => ['pages', 'cms.content.page'],
]);
