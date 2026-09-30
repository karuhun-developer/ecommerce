<?php

namespace App\Actions\Cms\Content;

use App\Data\Content\HeaderLinkData;
use App\Models\Content\HeaderLink;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class SaveHeaderLinkAction
{
    public function handle(HeaderLinkData $data, User $actor, ?HeaderLink $link = null): HeaderLink
    {
        Gate::forUser($actor)->authorize('manageWebsiteContent');
        $link ??= new HeaderLink(['key' => (string) Str::uuid()]);
        $link->fill($data->attributes())->save();

        return $link->refresh();
    }
}
