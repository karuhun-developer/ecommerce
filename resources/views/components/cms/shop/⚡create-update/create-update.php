<?php

use App\Actions\Cms\Shop\StoreShopAction;
use App\Actions\Cms\Shop\UpdateShopAction;
use App\Livewire\Forms\Cms\ShopForm;
use App\Models\Shop\Shop;
use App\Models\User;
use App\Services\BiteshipService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $modelInstance = Shop::class;

    #[Locked]
    public bool $isUpdate = false;

    #[Locked]
    public ?int $id = null;

    public ShopForm $form;

    public string $searchArea = '';

    #[Locked]
    public array $areas = [];

    #[On('set-action')]
    public function setAction(int|string|null $id = null): void
    {
        $this->resetValidation();
        $this->form->reset();
        $this->areas = [];
        $this->id = $id ? (int) $id : null;
        $this->isUpdate = $this->id !== null;
        if ($this->id !== null) {
            Gate::authorize('show'.Shop::class);
            $this->form->setShop(Shop::query()->accessibleTo(auth()->user())->with('location')->findOrFail($this->id));
        }
        $this->searchArea = $this->form->area_string ?? '';
        $this->dispatch('update-jodit-content', editorId: 'shop-description', content: $this->form->description ?? '');
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
        Gate::authorize(($this->isUpdate ? 'update' : 'create').Shop::class);
        $data = $this->form->shopData();
        $shop = $this->isUpdate ? Shop::query()->accessibleTo($actor)->findOrFail($this->id) : null;
        try {
            if ($shop) {
                $updateAction->handle($shop, $data, $actor);
            } else {
                $storeAction->handle($data, $actor);
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatch('toast', type: 'error', message: 'Unable to save the shop right now. Please try again.');

            return;
        }
        $this->dispatch('toast', type: 'success', message: $this->isUpdate ? 'Shop updated successfully.' : 'Shop created successfully.');
        $this->dispatch('reset-parent-page');
        Flux::modal('defaultModal')->close();
    }
};
