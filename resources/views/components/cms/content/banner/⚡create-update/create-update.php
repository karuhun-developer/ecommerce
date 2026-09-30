<?php

use App\Actions\Cms\Content\SaveBannerAction;
use App\Livewire\Forms\Cms\BannerForm;
use App\Models\Content\Banner;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public BannerForm $form;

    #[Locked]
    public bool $isUpdate = false;

    #[Locked]
    public ?int $id = null;

    #[Locked]
    public ?string $imageUrl = null;

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
        $record = Banner::query()->with('media')->findOrFail($id);
        $this->form->setBanner($record);
        $this->id = $record->id;
        $this->isUpdate = true;
        $this->imageUrl = $record->getFirstMediaUrl('banner') ?: null;
    }

    private function resetRecordData(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->reset(['id', 'isUpdate', 'imageUrl']);
    }

    public function closeModal(): void
    {
        $this->resetRecordData();
    }

    public function submit(SaveBannerAction $action): void
    {
        Gate::authorize('manageWebsiteContent');
        $record = $this->isUpdate ? Banner::query()->findOrFail($this->id) : null;
        $action->handle($this->form->data($record), auth()->user(), $record);

        $this->dispatch('toast', type: 'success', message: 'Banner tersimpan.');
        $this->dispatch('reset-parent-page')->to('cms.content.banner.table');
        Flux::modal('defaultModal')->close();
        $this->resetRecordData();
    }
};
