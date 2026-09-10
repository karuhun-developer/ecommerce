<?php

namespace App\Models\Shop;

use App\Models\Attribute\Attribute;
use App\Models\Location\Location;
use App\Models\Order\OrderReview;
use App\Models\Product\Product;
use App\Models\User;
use Database\Factories\Shop\ShopFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'name', to: 'slug')]
class Shop extends Model implements HasMedia
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'rating',
        'total_reviews',
        'total_sales',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_sales' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(Location::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(OrderReview::class, 'reviewable');
    }

    #[Scope]
    protected function accessibleTo(Builder $query, User $user): void
    {
        if (! $user->hasRole('superadmin')) {
            $query->where('user_id', $user->id);
        }
    }
}
