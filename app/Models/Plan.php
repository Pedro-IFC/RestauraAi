<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price_monthly',
        'price_yearly',
        'features',
        'trial_days_allowed',
    ];

    // O Laravel converte o JSON do banco para Array automaticamente
    protected $casts = [
        'features' => 'array',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}