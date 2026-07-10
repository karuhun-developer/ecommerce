<?php

namespace App\Models\Order;

use App\Models\Location\Location;
use App\Models\Payment\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'location_id',
        'reference',
        'ref_number',
        'guest_data',
        'total_checkout',
        'total_shipping',
        'application_fee',
        'insurance_fee',
        'payment_fee',
        'tax_total',
        'total',
        'status',
    ];

    protected $casts = [
        'guest_data' => 'array',
        'total_checkout' => 'decimal:2',
        'total_shipping' => 'decimal:2',
        'application_fee' => 'decimal:2',
        'insurance_fee' => 'decimal:2',
        'payment_fee' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function orderShops()
    {
        return $this->hasMany(OrderShop::class);
    }

    public function items()
    {
        return $this->hasMany(OrderShopItem::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function latestPayment()
    {
        return $this->morphOne(Payment::class, 'payable')->latestOfMany();
    }
}
