<?php

use App\Actions\Cms\Shop\StoreShopAction;
use App\Actions\Cms\Shop\UpdateShopAction;
use App\Models\Shop\Shop;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $modelInstance = Shop::class;

    #[Locked]
    public ?Shop $shop = null;

    public function mount(): void
    {
        Gate::authorize('view'.$this->modelInstance);

        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        if ($this->shop) {
            $this->shop = Shop::query()->accessibleTo($user)->findOrFail($this->shop->getKey());
        }

        $this->loadDefaultShop();
    }

    #[Locked]
    public $id;

    public $name;

    public $description;

    public $location_name;

    public $contact_name;

    public $contact_phone;

    public $address;

    public $note;

    public $postal_code;

    public $latitude;

    public $longitude;

    public $biteship_area_id;

    public $area_string;

    public $searchArea;

    public array $areas = [];

    public function loadDefaultShop(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $record = $this->shop
            ? Shop::query()->accessibleTo($user)->findOrFail($this->shop->getKey())
            : Shop::query()->accessibleTo($user)->first();

        if (! $record) {
            return;
        }

        $this->shop = $record;

        $this->fill(
            $record->only(
                'id',
                'name',
                'description',
            )
        );

        // Set location details if available
        if ($record->location) {
            $this->location_name = $record->location->name;
            $this->contact_name = $record->location->contact_name;
            $this->contact_phone = $record->location->contact_phone;
            $this->address = $record->location->address;
            $this->note = $record->location->note;
            $this->postal_code = $record->location->postal_code;
            $this->latitude = $record->location->latitude;
            $this->longitude = $record->location->longitude;
            $this->biteship_area_id = $record->location->biteship_area_id;
            $this->area_string = $record->location->area_string;
        }

        // Set jodit content
        $this->dispatch('update-jodit-content', $this->description);
    }

    public function searchBiteshipArea(BiteshipService $biteshipService): void
    {
        $this->validate([
            'searchArea' => 'required|string|min:3',
        ]);

        try {
            $res = $biteshipService->getMapsAreas([
                'input' => $this->searchArea,
            ]);
            $this->areas = $res['areas'] ?? [];
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch('toast',
                type: 'error',
                message: 'Unable to search areas right now. Please try again.',
            );
        }
    }

    public function selectArea(string $id, string $name, string $postal_code): void
    {
        $this->biteship_area_id = $id;
        $this->area_string = $name;
        $this->postal_code = $postal_code;
        $this->searchArea = $name;
        $this->areas = [];
    }

    public function submit(StoreShopAction $storeAction, UpdateShopAction $updateAction): void
    {
        Gate::authorize('update'.$this->modelInstance);

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'address' => 'required|string',
            'note' => 'nullable|string',
            'postal_code' => 'required|numeric',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'biteship_area_id' => 'required|string',
        ]);

        $shop = null;

        if ($this->id) {
            $user = auth()->user();
            abort_unless($user instanceof User, 404);
            $shop = Shop::query()->accessibleTo($user)->findOrFail($this->id);
        }

        try {
            if ($shop) {
                $this->shop = $updateAction->handle(shop: $shop, data: $this->shopPayload());

                $message = 'Shop updated successfully.';
            } else {
                $this->shop = $storeAction->handle(data: $this->shopPayload());

                $message = 'Shop created successfully.';
                $this->loadDefaultShop();
            }
        } catch (Throwable $exception) {
            report($exception);

            $this->dispatch(
                'toast',
                type: 'error',
                message: 'Unable to save the shop right now. Please try again.',
            );

            return;
        }

        // Forget default shop cache to reflect changes immediately
        Cache::forget('default:shop');

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $message,
        );
    }

    private function shopPayload(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'location_name' => $this->location_name,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'address' => $this->address,
            'note' => $this->note,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'biteship_area_id' => $this->biteship_area_id,
            'area_string' => $this->area_string,
        ];
    }
};
