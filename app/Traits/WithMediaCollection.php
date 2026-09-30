<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

trait WithMediaCollection
{
    private function saveMedia(Model $model, UploadedFile $file, string $collection = 'images', bool $deleteOlderMedia = true): void
    {
        if ($deleteOlderMedia) {
            $model->clearMediaCollection($collection);
        }

        $model->addMedia($file)->toMediaCollection($collection);
    }

    private function deleteMedia(Model $model, string $collection = 'images'): void
    {
        $model->clearMediaCollection($collection);
    }
}
