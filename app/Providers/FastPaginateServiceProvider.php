<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\ServiceProvider;

class FastPaginateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $getInnerSelectColumns = static function (Builder $builder): array {
            $base = $builder->getQuery();
            $model = $builder->newModelInstance();
            $key = $model->getKeyName();
            $table = $model->getTable();

            $orders = collect($base->orders)
                ->pluck('column')
                ->filter()
                ->map(function ($column) use ($base) {
                    $column = $column instanceof Expression ? $column->getValue($base->grammar) : $column;

                    return [$column, $base->grammar->wrap($column)];
                })
                ->flatten(1);

            return collect($base->columns)
                ->filter(function ($column) use ($orders, $base) {
                    $column = $column instanceof Expression ? $column->getValue($base->grammar) : $base->grammar->wrap($column);
                    foreach ($orders as $order) {
                        if (str_contains($column, "as $order")) {
                            return true;
                        }
                    }

                    return false;
                })
                ->prepend("$table.$key")
                ->unique()
                ->values()
                ->toArray();
        };

        Builder::macro('fastPaginate', function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) use ($getInnerSelectColumns) {
            /** @var Builder $this */
            $base = $this->getQuery();

            if (filled($base->havings) || filled($base->groups) || filled($base->unions)) {
                return $this->paginate($perPage, $columns, $pageName, $page);
            }

            $model = $this->newModelInstance();
            $key = $model->getKeyName();
            $table = $model->getTable();

            if ($perPage === -1) {
                return $this->paginate($perPage, $columns, $pageName, $page);
            }

            $innerSelectColumns = $getInnerSelectColumns($this);

            $paginator = $this->clone()
                ->select($innerSelectColumns)
                ->setEagerLoads([])
                ->paginate($perPage, ['*'], $pageName, $page);

            $ids = $paginator->getCollection()->map->getRawOriginal($key)->toArray();

            if (count($ids) <= 0) {
                return $paginator;
            }

            if (in_array($model->getKeyType(), ['int', 'integer'])) {
                $this->query->whereIntegerInRaw("$table.$key", $ids);
            } else {
                $this->query->whereIn("$table.$key", $ids);
            }

            $items = $this->simplePaginate($perPage, $columns, $pageName, 1)->items();

            return $this->paginator(
                $this->model->newCollection($items),
                $paginator->total(),
                $paginator->perPage(),
                $paginator->currentPage(),
                $paginator->getOptions()
            );
        });
    }
}
