<?php

namespace App\Data\Checkout;

final readonly class GuestCheckoutData
{
    public function __construct(public string $contactName, public string $contactPhone, public string $email, public string $address, public ?string $note, public string $postalCode, public ?string $areaString, public ?string $areaId, public float $latitude, public float $longitude) {}

    public static function fromArray(array $input, bool $requiresArea = false): self
    {
        $data = validator($input, self::rules($requiresArea))->validate();

        return new self($data['contact_name'], $data['contact_phone'], $data['email'], $data['address'], $data['note'] ?? null, $data['postal_code'], $data['area_string'] ?? null, filled($data['biteship_area_id'] ?? null) ? $data['biteship_area_id'] : null, (float) $data['latitude'], (float) $data['longitude']);
    }

    public static function rules(bool $requiresArea = false): array
    {
        return ['contact_name' => ['required', 'string', 'max:255'], 'contact_phone' => ['required', 'string', 'max:30'], 'email' => ['required', 'email', 'max:255'], 'address' => ['required', 'string', 'max:1000'], 'note' => ['nullable', 'string', 'max:1000'], 'postal_code' => ['required', 'string', 'max:20'], 'area_string' => ['nullable', 'string', 'max:255'], 'biteship_area_id' => [$requiresArea ? 'required' : 'nullable', 'string', 'max:255'], 'latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180']];
    }

    public function attributes(): array
    {
        return ['contact_name' => $this->contactName, 'contact_phone' => $this->contactPhone, 'contact_email' => $this->email, 'address' => $this->address, 'note' => $this->note, 'postal_code' => $this->postalCode, 'area_string' => $this->areaString, 'biteship_area_id' => $this->areaId, 'latitude' => $this->latitude, 'longitude' => $this->longitude];
    }
}
