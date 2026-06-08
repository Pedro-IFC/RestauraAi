<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutOrderStatusHistory extends Model
{
    protected $fillable = [
        'checkout_order_id',
        'user_id',
        'from_state',
        'to_state',
        'reason',
    ];

    public function checkoutOrder(): BelongsTo
    {
        return $this->belongsTo(CheckoutOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
