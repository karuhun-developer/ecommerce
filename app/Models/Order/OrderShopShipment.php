<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;

class OrderShopShipment extends Model
{
    protected $fillable = [
        'order_shop_id',
        'event',
        'courier_tracking_id',
        'courier_waybill_id',
        'courier_name',
        'courier_company',
        'courier_type',
        'courier_driver_name',
        'courier_driver_phone',
        'courier_driver_photo_url',
        'courier_driver_plate_number',
        'courier_link',
        'status',
    ];

    public function orderShop()
    {
        return $this->belongsTo(OrderShop::class);
    }
}
