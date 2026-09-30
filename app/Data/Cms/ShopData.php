<?php

namespace App\Data\Cms;

use App\Data\Location\LocationData;

final readonly class ShopData
{
    public function __construct(public string $name, public ?string $description, public LocationData $location) {}

    /** @param array{name: string, description?: ?string, location_name: string, contact_name: string, contact_phone: string, address: string, note?: ?string, postal_code: string|int, latitude: int|float|string, longitude: int|float|string, biteship_area_id: string, area_string?: ?string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['name'], $data['description'] ?? null, LocationData::fromArray($data, 'origin'));
    }
}
