<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantCustomization extends Model
{
    protected $fillable = [
        'tenant_id',
        'logo',              // Caminho/URL da logomarca
        'primary_color',     // Cor principal (ex: #FF0000)
        'secondary_color',   // Cor secundária
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

    public function getInstagramUrlAttribute(): ?string
    {
        if (! $this->instagram_handle) {
            return null;
        }

        if (str_starts_with($this->instagram_handle, 'http://') || str_starts_with($this->instagram_handle, 'https://')) {
            return $this->instagram_handle;
        }

        return 'https://instagram.com/'.ltrim($this->instagram_handle, '@');
    }

    public function getGoogleMapsEmbedSrcAttribute(): ?string
    {
        if (! $this->google_maps_iframe) {
            return null;
        }

        if (preg_match('/src=["\']([^"\']+)["\']/i', $this->google_maps_iframe, $matches)) {
            return $matches[1];
        }

        if (filter_var($this->google_maps_iframe, FILTER_VALIDATE_URL)) {
            return $this->google_maps_iframe;
        }

        return null;
    }
}
