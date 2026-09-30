<?php

namespace App\Actions\Cms\Attribute\Attribute;

use App\Data\Cms\AttributeData;
use App\Models\Attribute\Attribute;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StoreAttributeAction
{
    public function handle(AttributeData $data, User $actor): Attribute
    {
        Gate::forUser($actor)->authorize('create'.Attribute::class);

        return Attribute::create($data->attributes());
    }
}
