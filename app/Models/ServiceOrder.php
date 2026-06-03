<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'kanban_column_id',
        'device_model',
        'defect_symptoms',
        'attachments',
        'status',
        'total_cost',
        'total_price',
        'budget_decided_at',
        'kanban_position',
        'deadline_at',
        'hardware_received_at',
        'hardware_received_notes',
    ];

    protected $casts = [
        'attachments' => 'array',
        'budget_decided_at' => 'datetime',
        'deadline_at' => 'datetime',
        'hardware_received_at' => 'datetime',
        'total_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
        'kanban_position' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function kanbanColumn(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class);
    }

    // Relacionamento com os itens/peças usados na OS
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'service_order_items')
            ->using(ServiceOrderItem::class) // Usa a model Pivot para métodos extras se necessário
            ->withPivot(['quantity', 'unit_cost', 'unit_price'])
            ->withTimestamps();
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function runningTimeEntries(): HasMany
    {
        return $this->timeEntries()->whereNull('ended_at');
    }

    public function trackedSeconds(): int
    {
        $entries = $this->relationLoaded('timeEntries')
            ? $this->timeEntries
            : $this->timeEntries()->get();

        return $entries->sum(function (TimeEntry $entry) {
            if ($entry->ended_at) {
                return (int) $entry->duration_seconds;
            }

            return (int) $entry->started_at->diffInSeconds(now());
        });
    }

    public function runningTimeEntryForUser(int $userId): ?TimeEntry
    {
        $entries = $this->relationLoaded('timeEntries')
            ? $this->timeEntries
            : $this->timeEntries()->get();

        return $entries
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->first();
    }

    public function isDeadlineOverdue(): bool
    {
        return $this->deadline_at
            && $this->deadline_at->isPast()
            && ! in_array($this->status, ['finished', 'rejected'], true);
    }

    public function isDeadlineDueToday(): bool
    {
        return $this->deadline_at
            && $this->deadline_at->isToday()
            && ! in_array($this->status, ['finished', 'rejected'], true);
    }
}
