<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    ];

    protected $casts = [
        'is_for_sale' => 'boolean',
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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