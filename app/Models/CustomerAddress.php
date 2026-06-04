<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerAddress extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'label',
        'recipient_name',
        'phone',
        'postal_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function checkoutOrders(): HasMany
    {
        return $this->hasMany(CheckoutOrder::class);
    }

    public function formatted(): string
    {
        $parts = [
            trim($this->street.', '.$this->number),
            $this->complement,
            $this->neighborhood,
            trim($this->city.' - '.$this->state),
            $this->postal_code,
        ];

        return collect($parts)
            ->filter(fn ($part) => filled($part))
            ->implode(', ');
    }
}
