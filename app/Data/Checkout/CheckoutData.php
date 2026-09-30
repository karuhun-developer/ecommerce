<?php

namespace App\Data\Checkout;

final readonly class CheckoutData
{
    /** @param list<CheckoutShopData> $shops */
    public function __construct(public ?int $locationId, public ?GuestCheckoutData $guest, public array $shops) {}

    public static function fromArray(array $data): self
    {
        $shops = [];
        foreach ($data['shop_groups'] ?? [] as $id => $group) {
            $items = [];
            foreach ($group['items'] ?? [] as $flatId => $item) {
                $items[] = new CheckoutItemData((int) $flatId, (int) ($item['qty'] ?? 0));
            }
            $shops[] = new CheckoutShopData((int) $id, ShippingRateData::fromArray($group['selected_rate'] ?? []), $items);
        }

        return new self(isset($data['selected_location_id']) ? (int) $data['selected_location_id'] : null, isset($data['guest_data']) ? GuestCheckoutData::fromArray($data['guest_data']) : null, $shops);
    }
}
