<?php

use App\Actions\Ecommerce\Location\DeleteLocationAction;
use App\Models\Location\Location;
use App\Services\BiteshipService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    /** Selected location ID (null = none selected yet) */
    public $selectedLocationId = null;

    // ------------------------------------------------------------------
    // Guest fields (only used when not authenticated)
    // ------------------------------------------------------------------
    public $guest_contact_name = '';

    public $guest_contact_phone = '';

    public $guest_email = '';

    public $guest_address = '';

    public $guest_note = '';

    public $guest_postal_code = '';

    public $guest_area_string = '';

    public $guest_biteship_area_id = '';

    public $guest_latitude = null;

    public $guest_longitude = null;

    public $guest_searchArea = '';

    /** @var array<int, array<string, mixed>> */
    public $guest_areas = [];

    /**
     * Called from Alpine x-init to restore all guest fields from localStorage.
     */
    public function restoreGuestData(array $data)
    {
        $this->guest_contact_name = $data['contact_name'] ?? '';
        $this->guest_contact_phone = $data['contact_phone'] ?? '';
        $this->guest_email = $data['email'] ?? '';
        $this->guest_address = $data['address'] ?? '';
        $this->guest_note = $data['note'] ?? '';
        $this->guest_postal_code = $data['postal_code'] ?? '';
        $this->guest_area_string = $data['area_string'] ?? '';
        $this->guest_biteship_area_id = $data['biteship_area_id'] ?? '';
        $this->guest_searchArea = $data['area_string'] ?? '';
        $this->guest_latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $this->guest_longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;
    }

    // ------------------------------------------------------------------
    // Auth: user address list
    // ------------------------------------------------------------------
    #[Computed]
    public function addresses()
    {
        if (! auth()->check()) {
            return collect();
        }

        return Location::where('user_id', auth()->id())
            ->where('type', 'destination')
            ->latest()
            ->get();
    }

    public function mount()
    {
        if (auth()->check()) {
            // Auto-select first address
            $first = $this->addresses->first();
            if ($first) {
                $this->selectAddress($first->id);
            }
        }
    }

    public function selectAddress(int $id)
    {
        $this->selectedLocationId = $id;
        $this->dispatch('shipping-address-selected', locationId: $id);
    }

    #[On('deleteAddress')]
    public function deleteAddress(DeleteLocationAction $deleteAction, $id)
    {
        $deleteAction->handle(
            location: Location::where('user_id', auth()->id())->findOrFail($id)
        );

        if ($this->selectedLocationId === $id) {
            $this->selectedLocationId = null;
        }

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: 'Alamat berhasil dihapus.',
        );

        // Reset addresses property so the list re-renders
        unset($this->addresses);
    }

    public function openCreate()
    {
        $this->dispatch('shipping-create');
    }

    public function openEdit(int $id)
    {
        $this->dispatch('shipping-edit', id: $id);
    }

    #[On('shipping-list-refresh')]
    public function refreshList()
    {
        unset($this->addresses);

        // Auto-select the newest address if none selected
        if (! $this->selectedLocationId) {
            $first = $this->addresses->first();
            if ($first) {
                $this->selectedLocationId = $first->id;
            }
        }
    }

    // ------------------------------------------------------------------
    // Guest: Biteship area search
    // ------------------------------------------------------------------
    public function searchGuestArea(BiteshipService $biteshipService)
    {
        $this->validate(['guest_searchArea' => 'required|string|min:3']);

        try {
            $res = $biteshipService->getMapsAreas(['input' => $this->guest_searchArea]);
            $this->guest_areas = $res['areas'] ?? [];
        } catch (Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Gagal mencari area: '.$e->getMessage());
        }
    }

    public function selectGuestArea(string $id, string $name, string $postalCode)
    {
        $this->guest_biteship_area_id = $id;
        $this->guest_area_string = $name;
        $this->guest_postal_code = $postalCode;
        $this->guest_searchArea = $name;
        $this->guest_areas = [];

        $this->dispatch('guest-address-updated', areaId: $id, postalCode: $postalCode);
    }
};
