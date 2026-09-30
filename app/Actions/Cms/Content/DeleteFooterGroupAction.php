<?php

namespace App\Actions\Cms\Content;

use App\Models\Content\FooterGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DeleteFooterGroupAction
{
    public function handle(FooterGroup $group, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageWebsiteContent');

        DB::transaction(function () use ($group): void {
            $group->pages()->update(['footer_group' => null]);
            $group->delete();
        });
    }
}
