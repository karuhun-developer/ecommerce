<?php

use App\Actions\Cms\Attribute\Attribute\StoreAttributeAction;
use App\Actions\Cms\Attribute\Attribute\UpdateAttributeAction;
use App\Data\Cms\AttributeData;
use App\Models\Attribute\Attribute;
use App\Models\Attribute\AttributeGroup;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    // Model instance
    #[Locked]
    public string $modelInstance = Attribute::class;

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

    public $attribute_group_id;

    public $name;

    public $value;

    public $description;

    #[Computed]
    public function attributeGroups()
    {
        return AttributeGroup::orderBy('name')->get();
    }

    // Get record data
    private function getRecordData(int $id): void
    {
        Gate::authorize('show'.$this->modelInstance);

        $record = Attribute::findOrFail($id);
        $this->fill(
            $record->only(
                'id',
                'attribute_group_id',
                'name',
                'value',
                'description',
            )
        );
    }

    // Reset record data
    private function resetRecordData(): void
    {
        $this->reset([
            'id',
            'attribute_group_id',
            'name',
            'value',
            'description',
        ]);
    }

    // Handle form submit
    public function submit(StoreAttributeAction $storeAction, UpdateAttributeAction $updateAction): void
    {
        Gate::authorize(($this->isUpdate ? 'update' : 'create').$this->modelInstance);

        $validated = $this->validate([
            'attribute_group_id' => 'required|exists:attribute_groups,id',
            'name' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                attribute: Attribute::findOrFail($this->id),
                data: AttributeData::fromArray($validated),
                actor: auth()->user(),
            );
        } else {
            $storeAction->handle(
                data: AttributeData::fromArray($validated),
                actor: auth()->user(),
            );
        }

        // Toast message
        $this->dispatch('toast',
            type: 'success',
            message: $this->isUpdate ? 'Attribute updated successfully.' : 'Attribute created successfully.',
        );

        // Reset data table
        $this->dispatch('reset-parent-page');

        // Close modal
        Flux::modal('defaultModal')->close();
    }
};
