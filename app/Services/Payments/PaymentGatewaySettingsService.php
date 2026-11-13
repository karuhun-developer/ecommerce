<?php

namespace App\Services\Payments;

use App\Enums\PaymentGatewayDriver;
use App\Models\Setting\Setting;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewaySettingsService
{
    public const string KEY = 'payment_gateways';

    public const array CREDENTIAL_FIELDS = [
        'midtrans' => ['server_key', 'client_key'],
        'paywuz' => ['api_key'],
        'karuhunqris' => ['api_key'],
    ];

    public function defaultDriver(): mixed
    {
        return $this->storedSettings()['default'] ?? config('payment.default');
    }

    public function hasKaruhunQrisApiKey(): bool
    {
        return $this->hasCredential(PaymentGatewayDriver::KaruhunQris, 'api_key');
    }

    public function hasCredential(PaymentGatewayDriver $driver, string $field): bool
    {
        return filled($this->storedSettings()[$driver->value][$field] ?? $this->defaultConfiguration($driver)[$field] ?? null);
    }

    /** @return array<string, mixed> */
    public function configuration(PaymentGatewayDriver $driver, bool $includeSecrets = true): array
    {
        $stored = $this->storedSettings()[$driver->value] ?? [];
        $defaults = $this->defaultConfiguration($driver);
        $configuration = [...$defaults, ...$stored];

        foreach (self::CREDENTIAL_FIELDS[$driver->value] as $field) {
            $configuration[$field] = $includeSecrets
                ? (filled($stored[$field] ?? null) ? Crypt::decryptString($stored[$field]) : ($defaults[$field] ?? null))
                : '';
        }

        return $configuration;
    }

    /** @return array<string, mixed> */
    public function midtransConfiguration(): array
    {
        return $this->configuration(PaymentGatewayDriver::Midtrans);
    }

    /** @return array<string, mixed> */
    public function paywuzConfiguration(): array
    {
        return $this->configuration(PaymentGatewayDriver::Paywuz);
    }

    /**
     * @return array{base_url: string, api_key: ?string, timeout: int, connect_timeout: int}
     */
    public function karuhunQrisConfiguration(): array
    {
        return $this->configuration(PaymentGatewayDriver::KaruhunQris);
    }

    /** @return array<string, mixed> */
    private function defaultConfiguration(PaymentGatewayDriver $driver): array
    {
        return match ($driver) {
            PaymentGatewayDriver::Midtrans => config('midtrans'),
            PaymentGatewayDriver::Paywuz => config('payment.drivers.paywuz'),
            PaymentGatewayDriver::KaruhunQris => config('dynamic-qris'),
        };
    }

    /** @return array<string, mixed> */
    private function storedSettings(): array
    {
        return Setting::query()->where('key', self::KEY)->first()?->data ?? [];
    }
}
