<?php

namespace App\Actions\Cms\Content;

use App\Models\Content\Page;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class DeletePageAction
{
    public function handle(Page $record, User $actor): bool
    {
        Gate::forUser($actor)->authorize('manageWebsiteContent');

        return (bool) $record->delete();
    }
}
