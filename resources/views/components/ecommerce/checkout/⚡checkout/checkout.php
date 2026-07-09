<?php

use App\Actions\Ecommerce\Checkout\ResolveShopGroupsAction;
use App\Actions\Ecommerce\Checkout\StoreCheckoutAction;
use App\Actions\Ecommerce\Shipping\GetShippingRatesAction;
use App\Models\Location\Location;
use App\Models\Product\ProductFlat;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Sqids\Sqids;

new class extends Component
{
    // CONSTANTS
    public $asuransiPengiriman = 2500;

    public $jasaAplikasi = 1000;

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
        $sqids = new Sqids;
        $selectedIds = blank($this->selectedIds) ? [] : $sqids->decode($this->selectedIds);

        return array_values(array_filter(
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
    public function resolveShopGroups(array $cartItems, ResolveShopGroupsAction $resolveShopGroupsAction)
    {
        $selectedIds = $this->getSelectedIdsArrayProperty();

        $this->shopGroups = $resolveShopGroupsAction->handle($cartItems, $selectedIds);
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
    public function submit(?array $guestData, GetShippingRatesAction $getShippingRatesAction, StoreCheckoutAction $storeCheckoutAction)
    {
        // Check if user is authenticated and no location is selected
        if (auth()->check() && ! $this->selectedLocationId) {
            // Toast message
            $this->dispatch([
                'type' => 'error',
                'message' => 'Silakan pilih alamat tujuan pengiriman terlebih dahulu.',
            ]);

            return;
        }

        // Check if guest data is provided for guest checkout
        if (! auth()->check() && ! $guestData) {
            $this->dispatch([
                'type' => 'error',
                'message' => 'Silakan lengkapi data pengiriman terlebih dahulu.',
            ]);

            return;
        }

        // Validate the data before proceeding
        $this->validate([
            'selectedLocationId' => 'nullable|integer|exists:locations,id',
            'shopRates' => 'required|array',
            'shopRates.*.courier_code' => 'required|string',
            'shopRates.*.courier_service_code' => 'required|string',
            'shopRates.*.price' => 'required|integer|min:0',
            'shopRates.*.name' => 'required|string',
            'shopRates.*.etd' => 'nullable|string',
            'shopGroups' => 'required|array',
            'shopGroups.*.shop_id' => 'required|integer|exists:shops,id',
            'shopGroups.*.items' => 'required|array',
            'shopGroups.*.items.*' => 'required|integer|min:1', // value is qty, key is product_flat_id
        ]);

        // Validate that each shop in shopGroups has a corresponding rate in shopRates
        $totalCheckout = 0;
        $totalRates = 0;

        // Submited shop groups
        $submitedShopGroups = [];

        foreach ($this->shopGroups as $group) {
            $shopId = $group['shop_id'];
            if (! isset($this->shopRates[$shopId])) {
                $this->dispatch([
                    'type' => 'error',
                    'message' => "Silakan pilih ongkos kirim untuk toko {$group['shop_name']} terlebih dahulu.",
                ]);

                return;
            }

            // Validate the items in the group against the checkoutItems
            $totalCheckoutShop = 0;
            foreach ($group['items'] as $productFlatId => $qty) {
                $productFlat = ProductFlat::findOrFail($productFlatId);
                $totalCheckoutShop += $productFlat->price * $qty;

                // Store the submitted shop groups with product details for later use
                $submitedShopGroups[$shopId]['items'][$productFlatId] = [
                    'price' => $productFlat->price,
                    'qty' => $qty,
                    'total' => $productFlat->price * $qty,
                    'raw' => $productFlat->toArray(), // Store the raw product flat data for later use
                ];
            }

            // Validate the shipping rates
            $destinationAreaId = auth()->check()
                ? Location::find($this->selectedLocationId)?->biteship_area_id
                : ($guestData['biteship_area_id'] ?? null);

            try {
                $availableRates = $getShippingRatesAction->handle(
                    shopId: $shopId,
                    destinationAreaId: $destinationAreaId,
                    items: $group['items'],
                );
            } catch (Exception $e) {
                $this->dispatch([
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ]);

                return;
            }

            $selectedRate = $this->shopRates[$shopId];

            $matchedRate = collect($availableRates)->first(function ($rate) use ($selectedRate) {
                return $rate['courier_code'] === $selectedRate['courier_code'] &&
                    $rate['courier_service_code'] === $selectedRate['courier_service_code'];
            });

            if (! $matchedRate) {
                $this->dispatch([
                    'type' => 'error',
                    'message' => "Layanan kurir {$selectedRate['name']} tidak tersedia untuk toko {$group['shop_name']}. Silakan pilih ulang kurir.",
                ]);

                return;
            }

            // Make sure the price is updated to the matched rate's price
            $submitedShopGroups[$shopId]['selected_rate'] = $matchedRate;
            $submitedShopGroups[$shopId]['total_checkout'] = $totalCheckoutShop;
            $submitedShopGroups[$shopId]['total_shipping'] = $matchedRate['price'];
            $submitedShopGroups[$shopId]['total'] = $totalCheckoutShop + $matchedRate['price'];

            $totalCheckout += $totalCheckoutShop;
            $totalRates += $matchedRate['price'];
        }

        // Store order
        $submitedData = [
            'shop_groups' => $submitedShopGroups,
            'total_checkout' => $totalCheckout,
            'total_rates' => $totalRates,
            'asuransi_pengiriman' => $this->asuransiPengiriman,
            'jasa_aplikasi' => $this->jasaAplikasi,
            'selected_location_id' => $this->selectedLocationId,
            'guest_data' => $guestData,
        ];

        try {
            $checkout = $storeCheckoutAction->handle($submitedData);

            // Redirect to order detail page with the order reference
            return $this->redirectRoute('payment.detail', [
                'reference' => $checkout->reference,
            ], navigate: true);

        } catch (Exception $e) {
            // Log the error with additional context for debugging
            Log::error('Failed to store order: '.$e->getMessage(), $submitedData);

            $this->dispatch([
                'type' => 'error',
                'message' => 'Failed to store order: '.$e->getMessage(),
            ]);

            return;
        }
    }
};
