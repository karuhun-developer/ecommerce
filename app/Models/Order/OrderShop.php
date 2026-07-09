<?php

namespace App\Models\Order;

use App\Models\Shop\Shop;
use Illuminate\Database\Eloquent\Model;

class OrderShop extends Model
{
    protected $fillable = [
        'order_id',
        'shop_id',
        'waybill_number',
        'shipping_data',
        'total_checkout',
        'total_shipping',
        'tax',
        'total',
        'shipping_status',
    ];

    protected $casts = [
        'shipping_data' => 'array',
        'total_checkout' => 'decimal:2',
        'total_shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_status' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function shipments()
    {
        return $this->hasMany(OrderShopShipment::class);
    }

    public function latestShipment()
    {
        return $this->hasOne(OrderShopShipment::class)->latestOfMany();
    }

    public function items()
    {
        return $this->hasMany(OrderShopItem::class);
    }
}
