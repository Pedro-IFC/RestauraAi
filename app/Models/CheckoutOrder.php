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
    public const STATUS_REFUSED = 'refused';

    public const FULFILLMENT_PENDING = 'pending';
    public const FULFILLMENT_PREPARING = 'preparing';
    public const FULFILLMENT_READY_FOR_PICKUP = 'ready_for_pickup';
    public const FULFILLMENT_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const FULFILLMENT_DELIVERED = 'delivered';

    public const STATE_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATE_PAID = 'paid';
    public const STATE_PREPARING = 'preparing';
    public const STATE_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATE_SHIPPED = 'shipped';
    public const STATE_REFUSED = 'refused';
    public const STATE_CANCELED = 'canceled';

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

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CheckoutOrderStatusHistory::class);
    }

    public function canBeCanceled(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function fulfillmentStatusLabel(): string
    {
        return [
            self::FULFILLMENT_PENDING => 'Pendente',
            self::FULFILLMENT_PREPARING => 'Em preparaÃ§Ã£o',
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

    public function currentAuditState(): string
    {
        if ($this->status === self::STATUS_REFUSED) {
            return self::STATE_REFUSED;
        }

        if ($this->status === self::STATUS_CANCELED) {
            return self::STATE_CANCELED;
        }

        if ($this->fulfillment_status === self::FULFILLMENT_PREPARING) {
            return self::STATE_PREPARING;
        }

        if ($this->fulfillment_status === self::FULFILLMENT_READY_FOR_PICKUP) {
            return self::STATE_READY_FOR_PICKUP;
        }

        if ($this->fulfillment_status === self::FULFILLMENT_OUT_FOR_DELIVERY) {
            return self::STATE_SHIPPED;
        }

        if ($this->status === self::STATUS_PAID) {
            return self::STATE_PAID;
        }

        return self::STATE_AWAITING_PAYMENT;
    }

    public function transitionTo(string $state, ?User $user = null, ?string $reason = null): CheckoutOrderStatusHistory
    {
        $fromState = $this->currentAuditState();

        $attributes = match ($state) {
            self::STATE_AWAITING_PAYMENT => [
                'status' => self::STATUS_OPEN,
                'fulfillment_status' => self::FULFILLMENT_PENDING,
                'canceled_at' => null,
            ],
            self::STATE_PAID => [
                'status' => self::STATUS_PAID,
                'fulfillment_status' => self::FULFILLMENT_PENDING,
                'canceled_at' => null,
            ],
            self::STATE_PREPARING => [
                'status' => self::STATUS_PAID,
                'fulfillment_status' => self::FULFILLMENT_PREPARING,
                'canceled_at' => null,
            ],
            self::STATE_READY_FOR_PICKUP => [
                'status' => self::STATUS_PAID,
                'fulfillment_method' => 'pickup',
                'fulfillment_status' => self::FULFILLMENT_READY_FOR_PICKUP,
                'canceled_at' => null,
            ],
            self::STATE_SHIPPED => [
                'status' => self::STATUS_PAID,
                'fulfillment_method' => 'delivery',
                'fulfillment_status' => self::FULFILLMENT_OUT_FOR_DELIVERY,
                'canceled_at' => null,
            ],
            self::STATE_REFUSED => [
                'status' => self::STATUS_REFUSED,
                'canceled_at' => $this->canceled_at ?? now(),
            ],
            self::STATE_CANCELED => [
                'status' => self::STATUS_CANCELED,
                'canceled_at' => $this->canceled_at ?? now(),
            ],
        };

        $this->update($attributes);

        return $this->statusHistories()->create([
            'user_id' => $user?->id,
            'from_state' => $fromState,
            'to_state' => $state,
            'reason' => $reason,
        ]);
    }

    public static function auditStateLabels(): array
    {
        return [
            self::STATE_AWAITING_PAYMENT => 'Aguardando pagamento',
            self::STATE_PAID => 'Pago',
            self::STATE_PREPARING => 'Em preparaÃ§Ã£o',
            self::STATE_READY_FOR_PICKUP => 'Pronto para retirada',
            self::STATE_SHIPPED => 'Despachado',
            self::STATE_REFUSED => 'Recusado',
            self::STATE_CANCELED => 'Cancelado',
        ];
    }

    public function auditStateLabel(): string
    {
        return self::auditStateLabels()[$this->currentAuditState()] ?? $this->currentAuditState();
    }
}
