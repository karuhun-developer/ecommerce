<?php

namespace App\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;
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
        string $paginateFunction = 'fastPaginate',
    ) {
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

        return $model->{$paginateFunction}($paginate);
    }
}
