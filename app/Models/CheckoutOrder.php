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

    public const FULFILLMENT_PENDING = 'pending';
    public const FULFILLMENT_READY_FOR_PICKUP = 'ready_for_pickup';
    public const FULFILLMENT_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const FULFILLMENT_DELIVERED = 'delivered';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'customer_address_id',
        'service_order_id',
        'type',
        'status',
        'payment_method',
        'fulfillment_method',
        'fulfillment_status',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CheckoutOrderItem::class);
    }

    public function canBeCanceled(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function fulfillmentStatusLabel(): string
    {
        return [
            self::FULFILLMENT_PENDING => 'Pendente',
            self::FULFILLMENT_READY_FOR_PICKUP => 'Pronto para retirada',
            self::FULFILLMENT_OUT_FOR_DELIVERY => 'Saiu para entrega',
            self::FULFILLMENT_DELIVERED => 'Entregue',
        ][$this->fulfillment_status] ?? $this->fulfillment_status;
    }

    public function fulfillmentMethodLabel(): string
    {
        return [
            'pickup' => 'Retirada',
            'delivery' => 'Entrega',
        ][$this->fulfillment_method] ?? $this->fulfillment_method;
    }
}
