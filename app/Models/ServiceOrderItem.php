<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ServiceOrderItem extends Pivot
{
    // Indica a tabela no banco
    protected $table = 'service_order_items';

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];
}