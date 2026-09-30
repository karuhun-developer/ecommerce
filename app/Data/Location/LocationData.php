<?php

namespace App\Data\Location;

final readonly class LocationData
{
    public function __construct(
        public string $location_name,
        public string $contact_name,
        public string $contact_phone,
        public string $address,
        public ?string $note,
        public string $postal_code,
        public float $latitude,
        public float $longitude,
        public string $biteship_area_id,
        public ?string $area_string,
        public string $type = 'destination',
        public ?int $shop_id = null,
    ) {
        if (! in_array($type, ['origin', 'destination'], true)) {
            throw new \InvalidArgumentException('Invalid location type.');
        }
    }

    /** @param array{location_name: string, contact_name: string, contact_phone: string, address: string, note?: ?string, postal_code: string|int, latitude: int|float|string, longitude: int|float|string, biteship_area_id: string, area_string?: ?string} $data */
    public static function fromArray(array $data, string $type = 'destination', ?int $shopId = null): self
    {
        return new self(
            location_name: $data['location_name'],
            contact_name: $data['contact_name'],
            contact_phone: $data['contact_phone'],
            address: $data['address'],
            note: $data['note'] ?? null,
            postal_code: (string) $data['postal_code'],
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            biteship_area_id: $data['biteship_area_id'],
            area_string: $data['area_string'] ?? null,
            type: $type,
            shop_id: $shopId,
        );
    }

    public function forShop(int $shopId): self
    {
        return new self($this->location_name, $this->contact_name, $this->contact_phone, $this->address, $this->note, $this->postal_code, $this->latitude, $this->longitude, $this->biteship_area_id, $this->area_string, 'origin', $shopId);
    }

    /** @return array{name: string, contact_name: string, contact_phone: string, address: string, note: ?string, postal_code: string, latitude: float, longitude: float, type: string} */
    public function providerPayload(): array
    {
        return [
            'name' => $this->location_name,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'note' => $this->note,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'type' => $this->type,
        ];
    }

    /** @return array{name: string, contact_name: string, contact_phone: string, address: string, note: ?string, postal_code: string, latitude: float, longitude: float, type: string, biteship_area_id: string, area_string: ?string} */
    public function attributes(): array
    {
        return [...$this->providerPayload(), 'biteship_area_id' => $this->biteship_area_id, 'area_string' => $this->area_string];
    }
}
