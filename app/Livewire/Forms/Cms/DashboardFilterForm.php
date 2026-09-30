<?php

namespace App\Livewire\Forms\Cms;

use App\Data\Dashboard\DashboardFilterData;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class DashboardFilterForm extends Form
{
    public string $startDate = '';

    public string $endDate = '';

    public ?int $shopId = null;

    public function defaults(): void
    {
        $this->startDate = now()->subDays(29)->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function data(): DashboardFilterData
    {
        $data = $this->validate(['startDate' => ['required', 'date_format:Y-m-d'], 'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate'], 'shopId' => ['nullable', 'integer', 'min:1']]);
        $start = CarbonImmutable::parse($data['startDate'])->startOfDay();
        $end = CarbonImmutable::parse($data['endDate'])->endOfDay();
        if ($start->diffInDays($end) > 366) {
            throw ValidationException::withMessages(['form.endDate' => 'Pilih periode maksimal 366 hari.']);
        }

        return new DashboardFilterData($start, $end, $data['shopId']);
    }
}
