<?php

use App\Actions\Cms\Management\Menu\StoreMenuAction;
use App\Actions\Cms\Management\Menu\UpdateMenuAction;
use App\Models\Menu\Menu;
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
    public $modelInstance = Menu::class;

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

        $record = Menu::find($id);
        $this->fill(
            $record->only(
                'id',
                'role_id',
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
            'name',
            'url',
            'icon',
            'order',
            'active_pattern',
        ]);

        $this->status = 1;
    }

    // Handle form submit
    public function submit(StoreMenuAction $storeAction, UpdateMenuAction $updateAction)
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $this->validate([
            'role_id' => 'required|exists:roles,id',
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'required|integer',
            'active_pattern' => 'nullable|string|max:255',
            'status' => 'required|boolean',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                menu: Menu::findOrFail($this->id),
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
            message: $this->isUpdate ? 'Menu updated successfully.' : 'Menu created successfully.'
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Close modal
        Flux::modal('defaultModal')->close();
    }
};
