<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /**
     * Os atributos que podem ser atribuídos em massa.
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'cpf',
        'phone',
        'email',
    ];

    /**
     * Relacionamento: Um cliente pertence a uma Assistência (Tenant).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Um cliente pode ter várias Ordens de Serviço.
     */
    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function checkoutOrders(): HasMany
    {
        return $this->hasMany(CheckoutOrder::class);
    }
}
