<?php

namespace App\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

trait WithGetFilterDataApi
{
    public function getDataWithFilter(
        Model|Builder $model,
        array $searchBy = [],
        string $searchBySpecific = '',
        string $orderBy = 'id',
        string $order = 'asc',
        int $paginate = 10,
        string $s = '',
    ): LengthAwarePaginator {
        $allowedOrderColumns = array_values(array_filter(
            $searchBy,
            fn (mixed $field): bool => is_string($field) && $field !== '',
        ));
        $searchBySpecific = in_array($searchBySpecific, $allowedOrderColumns, true)
            ? $searchBySpecific
            : '';
        $orderBy = in_array($orderBy, $allowedOrderColumns, true)
            ? $orderBy
            : ($allowedOrderColumns[0] ?? 'id');
        $order = in_array(strtolower($order), ['asc', 'desc'], true) ? strtolower($order) : 'asc';
        $paginate = in_array($paginate, [10, 25, 50, 100], true) ? $paginate : 10;

        $model = $model->where(function ($query) use ($s, $searchBy, $searchBySpecific) {
            if ($searchBySpecific) {
                $query->where($searchBySpecific, 'like', "%$s%");
            } else {
                foreach ($searchBy as $value) {
                    $query->orWhere($value, 'like', "%$s%");
                }
            }
        });

        $model = $model->orderBy($orderBy, $order);

        return $model->fastPaginate($paginate);
    }
}
