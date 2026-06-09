<?php

namespace App\Models\Product;

use App\Models\Shop\Shop;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductFlat extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'shop_id',
        'product_id',
        'name',
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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }
}
