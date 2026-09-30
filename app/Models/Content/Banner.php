<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Banner extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['title', 'subtitle', 'image_alt', 'cta_label', 'cta_url', 'active', 'sort_order', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->useDisk('public')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->whereHas('media', fn (Builder $q) => $q->where('collection_name', 'banner'));
    }
}
