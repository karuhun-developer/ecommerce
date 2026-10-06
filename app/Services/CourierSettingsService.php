<?php

namespace App\Services;

use App\Models\Setting\Setting;
use RuntimeException;

class CourierSettingsService
{
    public const KEY = 'shipping_couriers';

    public const DEFAULT_COURIERS = ['jne', 'tiki', 'lion', 'ninja', 'jnt', 'sicepat'];

    public const COORDINATE_COURIERS = ['gojek', 'grab', 'paxel', 'lalamove', 'borzo'];

    public const RATE_METHODS = ['coordinates', 'area_id'];

    public function __construct(private BiteshipService $biteshipService) {}

    /** @return list<string> */
    public function enabledCodes(): array
    {
        $setting = Setting::query()->where('key', self::KEY)->first();

        return $setting?->data['enabled_couriers'] ?? self::DEFAULT_COURIERS;
    }

    public function rateMethod(): string
    {
        return Setting::query()->where('key', self::KEY)->first()?->data['rate_method'] ?? 'coordinates';
    }

    public function usesAreaIds(): bool
    {
        return $this->rateMethod() === 'area_id';
    }

    /** @return list<array{code: string, name: string, services: string}> */
    public function couriers(): array
    {
        $couriers = collect($this->biteshipService->couriers())
            ->filter(fn (array $courier): bool => filled($courier['courier_code'] ?? null))
            ->groupBy('courier_code')
            ->map(fn ($services, string $code): array => [
                'code' => $code,
                'name' => $services->first()['courier_name'] ?? $code,
                'services' => $services->pluck('courier_service_name')->filter()->unique()->implode(', '),
            ])
            ->sortBy('name')
            ->values()
            ->all();

        if ($couriers === []) {
            throw new RuntimeException('Daftar kurir Biteship belum tersedia.');
        }

        return $couriers;
    }
}
