<?php

namespace App\Data\Review;

use Illuminate\Http\UploadedFile;

final readonly class ReviewData
{
    /** @param list<UploadedFile> $images */
    public function __construct(public float $rating, public ?string $comment, public array $images = []) {}

    public function validate(): void
    {
        validator(['rating' => $this->rating, 'comment' => $this->comment, 'images' => $this->images], self::rules())->validate();
    }

    public static function rules(): array
    {
        return [
            'rating' => ['required', 'numeric', 'between:1,5', 'multiple_of:0.5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'images' => ['array', 'max:5'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
