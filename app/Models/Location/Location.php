<?php

namespace App\Models\Location;

use App\Models\Shop\Shop;
use App\Models\User;
use Database\Factories\Location\LocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
