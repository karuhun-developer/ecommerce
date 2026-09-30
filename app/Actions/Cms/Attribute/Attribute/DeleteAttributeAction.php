<?php

namespace App\Actions\Cms\Attribute\Attribute;

use App\Models\Attribute\Attribute;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DeleteAttributeAction
{
    public function handle(Attribute $attribute, User $actor): bool
    {
        Gate::forUser($actor)->authorize('delete'.Attribute::class);

        return $attribute->delete();
    }
}
