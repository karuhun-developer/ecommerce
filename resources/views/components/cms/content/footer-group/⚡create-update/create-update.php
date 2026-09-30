<?php

use App\Actions\Cms\Content\SaveFooterGroupAction;
use App\Livewire\Forms\Cms\FooterGroupForm;
use App\Models\Content\FooterGroup;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public FooterGroupForm $form;

    #[Locked]
    public bool $isUpdate = false;

    #[Locked]
    public ?int $id = null;

    public function mount(): void
    {
        Gate::authorize('manageWebsiteContent');
    }

    #[On('set-action')]
    public function setAction(?int $id = null): void
    {
        Gate::authorize('manageWebsiteContent');
        $this->resetRecordData();

        if ($id !== null) {
            $this->getRecordData($id);
        }

        Flux::modal('defaultModal')->show();
    }

    private function getRecordData(int $id): void
    {
        $record = FooterGroup::query()->findOrFail($id);
        $this->form->setFooterGroup($record);
        $this->id = $record->id;
        $this->isUpdate = true;
    }

    private function resetRecordData(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->reset(['id', 'isUpdate']);
    }

    public function closeModal(): void
    {
        $this->resetRecordData();
    }

    public function submit(SaveFooterGroupAction $action): void
    {
        Gate::authorize('manageWebsiteContent');
        $record = $this->isUpdate ? FooterGroup::query()->findOrFail($this->id) : null;
        $action->handle($this->form->data(), auth()->user(), $record);

        $this->dispatch('toast', type: 'success', message: 'Grup footer tersimpan.');
        $this->dispatch('reset-parent-page')->to('cms.content.footer-group.table');
        Flux::modal('defaultModal')->close();
        $this->resetRecordData();
    }
};
