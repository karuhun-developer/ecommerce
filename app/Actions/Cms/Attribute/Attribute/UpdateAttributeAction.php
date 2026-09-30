<?php

namespace App\Actions\Cms\Attribute\Attribute;

use App\Data\Cms\AttributeData;
use App\Models\Attribute\Attribute;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateAttributeAction
{
    public function handle(Attribute $attribute, AttributeData $data, User $actor): Attribute
    {
        Gate::forUser($actor)->authorize('update'.Attribute::class);

        $attribute->update($data->attributes());

        return $attribute->fresh();
    }
}
