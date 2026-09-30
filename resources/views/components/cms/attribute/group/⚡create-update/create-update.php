<?php

use App\Actions\Cms\Attribute\Group\StoreAttributeGroupAction;
use App\Actions\Cms\Attribute\Group\UpdateAttributeGroupAction;
use App\Data\Cms\AttributeGroupData;
use App\Models\Attribute\AttributeGroup;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    #[Locked]
    public string $modelInstance = AttributeGroup::class;

    #[Locked]
    public bool $isUpdate = false;

    #[On('set-action')]
    public function setAction(?int $id = null): void
    {
        $this->resetValidation();

        if ($id) {
            $this->isUpdate = true;
            $this->getRecordData($id);
        } else {
            $this->isUpdate = false;
            $this->resetRecordData();
        }
    }

    // Record data
    #[Locked]
    public ?int $id = null;

    public $name;

    public $description;

    // Get record data
    private function getRecordData(int $id): void
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = AttributeGroup::findOrFail($id);
        $this->fill(
            $record->only(
                'id',
                'name',
                'description',
            )
        );
    }

    // Reset record data
    private function resetRecordData(): void
    {
        $this->reset([
            'id',
            'name',
            'description',
        ]);
    }

    // Handle form submit
    public function submit(StoreAttributeGroupAction $storeAction, UpdateAttributeGroupAction $updateAction): void
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                attributeGroup: AttributeGroup::findOrFail($this->id),
                data: AttributeGroupData::fromArray($validated),
                actor: auth()->user(),
            );
        } else {
            $storeAction->handle(
                data: AttributeGroupData::fromArray($validated),
                actor: auth()->user(),
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
