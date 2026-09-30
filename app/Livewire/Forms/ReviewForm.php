<?php

namespace App\Livewire\Forms;

use App\Data\Review\ReviewData;
use App\Data\Review\ReviewSubmissionData;
use Livewire\Form;

class ReviewForm extends Form
{
    public array $reviewData = [];

    public array $images = [];

    public function data(): ReviewSubmissionData
    {
        $this->validate([
            'reviewData' => ['required', 'array'],
            'reviewData.*' => ['required', 'array'],
            'reviewData.*.rating' => ReviewData::rules()['rating'],
            'reviewData.*.comment' => ReviewData::rules()['comment'],
            'images' => ['array'],
            'images.*' => ReviewData::rules()['images'],
            'images.*.*' => ReviewData::rules()['images.*'],
        ]);

        return ReviewSubmissionData::fromArray($this->reviewData, $this->images);
    }
}
