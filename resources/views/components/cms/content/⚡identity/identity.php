<?php

use App\Actions\Cms\Content\SaveStorefrontAction;
use App\Livewire\Forms\Cms\StorefrontForm;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    public StorefrontForm $form;

    public function mount(): void
    {
        Gate::authorize('manageWebsiteContent');
        $this->form->load();
    }

    public function save(SaveStorefrontAction $action): void
    {
        Gate::authorize('manageWebsiteContent');
        $action->handle($this->form->data(), auth()->user());
        $this->dispatch('toast', type: 'success', message: 'Identitas website tersimpan.');
    }
};
