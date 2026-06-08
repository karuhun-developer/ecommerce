<?php

namespace App\Actions\Cms\Attribute\Attribute;

use App\Models\Attribute\Attribute;

class UpdateAttributeAction
{
    /**
     * Handle the action.
     */
    public function handle(Attribute $attribute, array $data): Attribute
    {
        $attribute->update($data);

        return $attribute->fresh();
    }
}
