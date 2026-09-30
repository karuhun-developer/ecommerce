<?php

namespace App\Actions\Cms\Dashboard;

use App\Data\Dashboard\DashboardData;
use App\Data\Dashboard\DashboardFilterData;
use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Models\Product\ProductFlat;
use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class GetDashboardAction
{
    public function handle(DashboardFilterData $filter, User $actor): DashboardData
    {
        abort_unless($actor->hasAnyRole(['superadmin', 'shopowner']), 403);
        $shops = Shop::query()->accessibleTo($actor);
        if ($filter->shopId !== null) {
            (clone $shops)->findOrFail($filter->shopId);
            $shops->whereKey($filter->shopId);
        }
        $query = OrderShop::query()->accessibleTo($actor)->whereIn('shop_id', (clone $shops)->select('id'))
            ->whereHas('order', fn (Builder $q) => $q->whereBetween('created_at', [$filter->start, $filter->end]));
        $paid = (clone $query)->whereHas('order', fn (Builder $q) => $q->where('status', true));
        $unpaid = (clone $query)->whereHas('order', fn (Builder $q) => $q->where('status', false));
        $expired = (clone $unpaid)->whereHas('order.latestPayment', fn (Builder $q) => $q->whereNotNull('expired_at')->where('expired_at', '<=', now()))->count();
        $products = ProductFlat::query()->whereIn('shop_id', (clone $shops)->select('id'));
        $stats = [
            'revenue' => (float) (clone $paid)->sum('total_checkout'), 'orders' => (clone $query)->count(),
            'paid' => (clone $paid)->count(), 'unpaid' => (clone $unpaid)->count() - $expired, 'expired' => $expired,
            'pending' => (clone $paid)->where('shipping_status', false)->where(fn (Builder $q) => $q->whereNull('waybill_number')->orWhere('waybill_number', ''))->count(),
            'in_transit' => (clone $paid)->where('shipping_status', false)->whereNotNull('waybill_number')->where('waybill_number', '!=', '')->count(),
            'delivered' => (clone $paid)->where('shipping_status', true)->count(),
            'shops' => (clone $shops)->count(), 'products' => (clone $products)->count(),
            'users' => $actor->hasRole('superadmin') ? User::query()->whereHas('roles', fn (Builder $q) => $q->where('name', 'user'))->count() : null,
        ];
        $daily = (clone $query)->join('orders', 'orders.id', '=', 'order_shops.order_id')
            ->selectRaw('DATE(orders.created_at) as day, SUM(CASE WHEN orders.status = 1 THEN order_shops.total_checkout ELSE 0 END) as revenue, SUM(CASE WHEN orders.status = 1 THEN 1 ELSE 0 END) as paid, SUM(CASE WHEN orders.status = 0 THEN 1 ELSE 0 END) as unpaid')
            ->groupByRaw('DATE(orders.created_at)')->get()->keyBy('day');
        $trend = [];
        for ($day = $filter->start; $day->lte($filter->end); $day = $day->addDay()) {
            $row = $daily->get($day->toDateString());
            $trend[] = ['date' => $day->toDateString(), 'revenue' => (float) ($row?->revenue ?? 0), 'paid' => (int) ($row?->paid ?? 0), 'unpaid' => (int) ($row?->unpaid ?? 0)];
        }
        $top = OrderShopItem::query()->whereIn('order_shop_id', (clone $paid)->select('order_shops.id'))
            ->select('product_flat_id')->selectRaw('SUM(quantity) as sold_quantity, SUM(total) as sold_revenue')
            ->groupBy('product_flat_id')->orderByDesc('sold_quantity')->orderBy('product_flat_id')->limit(5)->with('productFlat')->get();
        $topProducts = $top->map(fn (OrderShopItem $item): array => ['id' => (int) $item->product_flat_id, 'name' => $item->productFlat?->name ?? 'Produk dihapus', 'quantity' => (int) $item->sold_quantity, 'revenue' => (float) $item->sold_revenue])->all();
        $lowStock = (clone $products)->where('is_unlimited_stock', false)->where('stock', '<=', 5)->orderBy('stock')->orderBy('id')->limit(10)->with('shop')->get();
        $recent = (clone $query)->with(['order.user', 'order.latestPayment', 'shop', 'latestShipment'])->orderByDesc(Order::query()->select('orders.created_at')->whereColumn('orders.id', 'order_shops.order_id')->limit(1))->orderByDesc('id')->limit(10)->get();

        return new DashboardData($stats, $trend, $topProducts, $lowStock, $recent);
    }
}
