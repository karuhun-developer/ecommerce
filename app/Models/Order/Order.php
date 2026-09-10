<?php

namespace App\Models\Order;

use App\Models\Location\Location;
use App\Models\Payment\Payment;
use App\Models\User;
use Database\Factories\Order\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'location_id',
        'reference',
        'access_token',
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

    protected $hidden = [
        'access_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function orderShops(): HasMany
    {
        return $this->hasMany(OrderShop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderShopItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function latestPayment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable')->latestOfMany();
    }

    /** @return array<string, string> */
    public function guestRouteParameters(): array
    {
        return $this->user_id === null && filled($this->access_token)
            ? ['token' => $this->access_token]
            : [];
    }
}
