<?php

namespace App\Livewire\Forms;

use App\Data\Checkout\GuestCheckoutData;
use Livewire\Form;

class CheckoutForm extends Form
{
    public ?int $selectedLocationId = null;

    public array $shopRates = [];

    public function validateCheckout(array $shopGroups): void
    {
        $this->validate(['selectedLocationId' => ['nullable', 'integer'], 'shopRates' => ['required', 'array'], 'shopRates.*.courier_code' => ['required', 'string'], 'shopRates.*.courier_service_code' => ['required', 'string'], 'shopRates.*.price' => ['required', 'integer', 'min:0'], 'shopRates.*.name' => ['required', 'string'], 'shopRates.*.etd' => ['nullable', 'string']]);
        validator(['shopGroups' => $shopGroups], ['shopGroups' => ['required', 'array'], 'shopGroups.*.shop_id' => ['required', 'integer', 'exists:shops,id'], 'shopGroups.*.items' => ['required', 'array'], 'shopGroups.*.items.*' => ['required', 'integer', 'min:1', 'max:100']])->validate();
    }

    public function guestData(?array $data): ?GuestCheckoutData
    {
        return $data === null ? null : GuestCheckoutData::fromArray($data);
    }
}
