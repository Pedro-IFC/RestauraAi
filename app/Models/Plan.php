<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public static function defaultFeatures(): array
    {
        return [
            'max_service_orders_per_month' => null,
            'max_users' => null,
            'max_items' => null,
            'max_public_products' => null,
            'kanban' => false,
            'inventory' => false,
            'catalog' => false,
            'schedule' => false,
            'dashboard' => false,
            'time_tracking' => false,
            'customization_basic' => false,
            'customization_advanced' => false,
            'custom_domain' => false,
            'priority_support' => false,
        ];
    }

    public static function featureLabels(): array
    {
        return [
            'kanban' => 'Kanban operacional',
            'inventory' => 'Controle de estoque e insumos',
            'catalog' => 'Catálogo público',
            'schedule' => 'Agenda e prazos',
            'dashboard' => 'Dashboard avançado',
            'time_tracking' => 'Time tracking',
            'customization_basic' => 'Customização básica',
            'customization_advanced' => 'Customização avançada',
            'custom_domain' => 'Domínio personalizado',
            'priority_support' => 'Suporte prioritário',
        ];
    }

    public static function presetFeatures(string $preset): array
    {
        return match ($preset) {
            'bronze' => array_replace(self::defaultFeatures(), [
                'max_service_orders_per_month' => 50,
                'kanban' => true,
            ]),
            'prata' => array_replace(self::defaultFeatures(), [
                'kanban' => true,
                'inventory' => true,
                'catalog' => true,
                'schedule' => true,
                'customization_basic' => true,
            ]),
            'ouro' => array_replace(self::defaultFeatures(), [
                'kanban' => true,
                'inventory' => true,
                'catalog' => true,
                'schedule' => true,
                'dashboard' => true,
                'time_tracking' => true,
                'customization_basic' => true,
                'customization_advanced' => true,
            ]),
            default => self::defaultFeatures(),
        };
    }

    public function normalizedFeatures(): array
    {
        $features = array_replace(self::defaultFeatures(), $this->features ?? []);

        if (($this->features['customization'] ?? false) && ! $features['customization_basic']) {
            $features['customization_basic'] = true;
        }

        return $features;
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->normalizedFeatures();

        return (bool) ($features[$feature] ?? false);
    }

    public function limit(string $key): ?int
    {
        $value = $this->normalizedFeatures()[$key] ?? null;

        return $value === null || $value === '' ? null : (int) $value;
    }
}
