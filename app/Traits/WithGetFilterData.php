<?php

namespace App\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

trait WithGetFilterData
{
    public function getDataWithFilter(
        Model|Builder $model,
        array $searchBy = [
            [
                'name' => '',
                'field' => '',
                'no_search' => true,
                'hide' => false,
            ],
        ],
        string $orderBy = 'id',
        string $order = 'desc',
        int $paginate = 10,
        string $s = '',
    ): LengthAwarePaginator {
        $allowedOrderColumns = collect($searchBy)
            ->pluck('field')
            ->filter(fn (mixed $field): bool => is_string($field) && $field !== '')
            ->values()
            ->all();
        $orderBy = in_array($orderBy, $allowedOrderColumns, true)
            ? $orderBy
            : ($allowedOrderColumns[0] ?? 'id');
        $order = in_array(strtolower($order), ['asc', 'desc'], true) ? strtolower($order) : 'desc';
        $paginate = in_array($paginate, [10, 25, 50, 100], true) ? $paginate : 10;

        $model = $model->when(! empty($s) && ! empty($searchBy), function ($query) use ($s, $searchBy) {
            $query->where(function ($query) use ($s, $searchBy) {
                foreach ($searchBy as $value) {
                    if (isset($value['field']) && (! isset($value['no_search']) || $value['no_search'] !== true)) {
                        $field = $value['field'];
                        $query->orWhere($field, 'like', "%{$s}%");
                    }
                }
            });
        });

        $model = $model->orderBy($orderBy, $order);

        return $model->fastPaginate($paginate);
    }
}
