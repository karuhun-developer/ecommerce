<?php

namespace App\Actions\Cms\Shipping;

use App\Data\Cms\ShippingRateSettingsData;
use App\Models\Setting\Setting;
use App\Models\User;
use App\Services\CourierSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateShippingRateSettingsAction
{
    public function handle(ShippingRateSettingsData $data, User $actor): Setting
    {
        Gate::forUser($actor)->authorize('update'.Setting::class);
        validator(['method' => $data->method], ['method' => ['required', Rule::in(CourierSettingsService::RATE_METHODS)]])->validate();

        return DB::transaction(function () use ($data): Setting {
            Setting::query()->firstOrCreate(
                ['key' => CourierSettingsService::KEY],
                ['data' => ['enabled_couriers' => CourierSettingsService::DEFAULT_COURIERS]],
            );

            $setting = Setting::query()->where('key', CourierSettingsService::KEY)->lockForUpdate()->firstOrFail();
            $setting->update(['data' => [...$setting->data, 'rate_method' => $data->method]]);

            return $setting;
        });
    }
}
