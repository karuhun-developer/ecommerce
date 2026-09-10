<?php

namespace App\Models\Order;

use App\Models\Shop\Shop;
use App\Models\User;
use Database\Factories\Order\OrderShopFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderShop extends Model
{
    /** @use HasFactory<OrderShopFactory> */
    use HasFactory;

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
        'shipping_note',
    ];

    protected $casts = [
        'shipping_data' => 'array',
        'total_checkout' => 'decimal:2',
        'total_shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_status' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShopShipment::class);
    }

    public function latestShipment(): HasOne
    {
        return $this->hasOne(OrderShopShipment::class)->latestOfMany();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderShopItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(OrderReview::class);
    }

    #[Scope]
    protected function accessibleTo(Builder $query, User $user): void
    {
        if (! $user->hasRole('superadmin')) {
            $query->whereHas('shop', fn (Builder $shopQuery) => $shopQuery->where('user_id', $user->id));
        }
    }
}
