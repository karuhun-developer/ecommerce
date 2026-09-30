<?php

namespace App\Actions\Cms\Content;

use App\Data\Content\FooterGroupData;
use App\Models\Content\FooterGroup;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class SaveFooterGroupAction
{
    public function handle(FooterGroupData $data, User $actor, ?FooterGroup $group = null): FooterGroup
    {
        Gate::forUser($actor)->authorize('manageWebsiteContent');
        $group ??= new FooterGroup(['key' => (string) Str::uuid()]);
        $group->fill($data->attributes())->save();

        return $group->refresh();
    }
}
