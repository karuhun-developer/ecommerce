<?php

namespace App\Actions\Cms\Attribute\Group;

use App\Data\Cms\AttributeGroupData;
use App\Models\Attribute\AttributeGroup;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateAttributeGroupAction
{
    public function handle(AttributeGroup $attributeGroup, AttributeGroupData $data, User $actor): AttributeGroup
    {
        Gate::forUser($actor)->authorize('update'.AttributeGroup::class);

        $attributeGroup->update($data->attributes());

        return $attributeGroup->fresh();
    }
}
