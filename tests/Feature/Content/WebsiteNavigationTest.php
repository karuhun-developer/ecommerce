<?php

use App\Actions\Cms\Content\DeleteFooterGroupAction;
use App\Actions\Cms\Content\SaveHeaderLinkAction;
use App\Data\Content\HeaderLinkData;
use App\Models\Content\Banner;
use App\Models\Content\FooterGroup;
use App\Models\Content\HeaderLink;
use App\Models\Content\Page;
use App\Models\User;
use Database\Seeders\FooterGroupSeeder;
use Database\Seeders\HeaderLinkSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders homepage with the navigation components and download badges', function () {
    $this->seed([FooterGroupSeeder::class, PageSeeder::class, HeaderLinkSeeder::class]);
    $this->get('/')->assertSuccessful()->assertSeeLivewire('ecommerce.component.topbar')
        ->assertSee('Tentang Ecommerce')->assertSee('Mitra Ecommerce')->assertSee('Promo')->assertSee('Bantuan')
        ->assertSee('Hak Kekayaan Intelektual')->assertSee('Ecommerce.')
        ->assertSee('Download aplikasi Ecommerce sekarang.')->assertSee('google-play-badge.svg')->assertSee('app-store-badge.svg')
        ->assertDontSee('Haki QA');
    expect(is_file(public_path('images/google-play-badge.svg')))->toBeTrue()
        ->and(is_file(public_path('images/app-store-badge.svg')))->toBeTrue();
});

it('preserves edited content and navigation when defaults are seeded again', function () {
    $this->seed([FooterGroupSeeder::class, PageSeeder::class, HeaderLinkSeeder::class]);
    FooterGroup::where('key', 'brand')->update(['name' => 'Informasi', 'active' => false]);
    Page::where('slug', 'tentang-ecommerce')->update(['title' => 'Tentang Kami', 'published' => false]);
    HeaderLink::where('key', 'promo')->update(['label' => 'Penawaran', 'active' => false]);
    $this->seed([FooterGroupSeeder::class, PageSeeder::class, HeaderLinkSeeder::class]);
    expect(FooterGroup::count())->toBe(3)->and(Page::count())->toBe(11)->and(HeaderLink::count())->toBe(4)
        ->and(FooterGroup::where('key', 'brand')->first()->name)->toBe('Informasi')
        ->and(Page::where('slug', 'tentang-ecommerce')->first()->published)->toBeFalse()
        ->and(HeaderLink::where('key', 'promo')->first()->label)->toBe('Penawaran');
});

it('creates and renames a footer group without losing its assigned pages', function () {
    Gate::before(fn () => true);
    $component = Livewire::actingAs(User::factory()->create())->test('cms.content.footer-group.create-update')
        ->dispatch('set-action')->set('form.name', 'Layanan Pelanggan')->set('form.sort_order', 4)
        ->call('submit')->assertHasNoErrors()->assertDispatchedTo('cms.content.footer-group.table', 'reset-parent-page');
    $group = FooterGroup::where('name', 'Layanan Pelanggan')->firstOrFail();
    $page = Page::factory()->published()->create(['title' => 'Hubungi Kami', 'footer_group' => $group->key]);
    $component->dispatch('set-action', id: $group->id)->set('form.name', 'Bantuan Pelanggan')->call('submit')->assertHasNoErrors();
    expect($group->fresh()->key)->toBe($group->key)->and($page->fresh()->footerGroup->name)->toBe('Bantuan Pelanggan');
    Livewire::test('ecommerce.component.footer')->assertSee('Bantuan Pelanggan')->assertSee('Hubungi Kami');
    $component->dispatch('set-action', id: $group->id)->set('form.active', false)->call('submit');
    Livewire::test('ecommerce.component.footer')->assertDontSee('Bantuan Pelanggan')->assertDontSee('Hubungi Kami');
});

it('detaches footer pages when deleting a group while preserving their content', function () {
    Gate::before(fn () => true);
    $group = FooterGroup::factory()->create();
    $page = Page::factory()->published()->create(['footer_group' => $group->key]);
    Livewire::actingAs(User::factory()->create())->test('cms.content.footer-group.table')->dispatch('delete', id: $group->id);
    expect(FooterGroup::find($group->id))->toBeNull()->and($page->fresh()->footer_group)->toBeNull()
        ->and($page->fresh()->published)->toBeTrue();
});

