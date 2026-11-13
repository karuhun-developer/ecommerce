<?php

namespace App\Services;

use App\Models\Setting\Setting;
use Illuminate\Support\Facades\Crypt;

class BiteshipSettingsService
{
    public const string KEY = 'biteship';

    public const array CREDENTIAL_FIELDS = ['api_key', 'header_secret'];

    /** @return array{api_key: ?string, header_key: ?string, header_secret: ?string, timeout: int, connect_timeout: int, retries: int} */
    public function configuration(bool $includeSecrets = true): array
    {
        $stored = Setting::query()->where('key', self::KEY)->first()?->data ?? [];
        $defaults = [
            'api_key' => config('services.biteship.key'),
            'header_key' => config('services.biteship.webhook.header_key'),
            'header_secret' => config('services.biteship.webhook.header_secret'),
            'timeout' => 15,
            'connect_timeout' => 5,
            'retries' => 3,
        ];
        $configuration = [...$defaults, ...$stored];
        foreach (self::CREDENTIAL_FIELDS as $field) {
            $configuration[$field] = $includeSecrets
                ? (filled($stored[$field] ?? null) ? Crypt::decryptString($stored[$field]) : $defaults[$field])
                : '';
        }

        return $configuration;
    }

    public function hasCredential(string $field): bool
    {
        return filled($this->configuration()[$field] ?? null);
    }
}
