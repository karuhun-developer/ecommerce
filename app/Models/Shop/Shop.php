<?php

namespace App\Models\Shop;

use App\Models\Attribute\Attribute;
use App\Models\Location\Location;
use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'name', to: 'slug')]
class Shop extends Model implements HasMedia
{
    use InteractsWithMedia;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function location()
    {
        return $this->hasOne(Location::class);
    }

    public function attributes()
    {
        return $this->hasMany(Attribute::class);
    }
}