it('lets pages select any managed footer group and rejects unknown groups', function () {
    Gate::before(fn () => true);
    $group = FooterGroup::factory()->create(['name' => 'Custom Group']);
    $component = Livewire::actingAs(User::factory()->create())->test('cms.content.page.create-update')
        ->dispatch('set-action')->assertSee('Custom Group')->set('form.title', 'Custom Page')
        ->set('form.slug', 'custom-page')->set('form.body', '<p>Konten</p>')->set('form.footer_group', 'unknown')
        ->call('submit')->assertHasErrors(['form.footer_group'])->set('form.footer_group', $group->key)
        ->call('submit')->assertHasNoErrors();
    expect(Page::firstOrFail()->footer_group)->toBe($group->key);
});

it('orders footer groups and their published pages', function () {
    $first = FooterGroup::factory()->create(['name' => 'First Group', 'sort_order' => 0]);
    $last = FooterGroup::factory()->create(['name' => 'Last Group', 'sort_order' => 9]);
    Page::factory()->published()->create(['title' => 'Second Page', 'footer_group' => $first->key, 'sort_order' => 2]);
    Page::factory()->published()->create(['title' => 'First Page', 'footer_group' => $first->key, 'sort_order' => 1]);
    Page::factory()->published()->create(['title' => 'Last Page', 'footer_group' => $last->key]);
    Page::factory()->create(['title' => 'Secret Draft', 'footer_group' => $first->key]);
    Livewire::test('ecommerce.component.footer')->assertSeeInOrder(['First Group', 'First Page', 'Second Page', 'Last Group', 'Last Page'])->assertDontSee('Secret Draft');
});

it('creates updates and deletes a header link through its modal', function () {
    Gate::before(fn () => true);
    $page = Page::factory()->published()->create();
    $actor = User::factory()->create();
    $component = Livewire::actingAs($actor)->test('cms.content.header-link.create-update')
        ->dispatch('set-action')->set('form.label', 'Informasi')->set('form.page_id', $page->id)
        ->set('form.url', 'https://stale.test')->call('submit')->assertHasNoErrors()
        ->assertDispatchedTo('cms.content.header-link.table', 'reset-parent-page');
    $link = HeaderLink::firstOrFail();
    expect($link->url)->toBeNull()->and($link->page_id)->toBe($page->id);
    $component->dispatch('set-action', id: $link->id)->set('form.destination', 'url')->set('form.position', 'right')
        ->set('form.label', 'Penawaran')->set('form.url', 'https://example.test/promo')->call('submit')->assertHasNoErrors();
    expect($link->fresh()->page_id)->toBeNull()->and($link->fresh()->position)->toBe('right');
    Livewire::test('ecommerce.component.topbar')->assertSee('Penawaran')->assertSee('https://example.test/promo', false);
    Livewire::actingAs($actor)->test('cms.content.header-link.table')->dispatch('delete', id: $link->id);
    expect(HeaderLink::count())->toBe(0);
});

it('requires a valid header destination and rejects unsafe external URLs', function () {
    Gate::before(fn () => true);
    $component = Livewire::actingAs(User::factory()->create())->test('cms.content.header-link.create-update')
        ->dispatch('set-action')->set('form.label', 'Link')->set('form.page_id', '')
        ->call('submit')->assertHasErrors(['form.page_id']);
    $component->set('form.page_id', 99999)->call('submit')->assertHasErrors(['form.page_id']);
    $component->set('form.destination', 'url')->set('form.url', 'javascript:alert(1)')->call('submit')->assertHasErrors(['form.url']);
    $component->set('form.url', 'https://example.test')->set('form.position', 'invalid')->call('submit')->assertHasErrors(['form.position']);
    expect(HeaderLink::count())->toBe(0);
});

