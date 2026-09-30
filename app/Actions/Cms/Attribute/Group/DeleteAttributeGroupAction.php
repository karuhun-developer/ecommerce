<?php

namespace App\Actions\Cms\Attribute\Group;

use App\Models\Attribute\AttributeGroup;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteAttributeGroupAction
{
    public function handle(AttributeGroup $attributeGroup, User $actor): bool
    {
        Gate::forUser($actor)->authorize('delete'.AttributeGroup::class);

        return $attributeGroup->delete();
    }
}
