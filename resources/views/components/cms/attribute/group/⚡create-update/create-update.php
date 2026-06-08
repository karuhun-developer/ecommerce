<?php

use App\Actions\Cms\Attribute\Group\StoreAttributeGroupAction;
use App\Actions\Cms\Attribute\Group\UpdateAttributeGroupAction;
use App\Models\Attribute\AttributeGroup;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    public $modelInstance = AttributeGroup::class;

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

    // Record data
    public $id;

    public $name;

    public $description;

    // Get record data
    public function getRecordData($id)
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = AttributeGroup::find($id);
        $this->fill(
            $record->only(
                'id',
                'name',
                'description',
            )
        );
    }

    // Reset record data
    public function resetRecordData()
    {
        $this->reset([
            'id',
            'name',
            'description',
        ]);
    }

    // Handle form submit
    public function submit(StoreAttributeGroupAction $storeAction, UpdateAttributeGroupAction $updateAction)
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                attributeGroup: AttributeGroup::findOrFail($this->id),
                data: $this->all(),
            );
        } else {
            $storeAction->handle(
                data: $this->all(),
            );
        }

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $this->isUpdate ? 'Attribute Group updated successfully.' : 'Attribute Group created successfully.',
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Close modal
        Flux::modal('defaultModal')->close();
    }
};
