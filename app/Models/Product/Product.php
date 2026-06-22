<?php

namespace App\Models\Product;

use App\Models\Shop\Shop;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'name', to: 'slug')]
class Product extends Model
{
    protected $fillable = [
        'product_category_id',
        'shop_id',
        'type',
        'name',
        'slug',
        'description',
        'price',
        'weight',
        'length',
        'width',
        'height',
        'stock',
        'rating',
        'total_reviews',
        'total_sales',
        'is_unlimited_stock',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'stock' => 'integer',
        'rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'total_sales' => 'integer',
        'is_unlimited_stock' => 'boolean',
        'status' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function productFlats()
    {
        return $this->hasMany(ProductFlat::class);
    }

    public function mainProductFlat()
    {
        return $this->hasOne(ProductFlat::class)->orderBy('id', 'asc');
    }

    public function productAttributeGroups()
    {
        return $this->hasMany(ProductAttributeGroup::class);
    }
}
