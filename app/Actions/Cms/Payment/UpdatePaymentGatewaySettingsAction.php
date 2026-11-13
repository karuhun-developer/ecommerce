<?php

namespace App\Actions\Cms\Payment;

use App\Data\Cms\PaymentGatewaySettingsData;
use App\Enums\PaymentGatewayDriver;
use App\Models\Setting\Setting;
use App\Models\User;
use App\Services\Payments\PaymentGatewaySettingsService;
use Closure;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdatePaymentGatewaySettingsAction
{
    public function __construct(private readonly PaymentGatewaySettingsService $settings) {}

    public function handle(PaymentGatewaySettingsData $data, User $actor): Setting
    {
        Gate::forUser($actor)->authorize('update'.Setting::class);

        $driver = PaymentGatewayDriver::tryFrom($data->driver);
        $rules = match ($driver) {
            PaymentGatewayDriver::Midtrans => [
                'merchant_id' => ['nullable', 'string', 'max:255'],
                'server_key' => $this->credentialRules($driver, 'server_key', true),
                'client_key' => $this->credentialRules($driver, 'client_key'),
                'is_production' => ['required', 'boolean'],
                'retries' => ['required', 'integer', 'between:0,5'],
                'timezone' => ['required', 'timezone'],
            ],
            PaymentGatewayDriver::Paywuz => [
                'api_key' => $this->credentialRules($driver, 'api_key', true),
                'base_url' => $this->apiUrlRules(),
                'expiry_minutes' => ['nullable', 'integer', 'between:1,1440'],
                'redirect_url' => ['nullable', 'url:https', 'max:2048'],
                'fee_by_merchant' => ['nullable', 'boolean'],
                'payment_reminder_enabled' => ['nullable', 'boolean'],
            ],
            PaymentGatewayDriver::KaruhunQris => [
                'api_key' => $this->credentialRules($driver, 'api_key', true),
                'base_url' => $this->apiUrlRules(),
            ],
            default => [],
        };
        $rules += [
            'timeout' => ['required', 'integer', 'between:1,120'],
            'connect_timeout' => ['required', 'integer', 'between:1,120', 'lte:configuration.timeout'],
        ];
        $validationRules = [
            'driver' => ['required', Rule::enum(PaymentGatewayDriver::class)],
            'configuration' => ['required', 'array:'.implode(',', array_keys($rules))],
        ];
        foreach ($rules as $field => $fieldRules) {
            $validationRules['configuration.'.$field] = $fieldRules;
        }
        $configuration = $data->configuration;
        foreach (['expiry_minutes', 'redirect_url', 'fee_by_merchant', 'payment_reminder_enabled'] as $field) {
            if (array_key_exists($field, $configuration) && $configuration[$field] === '') {
                $configuration[$field] = null;
            }
        }
        $validated = validator(['driver' => $data->driver, 'configuration' => $configuration], $validationRules)->validate();

        return DB::transaction(function () use ($validated): Setting {
            Setting::query()->firstOrCreate(['key' => PaymentGatewaySettingsService::KEY], ['data' => []]);
            $setting = Setting::query()->where('key', PaymentGatewaySettingsService::KEY)->lockForUpdate()->firstOrFail();
            $driver = $validated['driver'];
            $configuration = $validated['configuration'];

            foreach (PaymentGatewaySettingsService::CREDENTIAL_FIELDS[$driver] as $field) {
                if (filled($configuration[$field] ?? null)) {
                    $configuration[$field] = Crypt::encryptString($configuration[$field]);
                } else {
                    unset($configuration[$field]);
                }
            }
            if (isset($configuration['base_url'])) {
                $configuration['base_url'] = rtrim($configuration['base_url'], '/');
            }
            $configuration['timeout'] = (int) $configuration['timeout'];
            $configuration['connect_timeout'] = (int) $configuration['connect_timeout'];
            foreach (['is_production', 'fee_by_merchant', 'payment_reminder_enabled'] as $field) {
                if (isset($configuration[$field])) {
                    $configuration[$field] = (bool) $configuration[$field];
                }
            }
            foreach (['retries', 'expiry_minutes'] as $field) {
                if (isset($configuration[$field])) {
                    $configuration[$field] = (int) $configuration[$field];
                }
            }

            $setting->update(['data' => [
                ...$setting->data,
                'default' => $driver,
                $driver => [...($setting->data[$driver] ?? []), ...$configuration],
            ]]);

            return $setting;
        });
    }

    /** @return list<mixed> */
    private function credentialRules(PaymentGatewayDriver $driver, string $field, bool $required = false): array
    {
        return [Rule::requiredIf($required && ! $this->settings->hasCredential($driver, $field)), 'nullable', 'string', 'max:2048', 'not_regex:/[\r\n]/'];
    }

    /** @return list<mixed> */
    private function apiUrlRules(): array
    {
        return [
            'bail', 'required', 'url:https', 'max:2048',
            function (string $attribute, mixed $value, Closure $fail): void {
                $url = parse_url($value);
                if ($url === false || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
                    $fail('URL API harus tanpa kredensial, query, atau fragment.');
                }
            },
        ];
    }
}
