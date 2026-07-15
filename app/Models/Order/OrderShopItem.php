<?php

namespace App\Models\Order;

use App\Models\Product\ProductFlat;
use Illuminate\Database\Eloquent\Model;

class OrderShopItem extends Model
{
    protected $fillable = [
        'order_id',
        'order_shop_id',
        'product_flat_id',
        'product_data',
        'quantity',
        'price',
        'total',
    ];

    protected $casts = [
        'product_data' => 'array',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderShop()
    {
        return $this->belongsTo(OrderShop::class);
    }

    public function productFlat()
    {
        return $this->belongsTo(ProductFlat::class);
    }

    public function reviews()
    {
        return $this->morphMany(OrderReview::class, 'reviewable');
    }
}
