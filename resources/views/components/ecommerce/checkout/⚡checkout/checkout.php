<?php

use Sqids\Sqids;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Selected product flat ids to checkout
    public $selectedIds;

    /**
     * Resolved per-shop groups populated by resolveShopGroups().
     */
    public $shopGroups = [];

    /**
     * Per-shop selected rate: [ shopId => [ courier_code, price, name, etd ] ]
     *
     * @var array<int, array<string, mixed>>
     */
    public $shopRates = [];

    /**
     * Total ongkir dari semua toko.
     */
    public $totalShippingCost = 0;

    /**
     * Selected location ID from the shipping list.
     */
    public $selectedLocationId = null;

    /**
     * Parsed array of selected IDs.
     */
    public function getSelectedIdsArrayProperty()
    {
        // Decode selectedIds using Sqids
        $sqids = new Sqids();
        $selectedIds = blank($this->selectedIds) ? [] : $sqids->decode($this->selectedIds);

        return  array_values(array_filter(
            array_map('intval', $selectedIds)
        ));
    }

    /**
     * Called from Alpine when cart items are available.
     * Groups items by shop_id and stores result into $this->shopGroups
     * so the blade @foreach re-renders via Livewire reactivity.
     *
     * @param  array<int, array<string, mixed>>  $cartItems
     */
    public function resolveShopGroups(array $cartItems)
    {
        $selectedIds = $this->getSelectedIdsArrayProperty();

        // Filter to checked items
        $items = empty($selectedIds) ? $cartItems : array_filter($cartItems, fn ($i) => in_array((int) $i['id'], $selectedIds));

        // Group by shop_id
        $groups = [];
        foreach ($items as $item) {
            $shopId = (int) ($item['shop_id'] ?? 0);
            if (! isset($groups[$shopId])) {
                $groups[$shopId] = [
                    'shop_id' => $shopId,
                    'shop_name' => $item['shop_name'] ?? 'Toko',
                    'items' => [],
                ];
            }

            // Store item id as key and qty as value for easier access in shipping-rates component
            $groups[$shopId]['items'][$item['id']] = $item['qty'];
        }

        $this->shopGroups = array_values($groups);
    }

    /**
     * Called from the shipping-rates child component when a rate is selected.
     */
    #[On('shipping-rate-selected')]
    public function onRateSelected(array $payload)
    {
        $shopId = $payload['shopId'];

        // Group shop rates by shop_id
        $this->shopRates[$shopId] = [
            'courier_code' => $payload['courier_code'],
            'courier_service_code' => $payload['courier_service_code'],
            'price' => (int) $payload['price'],
            'name' => $payload['name'],
            'etd' => $payload['etd'],
        ];

        // Calculate total shipping cost from all selected rates
        $this->totalShippingCost = (int) collect($this->shopRates)->sum('price');
    }

    #[On('shipping-address-selected')]
    public function onAddressSelected($locationId)
    {
        $this->selectedLocationId = $locationId;
    }

    /**
     * Submit checkout
     */
    public function submit(array $checkoutItems, $guestAddress = null)
    {
        $locationId = $this->selectedLocationId;

        // Auto-resolve location_id if authenticated and not selected yet
        if (auth()->check() && ! $locationId) {
            $locationId = \App\Models\Location\Location::where('user_id', auth()->id())
                ->where('type', 'destination')
                ->latest()
                ->first()?->id;
        }

        $data = [
            'is_authenticated' => auth()->check(),
            'location_id' => $locationId,
            'guest_address' => !auth()->check() ? $guestAddress : null,
            'checkout_items' => $checkoutItems,
            'shop_groups' => $this->shopGroups,
            'shop_rates' => $this->shopRates,
            'total_shipping_cost' => $this->totalShippingCost,
        ];

        dd($data);
    }
};
