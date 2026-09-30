<?php

namespace App\Data\Cms;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final readonly class ProductImageSlotData
{
    public function __construct(public int $flat_id, public int $slot, public ?UploadedFile $image, public bool $delete = false)
    {
        if (! in_array($slot, [0, 1, 2, 3], true) || ($image === null && ! $delete)) {
            throw ValidationException::withMessages(['images' => 'The selected image slot is invalid.']);
        }
    }
}
