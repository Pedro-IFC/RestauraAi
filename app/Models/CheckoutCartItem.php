<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutCartItem extends Model
{
    protected $fillable = [
        'checkout_cart_id',
        'item_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(CheckoutCart::class, 'checkout_cart_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
