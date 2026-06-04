<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'is_for_sale',
        'cost_price',
        'sale_price',
        'stock_quantity',
        'min_stock_alert',
        'images',
    ];

    protected $casts = [
        'is_for_sale' => 'boolean',
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'images' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query
            ->where('type', 'product')
            ->where('is_for_sale', true)
            ->where('stock_quantity', '>', 0);
    }

    public function getGrossMarginAttribute(): float
    {
        return (float) $this->sale_price - (float) $this->cost_price;
    }

    // Permite buscar em quais OS este item foi utilizado
    public function serviceOrders(): BelongsToMany
    {
        return $this->belongsToMany(ServiceOrder::class, 'service_order_items')
            ->using(ServiceOrderItem::class)
            ->withPivot(['quantity', 'unit_cost', 'unit_price'])
            ->withTimestamps();
    }
}
