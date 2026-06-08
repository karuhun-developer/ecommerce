<?php

namespace App\Actions\Cms\Attribute\Group;

use App\Models\Attribute\AttributeGroup;

class StoreAttributeGroupAction
{
    /**
     * Handle the action.
     */
    public function handle(array $data): AttributeGroup
    {
        return AttributeGroup::create($data);
    }
}
