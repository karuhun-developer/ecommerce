<?php

namespace App\Actions\Cms\Content;

use App\Models\Content\Banner;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class DeleteBannerAction
{
    public function handle(Banner $record, User $actor): bool
    {
        Gate::forUser($actor)->authorize('manageWebsiteContent');

        return (bool) $record->delete();
    }
}
