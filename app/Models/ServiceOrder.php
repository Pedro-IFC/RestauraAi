<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'kanban_column_id',
        'device_model',
        'defect_symptoms',
        'status',
        'total_cost',
        'total_price',
        'deadline_at',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'total_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
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
}