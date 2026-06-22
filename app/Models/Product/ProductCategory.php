<?php

namespace App\Models\Product;

use App\Models\Shop\Shop;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'name', to: 'slug')]
class ProductCategory extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'shop_id',
        'name',
        'slug',
        'description',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
