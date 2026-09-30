<?php

use App\Actions\Cms\Content\SavePageAction;
use App\Livewire\Forms\Cms\PageForm;
use App\Models\Content\Page;
use App\Models\Content\FooterGroup;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public PageForm $form;

    #[Locked]
    public bool $isUpdate = false;

    #[Locked]
    public ?int $id = null;

    #[Locked]
    public bool $formReady = false;

    #[Locked]
    public int $editorRevision = 0;

    #[Computed]
    public function footerGroups(): Collection
    {
        return FooterGroup::query()->orderBy('sort_order')->orderBy('id')->get();
    }

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

        $this->editorRevision++;
        $this->formReady = true;
        Flux::modal('defaultModal')->show();
    }

    private function getRecordData(int $id): void
    {
        $record = Page::query()->findOrFail($id);
        $this->form->setPage($record);
        $this->id = $record->id;
        $this->isUpdate = true;
    }

    private function resetRecordData(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->reset(['id', 'isUpdate', 'formReady']);
    }

    public function closeModal(): void
    {
        $this->resetRecordData();
    }

    public function submit(SavePageAction $action): void
    {
        Gate::authorize('manageWebsiteContent');
        $record = $this->isUpdate ? Page::query()->findOrFail($this->id) : null;
        $action->handle($this->form->data($record), auth()->user(), $record);

        $this->dispatch('toast', type: 'success', message: 'Halaman tersimpan.');
        $this->dispatch('reset-parent-page')->to('cms.content.page.table');
        Flux::modal('defaultModal')->close();
        $this->resetRecordData();
    }
};
