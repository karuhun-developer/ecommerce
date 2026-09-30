<?php

namespace App\Data\Callbacks;

final readonly class BiteshipCallbackData
{
    public function __construct(
        public ?string $event,
        public ?string $status,
        public ?string $waybillId,
        public ?string $trackingId,
        public CourierData $courier,
        public array $providerPayload,
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            self::nullableString($payload['event'] ?? null),
            self::nullableString($payload['status'] ?? null),
            self::nullableString($payload['courier_waybill_id'] ?? null),
            self::nullableString($payload['courier_tracking_id'] ?? null),
            CourierData::fromArray($payload),
            $payload,
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
