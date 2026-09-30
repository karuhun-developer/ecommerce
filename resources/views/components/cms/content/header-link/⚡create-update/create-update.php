<?php

use App\Actions\Cms\Content\SaveHeaderLinkAction;
use App\Livewire\Forms\Cms\HeaderLinkForm;
use App\Models\Content\HeaderLink;
use App\Models\Content\Page;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public HeaderLinkForm $form;

    #[Locked]
    public bool $isUpdate = false;

    #[Locked]
    public ?int $id = null;

    public function mount(): void
    {
        Gate::authorize('manageWebsiteContent');
    }

    #[Computed]
    public function pages(): Collection
    {
        return Page::query()->orderBy('title')->get(['id', 'title', 'published']);
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
        $record = HeaderLink::query()->findOrFail($id);
        $this->form->setHeaderLink($record);
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

    public function submit(SaveHeaderLinkAction $action): void
    {
        Gate::authorize('manageWebsiteContent');
        $record = $this->isUpdate ? HeaderLink::query()->findOrFail($this->id) : null;
        $action->handle($this->form->data(), auth()->user(), $record);

        $this->dispatch('toast', type: 'success', message: 'Tautan header tersimpan.');
        $this->dispatch('reset-parent-page')->to('cms.content.header-link.table');
        Flux::modal('defaultModal')->close();
        $this->resetRecordData();
    }
};
