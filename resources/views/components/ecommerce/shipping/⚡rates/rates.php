<?php

use App\Actions\Ecommerce\Shipping\GetShippingRatesAction;
use App\Data\Checkout\ShippingRateData;
use App\Data\Checkout\ShippingRatesData;
use App\Models\Location\Location;
use App\Services\CourierSettingsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
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
    #[Locked]
    public int $shopId;

    /**
     * Product flat IDs checked out for this shop.
     *
     * @var array<int>
     */
    #[Locked]
    public array $items = [];

    /**
     * Loaded from Alpine via wire:init or event — destination area ID.
     * For auth users this comes from their selected Location.
     * For guests this comes from localStorage passed via JS.
     */
    public string $destinationAreaId = '';

    public string $destinationPostalCode = '';

    public ?float $destinationLatitude = null;

    public ?float $destinationLongitude = null;

    /** @var array<int, array<string, mixed>> */
    #[Locked]
    public array $rates = [];

    public bool $loading = false;

    public string $error = '';

    public ?string $selectedCourierCode = null;

    public ?string $selectedServiceCode = null;

    public int $selectedPrice = 0;

    public string $selectedName = '';

    public string $selectedEtd = '';

    public function mount(): void
    {
        if (auth()->check()) {
            $this->resolveAuthDestination();
        }
    }

    #[Computed]
    public function destinationReady(): bool
    {
        return app(CourierSettingsService::class)->usesAreaIds()
            ? filled($this->destinationAreaId)
            : $this->destinationLatitude !== null && $this->destinationLongitude !== null;
    }

    private function resolveAuthDestination(): void
    {
        $location = Location::where('user_id', auth()->id())
            ->where('type', 'destination')
            ->latest()
            ->first();

        if ($location) {
            $this->destinationAreaId = $location->biteship_area_id ?? '';
            $this->destinationPostalCode = $location->postal_code ?? '';
            $this->destinationLatitude = is_numeric($location->latitude) ? (float) $location->latitude : null;
            $this->destinationLongitude = is_numeric($location->longitude) ? (float) $location->longitude : null;
        }
    }

    /**
     * Called when the auth user selects a different address.
     * Dispatched from ecommerce.checkout.shipping component.
     */
    #[On('shipping-address-selected')]
    public function onAddressSelected(int $locationId): void
    {
        abort_unless(auth()->check(), 403);
        $location = Location::where('user_id', auth()->id())->where('type', 'destination')->findOrFail($locationId);

        if ($location) {
            $this->destinationAreaId = $location->biteship_area_id ?? '';
            $this->destinationPostalCode = $location->postal_code ?? '';
            $this->destinationLatitude = is_numeric($location->latitude) ? (float) $location->latitude : null;
            $this->destinationLongitude = is_numeric($location->longitude) ? (float) $location->longitude : null;
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
    public function setGuestDestination(string $areaId, string $postalCode, ?float $latitude = null, ?float $longitude = null): void
    {
        if (auth()->check()) {
            return;
        }

        validator(['latitude' => $latitude, 'longitude' => $longitude], [
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ])->validate();

        $this->destinationAreaId = $areaId;
        $this->destinationPostalCode = $postalCode;
        $this->destinationLatitude = $latitude;
        $this->destinationLongitude = $longitude;
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
                ShippingRatesData::fromArray($this->shopId, $this->destinationAreaId, $this->items, $this->destinationLatitude, $this->destinationLongitude),
            );
        } catch (Throwable $exception) {
            $knownErrors = [
                'Informasi lokasi toko belum lengkap.',
                'Pilih alamat pengiriman terlebih dahulu.',
                'Item tidak ditemukan.',
                'Tidak ada layanan kurir yang tersedia untuk rute ini.',
                'Belum ada kurir pengiriman yang diaktifkan.',
                'Lengkapi titik lokasi toko dan alamat pengiriman di peta.',
            ];

            if (in_array($exception->getMessage(), $knownErrors, true)) {
                $this->error = $exception->getMessage();
            } else {
                report($exception);
                $this->error = 'Gagal mengambil tarif pengiriman. Silakan coba lagi.';
            }
        } finally {
            $this->loading = false;
        }
    }

    public function selectRate(string $courierCode, string $serviceCode, int $price, string $name, string $etd): void
    {
        $rate = collect($this->rates)->first(fn (array $rate): bool => $rate['courier_code'] === $courierCode && $rate['courier_service_code'] === $serviceCode);
        abort_unless($rate, 422);
        $selectedRate = ShippingRateData::fromArray($rate);
        $price = (int) $selectedRate->price;
        $name = $selectedRate->name;
        $etd = $selectedRate->etd ?? '';
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
