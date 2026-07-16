<?php

namespace App\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

trait WithFilterDateRange
{
    #[Url(as: 'start_date', except: '')]
    public $startDateFilter = '';

    #[Url(as: 'end_date', except: '')]
    public $endDateFilter = '';

    public function applyFilterTenantDateRange(
        Model|Builder $model,
        string $dateField = 'created_at',
    ) {
        return $model
            ->when(! empty($this->startDateFilter), function ($query) use ($dateField) {
                $query->where($dateField, '>=', $this->startDateFilter);
            })
            ->when(! empty($this->endDateFilter), function ($query) use ($dateField) {
                $query->where($dateField, '<=', $this->endDateFilter);
            });
    }
}
