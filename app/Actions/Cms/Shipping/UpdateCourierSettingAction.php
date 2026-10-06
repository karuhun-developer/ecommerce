<?php

namespace App\Actions\Cms\Shipping;

use App\Data\Cms\CourierSettingData;
use App\Models\Setting\Setting;
use App\Models\User;
use App\Services\CourierSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateCourierSettingAction
{
    public function __construct(private CourierSettingsService $courierSettings) {}

    public function handle(CourierSettingData $data, User $actor): Setting
    {
        Gate::forUser($actor)->authorize('update'.Setting::class);

        if (! in_array($data->code, array_column($this->courierSettings->couriers(), 'code'), true)) {
            throw ValidationException::withMessages(['code' => 'Kurir tidak tersedia di Biteship.']);
        }

        return DB::transaction(function () use ($data): Setting {
            Setting::query()->firstOrCreate(
                ['key' => CourierSettingsService::KEY],
                ['data' => ['enabled_couriers' => CourierSettingsService::DEFAULT_COURIERS]],
            );

            $setting = Setting::query()->where('key', CourierSettingsService::KEY)->lockForUpdate()->firstOrFail();
            $enabled = collect($setting->data['enabled_couriers'] ?? CourierSettingsService::DEFAULT_COURIERS);
            $enabled = $data->enabled
                ? $enabled->push($data->code)->unique()
                : $enabled->reject(fn (string $code): bool => $code === $data->code);

            $setting->update(['data' => [...$setting->data, 'enabled_couriers' => $enabled->sort()->values()->all()]]);

            return $setting;
        });
    }
}
