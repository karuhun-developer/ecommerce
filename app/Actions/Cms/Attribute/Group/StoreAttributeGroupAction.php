<?php

namespace App\Actions\Cms\Attribute\Group;

use App\Data\Cms\AttributeGroupData;
use App\Models\Attribute\AttributeGroup;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StoreAttributeGroupAction
{
    public function handle(AttributeGroupData $data, User $actor): AttributeGroup
    {
        Gate::forUser($actor)->authorize('create'.AttributeGroup::class);

        return AttributeGroup::create($data->attributes());
    }
}
