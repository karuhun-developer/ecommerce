<?php

namespace App\Data\Content;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

final readonly class BannerData
{
    public function __construct(
        public string $title,
        public ?string $subtitle,
        public string $image_alt,
        public ?string $cta_label,
        public ?string $cta_url,
        public bool $active,
        public int $sort_order,
        public ?CarbonImmutable $starts_at,
        public ?CarbonImmutable $ends_at,
        public ?UploadedFile $image = null,
    ) {}

    public function attributes(): array
    {
        return array_diff_key(get_object_vars($this), ['image' => true]);
    }
}
