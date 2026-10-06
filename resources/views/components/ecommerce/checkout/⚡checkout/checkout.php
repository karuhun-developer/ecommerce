<?php

use App\Actions\Ecommerce\Checkout\ResolveShopGroupsAction;
use App\Actions\Ecommerce\Checkout\StoreCheckoutAction;
use App\Actions\Ecommerce\Shipping\GetShippingRatesAction;
use App\Data\Checkout\CartData;
use App\Data\Checkout\CheckoutData;
use App\Data\Checkout\CheckoutItemData;
use App\Data\Checkout\CheckoutShopData;
use App\Data\Checkout\ShippingRateData;
use App\Data\Checkout\ShippingRatesData;
use App\Livewire\Forms\CheckoutForm;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Sqids\Sqids;

new class extends Component
{
    private const MAX_PURCHASABLE_QUANTITY = 100;

    #[Locked]
    public int $insuranceFee = 2500;

    #[Locked]
    public int $applicationFee = 1000;

    #[Locked]
    public mixed $selectedIds = '';

    #[Locked]
    public array $shopGroups = [];

    public CheckoutForm $form;

    /**
     * Total ongkir dari semua toko.
     */
    public int $totalShippingCost = 0;

    /**
     * Parsed array of selected IDs.
     */
    public function getSelectedIdsArrayProperty(): array
    {
        if (! is_string($this->selectedIds) || blank($this->selectedIds)) {
            return [];
        }

        $selectedIds = (new Sqids)->decode($this->selectedIds);

        return array_values(array_filter(
            array_unique(array_map('intval', $selectedIds)),
            fn (int $selectedId): bool => $selectedId > 0,
        ));
    }

    /**
     * Called from Alpine when cart items are available.
     * Groups items by shop_id and stores result into $this->shopGroups
     * so the blade @foreach re-renders via Livewire reactivity.
     *
     * @param  array<int, array<string, mixed>>  $cartItems
     */
    public function resolveShopGroups(array $cartItems, ResolveShopGroupsAction $resolveShopGroupsAction): void
    {
        $selectedIds = $this->getSelectedIdsArrayProperty();

        $this->shopGroups = $this->normalizeShopGroups(
            $resolveShopGroupsAction->handle(CartData::fromArray($this->normalizeCartItems($cartItems), $selectedIds)),
        );
    }

    /**
     * Called from the shipping-rates child component when a rate is selected.
     */
    #[On('shipping-rate-selected')]
    public function onRateSelected(array $payload): void
    {
        $shopId = $payload['shopId'];

        $this->form->shopRates[$shopId] = [
            'courier_code' => $payload['courier_code'],
            'courier_service_code' => $payload['courier_service_code'],
            'price' => (int) $payload['price'],
            'name' => $payload['name'],
            'etd' => $payload['etd'],
        ];

        $this->totalShippingCost = (int) collect($this->form->shopRates)->sum('price');
    }

    #[On('shipping-address-selected')]
    public function onAddressSelected(int $locationId): void
    {
        $this->form->selectedLocationId = (int) $locationId;
    }

    /**
     * Submit checkout
     */
    public function submit(?array $guestData, GetShippingRatesAction $getShippingRatesAction, StoreCheckoutAction $storeCheckoutAction): void
    {
        $this->shopGroups = $this->normalizeShopGroups($this->shopGroups);

        $location = auth()->check()
            ? auth()->user()->locations()->whereKey($this->form->selectedLocationId)->first()
            : null;

        if (auth()->check() && ! $location) {
            $this->dispatch('toast',
                type: 'error',
                message: 'Silakan pilih alamat tujuan pengiriman terlebih dahulu.',
            );

            return;
        }

        if (! auth()->check() && ! $guestData) {
            $this->dispatch('toast',
                type: 'error',
                message: 'Silakan isi data pengiriman terlebih dahulu.',
            );

            return;
        }

        $guestData = auth()->check() ? null : $guestData;
        $guest = $this->form->guestData($guestData);
        $this->form->validateCheckout($this->shopGroups);

        $submittedShops = [];

        foreach ($this->shopGroups as $group) {
            $shopId = $group['shop_id'];
            if (! isset($this->form->shopRates[$shopId])) {
                $this->dispatch('toast',
                    type: 'error',
                    message: "Kurir untuk toko {$group['shop_name']} belum dipilih. Silakan pilih kurir terlebih dahulu.",
                );

                return;
            }

            $destinationAreaId = auth()->check()
                ? $location?->biteship_area_id
                : $guest?->areaId;

            try {
                $availableRates = $getShippingRatesAction->handle(
                    ShippingRatesData::fromArray(
                        $shopId,
                        $destinationAreaId ?? '',
                        $group['items'],
                        auth()->check() ? (is_numeric($location?->latitude) ? (float) $location->latitude : null) : $guest?->latitude,
                        auth()->check() ? (is_numeric($location?->longitude) ? (float) $location->longitude : null) : $guest?->longitude,
                    ),
                );
            } catch (Exception $e) {
                Log::error('Failed to get shipping rates.', [
                    'shop_id' => (int) $shopId,
                    'product_flat_ids' => collect(array_keys($group['items']))
                        ->map(fn (int|string $productFlatId): int => (int) $productFlatId)
                        ->sort()
                        ->values()
                        ->all(),
                    'exception' => $e::class,
                ]);
                $this->dispatch('toast',
                    type: 'error',
                    message: "Gagal mendapatkan tarif pengiriman untuk toko {$group['shop_name']}.",
                );

                return;
            }

            $selectedRate = $this->form->shopRates[$shopId];

            $matchedRate = collect($availableRates)->first(function ($rate) use ($selectedRate) {
                return $rate['courier_code'] === $selectedRate['courier_code'] &&
                    $rate['courier_service_code'] === $selectedRate['courier_service_code'];
            });

            if (! $matchedRate) {
                $this->dispatch('toast',
                    type: 'error',
                    message: "Tarif pengiriman yang dipilih untuk toko {$group['shop_name']} tidak valid. Silakan pilih kurir yang tersedia.",
                );

                return;
            }

            $submittedShops[] = new CheckoutShopData(
                (int) $shopId,
                ShippingRateData::fromArray($matchedRate),
                collect($group['items'])->map(fn (int $quantity, int $flatId): CheckoutItemData => new CheckoutItemData($flatId, $quantity))->values()->all(),
            );
        }

        $data = new CheckoutData($this->form->selectedLocationId, $guest, $submittedShops);

        try {
            $checkout = $storeCheckoutAction->handle($data, auth()->user());
            $purchasedIds = collect($submittedShops)
                ->flatMap(fn (CheckoutShopData $shop): array => array_map(fn (CheckoutItemData $item): int => $item->productFlatId, $shop->items))
                ->map(fn (int|string $productFlatId): int => (int) $productFlatId)
                ->unique()
                ->values()
                ->all();

            $this->dispatch('toast',
                type: 'success',
                message: 'Order berhasil dibuat. Silakan lanjutkan ke pembayaran.',
            );

            $this->dispatch('remove-cart-items', ids: $purchasedIds);

            if (! auth()->check()) {
                $this->dispatch('delete-localstorage', key: 'checkout_guest_address');
            }

            $this->redirectRoute('payment.show', [
                'reference' => $checkout->reference,
                ...$checkout->guestRouteParameters(),
            ], navigate: true);

            return;
        } catch (Exception $e) {
            Log::error('Failed to store order.', [
                'checkout_actor' => auth()->check() ? 'user' : 'guest',
                'shop_ids' => collect($submittedShops)->map(fn (CheckoutShopData $shop): int => $shop->shopId)
                    ->map(fn (int|string $shopId): int => (int) $shopId)
                    ->sort()
                    ->values()
                    ->all(),
                'product_flat_ids' => collect($submittedShops)
                    ->flatMap(fn (CheckoutShopData $shop): array => array_map(fn (CheckoutItemData $item): int => $item->productFlatId, $shop->items))
                    ->map(fn (int|string $productFlatId): int => (int) $productFlatId)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
                'exception' => $e::class,
            ]);

            $this->dispatch('toast',
                type: 'error',
                message: 'Gagal membuat order. Silakan coba lagi.',
            );

            return;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $shopGroups
     * @return array<int, array<string, mixed>>
     */
    private function normalizeShopGroups(array $shopGroups): array
    {
        foreach ($shopGroups as &$group) {
            foreach ($group['items'] ?? [] as $productFlatId => $quantity) {
                $group['items'][$productFlatId] = $this->normalizeQuantity($quantity);
            }
        }
        unset($group);

        return $shopGroups;
    }

    /**
     * @param  array<int, mixed>  $cartItems
     * @return array<int, mixed>
     */
    private function normalizeCartItems(array $cartItems): array
    {
        return array_map(function (mixed $cartItem): mixed {
            if (! is_array($cartItem) || ! isset($cartItem['qty'])) {
                return $cartItem;
            }

            $cartItem['qty'] = $this->normalizeQuantity($cartItem['qty']);

            return $cartItem;
        }, $cartItems);
    }

    private function normalizeQuantity(mixed $quantity): int
    {
        $normalizedQuantity = is_numeric($quantity) ? (int) $quantity : 1;

        return min(self::MAX_PURCHASABLE_QUANTITY, max(1, $normalizedQuantity));
    }
};
