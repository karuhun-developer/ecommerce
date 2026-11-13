<?php

namespace App\Actions\Cms\Shipping;

use App\Data\Cms\BiteshipSettingsData;
use App\Models\Setting\Setting;
use App\Models\User;
use App\Services\BiteshipSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateBiteshipSettingsAction
{
    public function __construct(private readonly BiteshipSettingsService $settings) {}

    public function handle(BiteshipSettingsData $data, User $actor): Setting
    {
        Gate::forUser($actor)->authorize('update'.Setting::class);
        $configuration = $data->configuration;
        $configuration['header_key'] = ($configuration['header_key'] ?? '') ?: null;
        $current = $this->settings->configuration();
        $validated = validator(['configuration' => $configuration], [
            'configuration' => ['required', 'array:api_key,header_key,header_secret,timeout,connect_timeout,retries'],
            'configuration.api_key' => [Rule::requiredIf(! filled($current['api_key'])), 'nullable', 'string', 'max:2048', 'not_regex:/[\r\n]/'],
            'configuration.header_key' => [
                Rule::requiredIf(filled($configuration['header_secret'] ?? null) || filled($current['header_secret'])),
                'nullable', 'string', 'max:128', 'regex:/^[A-Za-z][A-Za-z0-9-]*$/D',
            ],
            'configuration.header_secret' => [
                Rule::requiredIf(filled($configuration['header_key']) && ! filled($current['header_secret'])),
                'nullable', 'string', 'max:2048', 'not_regex:/[\r\n]/',
            ],
            'configuration.timeout' => ['required', 'integer', 'between:1,120'],
            'configuration.connect_timeout' => ['required', 'integer', 'between:1,120', 'lte:configuration.timeout'],
            'configuration.retries' => ['required', 'integer', 'between:0,5'],
        ])->validate()['configuration'];

        $setting = DB::transaction(function () use ($validated): Setting {
            Setting::query()->firstOrCreate(['key' => BiteshipSettingsService::KEY], ['data' => []]);
            $setting = Setting::query()->where('key', BiteshipSettingsService::KEY)->lockForUpdate()->firstOrFail();
            $configuration = $validated;
            foreach (BiteshipSettingsService::CREDENTIAL_FIELDS as $field) {
                if (filled($configuration[$field] ?? null)) {
                    $configuration[$field] = Crypt::encryptString($configuration[$field]);
                } else {
                    unset($configuration[$field]);
                }
            }
            foreach (['timeout', 'connect_timeout', 'retries'] as $field) {
                $configuration[$field] = (int) $configuration[$field];
            }
            $setting->update(['data' => [...$setting->data, ...$configuration]]);

            return $setting;
        });
        Cache::forget('biteship:couriers');

        return $setting;
    }
}
