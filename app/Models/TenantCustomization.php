<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantCustomization extends Model
{
    protected $fillable = [
        'tenant_id',
        'banners',
        'about_text',
        'instagram_handle',
        'address_text',
        'google_maps_iframe',
    ];

    protected $casts = [
        'banners' => 'array', // Converte a lista de URLs de imagens
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}