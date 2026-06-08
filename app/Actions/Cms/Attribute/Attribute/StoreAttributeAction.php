<?php

namespace App\Actions\Cms\Attribute\Attribute;

use App\Models\Attribute\Attribute;

class StoreAttributeAction
{
    /**
     * Handle the action.
     */
    public function handle(array $data): Attribute
    {
        return Attribute::create($data);
    }
}
