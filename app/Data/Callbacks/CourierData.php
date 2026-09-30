<?php

namespace App\Data\Callbacks;

final readonly class CourierData
{
    public function __construct(
        public ?string $name,
        public ?string $company,
        public ?string $type,
        public ?string $driverName,
        public ?string $driverPhone,
        public ?string $driverPhotoUrl,
        public ?string $driverPlateNumber,
        public ?string $link,
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            self::nullableString($payload['courier_name'] ?? null),
            self::nullableString($payload['courier_company'] ?? null),
            self::nullableString($payload['courier_type'] ?? null),
            self::nullableString($payload['courier_driver_name'] ?? null),
            self::nullableString($payload['courier_driver_phone'] ?? null),
            self::nullableString($payload['courier_driver_photo_url'] ?? null),
            self::nullableString($payload['courier_driver_plate_number'] ?? null),
            self::nullableString($payload['courier_link'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
