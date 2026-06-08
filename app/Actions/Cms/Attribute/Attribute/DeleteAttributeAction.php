<?php

namespace App\Actions\Cms\Attribute\Attribute;

use App\Models\Attribute\Attribute;

class DeleteAttributeAction
{
    /**
     * Handle the action.
     */
    public function handle(Attribute $attribute): bool
    {
        return $attribute->delete();
    }
}
