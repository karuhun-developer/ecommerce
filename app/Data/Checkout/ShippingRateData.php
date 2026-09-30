<?php

namespace App\Data\Checkout;

final readonly class ShippingRateData
{
    public function __construct(public string $courierCode, public string $serviceCode, public float $price, public string $name, public ?string $etd) {}

    public static function fromArray(array $rate): self
    {
        $data = validator($rate, ['courier_code' => ['required', 'string'], 'courier_service_code' => ['required', 'string'], 'price' => ['required', 'numeric', 'min:0'], 'name' => ['required', 'string'], 'etd' => ['nullable', 'string']])->validate();

        return new self($data['courier_code'], $data['courier_service_code'], (float) $data['price'], $data['name'], $data['etd'] ?? null);
    }

    public function attributes(): array
    {
        return ['courier_code' => $this->courierCode, 'courier_service_code' => $this->serviceCode, 'company' => $this->courierCode, 'type' => $this->serviceCode, 'price' => $this->price, 'name' => $this->name, 'etd' => $this->etd];
    }
}
