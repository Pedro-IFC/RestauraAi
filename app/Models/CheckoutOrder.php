<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckoutOrder extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'tenant_id',
        'service_order_id',
        'type',
        'status',
        'payment_method',
        'total_amount',
        'fixgo_commission_rate',
        'fixgo_commission_amount',
        'tenant_amount',
        'canceled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'fixgo_commission_rate' => 'decimal:2',
        'fixgo_commission_amount' => 'decimal:2',
        'tenant_amount' => 'decimal:2',
        'canceled_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CheckoutOrderItem::class);
    }

    public function canBeCanceled(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