it('only shows active header links with published existing page destinations', function () {
    $published = Page::factory()->published()->create();
    $draft = Page::factory()->create();
    $deleted = Page::factory()->published()->create();
    HeaderLink::factory()->create(['label' => 'Public Page', 'destination' => 'page', 'page_id' => $published->id, 'url' => null, 'sort_order' => 0]);
    HeaderLink::factory()->create(['label' => 'External Link', 'position' => 'right', 'sort_order' => 1]);
    HeaderLink::factory()->create(['label' => 'Draft Page', 'destination' => 'page', 'page_id' => $draft->id]);
    HeaderLink::factory()->create(['label' => 'Deleted Page', 'destination' => 'page', 'page_id' => $deleted->id]);
    HeaderLink::factory()->create(['label' => 'Inactive Link', 'active' => false]);
    $deleted->delete();
    Livewire::test('ecommerce.component.topbar')->assertSeeInOrder(['Public Page', 'External Link'])
        ->assertSee(route('content.page', ['slug' => $published->slug]), false)->assertSee('wire:navigate', false)
        ->assertDontSee('Draft Page')->assertDontSee('Deleted Page')->assertDontSee('Inactive Link');
});

it('clears validation and edit state between navigation modal sessions', function (string $componentName, string $model, string $field) {
    Gate::before(fn () => true);
    $record = $model::factory()->create([$field => 'Existing']);
    Livewire::actingAs(User::factory()->create())->test($componentName)
        ->dispatch('set-action', id: $record->id)->assertSet('isUpdate', true)->assertSet('id', $record->id)
        ->set('form.'.$field, '')->call('submit')->assertHasErrors(['form.'.$field])
        ->call('closeModal')->assertHasNoErrors()->assertSet('id', null)->assertSet('isUpdate', false)
        ->dispatch('set-action')->assertSet('form.'.$field, '');
})->with([
    ['cms.content.footer-group.create-update', FooterGroup::class, 'name'],
    ['cms.content.header-link.create-update', HeaderLink::class, 'label'],
]);

it('filters the navigation tables and resets pagination', function (string $componentName, string $model, string $field) {
    Gate::before(fn () => true);
    $model::factory()->count(11)->create([$field => 'Other', 'sort_order' => 20]);
    $model::factory()->create([$field => 'Needle', 'sort_order' => 0]);
    Livewire::actingAs(User::factory()->create())->test($componentName)->assertSee('Needle')
        ->call('setPage', 2)->set('search', 'Needle')->assertSet('paginators.page', 1)->assertSee('Needle')->assertDontSee('Other')
        ->dispatch('reset-parent-page')->assertSet('paginators.page', 1);
})->with([
    ['cms.content.footer-group.table', FooterGroup::class, 'name'],
    ['cms.content.header-link.table', HeaderLink::class, 'label'],
]);

it('protects the navigation managers from unauthorized access', function (string $componentName) {
    Livewire::actingAs(User::factory()->create())->test($componentName)->assertForbidden();
})->with(['cms.content.footer-group.table', 'cms.content.footer-group.create-update', 'cms.content.header-link.table', 'cms.content.header-link.create-update']);

it('protects navigation actions from direct unauthorized mutations', function () {
    $actor = User::factory()->create();
    $group = FooterGroup::factory()->create();
    expect(fn () => app(DeleteFooterGroupAction::class)->handle($group, $actor))->toThrow(AuthorizationException::class);
    expect(fn () => app(SaveHeaderLinkAction::class)->handle(new HeaderLinkData('Test', 'left', 'url', null, 'https://example.test', true, 0), $actor))->toThrow(AuthorizationException::class);
    expect($group->fresh())->not->toBeNull()->and(HeaderLink::count())->toBe(0);
});

it('renders navigation tables with Flux modals on their CMS routes', function (string $path, string $componentName) {
    Gate::before(fn () => true);
    $this->actingAs(User::factory()->create())->get('/cms/content/'.$path)->assertSuccessful()
        ->assertSeeLivewire($componentName.'.table')->assertSeeLivewire($componentName.'.create-update');
})->with([['footer-groups', 'cms.content.footer-group'], ['header-links', 'cms.content.header-link']]);

it('renders banner cards with preview and Flux edit modal', function () {
    Gate::before(fn () => true);
    $banner = Banner::factory()->create(['title' => 'Card Banner']);
    $component = Livewire::actingAs(User::factory()->create())->test('cms.content.banner.table')->assertSee('Card Banner')
        ->assertSee('wire:key="banner-'.$banner->id.'"', false)->assertSee('md:grid-cols-2 xl:grid-cols-3', false)
        ->assertSeeLivewire('cms.content.banner.create-update')->assertSee('Tambah banner')->assertSee('Edit')->assertSee('Hapus');
    expect($component->html())->toContain('<article')->not->toContain('<table');
});
