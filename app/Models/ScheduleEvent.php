<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleEvent extends Model
{
    public const TYPE_EVENT = 'event';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_ORDER_RECEIPT = 'order_receipt';
    public const TYPE_SERVICE_ORDER = 'service_order';

    protected $fillable = [
        'tenant_id',
        'service_order_id',
        'created_by',
        'title',
        'type',
        'starts_at',
        'ends_at',
        'remind_at',
        'location',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'remind_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    public function isReminderDue(): bool
    {
        return $this->remind_at && ! $this->completed_at && $this->remind_at->isPast();
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_EVENT => 'Evento',
            self::TYPE_REMINDER => 'Lembrete',
            self::TYPE_MEETING => 'Reuniao',
            self::TYPE_ORDER_RECEIPT => 'Recebimento de pedido',
            self::TYPE_SERVICE_ORDER => 'Planejamento de OS',
        ];
    }
}
