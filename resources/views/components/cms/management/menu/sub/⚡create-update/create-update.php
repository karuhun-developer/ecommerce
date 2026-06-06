<?php

use App\Actions\Cms\Management\MenuSub\StoreMenuSubAction;
use App\Actions\Cms\Management\MenuSub\UpdateMenuSubAction;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuSub;
use App\Models\Spatie\Role;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    public $modelInstance = MenuSub::class;

    public Menu $menu;

    public $isUpdate = false;

    #[On('set-action')]
    public function setAction($id = null)
    {
        if ($id) {
            $this->isUpdate = true;
            $this->getRecordData($id);
        } else {
            $this->isUpdate = false;
            $this->resetRecordData();
        }
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }

    #[Computed]
    public function icons()
    {
        return collect(File::allFiles(resource_path('views/flux/icon')))->map(function ($file) {
            return str_replace('.blade.php', '', $file->getFilename());
        })->values()->toArray();
    }

    // Record data
    public $id;

    public $role_id;

    public $menu_id;

    public $name;

    public $url;

    public $icon;

    public $order;

    public $active_pattern;

    public $status;

    // Get record data
    public function getRecordData($id)
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = MenuSub::find($id);
        $this->fill(
            $record->only(
                'id',
                'role_id',
                'menu_id',
                'name',
                'url',
                'icon',
                'order',
                'active_pattern',
            )
        );
        $this->status = $record->status->value;
    }

    // Reset record data
    public function resetRecordData()
    {
        $this->reset([
            'id',
            'role_id',
            'menu_id',
            'name',
            'url',
            'icon',
            'order',
            'active_pattern',
        ]);

        $this->role_id = $this->menu->role_id;
        $this->menu_id = $this->menu->id;
        $this->status = 1;
    }

    // Handle form submit
    public function submit(StoreMenuSubAction $storeAction, UpdateMenuSubAction $updateAction)
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $this->validate([
            'role_id' => 'required|exists:roles,id',
            'menu_id' => 'required|exists:menus,id',
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'required|integer',
            'active_pattern' => 'nullable|string|max:255',
            'status' => 'required|boolean',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                menuSub: MenuSub::findOrFail($this->id),
                data: $this->all(),
            );
        } else {
            $storeAction->handle(
                data: $this->all(),
            );
        }

        // Flush menu cache
        Cache::flush();

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $this->isUpdate ? 'Menu Sub updated successfully.' : 'Menu Sub created successfully.'
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Close modal
        Flux::modal('defaultModal')->close();
    }
};
