<?php

namespace App\Actions\Cms\Content;

use App\Models\Content\HeaderLink;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class DeleteHeaderLinkAction
{
    public function handle(HeaderLink $link, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageWebsiteContent');
        $link->delete();
    }
}
