<?php

use App\Actions\Cms\Shop\StoreShopAction;
use App\Actions\Cms\Shop\UpdateShopAction;
use App\Livewire\Forms\Cms\ShopForm;
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

    #[Locked]
    public ?int $id = null;

    public ShopForm $form;

    public string $searchArea = '';

    #[Locked]
    public array $areas = [];

    public function mount(): void
    {
        Gate::authorize('view'.Shop::class);
        $this->loadDefaultShop();
    }

    private function loadDefaultShop(): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        $query = Shop::query()->accessibleTo($actor)->with('location');
        $this->shop = $this->shop ? $query->findOrFail($this->shop->getKey()) : $query->first();
        if ($this->shop) {
            $this->id = $this->shop->id;
            $this->form->setShop($this->shop);
            $this->searchArea = $this->form->area_string ?? '';
            $this->dispatch('update-jodit-content', editorId: 'single-shop-description', content: $this->form->description ?? '');
        }
    }

    public function searchBiteshipArea(BiteshipService $biteshipService): void
    {
        $this->validate(['searchArea' => ['required', 'string', 'min:3', 'max:255']]);
        try {
            $this->areas = $biteshipService->getMapsAreas(['input' => $this->searchArea])['areas'] ?? [];
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatch('toast', type: 'error', message: 'Unable to search areas right now. Please try again.');
        }
    }

    public function selectArea(string $id, string $name, string $postal_code): void
    {
        $area = collect($this->areas)->firstWhere('id', $id);
        abort_unless($area, 422);
        $this->form->biteship_area_id = $area['id'];
        $this->form->area_string = $area['name'];
        $this->form->postal_code = (string) ($area['postal_code'] ?? '');
        $this->searchArea = $area['name'];
        $this->areas = [];
    }

    public function submit(StoreShopAction $storeAction, UpdateShopAction $updateAction): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        Gate::authorize(($this->id ? 'update' : 'create').Shop::class);
        $data = $this->form->shopData();
        $shop = $this->id ? Shop::query()->accessibleTo($actor)->findOrFail($this->id) : null;
        try {
            $this->shop = $shop ? $updateAction->handle($shop, $data, $actor) : $storeAction->handle($data, $actor);
            $this->id = $this->shop->id;
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatch('toast', type: 'error', message: 'Unable to save the shop right now. Please try again.');

            return;
        }
        Cache::forget('default:shop');
        $this->dispatch('toast', type: 'success', message: $shop ? 'Shop updated successfully.' : 'Shop created successfully.');
    }
};
