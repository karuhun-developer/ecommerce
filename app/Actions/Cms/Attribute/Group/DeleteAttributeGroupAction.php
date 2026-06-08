<?php

namespace App\Actions\Cms\Attribute\Group;

use App\Models\Attribute\AttributeGroup;

class DeleteAttributeGroupAction
{
    /**
     * Handle the action.
     */
    public function handle(AttributeGroup $attributeGroup): bool
    {
        return $attributeGroup->delete();
    }
}
