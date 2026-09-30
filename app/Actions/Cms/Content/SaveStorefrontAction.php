<?php

namespace App\Actions\Cms\Content;

use App\Data\Content\StorefrontData;
use App\Models\Setting\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class SaveStorefrontAction
{
    public function handle(StorefrontData $data, User $actor): Setting
    {
        Gate::forUser($actor)->authorize('manageWebsiteContent');

        return Setting::query()->updateOrCreate(['key' => 'storefront'], ['data' => $data->attributes()]);
    }
}
