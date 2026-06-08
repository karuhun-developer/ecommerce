<?php

namespace App\Models\Location;

use App\Models\Shop\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'user_id',
        'shop_id',
        'biteship_location_id',
        'biteship_area_id',
        'area_string',
        'name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'note',
        'postal_code',
        'latitude',
        'longitude',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
