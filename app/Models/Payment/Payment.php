<?php

namespace App\Models\Payment;

use Database\Factories\Payment\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'driver',
        'payable_type',
        'payable_id',
        'order_id',
        'transaction_id',
        'payment_type',
        'account_number',
        'account_code',
        'channel',
        'expired_at',
        'paid_at',
        'amount',
        'fee',
        'total',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Get the activity log options.
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*']);
    }

    public function payable()
    {
        return $this->morphTo();
    }
}
