<?php

namespace App\Data\Dashboard;

use Illuminate\Database\Eloquent\Collection;

final readonly class DashboardData
{
    /**
     * @param  array{revenue:float,orders:int,paid:int,unpaid:int,expired:int,pending:int,in_transit:int,delivered:int,shops:int,products:int,users:?int}  $stats
     * @param  list<array{date:string,revenue:float,paid:int,unpaid:int}>  $trend
     * @param  list<array{id:int,name:string,quantity:int,revenue:float}>  $topProducts
     */
    public function __construct(public array $stats, public array $trend, public array $topProducts, public Collection $lowStock, public Collection $recentTransactions) {}
}
