<?php

use App\Actions\Ecommerce\Shipping\GetShippingRatesAction;
use App\Models\Location\Location;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Fetches Biteship shipping rates for a single shop's items.
 *
 * Props:
 *   - shopId: int
 *   - itemIds: array<int>  — product flat IDs (from Alpine cart)
 *
 * Emits: shipping-rate-selected → { shopId, courier_code, courier_service_code, price, name, etd }
 */
new class extends Component
{
    public int $shopId;

    /**
     * Product flat IDs checked out for this shop.
     *
     * @var array<int>
     */
    public array $items = [];

    /**
     * Loaded from Alpine via wire:init or event — destination area ID.
     * For auth users this comes from their selected Location.
     * For guests this comes from localStorage passed via JS.
     */
    public string $destinationAreaId = '';

    public string $destinationPostalCode = '';

    /** @var array<int, array<string, mixed>> */
    public array $rates = [];

    public bool $loading = false;

    public string $error = '';

    public ?string $selectedCourierCode = null;

    public ?string $selectedServiceCode = null;

    public int $selectedPrice = 0;

    public string $selectedName = '';

    public string $selectedEtd = '';

    /** Supported couriers */
    private const COURIERS = 'jne,tiki,lion,ninja,jnt,sicepat';

    public function mount(): void
    {
        // For authenticated users, auto-resolve destination from selected location
        if (auth()->check()) {
            $this->resolveAuthDestination();
        }
    }

    private function resolveAuthDestination(): void
    {
        // The shipping component emits the selected location ID via event
        // We also accept it on mount if a location is already selected
        $location = Location::where('user_id', auth()->id())
            ->where('type', 'destination')
            ->latest()
            ->first();

        if ($location && $location->biteship_area_id) {
            $this->destinationAreaId = $location->biteship_area_id;
            $this->destinationPostalCode = $location->postal_code ?? '';
        }
    }

    /**
     * Called when the auth user selects a different address.
     * Dispatched from ecommerce.checkout.shipping component.
     */
    #[On('shipping-address-selected')]
    public function onAddressSelected(int $locationId): void
    {
        $location = Location::where('user_id', auth()->id())->find($locationId);

        if ($location && $location->biteship_area_id) {
            $this->destinationAreaId = $location->biteship_area_id;
            $this->destinationPostalCode = $location->postal_code ?? '';
            $this->rates = [];
            $this->selectedCourierCode = null;
            $this->selectedPrice = 0;
        }
    }

    /**
     * Called from Alpine (JS) to pass guest destination info from localStorage.
     * wire:init="setGuestDestination(@js($guestData))" won't work cross-component,
     * so we expose this as a public action callable from x-init.
     */
    #[On('guest-address-updated')]
    public function setGuestDestination(string $areaId, string $postalCode): void
    {
        $this->destinationAreaId = $areaId;
        $this->destinationPostalCode = $postalCode;
        $this->rates = [];
        $this->selectedCourierCode = null;
        $this->selectedPrice = 0;
    }

    /**
     * Fetch rates from Biteship for this shop → destination pair.
     */
    public function fetchRates(GetShippingRatesAction $getShippingRatesAction): void
    {
        $this->error = '';
        $this->rates = [];

        $this->loading = true;
        try {
            $this->rates = $getShippingRatesAction->handle(
                shopId: $this->shopId,
                destinationAreaId: $this->destinationAreaId,
                items: $this->items,
            );
        } catch (Exception $e) {
            // Check if it's a known error message from the action
            $knownErrors = [
                'Informasi lokasi toko belum lengkap.',
                'Pilih alamat pengiriman terlebih dahulu.',
                'Item tidak ditemukan.',
                'Tidak ada layanan kurir yang tersedia untuk rute ini.',
            ];

            if (in_array($e->getMessage(), $knownErrors)) {
                $this->error = $e->getMessage();
            } else {
                $this->error = 'Gagal mengambil tarif pengiriman: '.$e->getMessage();
            }
        } finally {
            $this->loading = false;
        }
    }

    public function selectRate(string $courierCode, string $serviceCode, int $price, string $name, string $etd): void
    {
        $this->selectedCourierCode = $courierCode;
        $this->selectedServiceCode = $serviceCode;
        $this->selectedPrice = $price;
        $this->selectedName = $name;
        $this->selectedEtd = $etd;

        $this->dispatch('shipping-rate-selected', [
            'shopId' => $this->shopId,
            'courier_code' => $courierCode,
            'courier_service_code' => $serviceCode,
            'price' => $price,
            'name' => $name,
            'etd' => $etd,
        ]);
    }
};
