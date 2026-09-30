<?php

namespace App\Actions\Cms\Content;

use App\Data\Content\PageData;
use App\Models\Content\Page;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class SavePageAction
{
    public function handle(PageData $data, User $actor, ?Page $page = null): Page
    {
        Gate::forUser($actor)->authorize('manageWebsiteContent');
        $page ??= new Page;
        $page->fill($data->attributes())->save();

        return $page->refresh();
    }
}
