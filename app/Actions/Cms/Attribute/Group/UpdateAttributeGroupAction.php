<?php

namespace App\Actions\Cms\Attribute\Group;

use App\Models\Attribute\AttributeGroup;

class UpdateAttributeGroupAction
{
    /**
     * Handle the action.
     */
    public function handle(AttributeGroup $attributeGroup, array $data): AttributeGroup
    {
        $attributeGroup->update($data);

        return $attributeGroup->fresh();
    }
}
