<?php

namespace App\Data\Review;

final readonly class ReviewSubmissionData
{
    /** @param array<string, ReviewData> $reviews */
    public function __construct(public array $reviews) {}

    public static function fromArray(array $input, array $images): self
    {
        $validated = validator(['reviews' => $input, 'images' => $images], [
            'reviews' => ['required', 'array'],
            'reviews.*' => ['required', 'array'],
            'reviews.*.rating' => ReviewData::rules()['rating'],
            'reviews.*.comment' => ReviewData::rules()['comment'],
            'images' => ['array'],
            'images.*' => ReviewData::rules()['images'],
            'images.*.*' => ReviewData::rules()['images.*'],
        ])->validate();

        $reviews = [];
        foreach ($validated['reviews'] as $key => $review) {
            $reviews[$key] = new ReviewData((float) $review['rating'], $review['comment'] ?? null, array_values($validated['images'][$key] ?? []));
        }

        return new self($reviews);
    }
}
