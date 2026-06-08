<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plan_id',
        'name',
        'slug',
        'document',
        'status',
        'trial_ends_at',
        'payment_overdue_since',
        'payment_grace_days',
        'suspended_at',
        'canceled_at',
        'subscription_notes',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'payment_overdue_since' => 'datetime',
        'suspended_at' => 'datetime',
        'canceled_at' => 'datetime',
        'payment_grace_days' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function customization(): HasOne
    {
        return $this->hasOne(TenantCustomization::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function scheduleEvents(): HasMany
    {
        return $this->hasMany(ScheduleEvent::class);
    }

    public function checkoutCarts(): HasMany
    {
        return $this->hasMany(CheckoutCart::class);
    }

    public function hasFeature(string $feature): bool
    {
        return (bool) $this->plan?->hasFeature($feature);
    }

    public function planLimit(string $key): ?int
    {
        return $this->plan?->limit($key);
    }

    public function serviceOrdersCreatedThisMonth(): int
    {
        return $this->serviceOrders()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    public function hasReachedMonthlyServiceOrderLimit(): bool
    {
        $limit = $this->planLimit('max_service_orders_per_month');

        return $limit !== null && $this->serviceOrdersCreatedThisMonth() >= $limit;
    }

    public function isPubliclyAvailable(): bool
    {
        return in_array($this->status, ['active', 'trial'], true);
    }

    public function isBillingBlocked(): bool
    {
        return in_array($this->status, ['suspended', 'canceled'], true);
    }

    public function shouldAutoSuspendForOverduePayment(): bool
    {
        if (! $this->payment_overdue_since || ! in_array($this->status, ['active', 'trial'], true)) {
            return false;
        }

        return $this->payment_overdue_since
            ->copy()
            ->addDays($this->payment_grace_days ?? 0)
            ->isPast();
    }

    public function enforceSubscriptionLifecycle(): void
    {
        if (! $this->shouldAutoSuspendForOverduePayment()) {
            return;
        }

        $this->forceFill([
            'status' => 'suspended',
            'suspended_at' => now(),
        ])->save();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Ativa',
            'trial' => 'Em período de testes',
            'suspended' => 'Inadimplente',
            'canceled' => 'Cancelada',
            default => ucfirst((string) $this->status),
        };
    }
}
