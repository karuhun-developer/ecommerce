<?php

use App\Actions\Cms\Dashboard\GetDashboardAction;
use App\Data\Dashboard\DashboardFilterData;
use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Models\Payment\Payment;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\Spatie\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function dashboardActor(string $role): User
{
    $actor = User::factory()->create();
    $actor->assignRole(Role::findOrCreate($role, 'api'));

    return $actor;
}
function dashboardFilter(?int $shopId = null): DashboardFilterData
{
    return new DashboardFilterData(CarbonImmutable::parse('2026-09-01')->startOfDay(), CarbonImmutable::parse('2026-09-03')->endOfDay(), $shopId);
}

it('counts scoped shop orders and product revenue using the parent order date', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-30 12:00'));
    $admin = dashboardActor('superadmin');
    $owner = dashboardActor('shopowner');
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $foreign = Shop::factory()->create();
    $order = Order::factory()->create(['status' => true, 'created_at' => '2026-09-02 12:00']);
    $pending = OrderShop::factory()->create(['shop_id' => $shop->id, 'order_id' => $order->id, 'total_checkout' => 120000, 'total' => 150000, 'created_at' => '2026-09-20']);
    OrderShop::factory()->create(['shop_id' => $foreign->id, 'order_id' => $order->id, 'total_checkout' => 80000, 'shipping_status' => true]);
    $transit = Order::factory()->create(['status' => true, 'created_at' => '2026-09-03']);
    OrderShop::factory()->create(['shop_id' => $shop->id, 'order_id' => $transit->id, 'total_checkout' => 40000, 'waybill_number' => 'WAYBILL']);
    $unpaid = Order::factory()->create(['created_at' => '2026-09-01']);
    OrderShop::factory()->create(['shop_id' => $shop->id, 'order_id' => $unpaid->id]);
    $expired = Order::factory()->create(['created_at' => '2026-09-01']);
    OrderShop::factory()->create(['shop_id' => $shop->id, 'order_id' => $expired->id]);
    Payment::factory()->create(['payable_id' => $expired->id, 'expired_at' => now()->subDay()]);
    $outside = Order::factory()->create(['status' => true, 'created_at' => '2026-08-31 23:59:59']);
    OrderShop::factory()->create(['shop_id' => $shop->id, 'order_id' => $outside->id, 'total_checkout' => 999999]);
    $data = app(GetDashboardAction::class)->handle(dashboardFilter(), $owner);
    expect($data->stats)->toMatchArray(['revenue' => 160000.0, 'orders' => 4, 'paid' => 2, 'unpaid' => 1, 'expired' => 1, 'pending' => 1, 'in_transit' => 1, 'delivered' => 0, 'shops' => 1, 'users' => null]);
    expect($data->trend)->toHaveCount(3)->and($data->trend[0]['revenue'])->toBe(0.0)->and($data->trend[1]['revenue'])->toBe(120000.0);
    expect($data->recentTransactions->pluck('shop_id')->unique()->all())->toBe([$shop->id]);
    $all = app(GetDashboardAction::class)->handle(dashboardFilter(), $admin);
    expect($all->stats['revenue'])->toBe(240000.0)->and($all->stats['delivered'])->toBe(1);
    expect(app(GetDashboardAction::class)->handle(dashboardFilter($foreign->id), $admin)->stats['revenue'])->toBe(80000.0);
});

it('returns paid top products and current limited low stock only within scope', function () {
    $owner = dashboardActor('shopowner');
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $flat = ProductFlat::factory()->create(['shop_id' => $shop->id, 'name' => 'Popular', 'stock' => 3, 'is_unlimited_stock' => false]);
    ProductFlat::factory()->create(['shop_id' => $shop->id, 'stock' => 0, 'is_unlimited_stock' => true]);
    ProductFlat::factory()->create(['stock' => 0, 'is_unlimited_stock' => false]);
    $order = Order::factory()->create(['created_at' => '2026-09-02', 'status' => true]);
    $orderShop = OrderShop::factory()->create(['order_id' => $order->id, 'shop_id' => $shop->id]);
    OrderShopItem::factory()->create(['order_shop_id' => $orderShop->id, 'product_flat_id' => $flat->id, 'quantity' => 4, 'total' => 400000]);
    $unpaid = OrderShop::factory()->create(['shop_id' => $shop->id, 'order_id' => Order::factory()->create(['created_at' => '2026-09-02'])->id]);
    OrderShopItem::factory()->create(['order_shop_id' => $unpaid->id, 'quantity' => 99]);
    $data = app(GetDashboardAction::class)->handle(dashboardFilter(), $owner);
    expect($data->topProducts)->toHaveCount(1)->and($data->topProducts[0])->toMatchArray(['id' => $flat->id, 'name' => 'Popular', 'quantity' => 4, 'revenue' => 400000.0]);
    expect($data->lowStock->modelKeys())->toBe([$flat->id]);
});

it('rejects unauthorized users and foreign shop filters', function () {
    $owner = dashboardActor('shopowner');
    $foreign = Shop::factory()->create();
    expect(fn () => app(GetDashboardAction::class)->handle(dashboardFilter($foreign->id), $owner))->toThrow(ModelNotFoundException::class);
    expect(fn () => app(GetDashboardAction::class)->handle(dashboardFilter(), User::factory()->create()))->toThrow(HttpException::class);
});

it('renders an empty 30 day dashboard and validates date filters', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-30 12:00'));
    $component = Livewire::actingAs(dashboardActor('superadmin'))->test('cms.dashboard.analytics')
        ->assertSet('form.startDate', '2026-09-01')->assertSet('form.endDate', '2026-09-30')
        ->assertSee('Tren pendapatan')->assertSee('Tidak ada stok rendah.')->assertDontSee('cdn.jsdelivr.net/npm/apexcharts', false);
    $component->set('form.endDate', '2026-08-31')->call('apply')->assertHasErrors(['form.endDate']);
    $component->set('form.endDate', '2027-10-01')->call('apply')->assertHasErrors(['form.endDate']);
    $component->set('form.startDate', 'invalid')->call('apply')->assertHasErrors(['form.startDate']);
    $component->set('form.startDate', '2026-09-03')->set('form.endDate', '2026-09-03')->call('apply')->assertHasNoErrors()->assertSet('appliedStart', '2026-09-03');
});
