<?php

use App\Models\Order\OrderShop;
use App\Models\User;
use App\Traits\WithFilterDateRange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use WithFilterDateRange;

    public function mount()
    {
        $this->startDateFilter = Carbon::now()->subDays(30)->toDateString();
        $this->endDateFilter = Carbon::now()->toDateString();
    }

    public function updated($property)
    {
        if (in_array($property, ['startDateFilter', 'endDateFilter'])) {
            $this->dispatch('update-chart', data: $this->chartData);
        }
    }

    #[Computed]
    public function isSuperadmin()
    {
        return auth()->user()->hasRole('superadmin');
    }

    #[Computed]
    public function userStats()
    {
        if (! $this->isSuperadmin) {
            return null;
        }

        $totalUsers = User::whereHas('roles', function ($q) {
            $q->where('name', 'user');
        })->count();

        $totalShopOwners = 0;
        if (! isSingleShop()) {
            $totalShopOwners = User::whereHas('roles', function ($q) {
                $q->where('name', 'shopowner');
            })->count();
        }

        return [
            'users' => $totalUsers,
            'shopowners' => $totalShopOwners,
        ];
    }

    #[Computed]
    public function orderStats()
    {
        $query = OrderShop::query();

        if (! $this->isSuperadmin) {
            $query->whereHas('shop', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        $paidQuery = clone $query;
        $paidQuery->whereHas('order', function ($q) {
            $q->where('status', true);
        })->whereBetween('created_at', [
            Carbon::parse($this->startDateFilter),
            Carbon::parse($this->endDateFilter),
        ]);

        $totalRevenue = $paidQuery->sum('total');
        $totalPaidSales = $paidQuery->count();

        $unpaidQuery = clone $query;
        $unpaidQuery->whereHas('order', function ($q) {
            $q->where('status', false)
                ->whereHas('latestPayment', function ($sq) {
                    $sq->whereNull('expired_at')->orWhere('expired_at', '>', now());
                });
        })->whereBetween('created_at', [
            Carbon::parse($this->startDateFilter),
            Carbon::parse($this->endDateFilter),
        ]);

        $totalUnpaidSales = $unpaidQuery->count();

        return [
            'revenue' => $totalRevenue,
            'paid_sales' => $totalPaidSales,
            'unpaid_sales' => $totalUnpaidSales,
        ];
    }

    #[Computed]
    public function recentTransactions()
    {
        $query = OrderShop::with(['order', 'shop', 'order.user'])
            ->latest()
            ->take(10);

        if (! $this->isSuperadmin) {
            $query->whereHas('shop', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query->get();
    }

    #[Computed]
    public function chartData()
    {
        $start = Carbon::parse($this->startDateFilter);
        $end = Carbon::parse($this->endDateFilter);

        $query = OrderShop::query();
        if (! $this->isSuperadmin) {
            $query->whereHas('shop', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        $paidOrders = clone $query;
        $paidOrders = $paidOrders->whereHas('order', function ($q) {
            $q->where('status', true);
        })->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->pluck('total', 'date')->toArray();

        $unpaidOrders = clone $query;
        $unpaidOrders = $unpaidOrders->whereHas('order', function ($q) {
            $q->where('status', false)
                ->whereHas('latestPayment', function ($sq) {
                    $sq->whereNull('expired_at')->orWhere('expired_at', '>', now());
                });
        })->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->pluck('total', 'date')->toArray();

        $dates = [];
        $paidSeries = [];
        $unpaidSeries = [];

        $current = $start->copy();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d H:i:s');
            $dates[] = $current->format('d M');
            $paidSeries[] = $paidOrders[$dateStr] ?? 0;
            $unpaidSeries[] = $unpaidOrders[$dateStr] ?? 0;
            $current->addDay();
        }

        return [
            'categories' => $dates,
            'paid' => $paidSeries,
            'unpaid' => $unpaidSeries,
        ];
    }
};
