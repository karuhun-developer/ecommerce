<?php

namespace App\Actions\Cms\Content;

use App\Data\Content\BannerData;
use App\Models\Content\Banner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SaveBannerAction
{
    public function handle(BannerData $data, User $actor, ?Banner $banner = null): Banner
    {
        Gate::forUser($actor)->authorize('manageWebsiteContent');

        return DB::transaction(function () use ($data, $banner): Banner {
            $banner ??= new Banner;
            $banner->fill($data->attributes())->save();
            if ($data->image !== null) {
                $banner->addMedia($data->image)->toMediaCollection('banner');
            }

            return $banner->refresh();
        });
    }
}
